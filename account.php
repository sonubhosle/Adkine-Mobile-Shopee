<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

session_init();
require_auth();

$userId = auth_id();
$user = db_fetch_one("SELECT * FROM users WHERE id = ?", [$userId]);
$addresses = db_fetch_all("SELECT * FROM addresses WHERE user_id = ? ORDER BY is_default DESC, id DESC", [$userId]);

$pageTitle = 'My Profile & Addresses';
$robotsMeta = 'noindex, nofollow';
require_once __DIR__ . '/includes/header.php';
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8">
    <div class="border-b border-slate-200 pb-5">
        <h1 class="text-2xl font-black text-slate-900 tracking-tight">Account & Addresses</h1>
        <p class="text-xs text-slate-500 mt-0.5">Manage your personal information, security credentials, and shipping addresses</p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
        <!-- Left: Profile & Password -->
        <div class="lg:col-span-5 space-y-8">
            <!-- Personal Details Card -->
            <div class="bg-white rounded-3xl border border-slate-200/80 p-6 sm:p-8 shadow-xs space-y-4">
                <h2 class="text-sm font-extrabold text-slate-900 uppercase tracking-wider border-b border-slate-100 pb-3">Personal Profile</h2>
                
                <form action="<?= url('/actions/profile-update.php') ?>" method="POST" class="space-y-4">
                    <?= csrf_field() ?>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Full Name</label>
                        <input type="text" name="full_name" value="<?= e($user['full_name']) ?>" required class="w-full text-xs p-2.5 rounded-xl border border-slate-200">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Email Address</label>
                        <input type="email" value="<?= e($user['email']) ?>" disabled class="w-full text-xs p-2.5 rounded-xl border border-slate-200 bg-slate-50 text-slate-400 cursor-not-allowed">
                        <span class="text-[10px] text-slate-400 mt-1 block">Account status: <?= strtoupper($user['status']) ?></span>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Mobile Phone</label>
                        <input type="tel" name="phone" value="<?= e($user['phone'] ?? '') ?>" required class="w-full text-xs p-2.5 rounded-xl border border-slate-200">
                    </div>
                    <button type="submit" class="px-5 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs transition">
                        Save Changes
                    </button>
                </form>
            </div>

            <!-- Change Password Card -->
            <div class="bg-white rounded-3xl border border-slate-200/80 p-6 sm:p-8 shadow-xs space-y-4">
                <h2 class="text-sm font-extrabold text-slate-900 uppercase tracking-wider border-b border-slate-100 pb-3">Change Password</h2>
                
                <form action="<?= url('/actions/profile-password.php') ?>" method="POST" class="space-y-4">
                    <?= csrf_field() ?>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Current Password</label>
                        <input type="password" name="current_password" required placeholder="••••••••" class="w-full text-xs p-2.5 rounded-xl border border-slate-200">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">New Password (Min 8 Characters)</label>
                        <input type="password" name="password" required placeholder="••••••••" class="w-full text-xs p-2.5 rounded-xl border border-slate-200">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Confirm New Password</label>
                        <input type="password" name="password_confirmation" required placeholder="••••••••" class="w-full text-xs p-2.5 rounded-xl border border-slate-200">
                    </div>
                    <button type="submit" class="px-5 py-2.5 rounded-xl bg-slate-900 hover:bg-black text-white font-bold text-xs transition">
                        Update Password
                    </button>
                </form>
            </div>
        </div>

        <!-- Right: Address Book -->
        <div class="lg:col-span-7 space-y-6">
            <div class="bg-white rounded-3xl border border-slate-200/80 p-6 sm:p-8 shadow-xs space-y-6">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <h2 class="text-sm font-extrabold text-slate-900 uppercase tracking-wider">Address Book</h2>
                    <span class="text-xs text-slate-400 font-bold"><?= count($addresses) ?> Saved</span>
                </div>

                <?php if (empty($addresses)): ?>
                    <p class="text-xs text-slate-400">No saved addresses yet. Add your primary delivery address below.</p>
                <?php else: ?>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <?php foreach ($addresses as $addr): ?>
                            <div class="p-4 rounded-2xl border border-slate-200 bg-slate-50/50 flex flex-col justify-between space-y-3">
                                <div>
                                    <div class="flex items-center justify-between mb-1">
                                        <span class="font-bold text-xs text-slate-900"><?= e($addr['full_name']) ?></span>
                                        <?php if ($addr['is_default']): ?>
                                            <span class="px-2 py-0.5 rounded-full bg-indigo-100 text-blue-700 text-[9px] font-extrabold uppercase">Default</span>
                                        <?php endif; ?>
                                    </div>
                                    <p class="text-[11px] text-slate-600 leading-relaxed">
                                        <?= e($addr['address_line1']) ?><br>
                                        <?= !empty($addr['address_line2']) ? e($addr['address_line2']) . '<br>' : '' ?>
                                        <?= e($addr['city']) ?>, <?= e($addr['state']) ?> - <?= e($addr['postal_code']) ?>
                                    </p>
                                    <div class="text-[11px] text-slate-500 font-medium mt-1">Phone: <?= e($addr['phone']) ?></div>
                                </div>
                                <div class="pt-2 border-t border-slate-200/60 flex justify-end">
                                    <form action="<?= url('/actions/address-delete.php') ?>" method="POST" onsubmit="return confirm('Delete this address?');">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="address_id" value="<?= $addr['id'] ?>">
                                        <button type="submit" class="text-rose-600 hover:text-rose-700 font-bold text-xs">Delete</button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <!-- Add New Address Form -->
                <div class="border-t border-slate-100 pt-6 space-y-4">
                    <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wider">Add New Delivery Address</h3>
                    <form action="<?= url('/actions/address-add.php') ?>" method="POST" class="space-y-4">
                        <?= csrf_field() ?>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-[11px] font-bold text-slate-700 mb-1">Recipient Name</label>
                                <input type="text" name="full_name" required placeholder="Full Name" class="w-full text-xs p-2.5 rounded-xl border border-slate-200">
                            </div>
                            <div>
                                <label class="block text-[11px] font-bold text-slate-700 mb-1">Phone Number</label>
                                <input type="tel" name="phone" required placeholder="10-digit Phone" class="w-full text-xs p-2.5 rounded-xl border border-slate-200">
                            </div>
                        </div>

                        <div>
                            <label class="block text-[11px] font-bold text-slate-700 mb-1">House / Flat / Building</label>
                            <input type="text" name="address_line1" required placeholder="Street address" class="w-full text-xs p-2.5 rounded-xl border border-slate-200">
                        </div>

                        <div>
                            <label class="block text-[11px] font-bold text-slate-700 mb-1">Area / Landmark (Optional)</label>
                            <input type="text" name="address_line2" placeholder="e.g. Near Metro Station" class="w-full text-xs p-2.5 rounded-xl border border-slate-200">
                        </div>

                        <div class="grid grid-cols-3 gap-3">
                            <div>
                                <label class="block text-[11px] font-bold text-slate-700 mb-1">City</label>
                                <input type="text" name="city" required placeholder="City" class="w-full text-xs p-2.5 rounded-xl border border-slate-200">
                            </div>
                            <div>
                                <label class="block text-[11px] font-bold text-slate-700 mb-1">State</label>
                                <input type="text" name="state" required placeholder="State" class="w-full text-xs p-2.5 rounded-xl border border-slate-200">
                            </div>
                            <div>
                                <label class="block text-[11px] font-bold text-slate-700 mb-1">PIN Code</label>
                                <input type="text" name="postal_code" required placeholder="PIN Code" class="w-full text-xs p-2.5 rounded-xl border border-slate-200">
                            </div>
                        </div>

                        <div class="flex items-center gap-2 pt-1">
                            <input type="checkbox" id="is_default" name="is_default" value="1" class="rounded text-blue-600">
                            <label for="is_default" class="text-xs text-slate-600 font-semibold cursor-pointer">Set as default shipping address</label>
                        </div>

                        <button type="submit" class="px-5 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs transition">
                            Save Address
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

