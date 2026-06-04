<?php
require_once 'includes/config.php';
$pageTitle = t('بازی‌ها', 'Matches');
include 'includes/header.php';

$db = getDB();
$user = getCurrentUser();
$rtl = isRTL();

// فیلتر
$filter = $_GET['filter'] ?? 'all';
$validFilters = ['all','upcoming','live','finished'];
if (!in_array($filter, $validFilters)) $filter = 'all';

// بارگذاری بازی‌ها
$whereStatus = '';
if ($filter === 'upcoming')  $whereStatus = "AND m.status = 'NS'";
if ($filter === 'live')      $whereStatus = "AND m.status IN ('1H','HT','2H','ET','PEN')";
if ($filter === 'finished')  $whereStatus = "AND m.status IN ('FT','AET','PEN_FT')";

$matches = $db->query("
    SELECT m.*,
           ht.name_fa AS home_fa, ht.name_en AS home_en, ht.flag_url AS home_flag,
           at.name_fa AS away_fa, at.name_en AS away_en, at.flag_url AS away_flag
    FROM matches m
    JOIN teams ht ON m.home_team_id = ht.id
    JOIN teams at ON m.away_team_id = at.id
    WHERE 1=1 $whereStatus
    ORDER BY m.match_date ASC
")->fetch_all(MYSQLI_ASSOC);

// پیش‌بینی‌های کاربر
$userPreds = [];
if ($user) {
    $uid = (int)$user['id'];
    $pr = $db->query("SELECT * FROM predictions WHERE user_id=$uid");
    while ($p = $pr->fetch_assoc()) $userPreds[$p['match_id']] = $p;
}

// ثبت پیش‌بینی
$postMsg = '';
$postType = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['predict'])) {
    $perm = canUserPredict();
    if (!$perm['can']) {
        $postMsg = match($perm['reason']) {
            'login'        => t('ابتدا وارد شوید','Please login first'),
            'subscription' => t('برای پیش‌بینی اشتراک لازم است','Subscription required to predict'),
            'expired'      => t('اشتراک شما منقضی شده','Your subscription has expired'),
            'closed'       => t('پیش‌بینی‌ها فعلاً بسته هستند','Predictions are closed'),
            default        => t('خطا','Error'),
        };
        $postType = 'error';
    } else {
        $matchId   = (int)$_POST['match_id'];
        $homeScore = max(0, min(20, (int)$_POST['home_score']));
        $awayScore = max(0, min(20, (int)$_POST['away_score']));

        // بررسی بازی
        $match = $db->query("SELECT * FROM matches WHERE id=$matchId")->fetch_assoc();
        if (!$match) {
            $postMsg = t('بازی یافت نشد','Match not found'); $postType = 'error';
        } elseif (!isMatchPredictable($match)) {
            $postMsg = t('زمان پیش‌بینی این بازی تمام شده','Prediction time is over for this match'); $postType = 'error';
        } else {
            $stmt = $db->prepare("INSERT INTO predictions (user_id,match_id,home_score,away_score)
                                   VALUES (?,?,?,?)
                                   ON DUPLICATE KEY UPDATE home_score=VALUES(home_score), away_score=VALUES(away_score)");
            $stmt->bind_param('iiii', $uid, $matchId, $homeScore, $awayScore);
            $stmt->execute();
            $userPreds[$matchId] = ['home_score'=>$homeScore,'away_score'=>$awayScore,'coins_earned'=>0];
            $postMsg = t('پیش‌بینی شما ثبت شد! ✅','Prediction saved! ✅'); $postType = 'success';
        }
    }
}

// گروه‌بندی بازی‌ها بر اساس تاریخ
$grouped = [];
foreach ($matches as $m) {
    $day = date('Y-m-d', strtotime($m['match_date']));
    $grouped[$day][] = $m;
}

// آمار سریع
$liveCount = $db->query("SELECT COUNT(*) as c FROM matches WHERE status IN ('1H','HT','2H','ET','PEN')")->fetch_assoc()['c'];
$totalCount = $db->query("SELECT COUNT(*) as c FROM matches")->fetch_assoc()['c'];
?>

<style>
.page-hero {
    padding: 2.5rem 0 2rem;
    text-align: center;
}
.page-hero h1 {
    font-size: clamp(1.8rem, 4vw, 3rem);
    font-weight: 900;
    background: linear-gradient(135deg, var(--gold) 0%, var(--text) 50%, var(--gold) 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    margin-bottom: 0.5rem;
}
.page-hero p { color: var(--text2); font-size: 1rem; }

.filter-bar {
    display: flex;
    gap: 0.5rem;
    margin-bottom: 1.5rem;
    flex-wrap: wrap;
}

.filter-btn {
    padding: 0.45rem 1rem;
    border-radius: 20px;
    font-size: 0.85rem;
    font-weight: 600;
    cursor: pointer;
    border: 1px solid var(--border);
    background: var(--card);
    color: var(--text2);
    text-decoration: none;
    transition: all var(--transition);
}
.filter-btn.active, .filter-btn:hover {
    background: var(--gold-glow);
    border-color: var(--gold);
    color: var(--gold);
}

.day-group { margin-bottom: 2rem; }
.day-label {
    font-size: 0.85rem;
    font-weight: 700;
    color: var(--text2);
    margin-bottom: 0.75rem;
    padding: <?= $rtl ? '0 0.5rem 0.5rem' : '0 0.5rem 0.5rem' ?>;
    border-bottom: 1px solid var(--border);
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

/* Match Card */
.match-card {
    padding: 1.25rem 1.5rem;
    margin-bottom: 0.75rem;
    position: relative;
    overflow: hidden;
}

.match-card.live-card {
    border-color: rgba(239,68,68,0.3);
    background: rgba(239,68,68,0.04);
}

.match-card.predicted {
    border-color: rgba(245,200,66,0.25);
}

.match-meta {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 1rem;
    flex-wrap: wrap;
    gap: 0.5rem;
}

.live-pulse {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
}
.live-dot {
    width: 8px; height: 8px;
    background: var(--red);
    border-radius: 50%;
    animation: pulse 1.2s infinite;
}
@keyframes pulse {
    0%, 100% { opacity: 1; transform: scale(1); }
    50% { opacity: 0.5; transform: scale(1.3); }
}

/* Teams Row */
.teams-row {
    display: grid;
    grid-template-columns: 1fr auto 1fr;
    align-items: center;
    gap: 1rem;
}

.team {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.5rem;
    text-align: center;
}

.team-flag-img {
    width: 52px;
    height: 36px;
    object-fit: contain;
    border-radius: 4px;
    border: 1px solid var(--border);
}

.team-flag-fallback {
    width: 52px;
    height: 36px;
    background: var(--card);
    border-radius: 4px;
    border: 1px solid var(--border);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.4rem;
}

.team-name {
    font-weight: 700;
    font-size: 0.9rem;
    color: var(--text);
}

.vs-block {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.3rem;
    min-width: 90px;
}

.score-live {
    font-size: 2rem;
    font-weight: 900;
    color: var(--text);
    letter-spacing: 4px;
    line-height: 1;
}

.score-placeholder {
    font-size: 1.5rem;
    color: var(--text3);
    letter-spacing: 3px;
}

.match-time {
    font-size: 0.8rem;
    color: var(--text2);
}

/* Prediction Form */
.pred-section {
    margin-top: 1.2rem;
    padding-top: 1.2rem;
    border-top: 1px solid var(--border);
}

.pred-form {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.75rem;
    flex-wrap: wrap;
}

.score-inputs {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.score-in {
    width: 52px;
    height: 48px;
    text-align: center;
    font-size: 1.2rem;
    font-weight: 700;
    padding: 0;
    border-radius: var(--radius-sm);
}

.pred-existing {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    color: var(--gold);
    font-size: 0.875rem;
    font-weight: 600;
}

.login-hint {
    text-align: center;
    color: var(--text3);
    font-size: 0.85rem;
}
.login-hint a { color: var(--gold); text-decoration: none; }

.closed-hint {
    text-align: center;
    color: var(--text3);
    font-size: 0.8rem;
    font-style: italic;
}

/* Stats bar */
.stats-bar {
    display: flex;
    gap: 1rem;
    margin-bottom: 2rem;
    flex-wrap: wrap;
}
.stat-pill {
    display: flex;
    align-items: center;
    gap: 0.4rem;
    padding: 0.4rem 0.9rem;
    background: var(--card);
    border: 1px solid var(--border);
    border-radius: 20px;
    font-size: 0.82rem;
    color: var(--text2);
}

.empty-state {
    text-align: center;
    padding: 4rem 2rem;
    color: var(--text3);
}
.empty-state .emoji { font-size: 3rem; margin-bottom: 1rem; }
</style>

<?php if ($postMsg): ?>
<div class="alert alert-<?= $postType ?>"><?= e($postMsg) ?></div>
<?php endif; ?>

<div class="page-hero">
    <h1>⚽ <?= t('جام جهانی ۲۰۲۶','World Cup 2026') ?></h1>
    <p><?= t('پیش‌بینی کن، سکه بگیر، قهرمان شو!','Predict matches, earn coins, be a champion!') ?></p>
</div>

<!-- آمار سریع -->
<div class="stats-bar">
    <div class="stat-pill">⚽ <?= $totalCount ?> <?= t('بازی','Matches') ?></div>
    <?php if ($liveCount > 0): ?>
    <div class="stat-pill" style="border-color:rgba(239,68,68,0.3);color:var(--badge-live-text)">
        <span class="live-dot"></span><?= $liveCount ?> <?= t('زنده','Live') ?>
    </div>
    <?php endif; ?>
    <?php if ($user): ?>
    <div class="stat-pill">🎯 <?= count($userPreds) ?> <?= t('پیش‌بینی','Predictions') ?></div>
    <?php endif; ?>
</div>

<!-- فیلترها -->
<div class="filter-bar">
    <a href="?filter=all"      class="filter-btn <?= $filter==='all'?'active':'' ?>">📋 <?= t('همه','All') ?></a>
    <a href="?filter=live"     class="filter-btn <?= $filter==='live'?'active':'' ?>">🔴 <?= t('زنده','Live') ?><?= $liveCount>0?" ($liveCount)":'' ?></a>
    <a href="?filter=upcoming" class="filter-btn <?= $filter==='upcoming'?'active':'' ?>">⏰ <?= t('آینده','Upcoming') ?></a>
    <a href="?filter=finished" class="filter-btn <?= $filter==='finished'?'active':'' ?>">✅ <?= t('پایان یافته','Finished') ?></a>
</div>

<?php if (empty($matches)): ?>
<div class="empty-state">
    <div class="emoji">😴</div>
    <div><?= t('بازی‌ای در این بخش وجود ندارد','No matches in this section') ?></div>
    <?php if (isAdmin()): ?>
    <a href="admin/sync.php" class="btn btn-gold" style="margin-top:1rem"><?= t('همگام‌سازی از API','Sync from API') ?></a>
    <?php endif; ?>
</div>
<?php else: ?>

<?php foreach ($grouped as $day => $dayMatches):
    $ts = strtotime($day);
    $today = date('Y-m-d');
    $tomorrow = date('Y-m-d', strtotime('+1 day'));
    if ($day === $today) $dayLabel = t('امروز','Today');
    elseif ($day === $tomorrow) $dayLabel = t('فردا','Tomorrow');
    else $dayLabel = $rtl ? date('Y/m/d', $ts) : date('F j, Y', $ts);
?>
<div class="day-group">
    <div class="day-label">📅 <?= $dayLabel ?></div>

    <?php foreach ($dayMatches as $m):
        $isLive = in_array($m['status'], ['1H','HT','2H','ET','PEN']);
        $isFinished = in_array($m['status'], ['FT','AET','PEN_FT']);
        $hasPred = isset($userPreds[$m['id']]);
        $predictable = isMatchPredictable($m);
        $homeName = $rtl ? ($m['home_fa'] ?: $m['home_en']) : $m['home_en'];
        $awayName = $rtl ? ($m['away_fa'] ?: $m['away_en']) : $m['away_en'];
    ?>
    <div class="card card-hover match-card <?= $isLive?'live-card':'' ?> <?= $hasPred?'predicted':'' ?>">

        <!-- سر کارت -->
        <div class="match-meta">
            <div>
                <?php if ($isLive): ?>
                <span class="badge badge-live">
                    <span class="live-dot"></span>
                    <?= t('زنده','LIVE') ?>
                    <?php if ($m['minute']): ?> <?= $m['minute'] ?>'<?php endif; ?>
                </span>
                <?php elseif ($isFinished): ?>
                <span class="badge badge-finished">✓ <?= getMatchStatus($m['status'], null) ?></span>
                <?php else: ?>
                <span class="badge badge-upcoming">⏰ <?= formatDate($m['match_date'], true) ?></span>
                <?php endif; ?>
            </div>
            <span style="font-size:0.8rem;color:var(--text3)"><?= e($m['stage']) ?></span>
        </div>

        <!-- تیم‌ها -->
        <div class="teams-row">
            <div class="team">
                <?php if ($m['home_flag']): ?>
                <img src="<?= e($m['home_flag']) ?>" alt="<?= e($homeName) ?>" class="team-flag-img"
                     onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">
                <div class="team-flag-fallback" style="display:none">🏳</div>
                <?php else: ?>
                <div class="team-flag-fallback">🏳</div>
                <?php endif; ?>
                <div class="team-name"><?= e($homeName) ?></div>
            </div>

            <div class="vs-block">
                <?php if ($isLive || $isFinished): ?>
                <div class="score-live"><?= $m['home_score'] ?> - <?= $m['away_score'] ?></div>
                <?php else: ?>
                <div class="score-placeholder">- : -</div>
                <?php endif; ?>
                <div class="match-time"><?= formatDate($m['match_date'], true) ?></div>
                <?php if ($m['venue']): ?>
                <div style="font-size:0.72rem;color:var(--text3)">📍 <?= e($m['venue']) ?></div>
                <?php endif; ?>
            </div>

            <div class="team">
                <?php if ($m['away_flag']): ?>
                <img src="<?= e($m['away_flag']) ?>" alt="<?= e($awayName) ?>" class="team-flag-img"
                     onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">
                <div class="team-flag-fallback" style="display:none">🏳</div>
                <?php else: ?>
                <div class="team-flag-fallback">🏳</div>
                <?php endif; ?>
                <div class="team-name"><?= e($awayName) ?></div>
            </div>
        </div>

        <!-- بخش پیش‌بینی -->
        <div class="pred-section">
            <?php if ($predictable): ?>
            <form method="POST" class="pred-form">
                <input type="hidden" name="predict" value="1">
                <input type="hidden" name="match_id" value="<?= $m['id'] ?>">
                <?php if ($hasPred): ?>
                <div class="pred-existing">
                    🎯 <?= t('پیش‌بینی شما:','Your prediction:') ?>
                    <?= $userPreds[$m['id']]['home_score'] ?> - <?= $userPreds[$m['id']]['away_score'] ?>
                </div>
                <?php endif; ?>
                <div class="score-inputs">
                    <span style="font-size:.8rem;color:var(--text2)"><?= e($homeName) ?></span>
                    <input type="number" name="home_score" class="score-in" min="0" max="20"
                           value="<?= $hasPred ? $userPreds[$m['id']]['home_score'] : '' ?>" placeholder="0" required>
                    <span style="color:var(--text3);font-size:1.3rem">-</span>
                    <input type="number" name="away_score" class="score-in" min="0" max="20"
                           value="<?= $hasPred ? $userPreds[$m['id']]['away_score'] : '' ?>" placeholder="0" required>
                    <span style="font-size:.8rem;color:var(--text2)"><?= e($awayName) ?></span>
                </div>
                <button type="submit" class="btn btn-gold btn-sm">
                    <?= $hasPred ? t('✏️ ویرایش','✏️ Update') : t('🎯 پیش‌بینی','🎯 Predict') ?>
                </button>
            </form>
            <?php elseif ($isFinished && $hasPred): ?>
            <div class="pred-existing" style="justify-content:center">
                🎯 <?= t('پیش‌بینی شما:','Your prediction:') ?>
                <?= $userPreds[$m['id']]['home_score'] ?> - <?= $userPreds[$m['id']]['away_score'] ?>
                <?php if ($userPreds[$m['id']]['coins_earned'] > 0): ?>
                | 🪙 +<?= $userPreds[$m['id']]['coins_earned'] ?> <?= t('سکه','coins') ?>
                <?php endif; ?>
            </div>
            <?php elseif (!$user): ?>
            <div class="login-hint">
                <?= t('برای پیش‌بینی','To predict') ?>
                <a href="login.php"><?= t('وارد شو','login') ?></a>
                <?= t('یا','or') ?>
                <a href="register.php"><?= t('ثبت‌نام کن','register') ?></a>
            </div>
            <?php elseif ($m['status'] !== 'NS'): ?>
            <div class="closed-hint">
                <?= $isFinished
                    ? t('🔒 بازی تمام شده - پیش‌بینی بسته است','🔒 Match finished - predictions closed')
                    : t('🔒 پیش‌بینی برای این بازی بسته شده (دقیقه ۷۵+)','🔒 Predictions closed (after 75th minute)')
                ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php endforeach; ?>

<?php endif; ?>

<?php include 'includes/footer.php'; ?>
