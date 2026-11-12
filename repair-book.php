<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/repair-functions.php';

session_init();
$user = auth_user();
$userId = auth_id();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        set_flash('error', 'Invalid security token.');
        redirect('/repair-book.php');
    }

    $customerName = trim($_POST['customer_name'] ?? '');
    $customerPhone = trim($_POST['customer_phone'] ?? '');
    $customerEmail = trim($_POST['customer_email'] ?? '');
    $deviceBrand = trim($_POST['device_brand'] ?? '');
    $deviceModel = trim($_POST['device_model'] ?? '');
    $issueType = trim($_POST['issue_type'] ?? '');
    $issueDescription = trim($_POST['issue_description'] ?? '');
    $serviceMode = trim($_POST['service_mode'] ?? 'shop_visit');

    if (empty($customerName) || empty($customerPhone) || empty($customerEmail) || empty($deviceBrand) || empty($deviceModel) || empty($issueType) || empty($issueDescription)) {
        set_flash('error', 'Please complete all required fields.');
        redirect('/repair-book.php');
    }

    $images = [];
    if (!empty($_FILES['device_image']['tmp_name'])) {
        $uploadDir = __DIR__ . '/uploads/repairs/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        $ext = strtolower(pathinfo($_FILES['device_image']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
            $filename = 'repair_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
            if (move_uploaded_file($_FILES['device_image']['tmp_name'], $uploadDir . $filename)) {
                $images[] = $filename;
            }
        }
    }

    $res = repair_book([
        'customer_name' => $customerName,
        'customer_phone' => $customerPhone,
        'customer_email' => $customerEmail,
        'device_brand' => $deviceBrand,
        'device_model' => $deviceModel,
        'device_imei_serial' => trim($_POST['device_imei_serial'] ?? ''),
        'issue_type' => $issueType,
        'issue_description' => $issueDescription,
        'condition_notes' => trim($_POST['condition_notes'] ?? ''),
        'service_mode' => $serviceMode,
        'pickup_address' => trim($_POST['pickup_address'] ?? ''),
        'priority' => $_POST['priority'] ?? 'normal',
        'device_images' => !empty($images) ? json_encode($images) : null,
    ], $userId);

    set_flash('success', "Repair ticket #{$res['ticket_number']} booked successfully! Our technician team will inspect your device.");
    redirect('/repair-track.php?ticket=' . urlencode($res['ticket_number']) . '&phone=' . urlencode($customerPhone));
}

$brands = db_fetch_all("SELECT name FROM brands WHERE is_active = 1 ORDER BY name ASC");
$selectedIssue = $_GET['issue'] ?? '';
$selectedMode = $_GET['mode'] ?? 'shop_visit';

$pageTitle = 'Certified Smartphone & Tablet Repair Lab';
$metaDescription = 'Book instant diagnostic and repair service for Apple, Samsung, OnePlus, and Xiaomi devices. 45-minute turnaround, original OEM parts, and 90-day warranty.';
$canonicalUrl = absolute_url('/repair-book.php');

$pageSchema = [
    '@context' => 'https://schema.org',
    '@type' => 'Service',
    'name' => 'Certified Mobile Phone Repair & Screen Replacement',
    'serviceType' => 'Electronics Repair Service',
    'provider' => [
        '@type' => 'LocalBusiness',
        'name' => setting('site_name', 'Adkine Mobile Shopee'),
        'telephone' => setting('site_phone', '+91 98765 43210'),
        'address' => [
            '@type' => 'PostalAddress',
            'streetAddress' => setting('store_address', 'Electronics City Tech Park'),
            'addressCountry' => 'IN'
        ]
    ],
    'areaServed' => 'India',
    'description' => 'Fast 45-minute smartphone screen replacement, battery replacement, water damage recovery, and motherboard chip-level repair with original OEM components.'
];

require_once __DIR__ . '/includes/header.php';
?>

