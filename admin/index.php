<?php
require_once '../includes/config.php';
if (!isAdmin()) redirect('../login.php');
$pageTitle = t('پنل ادمین','Admin Panel');

$db = getDB();

// آمار کلی
$stats = [
    'users'   => $db->query("SELECT COUNT(*) as c FROM users WHERE role='user'")->fetch_assoc()['c'],
    'matches' => $db->query("SELECT COUNT(*) as c FROM matches")->fetch_assoc()['c'],
    'preds'   => $db->query("SELECT COUNT(*) as c FROM predictions")->fetch_assoc()['c'],
    'coins'   => $db->query("SELECT SUM(coins) as c FROM users")->fetch_assoc()['c'] ?? 0,
    'withdraw'=> $db->query("SELECT COUNT(*) as c FROM withdrawal_requests WHERE status='pending'")->fetch_assoc()['c'],
    'live'    => $db->query("SELECT COUNT(*) as c FROM matches WHERE status IN ('1H','HT','2H','ET','PEN')")->fetch_assoc()['c'],
];

include '../includes/header.php';
?>
<style>
.admin-stats{display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:1rem;margin-bottom:2rem;}
.admin-stat{padding:1.2rem;text-align:center;}
.admin-stat .num{font-size:2rem;font-weight:900;color:var(--gold);}
.admin-stat .lbl{font-size:.8rem;color:var(--text2);}
.admin-menu{display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:1rem;}
.admin-link{padding:1.5rem;text-decoration:none;color:var(--text);display:flex;flex-direction:column;gap:.5rem;border-radius:var(--radius);}
.admin-link:hover{color:var(--gold);}
.admin-link .icon{font-size:2rem;}
.admin-link .title{font-weight:700;font-size:1rem;}
.admin-link .desc{font-size:.8rem;color:var(--text2);}
</style>

<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:2rem;flex-wrap:wrap;gap:1rem">
    <h1 style="font-size:1.5rem;font-weight:900;color:var(--gold)">⚙️ <?= t('پنل مدیریت','Admin Panel') ?></h1>
    <a href="../index.php" class="btn btn-outline btn-sm">← <?= t('بازگشت به سایت','Back to Site') ?></a>
</div>

<div class="admin-stats">
    <div class="card admin-stat"><div class="num"><?= $stats['users'] ?></div><div class="lbl">👤 <?= t('کاربران','Users') ?></div></div>
    <div class="card admin-stat"><div class="num"><?= $stats['matches'] ?></div><div class="lbl">⚽ <?= t('بازی‌ها','Matches') ?></div></div>
    <div class="card admin-stat"><div class="num"><?= $stats['preds'] ?></div><div class="lbl">🎯 <?= t('پیش‌بینی‌ها','Predictions') ?></div></div>
    <div class="card admin-stat"><div class="num" style="color:var(--coin)"><?= number_format($stats['coins']) ?></div><div class="lbl">🪙 <?= t('کل سکه','Total Coins') ?></div></div>
    <div class="card admin-stat" style="<?= $stats['withdraw']>0?'border-color:rgba(239,68,68,.3)':'' ?>">
        <div class="num" style="color:<?= $stats['withdraw']>0?'var(--red)':'var(--gold)' ?>"><?= $stats['withdraw'] ?></div>
        <div class="lbl">💸 <?= t('درخواست برداشت','Withdrawals') ?></div>
    </div>
    <div class="card admin-stat" style="<?= $stats['live']>0?'border-color:rgba(239,68,68,.3)':'' ?>">
        <div class="num" style="color:<?= $stats['live']>0?'var(--red)':'var(--text3)' ?>"><?= $stats['live'] ?></div>
        <div class="lbl">🔴 <?= t('بازی زنده','Live Matches') ?></div>
    </div>
</div>

<div class="admin-menu">
    <a href="matches.php" class="card card-hover admin-link">
        <div class="icon">⚽</div>
        <div class="title"><?= t('مدیریت بازی‌ها','Manage Matches') ?></div>
        <div class="desc"><?= t('ثبت نتیجه، تغییر وضعیت','Set results, update status') ?></div>
    </a>
    <a href="sync.php" class="card card-hover admin-link">
        <div class="icon">🔄</div>
        <div class="title"><?= t('همگام‌سازی API','Sync from API') ?></div>
        <div class="desc"><?= t('دریافت بازی‌ها از API فوتبال','Fetch matches from Football API') ?></div>
    </a>
    <a href="users.php" class="card card-hover admin-link">
        <div class="icon">👥</div>
        <div class="title"><?= t('مدیریت کاربران','Manage Users') ?></div>
        <div class="desc"><?= t('مشاهده، مسدودسازی، ویرایش','View, block, edit users') ?></div>
    </a>
    <a href="withdrawals.php" class="card card-hover admin-link">
        <div class="icon">💸</div>
        <div class="title"><?= t('درخواست‌های برداشت','Withdrawal Requests') ?></div>
        <div class="desc"><?= t('تایید یا رد برداشت سکه','Approve or reject coin withdrawals') ?></div>
    </a>
    <a href="settings.php" class="card card-hover admin-link">
        <div class="icon">⚙️</div>
        <div class="title"><?= t('تنظیمات سایت','Site Settings') ?></div>
        <div class="desc"><?= t('API، اشتراک، سکه و...','API, subscription, coins...') ?></div>
    </a>
    <a href="subscriptions.php" class="card card-hover admin-link">
        <div class="icon">💎</div>
        <div class="title"><?= t('پلن‌های اشتراک','Subscription Plans') ?></div>
        <div class="desc"><?= t('تعریف و مدیریت پلن‌ها','Define and manage plans') ?></div>
    </a>
</div>

<?php include '../includes/footer.php'; ?>
