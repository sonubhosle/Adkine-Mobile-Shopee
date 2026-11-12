<?php

function send_smtp_email(string $to, string $subject, string $body, ?string $htmlBody = null): void
{
    if (MAIL_DRIVER !== 'smtp') {
        throw new RuntimeException('SMTP email delivery is not enabled.');
    }

    if (!filter_var($to, FILTER_VALIDATE_EMAIL)
        || !filter_var(SMTP_FROM_EMAIL, FILTER_VALIDATE_EMAIL)
        || SMTP_HOST === ''
        || SMTP_PORT < 1
        || SMTP_PORT > 65535
        || SMTP_USERNAME === ''
        || SMTP_PASSWORD === ''
        || preg_match('/[\r\n]/', SMTP_FROM_NAME . $subject)) {
        throw new RuntimeException('SMTP email configuration is incomplete or invalid.');
    }

    $smtpPassword = preg_replace('/\s+/', '', SMTP_PASSWORD);

    $errorCode = 0;
    $errorMessage = '';
    $socket = @stream_socket_client(
        'tcp://' . SMTP_HOST . ':' . SMTP_PORT,
        $errorCode,
        $errorMessage,
        15,
        STREAM_CLIENT_CONNECT
    );

    if ($socket === false) {
        throw new RuntimeException(
            'Unable to connect to the SMTP server (network error ' . $errorCode . ').'
            . ($errorMessage !== '' ? ' ' . $errorMessage : '')
        );
    }

    stream_set_timeout($socket, 15);

    $writeAll = static function (string $data) use ($socket): void {
        while ($data !== '') {
            $written = fwrite($socket, $data);
            if ($written === false || $written === 0) {
                throw new RuntimeException('Unable to write to the SMTP server.');
            }
            $data = substr($data, $written);
        }
    };

    $responseText = '';
    $smtpStage = 'server greeting';
    $readResponse = static function () use ($socket, &$responseText, &$smtpStage): int {
        $code = null;
        $responseText = '';
        do {
            $line = fgets($socket, 515);
            if ($line === false) {
                $metadata = stream_get_meta_data($socket);
                $reason = !empty($metadata['timed_out']) ? 'The server timed out.' : 'The connection closed unexpectedly.';
                throw new RuntimeException('No valid SMTP response during ' . $smtpStage . '. ' . $reason);
            }
            if (!preg_match('/^(\d{3})([ -])/', $line, $matches)) {
                $serverLine = trim($line);
                throw new RuntimeException(
                    'Invalid SMTP response during ' . $smtpStage . ': '
                    . ($serverLine !== '' ? $serverLine : 'empty response')
                );
            }

            $responseText .= trim($line) . ' ';
            $lineCode = (int)$matches[1];
            if ($code !== null && $lineCode !== $code) {
                throw new RuntimeException('Invalid multiline response from the SMTP server.');
            }
            $code = $lineCode;
        } while ($matches[2] === '-');

        return $code;
    };

    $sendCommand = static function (string $command, array $expectedCodes, string $stage) use ($writeAll, $readResponse, &$responseText, &$smtpStage): void {
        $smtpStage = $stage;
        $writeAll($command . "\r\n");
        $responseCode = $readResponse();
        if (!in_array($responseCode, $expectedCodes, true)) {
            throw new RuntimeException(
                'The SMTP server rejected a mail-delivery command (' . $responseCode . '). '
                . trim($responseText)
            );
        }
    };

    try {
        $smtpStage = 'server greeting';
        if ($readResponse() !== 220) {
            throw new RuntimeException('The SMTP server is not ready. ' . trim($responseText));
        }

        $smtpStage = 'initial EHLO';
        $sendCommand('EHLO localhost', [250], 'initial EHLO');
        $sendCommand('STARTTLS', [220], 'STARTTLS negotiation');

        $smtpStage = 'TLS handshake';
        if (stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT) !== true) {
            throw new RuntimeException('Could not establish a secure SMTP connection. Check the PHP OpenSSL extension and server TLS settings.');
        }

        $smtpStage = 'secure EHLO';
        $sendCommand('EHLO localhost', [250], 'secure EHLO');
        $sendCommand('AUTH LOGIN', [334], 'SMTP authentication');
        $sendCommand(base64_encode(SMTP_USERNAME), [334], 'SMTP authentication');
        $sendCommand(base64_encode($smtpPassword), [235], 'SMTP authentication');
        $sendCommand('MAIL FROM:<' . SMTP_FROM_EMAIL . '>', [250], 'sender address');
        $sendCommand('RCPT TO:<' . $to . '>', [250, 251], 'recipient address');
        $sendCommand('DATA', [354], 'message transfer');

        $fromName = mb_encode_mimeheader(SMTP_FROM_NAME, 'UTF-8', 'B', "\r\n");
        $encodedSubject = mb_encode_mimeheader($subject, 'UTF-8', 'B', "\r\n");
        $headers = [
            'From: ' . $fromName . ' <' . SMTP_FROM_EMAIL . '>',
            'To: <' . $to . '>',
            'Subject: ' . $encodedSubject,
            'MIME-Version: 1.0',
        ];

        if ($htmlBody === null) {
            $headers[] = 'Content-Type: text/plain; charset=UTF-8';
            $headers[] = 'Content-Transfer-Encoding: base64';
            $headers[] = '';
            $headers[] = chunk_split(base64_encode(str_replace(["\r\n", "\r"], "\n", $body)), 76, "\r\n");
        } else {
            $boundary = '=_Apex_' . bin2hex(random_bytes(16));
            $headers[] = 'Content-Type: multipart/alternative; boundary="' . $boundary . '"';
            $headers[] = '';
            $headers[] = '--' . $boundary;
            $headers[] = 'Content-Type: text/plain; charset=UTF-8';
            $headers[] = 'Content-Transfer-Encoding: base64';
            $headers[] = '';
            $headers[] = chunk_split(base64_encode(str_replace(["\r\n", "\r"], "\n", $body)), 76, "\r\n");
            $headers[] = '--' . $boundary;
            $headers[] = 'Content-Type: text/html; charset=UTF-8';
            $headers[] = 'Content-Transfer-Encoding: base64';
            $headers[] = '';
            $headers[] = chunk_split(base64_encode($htmlBody), 76, "\r\n");
            $headers[] = '--' . $boundary . '--';
        }

        $message = implode("\r\n", $headers);
        $message = preg_replace('/(?m)^\./', '..', $message);

        $smtpStage = 'message acceptance';
        $writeAll(rtrim($message, "\r\n") . "\r\n.\r\n");
        $responseCode = $readResponse();
        if ($responseCode !== 250) {
            throw new RuntimeException(
                'The SMTP server did not accept the email (' . $responseCode . '). '
                . trim($responseText)
            );
        }

        $writeAll("QUIT\r\n");
    } finally {
        fclose($socket);
    }
}
