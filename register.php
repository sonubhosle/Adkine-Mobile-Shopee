<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

session_init();

if (is_logged_in()) {
    redirect('/index.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        set_flash('error', 'Invalid security token. Please try again.');
        redirect('/register.php');
    }

    $fullName = trim($_POST['full_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $passwordConfirmation = $_POST['password_confirmation'] ?? '';

    if (empty($fullName) || empty($email) || empty($password)) {
        set_flash('error', 'Please complete all required fields.');
        redirect('/register.php');
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        set_flash('error', 'Please provide a valid email address.');
        redirect('/register.php');
    }

    if (strlen($password) < 8) {
        set_flash('error', 'Password must be at least 8 characters long.');
        redirect('/register.php');
    }

    if ($password !== $passwordConfirmation) {
        set_flash('error', 'Passwords do not match.');
        redirect('/register.php');
    }

    $existing = db_fetch_one("SELECT id FROM users WHERE email = ? LIMIT 1", [$email]);
    if ($existing) {
        set_flash('error', 'An account with this email address already exists. Please sign in.');
        redirect('/register.php');
    }

    $customerRole = db_fetch_one("SELECT id, slug FROM roles WHERE slug = 'customer' LIMIT 1");
    $roleId = $customerRole ? (int)$customerRole['id'] : 3;

    $passwordHash = password_hash($password, PASSWORD_BCRYPT);

    $newUserId = db_insert(
        "INSERT INTO users (role_id, full_name, email, phone, password_hash, status, email_verified_at, created_at, updated_at) 
         VALUES (?, ?, ?, ?, ?, 'active', NOW(), NOW(), NOW())",
        [$roleId, $fullName, $email, $phone, $passwordHash]
    );

    $newUser = db_fetch_one(
        "SELECT u.*, r.slug AS role_slug 
         FROM users u 
         JOIN roles r ON u.role_id = r.id 
         WHERE u.id = ? 
         LIMIT 1",
        [$newUserId]
    );

    if ($newUser) {
        login_user($newUser);
    }

    set_flash('success', 'Account created successfully! Welcome to ' . setting('site_name', 'Adkine Mobile Shopee') . '.');
    redirect('/index.php');
}

$pageTitle = 'Create Account';
$minimal = true;
require_once __DIR__ . '/includes/auth-header.php';
?>

<div class="space-y-4">
    <div>
        <h2 class="text-2xl font-black tracking-tight text-slate-900 leading-tight">Create your account</h2>
    </div>

    <form id="registerForm" action="<?= url('/register.php') ?>" method="POST" class="space-y-2.5">
        <?= csrf_field() ?>

        <div class="grid grid-cols-2 gap-2.5">
            <div>
                <label for="full_name" class="block text-xs font-bold text-slate-700 mb-1">Full Name</label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                        <i data-lucide="user" class="w-3.5 h-3.5"></i>
                    </div>
                    <input type="text" id="full_name" name="full_name" required value="<?= e(old('full_name')) ?>"
                        placeholder="John Doe" autocomplete="name"
                        class="auth-input w-full pl-9 pr-3 py-2 rounded-xl border border-slate-200 bg-slate-50 text-sm text-slate-900 placeholder-slate-400 transition">
                </div>
            </div>
            <div>
                <label for="phone" class="block text-xs font-bold text-slate-700 mb-1">Phone</label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                        <i data-lucide="phone" class="w-3.5 h-3.5"></i>
                    </div>
                    <input type="tel" id="phone" name="phone" required value="<?= e(old('phone')) ?>"
                        placeholder="9876543210" autocomplete="tel"
                        class="auth-input w-full pl-9 pr-3 py-2 rounded-xl border border-slate-200 bg-slate-50 text-sm text-slate-900 placeholder-slate-400 transition">
                </div>
            </div>
        </div>

        <div>
            <label for="email" class="block text-xs font-bold text-slate-700 mb-1">Email Address</label>
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                    <i data-lucide="mail" class="w-3.5 h-3.5"></i>
                </div>
                <input type="email" id="email" name="email" required value="<?= e(old('email')) ?>"
                    placeholder="john@example.com" autocomplete="email"
                    class="auth-input w-full pl-9 pr-4 py-2 rounded-xl border border-slate-200 bg-slate-50 text-sm text-slate-900 placeholder-slate-400 transition">
            </div>
        </div>

        <div class="grid grid-cols-2 gap-2.5">
            <div>
                <label for="password" class="block text-xs font-bold text-slate-700 mb-1">Password</label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                        <i data-lucide="lock" class="w-3.5 h-3.5"></i>
                    </div>
                    <input type="password" id="password" name="password" required minlength="8"
                        placeholder="Min. 8 chars" autocomplete="new-password" oninput="checkStrength();checkMatch()"
                        class="auth-input w-full pl-9 pr-8 py-2 rounded-xl border border-slate-200 bg-slate-50 text-sm text-slate-900 placeholder-slate-400 transition">
                    <button type="button" id="btnEi1" onclick="togglePwd('password','ei1','btnEi1')"
                        aria-label="Show password" aria-pressed="false"
                        class="absolute inset-y-0 right-0 pr-2.5 flex items-center text-slate-400 hover:text-slate-600 transition focus-visible:ring-2 focus-visible:ring-blue-600 rounded">
                        <i data-lucide="eye" id="ei1" class="w-3.5 h-3.5" aria-hidden="true"></i>
                    </button>
                </div>
            </div>
            <div>
                <label for="password_confirmation" class="block text-xs font-bold text-slate-700 mb-1">Confirm</label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                        <i data-lucide="check-check" class="w-3.5 h-3.5"></i>
                    </div>
                    <input type="password" id="password_confirmation" name="password_confirmation" required minlength="8"
                        placeholder="Repeat" autocomplete="new-password" oninput="checkMatch()"
                        class="auth-input w-full pl-9 pr-8 py-2 rounded-xl border border-slate-200 bg-slate-50 text-sm text-slate-900 placeholder-slate-400 transition">
                    <button type="button" id="btnEi2" onclick="togglePwd('password_confirmation','ei2','btnEi2')"
                        aria-label="Show confirmation password" aria-pressed="false"
                        class="absolute inset-y-0 right-0 pr-2.5 flex items-center text-slate-400 hover:text-slate-600 transition focus-visible:ring-2 focus-visible:ring-blue-600 rounded">
                        <i data-lucide="eye" id="ei2" class="w-3.5 h-3.5" aria-hidden="true"></i>
                    </button>
                </div>
            </div>
        </div>

        <div class="space-y-1 pt-0.5">
            <div class="flex items-center justify-between text-[10px]">
                <span class="text-slate-400">Strength:</span>
                <span id="strengthLabel" class="font-bold text-slate-400">—</span>
            </div>
            <div class="flex gap-1 h-1 w-full">
                <div id="b1" class="flex-1 rounded-full bg-slate-200 transition-colors duration-300"></div>
                <div id="b2" class="flex-1 rounded-full bg-slate-200 transition-colors duration-300"></div>
                <div id="b3" class="flex-1 rounded-full bg-slate-200 transition-colors duration-300"></div>
            </div>
            <div id="matchMsg" class="hidden text-[10px] font-semibold text-emerald-600 flex items-center gap-1">
                <i data-lucide="check-circle" class="w-3 h-3"></i> Passwords match
            </div>
        </div>

        <div class="flex items-start gap-2 pt-0.5">
            <input type="checkbox" required id="terms" class="w-3.5 h-3.5 mt-0.5 rounded text-blue-600 border-slate-300 focus:ring-blue-500 shrink-0">
            <label for="terms" class="text-[11px] text-slate-500 leading-snug cursor-pointer">
                I agree to the <a href="#" class="text-blue-600 hover:underline font-semibold">Terms</a> &amp; <a href="#" class="text-blue-600 hover:underline font-semibold">Privacy Policy</a>.
            </label>
        </div>

        <button type="submit"
            class="w-full py-2.5 px-4 rounded-xl bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white font-bold text-sm shadow-lg shadow-blue-500/25 transition duration-150 flex items-center justify-center gap-2 active:scale-[0.99] hover:-translate-y-px mt-1">
            <span>Create Account</span>
            <i data-lucide="arrow-right" class="w-4 h-4"></i>
        </button>
    </form>

    <div class="relative flex items-center gap-3 pt-1">
        <div class="flex-1 h-px bg-slate-100"></div>
        <span class="text-[10px] text-slate-400 font-medium">HAVE AN ACCOUNT?</span>
        <div class="flex-1 h-px bg-slate-100"></div>
    </div>
    <a href="<?= url('/login.php') ?>"
       class="flex items-center justify-center gap-2 w-full py-2.5 rounded-xl border border-slate-200 bg-slate-50 hover:bg-slate-100 text-slate-700 font-bold text-sm transition hover:-translate-y-px">
        <i data-lucide="log-in" class="w-4 h-4 text-blue-500"></i>
        Sign in instead
    </a>
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
function checkStrength() {
    const v = document.getElementById('password').value;
    const b1=document.getElementById('b1'), b2=document.getElementById('b2'), b3=document.getElementById('b3');
    const lbl=document.getElementById('strengthLabel');
    let s=0;
    if(v.length>=8) s++;
    if(/[A-Z]/.test(v)||/[0-9]/.test(v)) s++;
    if(/[^A-Za-z0-9]/.test(v)&&v.length>=10) s++;
    const cl=['bg-slate-200','bg-rose-500','bg-amber-400','bg-emerald-500'];
    const tx=['—','Weak','Medium','Strong'];
    const tc=['text-slate-400','text-rose-500','text-amber-500','text-emerald-600'];
    b1.className='flex-1 rounded-full transition-colors duration-300 '+(s>=1?cl[s]:'bg-slate-200');
    b2.className='flex-1 rounded-full transition-colors duration-300 '+(s>=2?cl[s]:'bg-slate-200');
    b3.className='flex-1 rounded-full transition-colors duration-300 '+(s>=3?cl[s]:'bg-slate-200');
    lbl.textContent=tx[s]; lbl.className='font-bold '+tc[s];
}
function checkMatch() {
    const p=document.getElementById('password').value;
    const c=document.getElementById('password_confirmation').value;
    const m=document.getElementById('matchMsg');
    p&&c&&p===c ? m.classList.remove('hidden') : m.classList.add('hidden');
    if(p&&c&&p!==c) {
        document.getElementById('password_confirmation').style.borderColor='#f43f5e';
    } else {
        document.getElementById('password_confirmation').style.borderColor='';
    }
}
</script>

<?php require_once __DIR__ . '/includes/auth-footer.php'; ?>
