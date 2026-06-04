<?php /* predictions.php */
require_once 'includes/config.php';
if (!isLoggedIn()) redirect('login.php');
$pageTitle = t('پیش‌بینی‌هام','My Predictions');

$db = getDB();
$user = getCurrentUser();
$uid = (int)$user['id'];

$preds = $db->query("
    SELECT p.*, m.match_date, m.status, m.home_score AS real_home, m.away_score AS real_away,
           m.stage, m.minute,
           ht.name_fa AS home_fa, ht.name_en AS home_en, ht.flag_url AS home_flag,
           at.name_fa AS away_fa, at.name_en AS away_en, at.flag_url AS away_flag
    FROM predictions p
    JOIN matches m ON p.match_id=m.id
    JOIN teams ht ON m.home_team_id=ht.id
    JOIN teams at ON m.away_team_id=at.id
    WHERE p.user_id=$uid
    ORDER BY m.match_date DESC
")->fetch_all(MYSQLI_ASSOC);

$totalCoins = array_sum(array_column($preds, 'coins_earned'));
$correct = count(array_filter($preds, fn($p)=>$p['is_correct']==1));

include 'includes/header.php';
?>
<style>
.page-title{text-align:center;padding:2rem 0 1.5rem;}
.page-title h1{font-size:1.8rem;font-weight:900;color:var(--gold);}
.stats-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(140px,1fr));gap:1rem;margin-bottom:2rem;}
.stat-card{padding:1.2rem;text-align:center;}
.stat-card .num{font-size:1.8rem;font-weight:900;color:var(--gold);}
.stat-card .lbl{font-size:.8rem;color:var(--text2);margin-top:.2rem;}
.pred-card{display:flex;align-items:center;justify-content:space-between;gap:1rem;padding:1rem 1.25rem;margin-bottom:.6rem;flex-wrap:wrap;}
.pred-match{display:flex;align-items:center;gap:.7rem;flex:1;min-width:180px;}
.flag-sm{width:32px;height:22px;object-fit:contain;border-radius:2px;}
.pred-scores{display:flex;align-items:center;gap:1rem;flex-wrap:wrap;}
.score-block{text-align:center;}
.score-block .lbl{font-size:.7rem;color:var(--text3);margin-bottom:.2rem;}
.score-block .val{font-size:1.1rem;font-weight:900;}
.result-pill{padding:.3rem .8rem;border-radius:20px;font-size:.78rem;font-weight:700;white-space:nowrap;}
.res-correct{background:rgba(34,197,94,.15);color:#86EFAC;}
.res-wrong{background:rgba(239,68,68,.12);color:#FCA5A5;}
.res-pending{background:var(--card);color:var(--text3);}
</style>

<div class="page-title">
    <h1><?= $user['avatar'] ?> <?= t('پیش‌بینی‌هام','My Predictions') ?></h1>
</div>

<div class="stats-grid">
    <div class="card stat-card"><div class="num"><?= count($preds) ?></div><div class="lbl">📊 <?= t('کل پیش‌بینی','Total') ?></div></div>
    <div class="card stat-card"><div class="num" style="color:var(--success)"><?= $correct ?></div><div class="lbl">✅ <?= t('درست','Correct') ?></div></div>
    <div class="card stat-card"><div class="num" style="color:var(--coin)"><?= $totalCoins ?></div><div class="lbl">🪙 <?= t('سکه کسب شده','Coins Earned') ?></div></div>
    <div class="card stat-card"><div class="num"><?= number_format($user['coins']) ?></div><div class="lbl">💰 <?= t('کل سکه','Total Coins') ?></div></div>
</div>

<?php if (empty($preds)): ?>
<div style="text-align:center;padding:4rem;color:var(--text3)">
    <div style="font-size:3rem">🎯</div>
    <div><?= t('هنوز پیش‌بینی نکردی!','No predictions yet!') ?></div>
    <a href="index.php" class="btn btn-gold" style="margin-top:1rem"><?= t('برو پیش‌بینی کن','Go Predict') ?></a>
</div>
<?php else: ?>

<?php foreach ($preds as $p):
    $isRTL = isRTL();
    $homeName = $isRTL ? ($p['home_fa']?:$p['home_en']) : $p['home_en'];
    $awayName = $isRTL ? ($p['away_fa']?:$p['away_en']) : $p['away_en'];
    $isFinished = in_array($p['status'],['FT','AET','PEN_FT']);
    $resClass = $p['is_correct']===null ? 'res-pending' : ($p['is_correct']?'res-correct':'res-wrong');
    $resText = $p['is_correct']===null
        ? t('⏳ در انتظار','⏳ Pending')
        : ($p['is_correct'] ? "✅ +".$p['coins_earned']."🪙" : t('❌ اشتباه','❌ Wrong'));
?>
<div class="card card-hover pred-card">
    <div class="pred-match">
        <?php if ($p['home_flag']): ?>
        <img src="<?= e($p['home_flag']) ?>" class="flag-sm" alt="">
        <?php endif; ?>
        <div>
            <div style="font-weight:700;font-size:.875rem"><?= e($homeName) ?> vs <?= e($awayName) ?></div>
            <div style="font-size:.75rem;color:var(--text3)"><?= formatDate($p['match_date']) ?> | <?= e($p['stage']) ?></div>
        </div>
        <?php if ($p['away_flag']): ?>
        <img src="<?= e($p['away_flag']) ?>" class="flag-sm" alt="">
        <?php endif; ?>
    </div>
    <div class="pred-scores">
        <div class="score-block">
            <div class="lbl"><?= t('پیش‌بینی','Prediction') ?></div>
            <div class="val"><?= $p['home_score'] ?> - <?= $p['away_score'] ?></div>
        </div>
        <?php if ($isFinished): ?>
        <div class="score-block">
            <div class="lbl"><?= t('واقعی','Real') ?></div>
            <div class="val" style="color:var(--gold)"><?= $p['real_home'] ?> - <?= $p['real_away'] ?></div>
        </div>
        <?php endif; ?>
    </div>
    <div class="result-pill <?= $resClass ?>"><?= $resText ?></div>
</div>
<?php endforeach; ?>

<?php endif; ?>
<?php include 'includes/footer.php'; ?>
