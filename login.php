<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

session_init();

if (is_logged_in()) {
    $role = auth_role();
    if (in_array($role, ['admin', 'manager'], true)) {
        redirect('/admin/index.php');
    } elseif ($role === 'technician') {
        redirect('/technician/index.php');
    } else {
        redirect('/index.php');
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        set_flash('error', 'Invalid security token. Please try again.');
        redirect('/login.php');
    }

    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        set_flash('error', 'Please enter your email and password.');
        redirect('/login.php');
    }

    $user = db_fetch_one(
        "SELECT u.*, r.slug AS role_slug 
         FROM users u 
         JOIN roles r ON u.role_id = r.id 
         WHERE u.email = ? 
         LIMIT 1",
        [$email]
    );

    if ($user && password_verify($password, $user['password_hash'])) {
        if ($user['status'] !== 'active') {
            set_flash('error', 'Your account is suspended or pending verification.');
            redirect('/login.php');
        }

        login_user($user);

        $returnUrl = $_GET['return'] ?? '';
        if (!empty($returnUrl) && str_starts_with($returnUrl, '/')) {
            redirect($returnUrl);
        }

        if (in_array($user['role_slug'], ['admin', 'manager'], true)) {
            redirect('/admin/index.php');
        } elseif ($user['role_slug'] === 'technician') {
            redirect('/technician/index.php');
        } else {
            redirect('/index.php');
        }
    } else {
        set_flash('error', 'Invalid email address or password.');
        redirect('/login.php');
    }
}

$pageTitle = 'Sign In';
$minimal = true;
require_once __DIR__ . '/includes/auth-header.php';
?>

<div class="space-y-5">
    <div>
        <h2 class="text-2xl font-black tracking-tight text-slate-900 leading-tight">Welcome back</h2>
    </div>

    <form id="loginForm" action="<?= url('/login.php' . (!empty($_GET['return']) ? '?return=' . urlencode($_GET['return']) : '')) ?>" method="POST" class="space-y-3.5">
        <?= csrf_field() ?>

        <div>
            <label for="email" class="block text-xs font-bold text-slate-700 mb-1">Email Address</label>
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                    <i data-lucide="mail" class="w-3.5 h-3.5"></i>
                </div>
                <input type="email" id="email" name="email" required value="<?= e(old('email')) ?>"
                    placeholder="name@example.com" autocomplete="email"
                    class="auth-input w-full pl-9 pr-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-sm text-slate-900 placeholder-slate-400 transition">
            </div>
        </div>

        <div>
            <div class="flex items-center justify-between mb-1">
                <label for="password" class="text-xs font-bold text-slate-700">Password</label>
                <a href="<?= url('/forgot-password.php') ?>" class="text-[11px] font-semibold text-blue-600 hover:text-blue-700 hover:underline transition">Forgot password?</a>
            </div>
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                    <i data-lucide="lock" class="w-3.5 h-3.5"></i>
                </div>
                <input type="password" id="password" name="password" required placeholder="••••••••"
                    autocomplete="current-password"
                    class="auth-input w-full pl-9 pr-10 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-sm text-slate-900 placeholder-slate-400 transition">
                <button type="button" id="pwdToggleBtn" onclick="togglePwd('password','eyeIcon','pwdToggleBtn')"
                    aria-label="Show password" aria-pressed="false"
                    class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-slate-600 transition focus-visible:ring-2 focus-visible:ring-blue-600 rounded">
                    <i data-lucide="eye" id="eyeIcon" class="w-3.5 h-3.5" aria-hidden="true"></i>
                </button>
            </div>
        </div>

        <div class="flex items-center gap-2 pt-0.5">
            <input type="checkbox" name="remember" id="remember" class="w-3.5 h-3.5 rounded text-blue-600 border-slate-300 focus:ring-blue-500">
            <label for="remember" class="text-xs text-slate-600 font-medium cursor-pointer">Keep me signed in</label>
        </div>

        <button type="submit" id="submitBtn"
            class="w-full py-2.5 px-4 rounded-xl bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white font-bold text-sm shadow-lg shadow-blue-500/25 transition duration-150 flex items-center justify-center gap-2 active:scale-[0.99] hover:-translate-y-px focus-visible:ring-2 focus-visible:ring-blue-600">
            <span>Sign In</span>
            <i data-lucide="arrow-right" class="w-4 h-4" aria-hidden="true"></i>
        </button>
    </form>

    <p class="text-center text-xs text-slate-500">
        New here?
        <a href="<?= url('/register.php') ?>" class="font-semibold text-blue-600 hover:text-blue-700 hover:underline focus-visible:ring-2 focus-visible:ring-blue-600">Create an account</a>
    </p>
</div>

<script>
function togglePwd(id, iconId, btnId) {
    const f = document.getElementById(id), i = document.getElementById(iconId), b = document.getElementById(btnId);
    const isShowing = f.type === 'text';
    f.type = isShowing ? 'password' : 'text';
    i.setAttribute('data-lucide', isShowing ? 'eye' : 'eye-off');
    if (b) {
        b.setAttribute('aria-label', isShowing ? 'Show password' : 'Hide password');
        b.setAttribute('aria-pressed', isShowing ? 'false' : 'true');
    }
    lucide.createIcons();
}
</script>

<?php require_once __DIR__ . '/includes/auth-footer.php'; ?>
