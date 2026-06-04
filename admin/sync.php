<?php
require_once '../includes/config.php';
if (!isAdmin()) redirect('../login.php');
require_once '../api/football.php';

$pageTitle = t('همگام‌سازی API','API Sync');
$result = null;

if (isset($_POST['sync_all'])) {
    $api = new FootballAPI();
    $result = $api->syncFixtures();
    $result['action'] = 'all';
}
if (isset($_POST['sync_live'])) {
    $api = new FootballAPI();
    $result = $api->syncLive();
    $result['action'] = 'live';
}
if (isset($_POST['calc_coins']) && isset($_POST['match_id'])) {
    $api = new FootballAPI();
    $api->calculateCoins((int)$_POST['match_id']);
    $result = ['success'=>true,'action'=>'coins'];
}

include '../includes/header.php';
?>
<div style="max-width:700px;margin:0 auto">
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:2rem;flex-wrap:wrap;gap:1rem">
    <h1 style="font-size:1.4rem;font-weight:900;color:var(--gold)">🔄 <?= t('همگام‌سازی از API','Sync from API') ?></h1>
    <a href="index.php" class="btn btn-outline btn-sm">← <?= t('پنل ادمین','Admin Panel') ?></a>
</div>

<?php if ($result): ?>
<div class="alert alert-<?= $result['success']?'success':'error' ?>">
    <?php if ($result['success']): ?>
        <?php if ($result['action']==='all'): ?>
        ✅ <?= t("همگام‌سازی کامل شد. {$result['synced']} بازی دریافت شد.","Sync complete. {$result['synced']} fixtures synced.") ?>
        <?php elseif ($result['action']==='live'): ?>
        ✅ <?= t("بازی‌های زنده بروزرسانی شد.","Live matches updated.") ?>
        <?php else: ?>
        ✅ <?= t("امتیازات محاسبه شد.","Coins calculated.") ?>
        <?php endif; ?>
    <?php else: ?>
        ⚠️ <?= t($result['message']??'خطا در ارتباط با API',$result['message']??'API connection error') ?>
    <?php endif; ?>
</div>
<?php endif; ?>

<div class="card" style="padding:1.5rem;margin-bottom:1rem">
    <h3 style="margin-bottom:1rem;color:var(--text)"><?= t('تنظیمات API','API Settings') ?></h3>
    <?php $apiKey = getSetting('api_football_key'); ?>
    <div class="alert alert-<?= $apiKey?'success':'warning' ?>">
        <?= $apiKey ? '✅ '.t('API Key تنظیم شده','API Key is configured') : '⚠️ '.t('API Key تنظیم نشده - به تنظیمات بروید','API Key not set - go to Settings') ?>
    </div>
    <div style="font-size:.875rem;color:var(--text2)">
        League ID: <strong><?= getSetting('api_league_id','4429') ?></strong> |
        Season: <strong><?= getSetting('api_season','2026') ?></strong>
        <br><small style="color:var(--text3)">Endpoint: /eventsseason.php?id=4429&s=2026 ✅</small>
    </div>
</div>

<div style="display:grid;gap:1rem">
    <div class="card" style="padding:1.5rem">
        <h3 style="margin-bottom:.5rem"><?= t('همگام‌سازی کامل','Full Sync') ?></h3>
        <p style="color:var(--text2);font-size:.875rem;margin-bottom:1rem"><?= t('همه بازی‌های لیگ را از API دریافت می‌کند (مصرف ۱ درخواست API)','Fetches all league fixtures from API (1 API request)') ?></p>
        <form method="POST">
            <button type="submit" name="sync_all" class="btn btn-gold">🔄 <?= t('همگام‌سازی کامل','Full Sync') ?></button>
        </form>
    </div>

    <div class="card" style="padding:1.5rem">
        <h3 style="margin-bottom:.5rem"><?= t('بروزرسانی بازی‌های زنده','Update Live Matches') ?></h3>
        <p style="color:var(--text2);font-size:.875rem;margin-bottom:1rem"><?= t('فقط بازی‌های در حال اجرا را بروز می‌کند','Updates only currently running matches') ?></p>
        <form method="POST">
            <button type="submit" name="sync_live" class="btn btn-ghost">⚡ <?= t('بروزرسانی زنده','Update Live') ?></button>
        </form>
    </div>
</div>
</div>
<?php include '../includes/footer.php'; ?>
