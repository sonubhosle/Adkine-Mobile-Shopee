<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

session_init();

$email = $_SESSION['reset_email'] ?? '';
if (empty($email)) {
    redirect('/forgot-password.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        set_flash('error', 'Invalid security token.');
        redirect('/reset-password.php');
    }

    $otpDigits = $_POST['otp_digits'] ?? [];
    $otpCode = '';
    if (is_array($otpDigits) && count($otpDigits) === 6) {
        foreach ($otpDigits as $digit) {
            if (!is_string($digit) || !preg_match('/^\d$/', $digit)) {
                $otpCode = '';
                break;
            }
            $otpCode .= $digit;
        }
    }
    $newPass = $_POST['password'] ?? '';
    $confirmPass = $_POST['password_confirmation'] ?? '';

    $savedCode = $_SESSION['reset_code'] ?? '';
    $expiry = (int)($_SESSION['reset_expiry'] ?? 0);

    if (empty($savedCode) || time() > $expiry) {
        set_flash('error', 'Reset session expired. Please request a new code.');
        redirect('/forgot-password.php');
    }

    if (strlen($otpCode) !== 6 || !hash_equals((string)$savedCode, $otpCode)) {
        set_flash('error', 'Invalid 6-digit reset code.');
        redirect('/reset-password.php');
    }

    if (strlen($newPass) < 8) {
        set_flash('error', 'Password must be at least 8 characters long.');
        redirect('/reset-password.php');
    }

    if ($newPass !== $confirmPass) {
        set_flash('error', 'Passwords do not match.');
        redirect('/reset-password.php');
    }

    $hash = password_hash($newPass, PASSWORD_BCRYPT);
    db_query("UPDATE users SET password_hash = ?, updated_at = NOW() WHERE email = ?", [$hash, $email]);

    unset($_SESSION['reset_email'], $_SESSION['reset_code'], $_SESSION['reset_expiry']);

    set_flash('success', 'Your password has been successfully reset. Please sign in.');
    redirect('/login.php');
}

$pageTitle = 'Reset Password';
$minimal = true;
require_once __DIR__ . '/includes/auth-header.php';
?>

<div class="space-y-5">
    <div class="text-center">
        <div class="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-2xl bg-indigo-50 text-indigo-700">
            <i data-lucide="mail-check" class="h-6 w-6"></i>
        </div>
        <div class="mb-3 inline-flex items-center gap-2 rounded-full bg-indigo-50 py-1.5 pl-1.5 pr-3 text-xs font-bold text-indigo-800">
            <span class="flex h-7 w-7 items-center justify-center rounded-full bg-indigo-700 text-white"><i data-lucide="smartphone" class="h-4 w-4"></i></span>
            <span><?= e(setting('site_name', 'Apex Mobile Hub')) ?> <span class="font-medium text-indigo-500">· Store account</span></span>
        </div>
        <h2 class="text-2xl font-bold tracking-tight text-slate-900">Check your email</h2>
        <p class="mt-1 text-sm text-slate-500">Enter the 6-digit code we sent to</p>
        <p class="mt-1 break-all text-sm font-semibold text-slate-700"><?= e($email) ?></p>
        <p class="mt-2 text-xs leading-5 text-slate-500">Type each number in its own box. You can also paste the full code into any box.</p>
    </div>

    <form action="<?= url('/reset-password.php') ?>" method="POST" class="space-y-4">
        <?= csrf_field() ?>

        <div class="space-y-2">
            <label for="otp_digit_1" class="block text-center text-xs font-bold uppercase tracking-wider text-slate-600">Verification code</label>
            <div id="otpDigits" class="mx-auto grid max-w-sm grid-cols-6 gap-2.5" role="group" aria-label="6-digit reset code">
                <?php for ($digit = 0; $digit < 6; $digit++): ?>
                <input type="text" id="otp_digit_<?= $digit + 1 ?>" name="otp_digits[]" required maxlength="6" pattern="[0-9]" inputmode="numeric"
                    aria-label="Digit <?= $digit + 1 ?>"
                    <?= $digit === 0 ? 'autocomplete="one-time-code"' : 'autocomplete="off"' ?>
                    class="auth-input h-12 min-w-0 w-full rounded-xl border border-slate-200 bg-white text-center text-xl font-bold text-slate-900 shadow-sm transition focus:border-blue-500 focus:bg-blue-50/40 focus:ring-4 focus:ring-blue-100 sm:h-14 sm:text-2xl">
                <?php endfor; ?>
            </div>
            <p class="text-center text-xs text-slate-400">The code expires in 15 minutes.</p>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
            <div>
                <label for="password" class="block text-xs font-bold text-slate-700 mb-1">New password</label>
                <input type="password" id="password" name="password" required minlength="8" placeholder="••••••••"
                    class="auth-input w-full px-3.5 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-sm text-slate-900 transition">
            </div>

            <div>
                <label for="password_confirmation" class="block text-xs font-bold text-slate-700 mb-1">Confirm password</label>
                <input type="password" id="password_confirmation" name="password_confirmation" required minlength="8" placeholder="••••••••"
                    class="auth-input w-full px-3.5 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-sm text-slate-900 transition">
            </div>
        </div>

        <button type="submit"
            class="w-full py-2.5 px-4 rounded-xl bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white font-bold text-sm shadow-lg shadow-blue-500/25 transition duration-150 flex items-center justify-center gap-2 mt-1">
            <span>Save new password</span>
        </button>
    </form>

</div>

<script>
(() => {
    const fields = Array.from(document.querySelectorAll('#otpDigits input'));

    fields.forEach((field, index) => {
        field.addEventListener('input', () => {
            const digits = field.value.replace(/\D/g, '');
            if (digits.length > 1) {
                const enteredDigits = digits.slice(0, fields.length - index);
                enteredDigits.split('').forEach((digit, offset) => {
                    if (fields[index + offset]) fields[index + offset].value = digit;
                });
                field.value = enteredDigits[0] ?? '';
                fields[Math.min(index + enteredDigits.length, fields.length - 1)].focus();
            } else {
                field.value = digits;
                if (field.value && index < fields.length - 1) fields[index + 1].focus();
            }
        });

        field.addEventListener('keydown', event => {
            if (event.key === 'Backspace' && !field.value && index > 0) {
                fields[index - 1].focus();
                fields[index - 1].value = '';
            } else if (event.key === 'ArrowLeft' && index > 0) {
                event.preventDefault();
                fields[index - 1].focus();
            } else if (event.key === 'ArrowRight' && index < fields.length - 1) {
                event.preventDefault();
                fields[index + 1].focus();
            }
        });

        field.addEventListener('paste', event => {
            const pasted = (event.clipboardData || window.clipboardData).getData('text').replace(/\D/g, '');
            if (!pasted) return;
            event.preventDefault();
            const enteredDigits = pasted.slice(0, fields.length - index);
            enteredDigits.split('').forEach((digit, offset) => {
                fields[index + offset].value = digit;
            });
            fields[Math.min(index + enteredDigits.length, fields.length - 1)].focus();
        });
    });
})();
</script>

<?php require_once __DIR__ . '/includes/auth-footer.php'; ?>
