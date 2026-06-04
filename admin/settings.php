<?php
require_once '../includes/config.php';
if (!isAdmin()) redirect('../login.php');
$pageTitle = t('تنظیمات سایت','Site Settings');

$db = getDB();
$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fields = [
        'api_football_key','api_season',
        'coins_per_correct','coins_to_rial_rate',
        'prediction_open','subscription_required','maintenance_mode',
        'site_name_fa','site_name_en'
    ];
    foreach ($fields as $f) {
        if (isset($_POST[$f])) {
            $k = $db->real_escape_string($f);
            $v = $db->real_escape_string(trim($_POST[$f]));
            $db->query("INSERT INTO settings (`key`,`value`) VALUES ('$k','$v') ON DUPLICATE KEY UPDATE `value`='$v'");
        }
    }
    $msg = t('تنظیمات ذخیره شد ✅','Settings saved ✅');
}

// خواندن تنظیمات
$settings = [];
$res = $db->query("SELECT * FROM settings");
while ($r = $res->fetch_assoc()) $settings[$r['key']] = $r['value'];
$s = fn($k,$d='') => $settings[$k] ?? $d;

include '../includes/header.php';
?>
<style>
.settings-section{margin-bottom:2rem;}
.settings-section h2{font-size:1rem;font-weight:700;color:var(--gold);margin-bottom:1rem;padding-bottom:.5rem;border-bottom:1px solid var(--border);}
.settings-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:1rem;}
.toggle-row{display:flex;align-items:center;justify-content:space-between;padding:.8rem 0;border-bottom:1px solid var(--border);}
.toggle-row:last-child{border-bottom:none;}
.toggle-label{font-size:.9rem;font-weight:500;}
.toggle-desc{font-size:.78rem;color:var(--text2);margin-top:.2rem;}
/* toggle switch */
.switch{position:relative;display:inline-block;width:44px;height:24px;}
.switch input{opacity:0;width:0;height:0;}
.slider{position:absolute;cursor:pointer;inset:0;background:var(--bg3);border-radius:24px;transition:.3s;}
.slider:before{position:absolute;content:"";height:18px;width:18px;right:3px;bottom:3px;background:white;border-radius:50%;transition:.3s;}
input:checked+.slider{background:var(--green-light);}
input:checked+.slider:before{transform:translateX(-20px);}
</style>

<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1.5rem;flex-wrap:wrap;gap:1rem">
    <h1 style="font-size:1.4rem;font-weight:900;color:var(--gold)">⚙️ <?= t('تنظیمات','Settings') ?></h1>
    <a href="index.php" class="btn btn-outline btn-sm">← <?= t('ادمین','Admin') ?></a>
</div>

<?php if ($msg): ?><div class="alert alert-success"><?= e($msg) ?></div><?php endif; ?>

<form method="POST">

<!-- API تنظیمات -->
<div class="card" style="padding:1.5rem;margin-bottom:1.5rem">
    <div class="settings-section">
        <h2>🔌 <?= t('تنظیمات API فوتبال (football-data.org)','Football API Settings (football-data.org)') ?></h2>
        <div class="settings-grid">
            <div class="form-group">
                <label>API Key (football-data.org)</label>
                <input type="text" name="api_football_key" value="<?= e($s('api_football_key', API_FOOTBALL_KEY)) ?>" placeholder="your-api-key-here">
            </div>
            <div class="form-group">
                <label><?= t('فصل (Season)','Season') ?> <small style="color:var(--text3)">e.g. 2026</small></label>
                <input type="number" name="api_season" value="<?= e($s('api_season','2026')) ?>">
            </div>
        </div>
        <div class="alert alert-info" style="margin-top:.5rem">
            ℹ️ <?= t('از football-data.org استفاده می‌شود. کد جام جهانی: <strong>WC</strong>','Uses football-data.org. World Cup code: <strong>WC</strong>') ?>
        </div>
    </div>
</div>

<!-- تنظیمات سکه -->
<div class="card" style="padding:1.5rem;margin-bottom:1.5rem">
    <div class="settings-section">
        <h2>🪙 <?= t('تنظیمات سکه','Coin Settings') ?></h2>
        <div class="settings-grid">
            <div class="form-group">
                <label><?= t('سکه به ازای پیش‌بینی درست','Coins per correct prediction') ?></label>
                <input type="number" name="coins_per_correct" value="<?= e($s('coins_per_correct','10')) ?>" min="1">
            </div>
            <div class="form-group">
                <label><?= t('ارزش هر سکه (تومان)','Coin value (Rial)') ?></label>
                <input type="number" name="coins_to_rial_rate" value="<?= e($s('coins_to_rial_rate','1000')) ?>" min="1">
            </div>
        </div>
    </div>
</div>

<!-- نام سایت -->
<div class="card" style="padding:1.5rem;margin-bottom:1.5rem">
    <div class="settings-section">
        <h2>🌐 <?= t('اطلاعات سایت','Site Info') ?></h2>
        <div class="settings-grid">
            <div class="form-group">
                <label><?= t('نام سایت (فارسی)','Site name (Farsi)') ?></label>
                <input type="text" name="site_name_fa" value="<?= e($s('site_name_fa','جام جهانی ۲۰۲۶')) ?>">
            </div>
            <div class="form-group">
                <label><?= t('نام سایت (انگلیسی)','Site name (English)') ?></label>
                <input type="text" name="site_name_en" value="<?= e($s('site_name_en','World Cup 2026')) ?>">
            </div>
        </div>
    </div>
</div>

<!-- سوئیچ‌ها -->
<div class="card" style="padding:1.5rem;margin-bottom:1.5rem">
    <div class="settings-section">
        <h2>🎛️ <?= t('کنترل‌های سایت','Site Controls') ?></h2>

        <div class="toggle-row">
            <div>
                <div class="toggle-label">🎯 <?= t('پیش‌بینی فعال','Predictions Open') ?></div>
                <div class="toggle-desc"><?= t('اگر خاموش شود هیچ کاربری نمی‌تواند پیش‌بینی کند','If off, no user can predict') ?></div>
            </div>
            <label class="switch">
                <input type="checkbox" name="prediction_open" value="1" <?= $s('prediction_open','1')==='1'?'checked':'' ?>>
                <span class="slider"></span>
            </label>
        </div>

        <div class="toggle-row">
            <div>
                <div class="toggle-label">💎 <?= t('اشتراک اجباری','Subscription Required') ?></div>
                <div class="toggle-desc"><?= t('کاربران باید اشتراک داشته باشند تا پیش‌بینی کنند','Users must subscribe to predict') ?></div>
            </div>
            <label class="switch">
                <input type="checkbox" name="subscription_required" value="1" <?= $s('subscription_required','0')==='1'?'checked':'' ?>>
                <span class="slider"></span>
            </label>
        </div>

        <div class="toggle-row">
            <div>
                <div class="toggle-label">🔧 <?= t('حالت تعمیر','Maintenance Mode') ?></div>
                <div class="toggle-desc"><?= t('سایت برای کاربران عادی غیرفعال می‌شود','Site becomes unavailable for regular users') ?></div>
            </div>
            <label class="switch">
                <input type="checkbox" name="maintenance_mode" value="1" <?= $s('maintenance_mode','0')==='1'?'checked':'' ?>>
                <span class="slider"></span>
            </label>
        </div>
    </div>
</div>

<button type="submit" class="btn btn-gold btn-lg">💾 <?= t('ذخیره تنظیمات','Save Settings') ?></button>
</form>

<?php include '../includes/footer.php'; ?>
