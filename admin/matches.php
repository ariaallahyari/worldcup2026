<?php
require_once '../includes/config.php';
require_once '../api/football.php';
if (!isAdmin()) redirect('../login.php');
$pageTitle = t('مدیریت بازی‌ها','Manage Matches');

$db = getDB();
$msg = '';

// ثبت نتیجه دستی
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['set_result'])) {
    $mid = (int)$_POST['match_id'];
    $hs  = (int)$_POST['home_score'];
    $as  = (int)$_POST['away_score'];
    $st  = in_array($_POST['status'],['NS','1H','HT','2H','ET','PEN','FT','AET','PEN_FT']) ? $_POST['status'] : 'FT';
    $min = (int)($_POST['minute']??0);
    $db->query("UPDATE matches SET home_score=$hs, away_score=$as, status='$st', minute=$min WHERE id=$mid");
    if (in_array($st,['FT','AET','PEN_FT'])) {
        $api = new FootballAPI();
        $api->calculateCoins($mid);
        $msg = t('نتیجه ثبت و سکه‌ها محاسبه شد ✅','Result saved and coins calculated ✅');
    } else {
        $msg = t('وضعیت بازی بروز شد ✅','Match status updated ✅');
    }
}

// همگام‌سازی یک بازی خاص
if (isset($_GET['sync']) && (int)$_GET['sync'] > 0) {
    $api = new FootballAPI();
    $api->syncFixture((int)$_GET['sync']);
    $msg = t('بازی همگام‌سازی شد ✅','Match synced ✅');
}

$matches = $db->query("
    SELECT m.*, ht.name_fa AS home_fa, ht.name_en AS home_en, ht.flag_url AS hf,
           at.name_fa AS away_fa, at.name_en AS away_en, at.flag_url AS af,
           (SELECT COUNT(*) FROM predictions WHERE match_id=m.id) AS pred_count
    FROM matches m
    JOIN teams ht ON m.home_team_id=ht.id
    JOIN teams at ON m.away_team_id=at.id
    ORDER BY m.match_date ASC
")->fetch_all(MYSQLI_ASSOC);

include '../includes/header.php';
?>
<style>
.match-admin-card{padding:1.2rem 1.5rem;margin-bottom:.75rem;}
.ma-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:.8rem;flex-wrap:wrap;gap:.5rem;}
.ma-title{font-weight:700;display:flex;align-items:center;gap:.5rem;}
.flag-xs{width:24px;height:16px;object-fit:contain;border-radius:2px;}
.ma-form{display:flex;align-items:center;gap:.5rem;flex-wrap:wrap;margin-top:.8rem;padding-top:.8rem;border-top:1px solid var(--border);}
.ma-form input[type=number]{width:55px;padding:.4rem;text-align:center;}
.ma-form select{padding:.4rem .8rem;}
</style>

<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1.5rem;flex-wrap:wrap;gap:1rem">
    <h1 style="font-size:1.4rem;font-weight:900;color:var(--gold)">⚽ <?= t('مدیریت بازی‌ها','Manage Matches') ?></h1>
    <div style="display:flex;gap:.5rem">
        <a href="sync.php" class="btn btn-ghost btn-sm">🔄 <?= t('همگام‌سازی','Sync') ?></a>
        <a href="index.php" class="btn btn-outline btn-sm">← <?= t('ادمین','Admin') ?></a>
    </div>
</div>

<?php if ($msg): ?><div class="alert alert-success"><?= e($msg) ?></div><?php endif; ?>

<?php foreach ($matches as $m):
    $isRTL = isRTL();
    $homeName = $isRTL ? ($m['home_fa']?:$m['home_en']) : $m['home_en'];
    $awayName = $isRTL ? ($m['away_fa']?:$m['away_en']) : $m['away_en'];
    $isLive = in_array($m['status'],['1H','HT','2H','ET','PEN']);
    $isDone = in_array($m['status'],['FT','AET','PEN_FT']);
?>
<div class="card match-admin-card <?= $isLive?'':''; ?>">
    <div class="ma-header">
        <div class="ma-title">
            <?php if ($m['hf']): ?><img src="<?= e($m['hf']) ?>" class="flag-xs" alt=""><?php endif; ?>
            <?= e($homeName) ?> vs <?= e($awayName) ?>
            <?php if ($m['af']): ?><img src="<?= e($m['af']) ?>" class="flag-xs" alt=""><?php endif; ?>
            <?php if ($isDone): ?>
            <span style="color:var(--gold);font-weight:900">(<?= $m['home_score'] ?>-<?= $m['away_score'] ?>)</span>
            <?php endif; ?>
        </div>
        <div style="display:flex;align-items:center;gap:.5rem;flex-wrap:wrap">
            <span style="font-size:.8rem;color:var(--text3)"><?= formatDate($m['match_date']) ?></span>
            <span class="badge <?= $isDone?'badge-finished':($isLive?'badge-live':'badge-upcoming') ?>">
                <?= $m['status'] ?><?= $m['minute']?" {$m['minute']}'":'' ?>
            </span>
            <span style="font-size:.78rem;color:var(--text3)">🎯 <?= $m['pred_count'] ?></span>
            <?php if ($m['api_fixture_id']): ?>
            <a href="?sync=<?= $m['api_fixture_id'] ?>" class="btn btn-ghost btn-sm">⚡</a>
            <?php endif; ?>
        </div>
    </div>

    <form method="POST" class="ma-form">
        <input type="hidden" name="match_id" value="<?= $m['id'] ?>">
        <label style="font-size:.8rem;color:var(--text2)"><?= t('نتیجه:','Score:') ?></label>
        <input type="number" name="home_score" min="0" max="30" value="<?= $m['home_score'] ?? '' ?>" placeholder="0">
        <span style="color:var(--text3)">-</span>
        <input type="number" name="away_score" min="0" max="30" value="<?= $m['away_score'] ?? '' ?>" placeholder="0">
        <select name="status">
            <?php foreach (['NS','1H','HT','2H','ET','PEN','FT','AET','PEN_FT'] as $s): ?>
            <option value="<?= $s ?>" <?= $m['status']===$s?'selected':'' ?>><?= $s ?></option>
            <?php endforeach; ?>
        </select>
        <input type="number" name="minute" min="0" max="120" value="<?= $m['minute'] ?>" placeholder="min" style="width:60px">
        <button type="submit" name="set_result" class="btn btn-gold btn-sm"><?= t('ذخیره','Save') ?></button>
    </form>
</div>
<?php endforeach; ?>

<?php include '../includes/footer.php'; ?>
