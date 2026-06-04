<?php /* withdrawals.php */
require_once '../includes/config.php';
if (!isAdmin()) redirect('../login.php');
$pageTitle = t('درخواست‌های برداشت','Withdrawal Requests');

$db = getDB();
$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rid    = (int)$_POST['req_id'];
    $action = $_POST['action'];
    $note   = $db->real_escape_string(trim($_POST['note'] ?? ''));

    if ($action === 'approve') {
        $req = $db->query("SELECT * FROM withdrawal_requests WHERE id=$rid AND status='pending'")->fetch_assoc();
        if ($req) {
            $db->query("UPDATE withdrawal_requests SET status='approved',admin_note='$note' WHERE id=$rid");
            $db->query("UPDATE users SET coins=coins-{$req['coins']} WHERE id={$req['user_id']} AND coins>={$req['coins']}");
            $db->query("INSERT INTO coin_transactions (user_id,amount,type,description) VALUES ({$req['user_id']},-{$req['coins']},'withdrawn','برداشت تایید شده')");
            sendNotification((int)$req['user_id'],
                '✅ برداشت تایید شد','✅ Withdrawal Approved',
                "درخواست برداشت {$req['coins']} سکه شما تایید شد.",
                "Your withdrawal of {$req['coins']} coins was approved."
            );
            $msg = t('برداشت تایید شد','Withdrawal approved');
        }
    } elseif ($action === 'reject') {
        $db->query("UPDATE withdrawal_requests SET status='rejected',admin_note='$note' WHERE id=$rid");
        $req = $db->query("SELECT user_id,coins FROM withdrawal_requests WHERE id=$rid")->fetch_assoc();
        if ($req) sendNotification((int)$req['user_id'],
            '❌ برداشت رد شد','❌ Withdrawal Rejected',
            "درخواست برداشت شما رد شد. دلیل: $note",
            "Your withdrawal was rejected. Reason: $note"
        );
        $msg = t('درخواست رد شد','Request rejected');
    }
}

$reqs = $db->query("
    SELECT wr.*, u.username, u.avatar, u.coins AS user_coins
    FROM withdrawal_requests wr
    JOIN users u ON wr.user_id=u.id
    ORDER BY wr.status ASC, wr.created_at DESC
    LIMIT 100
")->fetch_all(MYSQLI_ASSOC);

include '../includes/header.php';
?>
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1.5rem;flex-wrap:wrap;gap:1rem">
    <h1 style="font-size:1.4rem;font-weight:900;color:var(--gold)">💸 <?= t('درخواست‌های برداشت','Withdrawal Requests') ?></h1>
    <a href="index.php" class="btn btn-outline btn-sm">← <?= t('ادمین','Admin') ?></a>
</div>

<?php if ($msg): ?><div class="alert alert-success"><?= e($msg) ?></div><?php endif; ?>

<?php if (empty($reqs)): ?>
<div style="text-align:center;padding:4rem;color:var(--text3)">
    <div style="font-size:3rem">💤</div>
    <div><?= t('درخواستی وجود ندارد','No requests yet') ?></div>
</div>
<?php else: ?>
<?php foreach ($reqs as $r):
    $isPending = $r['status'] === 'pending';
?>
<div class="card" style="padding:1.25rem 1.5rem;margin-bottom:.75rem;<?= $isPending?'border-color:rgba(245,200,66,.3)':'' ?>">
    <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:1rem;margin-bottom:.75rem">
        <div style="display:flex;align-items:center;gap:.7rem">
            <span style="font-size:1.5rem"><?= $r['avatar'] ?></span>
            <div>
                <div style="font-weight:700"><?= e($r['username']) ?></div>
                <div style="font-size:.78rem;color:var(--text2)"><?= formatDate($r['created_at']) ?></div>
            </div>
        </div>
        <div style="text-align:<?= isRTL()?'right':'left' ?>">
            <div style="font-size:1.2rem;font-weight:900;color:var(--coin)">🪙 <?= number_format($r['coins']) ?></div>
            <div style="font-size:.8rem;color:var(--text2)"><?= number_format($r['amount_rial']) ?> <?= t('تومان','Rial') ?></div>
        </div>
        <span class="badge <?= $r['status']==='pending'?'badge-gold':($r['status']==='approved'?'badge-upcoming':'badge-live') ?>">
            <?= $r['status']==='pending' ? t('در انتظار','Pending') : ($r['status']==='approved' ? t('تایید شده','Approved') : t('رد شده','Rejected')) ?>
        </span>
    </div>
    <div style="font-size:.82rem;color:var(--text2);background:var(--bg2);padding:.6rem .9rem;border-radius:var(--radius-sm);margin-bottom:.75rem">
        📋 <?= e($r['bank_info']) ?>
    </div>
    <?php if ($r['admin_note']): ?>
    <div style="font-size:.8rem;color:var(--text3);margin-bottom:.5rem">📝 <?= e($r['admin_note']) ?></div>
    <?php endif; ?>
    <?php if ($isPending): ?>
    <form method="POST" style="display:flex;gap:.5rem;flex-wrap:wrap">
        <input type="hidden" name="req_id" value="<?= $r['id'] ?>">
        <input type="text" name="note" placeholder="<?= t('یادداشت (اختیاری)','Note (optional)') ?>" style="flex:1;min-width:150px">
        <button type="submit" name="action" value="approve" class="btn btn-gold btn-sm">✅ <?= t('تایید','Approve') ?></button>
        <button type="submit" name="action" value="reject" class="btn btn-danger btn-sm">❌ <?= t('رد','Reject') ?></button>
    </form>
    <?php endif; ?>
</div>
<?php endforeach; ?>
<?php endif; ?>

<?php include '../includes/footer.php'; ?>