<div class="bg-slate-50/60 min-h-screen pb-16">
    <div class="px-6 pt-8">
        <h1 class="text-2xl font-black text-slate-900 tracking-tight">Book Certified Device Repair Service</h1>
    </div>

    <!-- STEP INDICATOR BAR -->
    <div class="px-6 mt-5">
        <nav aria-label="Repair booking steps" class="bg-white rounded-2xl shadow-xl shadow-slate-200/60 border border-slate-200/80 p-3 sm:p-4">
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 text-xs font-bold">
                <div class="flex items-center gap-2.5 p-2 rounded-xl bg-indigo-50 text-blue-700 border border-blue-200/60" aria-current="step">
                    <div class="w-7 h-7 rounded-lg bg-indigo-600 text-white flex items-center justify-center text-xs font-black shrink-0" aria-hidden="true">1</div>
                    <div class="truncate">
                        <div class="text-[10px] text-blue-500 font-semibold uppercase tracking-wider">Step 1</div>
                        <div class="text-xs font-bold">Contact &amp; Device</div>
                    </div>
                </div>

                <div class="flex items-center gap-2.5 p-2 rounded-xl text-slate-600 hover:bg-slate-50 transition">
                    <div class="w-7 h-7 rounded-lg bg-slate-100 text-slate-600 flex items-center justify-center text-xs font-black shrink-0" aria-hidden="true">2</div>
                    <div class="truncate">
                        <div class="text-[10px] text-slate-500 font-semibold uppercase tracking-wider">Step 2</div>
                        <div class="text-xs font-bold">Issue Diagnostics</div>
                    </div>
                </div>

                <div class="flex items-center gap-2.5 p-2 rounded-xl text-slate-600 hover:bg-slate-50 transition">
                    <div class="w-7 h-7 rounded-lg bg-slate-100 text-slate-600 flex items-center justify-center text-xs font-black shrink-0" aria-hidden="true">3</div>
                    <div class="truncate">
                        <div class="text-[10px] text-slate-500 font-semibold uppercase tracking-wider">Step 3</div>
                        <div class="text-xs font-bold">Inspection Photos</div>
                    </div>
                </div>

                <div class="flex items-center gap-2.5 p-2 rounded-xl text-slate-600 hover:bg-slate-50 transition">
                    <div class="w-7 h-7 rounded-lg bg-slate-100 text-slate-600 flex items-center justify-center text-xs font-black shrink-0" aria-hidden="true">4</div>
                    <div class="truncate">
                        <div class="text-[10px] text-slate-500 font-semibold uppercase tracking-wider">Step 4</div>
                        <div class="text-xs font-bold">Service Mode</div>
                    </div>
                </div>
            </div>
        </nav>
    </div>

    <div class="px-6 mt-8">
        <form method="POST" action="<?= url('/repair-book.php') ?>" enctype="multipart/form-data" id="repairBookingForm" class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
            <?= csrf_field() ?>

            <!-- LEFT FORM COLUMN (8 Cols) -->
            <div class="lg:col-span-8 space-y-6">
                <!-- SECTION 1: Customer Contact & Device Info -->
                <div class="bg-white rounded-3xl border border-slate-200/80 shadow-md shadow-slate-100 overflow-hidden transition-all">
                    <div class="p-6 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-2xl bg-indigo-600 text-white flex items-center justify-center shadow-sm shadow-blue-500/20">
                                <i data-lucide="user-check" class="w-5 h-5"></i>
                            </div>
                            <div>
                                <h2 class="font-extrabold text-slate-900 text-base">Contact &amp; Device Identity</h2>
                                <p class="text-xs text-slate-500">Provide your contact info and device specifications</p>
                            </div>
                        </div>
                        <span class="text-xs font-bold text-blue-600 bg-indigo-50 px-2.5 py-1 rounded-full">Step 1 of 4</span>
                    </div>

                    <div class="p-6 sm:p-8 space-y-5">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label for="customer_name" class="block text-xs font-bold text-slate-700 mb-1.5">
                                    Full Name <span class="text-rose-500">*</span>
                                </label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                                        <i data-lucide="user" class="w-4 h-4"></i>
                                    </div>
                                    <input type="text" id="customer_name" name="customer_name" required maxlength="150"
                                        placeholder="e.g. Rahul Sharma"
                                        value="<?= e($_POST['customer_name'] ?? ($user['full_name'] ?? '')) ?>"
                                        class="w-full pl-10 pr-4 py-3 rounded-xl border border-slate-200 bg-white text-sm text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-4 focus:ring-blue-500/10 focus:border-blue-600 transition shadow-2xs">
                                </div>
                            </div>

                            <div>
                                <label for="customer_phone" class="block text-xs font-bold text-slate-700 mb-1.5">
                                    Mobile Phone Number <span class="text-rose-500">*</span>
                                </label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                                        <i data-lucide="phone" class="w-4 h-4"></i>
                                    </div>
                                    <input type="tel" id="customer_phone" name="customer_phone" required maxlength="20"
                                        placeholder="9876543210"
                                        value="<?= e($_POST['customer_phone'] ?? ($user['phone'] ?? '')) ?>"
                                        class="w-full pl-10 pr-4 py-3 rounded-xl border border-slate-200 bg-white text-sm text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-4 focus:ring-blue-500/10 focus:border-blue-600 transition shadow-2xs">
                                </div>
                            </div>

                            <div class="sm:col-span-2">
                                <label for="customer_email" class="block text-xs font-bold text-slate-700 mb-1.5">
                                    Email Address (For Status Updates &amp; Invoice) <span class="text-rose-500">*</span>
                                </label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                                        <i data-lucide="mail" class="w-4 h-4"></i>
                                    </div>
                                    <input type="email" id="customer_email" name="customer_email" required maxlength="191"
                                        placeholder="rahul@example.com"
                                        value="<?= e($_POST['customer_email'] ?? ($user['email'] ?? '')) ?>"
                                        class="w-full pl-10 pr-4 py-3 rounded-xl border border-slate-200 bg-white text-sm text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-4 focus:ring-blue-500/10 focus:border-blue-600 transition shadow-2xs">
                                </div>
                            </div>
                        </div>

                        <!-- Brand & Quick Selection -->
                        <div class="pt-2 border-t border-slate-100">
                            <label for="device_brand" class="block text-xs font-bold text-slate-700 mb-1.5">
                                Device Brand <span class="text-rose-500">*</span>
                            </label>
                            
                            <div class="flex flex-wrap gap-1.5 mb-2.5">
                                <?php
                                $popularBrands = ['Apple', 'Samsung', 'OnePlus', 'Xiaomi', 'Google', 'Vivo', 'Realme', 'Oppo'];
                                foreach ($popularBrands as $pBrand): ?>
                                    <button type="button" onclick="selectBrandQuick('<?= $pBrand ?>')"
                                        class="brand-chip px-3 py-1 rounded-lg text-xs font-semibold border border-slate-200 hover:border-blue-400 bg-slate-50 hover:bg-indigo-50/50 text-slate-700 transition">
                                        <?= $pBrand ?>
                                    </button>
                                <?php endforeach; ?>
                            </div>

                            <div class="relative">
                                <select id="device_brand" name="device_brand" required onchange="updateSummary()"
                                    class="w-full pl-4 pr-10 py-3 rounded-xl border border-slate-200 bg-white text-sm text-slate-900 appearance-none focus:outline-none focus:ring-4 focus:ring-blue-500/10 focus:border-blue-600 transition shadow-2xs">
                                    <option value="">Select brand...</option>
                                    <?php foreach ($brands as $brand): ?>
                                        <option value="<?= e($brand['name']) ?>"
                                            <?= (isset($_POST['device_brand']) && $_POST['device_brand'] === $brand['name']) ? 'selected' : '' ?>>
                                            <?= e($brand['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <i data-lucide="chevron-down" class="w-4 h-4 text-slate-400 absolute right-3.5 top-1/2 -translate-y-1/2 pointer-events-none"></i>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label for="device_model" class="block text-xs font-bold text-slate-700 mb-1.5">
                                    Exact Model Name / Number <span class="text-rose-500">*</span>
                                </label>
                                <input type="text" id="device_model" name="device_model" required oninput="updateSummary()"
                                    placeholder="e.g. iPhone 15 Pro, Galaxy S24 Ultra"
                                    value="<?= e($_POST['device_model'] ?? '') ?>"
                                    class="w-full px-4 py-3 rounded-xl border border-slate-200 bg-white text-sm text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-4 focus:ring-blue-500/10 focus:border-blue-600 transition shadow-2xs">
                            </div>

                            <div>
                                <div class="flex items-center justify-between mb-1.5">
                                    <label for="device_imei_serial" class="block text-xs font-bold text-slate-700">
                                        IMEI or Serial Number
                                    </label>
                                    <span class="text-[10px] text-slate-400 font-normal">Dial *#06# to find</span>
                                </div>
                                <input type="text" id="device_imei_serial" name="device_imei_serial" maxlength="100"
                                    placeholder="15-digit IMEI (Optional)"
                                    value="<?= e($_POST['device_imei_serial'] ?? '') ?>"
                                    class="w-full px-4 py-3 rounded-xl border border-slate-200 bg-white text-sm text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-4 focus:ring-blue-500/10 focus:border-blue-600 transition shadow-2xs">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- SECTION 2: Issue Diagnostics & Description -->
                <div class="bg-white rounded-3xl border border-slate-200/80 shadow-md shadow-slate-100 overflow-hidden transition-all">
                    <div class="p-6 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-2xl bg-rose-600 text-white flex items-center justify-center shadow-sm shadow-rose-500/20">
                                <i data-lucide="wrench" class="w-5 h-5"></i>
                            </div>
                            <div>
                                <h2 class="font-extrabold text-slate-900 text-base">Select Primary Issue</h2>
                                <p class="text-xs text-slate-500">Pick the fault symptom to help our diagnostic lab prepare OEM parts</p>
                            </div>
                        </div>
                        <span class="text-xs font-bold text-blue-600 bg-indigo-50 px-2.5 py-1 rounded-full">Step 2 of 4</span>
                    </div>

                    <div class="p-6 sm:p-8 space-y-6">
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-2.5">
                            <?php
                            $issueTypes = [
                                ['value' => 'screen_damage', 'label' => 'Cracked Screen', 'icon' => 'smartphone', 'desc' => 'Glass, OLED, Lines'],
                                ['value' => 'battery_replacement', 'label' => 'Battery Drain', 'icon' => 'battery-charging', 'desc' => 'Fast drain, swelling'],
                                ['value' => 'charging_port', 'label' => 'Charging Port', 'icon' => 'zap', 'desc' => 'Loose pin, slow charge'],
                                ['value' => 'water_damage', 'label' => 'Water Damage', 'icon' => 'droplets', 'desc' => 'Liquid spill, corrosion'],
                                ['value' => 'speaker_mic', 'label' => 'Speaker / Mic', 'icon' => 'volume-2', 'desc' => 'Low sound, no audio'],
                                ['value' => 'camera_issue', 'label' => 'Camera Lens', 'icon' => 'camera', 'desc' => 'Blurry focus, cracked'],
                                ['value' => 'software_os', 'label' => 'Bootloop / OS', 'icon' => 'cpu', 'desc' => 'Stuck on logo, bricked'],
                                ['value' => 'other', 'label' => 'Other / Unknown', 'icon' => 'search', 'desc' => 'Full lab test required'],
                            ];

                            $currentIssue = $_POST['issue_type'] ?? ($selectedIssue ?? '');
                            foreach ($issueTypes as $issue):
                                $isSelected = ($currentIssue === $issue['value']);
                            ?>
                                <label class="issue-selector cursor-pointer block select-none">
                                    <input type="radio" name="issue_type" value="<?= $issue['value'] ?>" class="sr-only" required
                                        <?= $isSelected ? 'checked' : '' ?> onchange="onIssueSelect('<?= $issue['label'] ?>')">
                                    <div class="issue-card p-3.5 rounded-2xl border-2 text-left transition-all duration-200 <?= $isSelected ? 'border-blue-600 bg-indigo-50/60 shadow-sm' : 'border-slate-200 hover:border-slate-300 bg-white hover:bg-slate-50/50' ?>">
                                        <div class="w-8 h-8 rounded-xl <?= $isSelected ? 'bg-indigo-600 text-white' : 'bg-slate-100 text-slate-700' ?> flex items-center justify-center mb-2 transition">
                                            <i data-lucide="<?= $issue['icon'] ?>" class="w-4 h-4"></i>
                                        </div>
                                        <div class="text-xs font-bold text-slate-900 leading-tight"><?= $issue['label'] ?></div>
                                        <div class="text-[10px] text-slate-400 mt-0.5 truncate"><?= $issue['desc'] ?></div>
                                    </div>
                                </label>
                            <?php endforeach; ?>
                        </div>

                        <div>
                            <div class="flex items-center justify-between mb-1.5">
                                <label for="issue_description" class="block text-xs font-bold text-slate-700">
                                    Describe the Problem in Detail <span class="text-rose-500">*</span>
                                </label>
                                <span class="text-[10px] text-slate-400 font-normal">Min. 10 characters</span>
                            </div>
                            <textarea id="issue_description" name="issue_description" rows="4" required minlength="10"
                                placeholder="Explain when and how the issue occurred..."
                                class="w-full px-4 py-3 rounded-xl border border-slate-200 bg-white text-sm text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-4 focus:ring-blue-500/10 focus:border-blue-600 transition shadow-2xs resize-none"><?= e($_POST['issue_description'] ?? '') ?></textarea>
                        </div>

                        <div class="p-3.5 rounded-2xl bg-slate-50 border border-slate-200/80">
                            <label class="block text-xs font-bold text-slate-700 mb-2">Repair Queue Urgency</label>
                            <div class="grid grid-cols-2 gap-2 text-xs">
                                <label class="cursor-pointer">
                                    <input type="radio" name="priority" value="normal" checked class="sr-only">
                                    <div class="priority-card p-3 rounded-xl border border-blue-600 bg-white text-slate-800 font-bold flex items-center justify-between transition shadow-2xs">
                                        <div class="flex items-center gap-2">
                                            <i data-lucide="clock" class="w-4 h-4 text-blue-600"></i>
                                            <span>Standard (24–48 Hrs)</span>
                                        </div>
                                        <span class="text-[10px] text-emerald-600 font-bold">Standard Queue</span>
                                    </div>
                                </label>
                                <label class="cursor-pointer">
                                    <input type="radio" name="priority" value="urgent" class="sr-only">
                                    <div class="priority-card p-3 rounded-xl border border-slate-200 bg-white text-slate-600 font-bold flex items-center justify-between hover:border-slate-300 transition shadow-2xs">
                                        <div class="flex items-center gap-2">
                                            <i data-lucide="zap" class="w-4 h-4 text-amber-500"></i>
                                            <span>Express Priority (Same Day)</span>
                                        </div>
                                        <span class="text-[10px] text-amber-600 font-bold">Priority Lab</span>
                                    </div>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- SECTION 3: Device Photos -->
                <div class="bg-white rounded-3xl border border-slate-200/80 shadow-md shadow-slate-100 overflow-hidden transition-all">
                    <div class="p-6 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-2xl bg-indigo-600 text-white flex items-center justify-center shadow-sm shadow-indigo-500/20">
                                <i data-lucide="camera" class="w-5 h-5"></i>
                            </div>
                            <div>
                                <h2 class="font-extrabold text-slate-900 text-base">Visual Damage Inspection Photo</h2>
                                <p class="text-xs text-slate-500">Attach a photo of the device condition</p>
                            </div>
                        </div>
                        <span class="text-xs font-bold text-slate-400 bg-slate-100 px-2.5 py-1 rounded-full">Optional</span>
                    </div>

                    <div class="p-6 sm:p-8">
                        <label for="device_image" class="relative flex flex-col items-center justify-center p-8 border-2 border-dashed border-slate-200 hover:border-blue-500 rounded-3xl cursor-pointer bg-slate-50/50 hover:bg-indigo-50/30 transition group">
                            <div class="w-14 h-14 rounded-2xl bg-white border border-slate-200 shadow-xs flex items-center justify-center text-slate-400 group-hover:text-blue-600 group-hover:scale-105 transition duration-200">
                                <i data-lucide="upload-cloud" class="w-7 h-7"></i>
                            </div>
                            <div class="text-center mt-3">
                                <p class="text-sm font-extrabold text-slate-800">Click to upload photo</p>
                                <p class="text-xs text-slate-400 mt-1">Supports JPG, PNG, WEBP &bull; Max 5MB</p>
                            </div>
                            <input type="file" id="device_image" name="device_image" accept="image/jpeg,image/png,image/webp" class="hidden">
                        </label>
                    </div>
                </div>

                <!-- SECTION 4: Service Delivery Mode -->
                <div class="bg-white rounded-3xl border border-slate-200/80 shadow-md shadow-slate-100 overflow-hidden transition-all">
                    <div class="p-6 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-2xl bg-emerald-600 text-white flex items-center justify-center shadow-sm shadow-emerald-500/20">
                                <i data-lucide="truck" class="w-5 h-5"></i>
                            </div>
                            <div>
                                <h2 class="font-extrabold text-slate-900 text-base">Service Delivery Mode</h2>
                                <p class="text-xs text-slate-500">Choose in-store walk-in or doorstep courier collection</p>
                            </div>
                        </div>
                        <span class="text-xs font-bold text-blue-600 bg-indigo-50 px-2.5 py-1 rounded-full">Step 4 of 4</span>
                    </div>

                    <div class="p-6 sm:p-8 space-y-6">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                            <label class="cursor-pointer block select-none">
                                <input type="radio" name="service_mode" value="shop_visit" class="sr-only" id="modeShopVisit"
                                    <?= (!isset($_POST['service_mode']) || $_POST['service_mode'] === 'shop_visit') ? 'checked' : '' ?>
                                    onchange="toggleServiceMode(false)">
                                <div id="cardShopVisit" class="p-4 rounded-2xl border-2 border-blue-600 bg-indigo-50/50 transition shadow-2xs">
                                    <div class="flex items-center justify-between mb-2">
                                        <div class="w-9 h-9 rounded-xl bg-indigo-600 text-white flex items-center justify-center">
                                            <i data-lucide="store" class="w-5 h-5"></i>
                                        </div>
                                        <span class="text-[10px] font-black uppercase text-blue-700 bg-indigo-100 px-2 py-0.5 rounded-full">RECOMMENDED</span>
                                    </div>
                                    <div class="font-black text-slate-900 text-sm">Walk-in Service Centre</div>
                                    <p class="text-xs text-slate-500 mt-1 leading-relaxed">Drop off directly at our certified repair facility. Free instant inspection while you wait.</p>
                                </div>
                            </label>

                            <label class="cursor-pointer block select-none">
                                <input type="radio" name="service_mode" value="pickup" class="sr-only" id="modePickup"
                                    <?= (isset($_POST['service_mode']) && $_POST['service_mode'] === 'pickup') ? 'checked' : '' ?>
                                    onchange="toggleServiceMode(true)">
                                <div id="cardPickup" class="p-4 rounded-2xl border-2 border-slate-200 hover:border-slate-300 bg-white transition shadow-2xs">
                                    <div class="flex items-center justify-between mb-2">
                                        <div class="w-9 h-9 rounded-xl bg-slate-100 text-slate-600 flex items-center justify-center">
                                            <i data-lucide="truck" class="w-5 h-5"></i>
                                        </div>
                                        <span class="text-[10px] font-bold text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded-full">DOORSTEP</span>
                                    </div>
                                    <div class="font-black text-slate-900 text-sm">Doorstep Pickup &amp; Return</div>
                                    <p class="text-xs text-slate-500 mt-1 leading-relaxed">Insured rider collects your phone from your home or office and returns it repaired.</p>
                                </div>
                            </label>
                        </div>

                        <div id="pickupSection" class="p-5 rounded-2xl bg-slate-50 border border-slate-200/80 space-y-4 <?= (isset($_POST['service_mode']) && $_POST['service_mode'] === 'pickup') ? '' : 'hidden' ?>">
                            <div class="flex items-center gap-2 text-xs font-bold text-slate-800">
                                <i data-lucide="map-pin" class="w-4 h-4 text-emerald-600"></i>
                                <span>Doorstep Pickup Address Details</span>
                            </div>
                            <textarea id="pickup_address" name="pickup_address" rows="3" placeholder="Complete pickup address..."
                                class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-white text-sm text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-4 focus:ring-blue-500/10 focus:border-blue-600 transition resize-none"><?= e($_POST['pickup_address'] ?? '') ?></textarea>
                        </div>
                    </div>
                </div>

                <div class="p-6 rounded-3xl bg-white border border-slate-200/80 shadow-lg shadow-slate-100 flex flex-col sm:flex-row items-center justify-between gap-4">
                    <div class="text-center sm:text-left">
                        <div class="text-sm font-black text-slate-900">Ready to restore your device?</div>
                        <div class="text-xs text-slate-400">Zero upfront charges &bull; Pay only after approving the estimate</div>
                    </div>
                    <button type="submit"
                        class="w-full sm:w-auto px-8 py-3.5 rounded-2xl bg-gradient-to-r from-indigo-600 to-indigo-600 hover:from-indigo-700 hover:to-indigo-700 text-white font-extrabold text-sm shadow-xl shadow-blue-500/25 transition-all duration-150 flex items-center justify-center gap-2 active:scale-[0.99]">
                        <i data-lucide="check-circle" class="w-4 h-4"></i>
                        <span>Confirm &amp; Book Repair</span>
                    </button>
                </div>
            </div>

            <!-- RIGHT STICKY SUMMARY COLUMN (4 Cols) -->
            <div class="lg:col-span-4 space-y-6 lg:sticky lg:top-24">
                <div class="bg-white rounded-3xl border border-slate-200/80 shadow-xl shadow-slate-200/50 p-6 space-y-5">
                    <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                        <h3 class="font-extrabold text-slate-900 text-sm">Booking Overview</h3>
                        <span class="inline-flex items-center gap-1 text-[11px] font-bold text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded-full">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                            Live Quote Ready
                        </span>
                    </div>

                    <div class="space-y-3 text-xs">
                        <div class="flex justify-between items-center py-1 border-b border-slate-50">
                            <span class="text-slate-400 font-medium">Selected Device</span>
                            <span id="summaryDevice" class="font-bold text-slate-800">Not specified yet</span>
                        </div>
                        <div class="flex justify-between items-center py-1 border-b border-slate-50">
                            <span class="text-slate-400 font-medium">Selected Issue</span>
                            <span id="summaryIssue" class="font-bold text-blue-600">Select an issue</span>
                        </div>
                        <div class="flex justify-between items-center py-1 border-b border-slate-50">
                            <span class="text-slate-400 font-medium">Diagnostic Fee</span>
                            <span class="font-extrabold text-emerald-600">₹0 (FREE)</span>
                        </div>
                        <div class="flex justify-between items-center py-1">
                            <span class="text-slate-400 font-medium">Warranty Coverage</span>
                            <span class="font-bold text-slate-800">90 Days Replacement</span>
                        </div>
                    </div>

                    <div class="p-3.5 rounded-2xl bg-slate-50 border border-slate-100 space-y-2 text-xs">
                        <div class="flex items-start gap-2 text-slate-600">
                            <i data-lucide="check" class="w-3.5 h-3.5 text-blue-600 shrink-0 mt-0.5"></i>
                            <span>100% Genuine Certified OEM Spares</span>
                        </div>
                        <div class="flex items-start gap-2 text-slate-600">
                            <i data-lucide="check" class="w-3.5 h-3.5 text-blue-600 shrink-0 mt-0.5"></i>
                            <span>Personal Data Protected &amp; Never Formatted</span>
                        </div>
                        <div class="flex items-start gap-2 text-slate-600">
                            <i data-lucide="check" class="w-3.5 h-3.5 text-blue-600 shrink-0 mt-0.5"></i>
                            <span>Real-Time Online Ticket Tracking</span>
                        </div>
                    </div>

                    <a href="<?= url('/repair-track.php') ?>" class="w-full py-2.5 rounded-xl border border-slate-200 hover:border-slate-300 text-slate-700 hover:text-blue-600 hover:bg-slate-50 transition text-xs font-bold flex items-center justify-center gap-2">
                        <i data-lucide="search" class="w-3.5 h-3.5 text-slate-400"></i>
                        <span>Track Existing Ticket</span>
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
function selectBrandQuick(brand) {
    const sel = document.getElementById('device_brand');
    sel.value = brand;
    updateSummary();
}

function updateSummary() {
    const b = document.getElementById('device_brand').value;
    const m = document.getElementById('device_model').value;
    const dev = (b || m) ? `${b} ${m}`.trim() : 'Not specified yet';
    document.getElementById('summaryDevice').textContent = dev;
}

function onIssueSelect(label) {
    document.getElementById('summaryIssue').textContent = label;
}

function toggleServiceMode(isPickup) {
    const pSec = document.getElementById('pickupSection');
    const cShop = document.getElementById('cardShopVisit');
    const cPick = document.getElementById('cardPickup');

    if (isPickup) {
        pSec.classList.remove('hidden');
        cPick.className = "p-4 rounded-2xl border-2 border-blue-600 bg-indigo-50/50 transition shadow-2xs";
        cShop.className = "p-4 rounded-2xl border-2 border-slate-200 hover:border-slate-300 bg-white transition shadow-2xs";
    } else {
        pSec.classList.add('hidden');
        cShop.className = "p-4 rounded-2xl border-2 border-blue-600 bg-indigo-50/50 transition shadow-2xs";
        cPick.className = "p-4 rounded-2xl border-2 border-slate-200 hover:border-slate-300 bg-white transition shadow-2xs";
    }
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

