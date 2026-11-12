<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/mailer.php';

session_init();

if (is_logged_in()) {
    redirect('/index.php');
}

$isAjaxRequest = is_ajax()
    && str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        if ($isAjaxRequest) {
            json_response(['success' => false, 'message' => 'Your session expired. Refresh the page and try again.'], 400);
        }
        set_flash('error', 'Invalid security token.');
        redirect('/forgot-password.php');
    }

    $email = trim($_POST['email'] ?? '');
    if (empty($email)) {
        if ($isAjaxRequest) {
            json_response(['success' => false, 'message' => 'Please enter your email address.'], 422);
        }
        set_flash('error', 'Please enter your email address.');
        redirect('/forgot-password.php');
    }

    $user = db_fetch_one("SELECT id, email FROM users WHERE email = ? LIMIT 1", [$email]);
    if ($user) {
        $code = (string)random_int(100000, 999999);
        $storeName = htmlspecialchars(SMTP_FROM_NAME, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $htmlBody = '<!doctype html><html lang="en"><head><meta name="viewport" content="width=device-width,initial-scale=1"></head>'
            . '<body style="margin:0;padding:0;background:#f3f6fb;font-family:Arial,Helvetica,sans-serif;color:#172033;">'
            . '<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background:#f3f6fb;padding:36px 12px;">'
            . '<tr><td align="center"><table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="max-width:560px;background:#ffffff;border:1px solid #e6eaf0;border-radius:18px;overflow:hidden;">'
            . '<tr><td style="padding:26px 32px;background:#111827;">'
            . '<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0"><tr>'
            . '<td><div style="font-size:19px;line-height:1.3;font-weight:700;letter-spacing:.2px;color:#ffffff;">' . $storeName . '</div>'
            . '<div style="margin-top:5px;font-size:11px;line-height:1.4;font-weight:600;letter-spacing:1.5px;color:#a5b4fc;">ACCOUNT SECURITY</div></td>'
            . '<td align="right" style="font-size:12px;color:#cbd5e1;">PASSWORD RESET</td></tr></table></td></tr>'
            . '<tr><td style="padding:34px 32px 30px;">'
            . '<div style="display:inline-block;padding:7px 11px;background:#eef2ff;border-radius:20px;color:#4338ca;font-size:11px;font-weight:700;letter-spacing:.5px;">SECURE VERIFICATION</div>'
            . '<h1 style="margin:18px 0 10px;font-size:25px;line-height:1.25;color:#111827;">Reset your password</h1>'
            . '<p style="margin:0 0 24px;font-size:15px;line-height:1.7;color:#526078;">We received a request to reset the password for your account. Enter this one-time code to continue:</p>'
            . '<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background:#f8faff;border:1px solid #dfe7fb;border-radius:14px;">'
            . '<tr><td align="center" style="padding:22px 14px 20px;">'
            . '<div style="margin-bottom:10px;font-size:11px;font-weight:700;letter-spacing:1.4px;color:#64748b;">YOUR RESET CODE</div>'
            . '<div style="font-family:Arial,Helvetica,sans-serif;font-size:36px;line-height:1.25;font-weight:700;letter-spacing:11px;color:#273cba;padding-left:11px;">' . $code . '</div>'
            . '</td></tr></table>'
            . '<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="margin-top:22px;"><tr>'
            . '<td width="28" valign="top" style="padding-top:2px;color:#4f46e5;font-size:16px;">&#9432;</td>'
            . '<td style="font-size:13px;line-height:1.65;color:#526078;">This code expires in <strong style="color:#273044;">15 minutes</strong>. Keep it private; our team will never ask you to share it.</td>'
            . '</tr></table>'
            . '<p style="margin:22px 0 0;padding-top:18px;border-top:1px solid #edf0f5;font-size:12px;line-height:1.7;color:#7a8699;">Didn\'t request a password reset? You can ignore this email. Your password will remain unchanged.</p>'
            . '</td></tr><tr><td align="center" style="padding:18px 24px;background:#f8fafc;border-top:1px solid #edf0f5;">'
            . '<div style="font-size:12px;font-weight:600;color:#526078;">' . $storeName . '</div>'
            . '<div style="margin-top:5px;font-size:11px;color:#98a2b3;">Automated account security message · Please do not reply</div>'
            . '</td></tr></table></td></tr></table></body></html>';

        try {
            send_smtp_email(
                $email,
                'Your password reset code',
                "Your password reset code is {$code}.\n\nThis code expires in 15 minutes. Do not share it with anyone. If you did not request a password reset, you can ignore this email.",
                $htmlBody
            );
        } catch (RuntimeException $exception) {
            error_log('Password reset email delivery failed: ' . $exception->getMessage());
            $errorMessage = APP_DEBUG
                ? 'Email delivery error: ' . $exception->getMessage()
                : 'We could not send the reset email. Please try again later.';
            if ($isAjaxRequest) {
                json_response(['success' => false, 'message' => $errorMessage], 500);
            }
            set_flash('error', $errorMessage);
            redirect('/forgot-password.php');
        }

        $_SESSION['reset_email'] = $email;
        $_SESSION['reset_code'] = $code;
        $_SESSION['reset_expiry'] = time() + (15 * 60);

        if ($isAjaxRequest) {
            json_response([
                'success' => true,
                'message' => 'Reset email accepted. Opening code entry…',
                'redirect' => url('/reset-password.php'),
            ]);
        }

        set_flash('success', 'A password reset code has been sent to your email address.');
        redirect('/reset-password.php');
    } else {
        if ($isAjaxRequest) {
            json_response(['success' => false, 'message' => 'No account found with this email address.'], 404);
        }
        set_flash('error', 'No account found with this email address.');
        redirect('/forgot-password.php');
    }
}

