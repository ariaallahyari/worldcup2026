<?php
require_once 'includes/config.php';
if (!isLoggedIn()) redirect('login.php');
$pageTitle = t('اعلان‌ها','Notifications');

$db = getDB();
$uid = (int)$_SESSION['user_id'];

// mark all as read
$db->query("UPDATE notifications SET is_read=1 WHERE user_id=$uid");

$notifs = $db->query("SELECT * FROM notifications WHERE user_id=$uid ORDER BY created_at DESC LIMIT 50")->fetch_all(MYSQLI_ASSOC);

include 'includes/header.php';
?>
<div style="max-width:700px;margin:0 auto">
<div style="text-align:center;padding:2rem 0 1.5rem">
    <h1 style="font-size:1.8rem;font-weight:900;color:var(--gold)">🔔 <?= t('اعلان‌ها','Notifications') ?></h1>
</div>

<?php if (empty($notifs)): ?>
<div style="text-align:center;padding:4rem;color:var(--text3)">
    <div style="font-size:3rem">🔕</div>
    <div><?= t('اعلانی وجود ندارد','No notifications') ?></div>
</div>
<?php else: ?>
<?php foreach ($notifs as $n): ?>
<div class="card" style="padding:1rem 1.25rem;margin-bottom:.6rem">
    <div style="font-weight:700;margin-bottom:.3rem"><?= e(isRTL() ? $n['title_fa'] : $n['title_en']) ?></div>
    <div style="font-size:.875rem;color:var(--text2)"><?= e(isRTL() ? $n['message_fa'] : $n['message_en']) ?></div>
    <div style="font-size:.75rem;color:var(--text3);margin-top:.4rem"><?= formatDate($n['created_at']) ?></div>
</div>
<?php endforeach; ?>
<?php endif; ?>
</div>
<?php include 'includes/footer.php'; ?>
