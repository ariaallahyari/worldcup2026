<?php
require_once '../includes/config.php';
if (!isAdmin()) redirect('../login.php');
$pageTitle = t('مدیریت کاربران','Manage Users');

$db = getDB();
$msg = '';

// اعمال اقدام
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $uid  = (int)$_POST['user_id'];
    $act  = $_POST['action'] ?? '';
    if ($uid === (int)$_SESSION['user_id']) {
        $msg = t('نمی‌توانید روی حساب خودتان عمل کنید','Cannot perform action on your own account');
    } else {
        switch ($act) {
            case 'block':
                $db->query("UPDATE users SET is_active=0 WHERE id=$uid"); $msg=t('کاربر مسدود شد','User blocked'); break;
            case 'unblock':
                $db->query("UPDATE users SET is_active=1 WHERE id=$uid"); $msg=t('کاربر فعال شد','User unblocked'); break;
            case 'add_coins':
                $coins = (int)$_POST['coins'];
                addCoins($uid, $coins, 'bonus', t('هدیه ادمین','Admin bonus'));
                $msg=t("$coins سکه اضافه شد","$coins coins added"); break;
            case 'set_premium':
                $days = (int)$_POST['days'];
                $exp  = date('Y-m-d H:i:s', strtotime("+$days days"));
                $db->query("UPDATE users SET subscription_status='premium', subscription_expires='$exp' WHERE id=$uid");
                $msg=t("اشتراک پریمیوم برای $days روز فعال شد","Premium activated for $days days"); break;
            case 'revoke_premium':
                $db->query("UPDATE users SET subscription_status='free', subscription_expires=NULL WHERE id=$uid");
                $msg=t('اشتراک پریمیوم لغو شد','Premium revoked'); break;
        }
    }
}

$search = trim($_GET['q'] ?? '');
$where  = $search ? "WHERE username LIKE '%".addslashes($search)."%' OR email LIKE '%".addslashes($search)."%'" : '';
$users  = $db->query("
    SELECT u.*, COUNT(p.id) as preds
    FROM users u
    LEFT JOIN predictions p ON u.id=p.user_id
    $where
    GROUP BY u.id
    ORDER BY u.created_at DESC
    LIMIT 100
")->fetch_all(MYSQLI_ASSOC);

include '../includes/header.php';
?>
<style>
.user-row{padding:1rem 1.25rem;margin-bottom:.5rem;display:flex;align-items:center;gap:1rem;flex-wrap:wrap;}
.u-info{flex:1;min-width:180px;}
.u-actions{display:flex;gap:.4rem;flex-wrap:wrap;}
.action-form{display:inline-flex;align-items:center;gap:.3rem;}
</style>

<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1.5rem;flex-wrap:wrap;gap:1rem">
    <h1 style="font-size:1.4rem;font-weight:900;color:var(--gold)">👥 <?= t('مدیریت کاربران','Manage Users') ?></h1>
    <a href="index.php" class="btn btn-outline btn-sm">← <?= t('ادمین','Admin') ?></a>
</div>

<?php if ($msg): ?><div class="alert alert-success"><?= e($msg) ?></div><?php endif; ?>

<!-- جستجو -->
<form method="GET" style="margin-bottom:1.5rem;display:flex;gap:.5rem">
    <input type="text" name="q" value="<?= e($search) ?>" placeholder="<?= t('جستجوی نام کاربری یا ایمیل...','Search username or email...') ?>" style="max-width:300px">
    <button type="submit" class="btn btn-ghost">🔍</button>
</form>

<?php foreach ($users as $u): ?>
<div class="card user-row <?= !$u['is_active']?'':'' ?>" style="<?= !$u['is_active']?'opacity:.6':'' ?>">
    <div style="font-size:1.5rem"><?= $u['avatar'] ?></div>
    <div class="u-info">
        <div style="font-weight:700;display:flex;align-items:center;gap:.4rem">
            <?= e($u['username']) ?>
            <?php if ($u['role']==='admin'): ?><span class="badge badge-gold">Admin</span><?php endif; ?>
            <?php if ($u['subscription_status']==='premium'): ?><span class="badge" style="background:rgba(139,92,246,.2);color:#C4B5FD">💎 Premium</span><?php endif; ?>
            <?php if (!$u['is_active']): ?><span class="badge badge-live">🚫</span><?php endif; ?>
        </div>
        <div style="font-size:.78rem;color:var(--text2)"><?= e($u['email']) ?></div>
        <div style="font-size:.75rem;color:var(--text3)">
            🪙 <?= number_format($u['coins']) ?> |
            🎯 <?= $u['preds'] ?> <?= t('پیش‌بینی','preds') ?> |
            <?= formatDate($u['created_at']) ?>
        </div>
    </div>
    <?php if ($u['role'] !== 'admin'): ?>
    <div class="u-actions">
        <!-- بلاک/آنبلاک -->
        <form method="POST" class="action-form">
            <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
            <input type="hidden" name="action" value="<?= $u['is_active']?'block':'unblock' ?>">
            <button type="submit" class="btn btn-sm <?= $u['is_active']?'btn-danger':'btn-ghost' ?>">
                <?= $u['is_active'] ? t('🚫 مسدود','🚫 Block') : t('✅ فعال','✅ Unblock') ?>
            </button>
        </form>

        <!-- افزودن سکه -->
        <form method="POST" class="action-form">
            <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
            <input type="hidden" name="action" value="add_coins">
            <input type="number" name="coins" min="1" placeholder="🪙" style="width:60px;padding:.3rem .5rem">
            <button type="submit" class="btn btn-ghost btn-sm"><?= t('افزودن','Add') ?></button>
        </form>

        <!-- اشتراک -->
        <?php if ($u['subscription_status'] !== 'premium'): ?>
        <form method="POST" class="action-form">
            <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
            <input type="hidden" name="action" value="set_premium">
            <input type="number" name="days" min="1" value="30" style="width:55px;padding:.3rem .5rem">
            <button type="submit" class="btn btn-ghost btn-sm">💎 <?= t('پریمیوم','Premium') ?></button>
        </form>
        <?php else: ?>
        <form method="POST" class="action-form">
            <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
            <input type="hidden" name="action" value="revoke_premium">
            <button type="submit" class="btn btn-danger btn-sm"><?= t('لغو پریمیوم','Revoke') ?></button>
        </form>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>
<?php endforeach; ?>

<?php include '../includes/footer.php'; ?>
