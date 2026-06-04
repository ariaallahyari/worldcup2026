<?php
require_once '../includes/config.php';
if (!isAdmin()) redirect('../login.php');
$pageTitle = t('پلن‌های اشتراک','Subscription Plans');

$db = getDB();
$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $act = $_POST['action'] ?? '';
    if ($act === 'save_plan') {
        $id    = (int)($_POST['plan_id'] ?? 0);
        $nf    = $db->real_escape_string(trim($_POST['name_fa']));
        $ne    = $db->real_escape_string(trim($_POST['name_en']));
        $price = (int)$_POST['price_rial'];
        $days  = (int)$_POST['duration_days'];
        $df    = $db->real_escape_string(trim($_POST['description_fa']));
        $de    = $db->real_escape_string(trim($_POST['description_en']));
        $active= isset($_POST['is_active']) ? 1 : 0;
        if ($id) {
            $db->query("UPDATE subscription_plans SET name_fa='$nf',name_en='$ne',price_rial=$price,duration_days=$days,description_fa='$df',description_en='$de',is_active=$active WHERE id=$id");
            $msg = t('پلن بروز شد','Plan updated');
        } else {
            $db->query("INSERT INTO subscription_plans (name_fa,name_en,price_rial,duration_days,description_fa,description_en,is_active) VALUES ('$nf','$ne',$price,$days,'$df','$de',$active)");
            $msg = t('پلن اضافه شد','Plan added');
        }
    } elseif ($act === 'toggle') {
        $pid = (int)$_POST['plan_id'];
        $db->query("UPDATE subscription_plans SET is_active = 1-is_active WHERE id=$pid");
        $msg = t('وضعیت پلن تغییر کرد','Plan status toggled');
    } elseif ($act === 'delete') {
        $pid = (int)$_POST['plan_id'];
        $db->query("DELETE FROM subscription_plans WHERE id=$pid");
        $msg = t('پلن حذف شد','Plan deleted');
    }
}

$plans = $db->query("SELECT * FROM subscription_plans ORDER BY price_rial ASC")->fetch_all(MYSQLI_ASSOC);
$editPlan = null;
if (isset($_GET['edit'])) {
    foreach ($plans as $pl) if ($pl['id'] == (int)$_GET['edit']) { $editPlan = $pl; break; }
}

include '../includes/header.php';
?>
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1.5rem;flex-wrap:wrap;gap:1rem">
    <h1 style="font-size:1.4rem;font-weight:900;color:var(--gold)">💎 <?= t('پلن‌های اشتراک','Subscription Plans') ?></h1>
    <a href="index.php" class="btn btn-outline btn-sm">← <?= t('ادمین','Admin') ?></a>
</div>

<?php if ($msg): ?><div class="alert alert-success"><?= e($msg) ?></div><?php endif; ?>

<div class="alert alert-info">
    ℹ️ <?= t('برای فعال‌سازی پرداخت، درگاه بانکی را از تنظیمات اضافه کنید. فعلاً پیش‌بینی رایگان است.',
              'To enable payments, add a payment gateway from settings. Currently predictions are free.') ?>
</div>

<!-- فرم افزودن/ویرایش پلن -->
<div class="card" style="padding:1.5rem;margin-bottom:2rem">
    <h2 style="font-size:1rem;font-weight:700;color:var(--gold);margin-bottom:1rem">
        <?= $editPlan ? t('ویرایش پلن','Edit Plan') : t('افزودن پلن جدید','Add New Plan') ?>
    </h2>
    <form method="POST">
        <input type="hidden" name="action" value="save_plan">
        <input type="hidden" name="plan_id" value="<?= $editPlan['id'] ?? 0 ?>">
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:1rem">
            <div class="form-group">
                <label><?= t('نام فارسی','Name (FA)') ?></label>
                <input type="text" name="name_fa" value="<?= e($editPlan['name_fa'] ?? '') ?>" required>
            </div>
            <div class="form-group">
                <label><?= t('نام انگلیسی','Name (EN)') ?></label>
                <input type="text" name="name_en" value="<?= e($editPlan['name_en'] ?? '') ?>" required>
            </div>
            <div class="form-group">
                <label><?= t('قیمت (تومان)','Price (Rial)') ?></label>
                <input type="number" name="price_rial" value="<?= $editPlan['price_rial'] ?? 50000 ?>" min="0" required>
            </div>
            <div class="form-group">
                <label><?= t('مدت (روز)','Duration (days)') ?></label>
                <input type="number" name="duration_days" value="<?= $editPlan['duration_days'] ?? 30 ?>" min="1" required>
            </div>
            <div class="form-group">
                <label><?= t('توضیح فارسی','Description (FA)') ?></label>
                <input type="text" name="description_fa" value="<?= e($editPlan['description_fa'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label><?= t('توضیح انگلیسی','Description (EN)') ?></label>
                <input type="text" name="description_en" value="<?= e($editPlan['description_en'] ?? '') ?>">
            </div>
        </div>
        <div style="display:flex;align-items:center;gap:1rem;margin-top:.5rem">
            <label style="display:flex;align-items:center;gap:.5rem;cursor:pointer;margin:0">
                <input type="checkbox" name="is_active" value="1" <?= ($editPlan['is_active'] ?? 0)?'checked':'' ?>>
                <?= t('فعال','Active') ?>
            </label>
            <button type="submit" class="btn btn-gold"><?= t('ذخیره','Save') ?></button>
            <?php if ($editPlan): ?><a href="subscriptions.php" class="btn btn-ghost"><?= t('انصراف','Cancel') ?></a><?php endif; ?>
        </div>
    </form>
</div>

<!-- لیست پلن‌ها -->
<?php foreach ($plans as $pl): ?>
<div class="card" style="padding:1.25rem 1.5rem;margin-bottom:.75rem;opacity:<?= $pl['is_active']?1:.5 ?>">
    <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:1rem">
        <div>
            <div style="font-weight:700"><?= e($pl['name_fa']) ?> / <?= e($pl['name_en']) ?></div>
            <div style="font-size:.8rem;color:var(--text2);margin-top:.2rem">
                💰 <?= number_format($pl['price_rial']) ?> <?= t('تومان','Rial') ?> |
                📅 <?= $pl['duration_days'] ?> <?= t('روز','days') ?>
            </div>
            <div style="font-size:.78rem;color:var(--text3)"><?= e($pl['description_fa']) ?></div>
        </div>
        <div style="display:flex;gap:.4rem;flex-wrap:wrap">
            <span class="badge <?= $pl['is_active']?'badge-upcoming':'badge-finished' ?>">
                <?= $pl['is_active']?t('فعال','Active'):t('غیرفعال','Inactive') ?>
            </span>
            <a href="?edit=<?= $pl['id'] ?>" class="btn btn-ghost btn-sm">✏️</a>
            <form method="POST" style="display:inline">
                <input type="hidden" name="action" value="toggle">
                <input type="hidden" name="plan_id" value="<?= $pl['id'] ?>">
                <button type="submit" class="btn btn-ghost btn-sm"><?= $pl['is_active']?'🔴':t('✅ فعال','✅ Enable') ?></button>
            </form>
            <form method="POST" style="display:inline" onsubmit="return confirm('<?= t('حذف شود؟','Delete?') ?>')">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="plan_id" value="<?= $pl['id'] ?>">
                <button type="submit" class="btn btn-danger btn-sm">🗑️</button>
            </form>
        </div>
    </div>
</div>
<?php endforeach; ?>

<?php include '../includes/footer.php'; ?>
