<?php
require_once 'includes/config.php';
$pageTitle = t('رتبه‌بندی','Leaderboard');
include 'includes/header.php';

$db = getDB();
$currentUid = $_SESSION['user_id'] ?? 0;

$users = $db->query("
    SELECT u.id, u.username, u.avatar, u.coins,
           COUNT(p.id) as total_preds,
           SUM(CASE WHEN p.is_correct=1 THEN 1 ELSE 0 END) as correct_preds,
           SUM(CASE WHEN p.coins_earned>0 THEN p.coins_earned ELSE 0 END) as earned_coins
    FROM users u
    LEFT JOIN predictions p ON u.id=p.user_id
    WHERE u.is_active=1 AND u.role='user'
    GROUP BY u.id
    ORDER BY u.coins DESC, correct_preds DESC, total_preds DESC
    LIMIT 100
")->fetch_all(MYSQLI_ASSOC);
?>
<style>
.page-title{text-align:center;padding:2.5rem 0 2rem;}
.page-title h1{font-size:2rem;font-weight:900;color:var(--gold);}

.podium{display:flex;justify-content:center;align-items:flex-end;gap:1rem;margin:2rem 0 3rem;flex-wrap:wrap;}
.podium-item{text-align:center;flex:0 0 140px;}
.p-avatar{font-size:2.2rem;margin-bottom:.4rem;}
.p-name{font-weight:700;font-size:.9rem;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:130px;margin:0 auto;}
.p-coins{font-size:.85rem;color:var(--coin);font-weight:700;margin:.2rem 0;}
.p-bar{border-radius:12px 12px 0 0;display:flex;align-items:center;justify-content:center;font-size:1.5rem;font-weight:900;margin-top:.6rem;}
.p1 .p-bar{background:var(--gold);height:90px;color:#000;}
.p2 .p-bar{background:#9CA3AF;height:65px;color:#fff;}
.p3 .p-bar{background:#CD7F32;height:45px;color:#fff;}

.lb-table{width:100%;border-collapse:separate;border-spacing:0 4px;}
.lb-row td{background:var(--card);padding:.9rem 1rem;transition:all .2s;}
.lb-row:hover td{background:var(--card-hover);}
.lb-row td:first-child{border-radius:0 var(--radius-sm) var(--radius-sm) 0;}
.lb-row td:last-child{border-radius:var(--radius-sm) 0 0 var(--radius-sm);}
.lb-row.is-me td{background:var(--gold-glow);border:1px solid rgba(245,200,66,.2);}
.rank-cell{text-align:center;font-weight:900;font-size:1rem;color:var(--text3);min-width:45px;}
.rank-1 .rank-cell{color:var(--gold);}
.rank-2 .rank-cell{color:#C0C0C0;}
.rank-3 .rank-cell{color:#CD7F32;}
.user-cell{display:flex;align-items:center;gap:.7rem;}
.u-av{font-size:1.5rem;}
.u-name{font-weight:700;}
.u-sub{font-size:.75rem;color:var(--text2);}
.coins-cell{text-align:center;font-weight:900;color:var(--coin);}
.stat-cell{text-align:center;color:var(--text2);font-size:.82rem;}
</style>

<div class="page-title">
    <h1>🏆 <?= t('جدول رتبه‌بندی','Leaderboard') ?></h1>
</div>

<?php if (count($users) >= 3): ?>
<div class="podium">
    <div class="podium-item p2">
        <div class="p-avatar"><?= $users[1]['avatar'] ?></div>
        <div class="p-name"><?= e($users[1]['username']) ?></div>
        <div class="p-coins">🪙 <?= number_format($users[1]['coins']) ?></div>
        <div class="p-bar">🥈</div>
    </div>
    <div class="podium-item p1">
        <div class="p-avatar"><?= $users[0]['avatar'] ?></div>
        <div class="p-name"><?= e($users[0]['username']) ?></div>
        <div class="p-coins">🪙 <?= number_format($users[0]['coins']) ?></div>
        <div class="p-bar">🥇</div>
    </div>
    <div class="podium-item p3">
        <div class="p-avatar"><?= $users[2]['avatar'] ?></div>
        <div class="p-name"><?= e($users[2]['username']) ?></div>
        <div class="p-coins">🪙 <?= number_format($users[2]['coins']) ?></div>
        <div class="p-bar">🥉</div>
    </div>
</div>
<?php endif; ?>

<div class="card" style="padding:.5rem">
<table class="lb-table">
    <thead>
        <tr>
            <td style="padding:.6rem 1rem;font-size:.75rem;color:var(--text3);text-align:center"><?= t('رتبه','Rank') ?></td>
            <td style="padding:.6rem 1rem;font-size:.75rem;color:var(--text3)"><?= t('کاربر','User') ?></td>
            <td style="padding:.6rem 1rem;font-size:.75rem;color:var(--text3);text-align:center">🪙 <?= t('سکه','Coins') ?></td>
            <td style="padding:.6rem 1rem;font-size:.75rem;color:var(--text3);text-align:center">🎯 <?= t('درست','Correct') ?></td>
            <td style="padding:.6rem 1rem;font-size:.75rem;color:var(--text3);text-align:center">📊 <?= t('کل','Total') ?></td>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($users as $i => $u): ?>
    <tr class="lb-row rank-<?= $i+1 ?> <?= $u['id']==$currentUid?'is-me':'' ?>">
        <td class="rank-cell">
            <?= $i===0?'🥇':($i===1?'🥈':($i===2?'🥉':'#'.($i+1))) ?>
        </td>
        <td>
            <div class="user-cell">
                <span class="u-av"><?= $u['avatar'] ?></span>
                <div>
                    <div class="u-name">
                        <?= e($u['username']) ?>
                        <?= $u['id']==$currentUid ? ' <span class="badge badge-gold" style="font-size:.7rem">'.t('شما','You').'</span>' : '' ?>
                    </div>
                    <div class="u-sub"><?= $u['total_preds'] ?> <?= t('پیش‌بینی','predictions') ?></div>
                </div>
            </div>
        </td>
        <td class="coins-cell"><?= number_format($u['coins']) ?></td>
        <td class="stat-cell"><?= $u['correct_preds'] ?></td>
        <td class="stat-cell"><?= $u['total_preds'] ?></td>
    </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div>

<div style="display:flex;gap:1rem;justify-content:center;margin-top:1.5rem;font-size:.82rem;color:var(--text3);flex-wrap:wrap">
    <span>🎯 <?= t('نتیجه دقیق','Exact result') ?> = <strong style="color:var(--coin)"><?= getSetting('coins_per_correct','10') ?> <?= t('سکه','coins') ?></strong></span>
    <span>✅ <?= t('برنده درست','Correct winner') ?> = <strong style="color:var(--coin)"><?= intdiv((int)getSetting('coins_per_correct','10'),2) ?> <?= t('سکه','coins') ?></strong></span>
</div>

<?php include 'includes/footer.php'; ?>