$pageTitle = 'Forgot Password';
$minimal = true;
require_once __DIR__ . '/includes/auth-header.php';
?>

<div class="space-y-5">
    <div class="text-left">
        <div class="mb-4 inline-flex items-center gap-2 rounded-full bg-indigo-50 py-1.5 pl-1.5 pr-3 text-xs font-bold text-indigo-800">
            <span class="flex h-7 w-7 items-center justify-center rounded-full bg-indigo-700 text-white"><i data-lucide="smartphone" class="h-4 w-4"></i></span>
            <span><?= e(setting('site_name', 'Apex Mobile Hub')) ?> <span class="font-medium text-indigo-500">· Store account</span></span>
        </div>
        <h2 class="text-2xl font-bold tracking-tight text-slate-900">Forgot password</h2>
        <p class="mt-1.5 text-sm leading-6 text-slate-500">Enter the email address linked to your store account. We’ll send you a secure, one-time reset code.</p>
    </div>

    <form id="forgotPasswordForm" action="<?= url('/forgot-password.php') ?>" method="POST" class="space-y-4">
        <?= csrf_field() ?>

        <div>
            <label for="email" class="block text-sm font-medium text-slate-700 mb-1.5">Email address</label>
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                    <i data-lucide="mail" class="w-4 h-4"></i>
                </div>
                <input type="email" id="email" name="email" required value="<?= e(old('email')) ?>" placeholder="name@example.com"
                    autocomplete="email"
                    class="auth-input w-full pl-10 pr-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-sm text-slate-900 placeholder-slate-400 transition">
            </div>
        </div>

        <p id="sendCodeMessage" class="hidden text-sm font-medium" role="status" aria-live="polite"></p>

        <button type="submit" id="sendCodeButton"
            class="w-full py-2.5 px-4 rounded-xl bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white font-bold text-sm shadow-lg shadow-blue-500/25 transition duration-150 flex items-center justify-center gap-2 disabled:cursor-wait disabled:opacity-80">
            <span id="sendCodeLabel">Send reset code</span>
            <i data-lucide="arrow-right" id="sendCodeArrow" class="w-4 h-4"></i>
            <span id="sendCodeSpinner" class="hidden w-4 h-4 rounded-full border-2 border-white/40 border-t-white animate-spin" aria-hidden="true"></span>
        </button>
    </form>

</div>

<script>
document.getElementById('forgotPasswordForm').addEventListener('submit', async function (event) {
    event.preventDefault();

    const form = event.currentTarget;
    const button = document.getElementById('sendCodeButton');
    const label = document.getElementById('sendCodeLabel');
    const arrow = document.getElementById('sendCodeArrow');
    const spinner = document.getElementById('sendCodeSpinner');
    const message = document.getElementById('sendCodeMessage');

    button.disabled = true;
    label.textContent = 'Sending your code…';
    arrow.classList.add('hidden');
    spinner.classList.remove('hidden');
    button.setAttribute('aria-busy', 'true');
    message.classList.add('hidden');

    try {
        const response = await fetch(form.action, {
            method: 'POST',
            body: new FormData(form),
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        });
        const result = await response.json();

        if (!response.ok || !result.success) {
            throw new Error(result.message || 'We could not send the reset email. Please try again.');
        }

        label.textContent = 'Email sent';
        message.textContent = 'Code sent successfully. Opening verification…';
        message.className = 'text-sm font-medium text-emerald-700';
        message.classList.remove('hidden');
        window.setTimeout(() => window.location.assign(result.redirect), 650);
    } catch (error) {
        label.textContent = 'Send reset code';
        button.disabled = false;
        arrow.classList.remove('hidden');
        message.textContent = error instanceof Error ? error.message : 'Something went wrong. Please try again.';
        message.className = 'text-sm font-medium text-rose-600';
        message.classList.remove('hidden');
    } finally {
        spinner.classList.add('hidden');
        button.removeAttribute('aria-busy');
    }
});
</script>

<?php require_once __DIR__ . '/includes/auth-footer.php'; ?>
