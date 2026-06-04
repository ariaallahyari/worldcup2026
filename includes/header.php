<?php
$user = getCurrentUser();
$lang = getLang();
$rtl  = isRTL();
$dir  = $rtl ? 'rtl' : 'ltr';
$siteName = getSetting($rtl ? 'site_name_fa' : 'site_name_en', 'WC 2026');
$page = basename($_SERVER['PHP_SELF'], '.php');

// تعداد نوتیف‌های خوانده‌نشده
$unreadNotifs = 0;
if ($user) {
    $uid = (int)$user['id'];
    $nr = getDB()->query("SELECT COUNT(*) as c FROM notifications WHERE user_id=$uid AND is_read=0");
    if ($nr) $unreadNotifs = (int)$nr->fetch_assoc()['c'];
}
?>
<!DOCTYPE html>
<html lang="<?= $lang ?>" dir="<?= $dir ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= isset($pageTitle) ? e($pageTitle).' | ' : '' ?><?= e($siteName) ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@300;400;500;700;900&family=Inter:wght@300;400;500;700;900&display=swap" rel="stylesheet">
<style>
/* ==========================================
   CSS VARIABLES - DARK & LIGHT
   ========================================== */
:root {
    --gold: #F5C842;
    --gold-dark: #C9971A;
    --gold-glow: rgba(245,200,66,0.15);
    --green-deep: #0A3D1F;
    --green-mid: #155232;
    --green-light: #1E6B3F;
    --coin: #FFD700;
    --red: #EF4444;
    --success: #22C55E;
    --radius: 14px;
    --radius-sm: 8px;
    --shadow: 0 4px 24px rgba(0,0,0,0.3);
    --transition: 0.2s ease;

    /* Dark mode (default) */
    --bg: #080E0A;
    --bg2: #0F1A12;
    --bg3: #162219;
    --card: rgba(21,82,50,0.18);
    --card-hover: rgba(21,82,50,0.30);
    --border: rgba(245,200,66,0.12);
    --border-hover: rgba(245,200,66,0.35);
    --text: #EFF4F0;
    --text2: #7E9E87;
    --text3: #4A6B52;
    --nav-bg: rgba(8,14,10,0.95);
    --input-bg: rgba(6,15,8,0.7);
    --badge-live: rgba(239,68,68,0.2);
    --badge-live-text: #FCA5A5;
    --badge-upcoming: rgba(34,197,94,0.15);
    --badge-upcoming-text: #86EFAC;
    --badge-finished: rgba(126,158,135,0.15);
    --badge-finished-text: #7E9E87;
}

[data-theme="light"] {
    --bg: #F0F5F1;
    --bg2: #E4EDE6;
    --bg3: #D8E6DB;
    --card: rgba(255,255,255,0.9);
    --card-hover: rgba(255,255,255,1);
    --border: rgba(21,82,50,0.12);
    --border-hover: rgba(21,82,50,0.3);
    --text: #0F2414;
    --text2: #3A6347;
    --text3: #7A9B82;
    --nav-bg: rgba(240,245,241,0.97);
    --input-bg: rgba(255,255,255,0.9);
    --badge-live: rgba(239,68,68,0.1);
    --badge-live-text: #DC2626;
    --badge-upcoming: rgba(21,82,50,0.1);
    --badge-upcoming-text: #155232;
    --badge-finished: rgba(126,158,135,0.2);
    --badge-finished-text: #3A6347;
    --shadow: 0 4px 24px rgba(0,0,0,0.1);
}

/* ==========================================
   RESET & BASE
   ========================================== */
*, *::before, *::after { margin:0; padding:0; box-sizing:border-box; }

html { scroll-behavior: smooth; }

body {
    font-family: <?= $rtl ? "'Vazirmatn'" : "'Inter'" ?>, sans-serif;
    background: var(--bg);
    color: var(--text);
    min-height: 100vh;
    line-height: 1.6;
    transition: background 0.3s, color 0.3s;
}

body::before {
    content: '';
    position: fixed;
    inset: 0;
    background:
        radial-gradient(ellipse 80% 50% at 15% 40%, rgba(10,61,31,0.3) 0%, transparent 60%),
        radial-gradient(ellipse 60% 60% at 85% 10%, rgba(245,200,66,0.04) 0%, transparent 50%);
    pointer-events: none;
    z-index: 0;
}

[data-theme="light"] body::before {
    background:
        radial-gradient(ellipse 80% 50% at 15% 40%, rgba(21,82,50,0.06) 0%, transparent 60%),
        radial-gradient(ellipse 60% 60% at 85% 10%, rgba(245,200,66,0.04) 0%, transparent 50%);
}

/* ==========================================
   NAVBAR
   ========================================== */
nav {
    background: var(--nav-bg);
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
    border-bottom: 1px solid var(--border);
    position: sticky;
    top: 0;
    z-index: 200;
    transition: background 0.3s;
}

.nav-inner {
    max-width: 1280px;
    margin: 0 auto;
    padding: 0 1.5rem;
    height: 64px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
}

.logo {
    font-size: 1.2rem;
    font-weight: 900;
    color: var(--gold);
    text-decoration: none;
    display: flex;
    align-items: center;
    gap: 0.4rem;
    white-space: nowrap;
    letter-spacing: -0.5px;
}

.logo em { color: var(--text); font-style: normal; }

.nav-links {
    display: flex;
    align-items: center;
    gap: 0.25rem;
    list-style: none;
    flex-wrap: wrap;
}

.nav-links a {
    color: var(--text2);
    text-decoration: none;
    padding: 0.45rem 0.85rem;
    border-radius: var(--radius-sm);
    font-size: 0.875rem;
    font-weight: 500;
    transition: all var(--transition);
    white-space: nowrap;
}

.nav-links a:hover { color: var(--text); background: var(--card); }
.nav-links a.active { color: var(--gold); background: var(--gold-glow); }

.nav-actions {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    flex-shrink: 0;
}

/* ==========================================
   BUTTONS
   ========================================== */
.btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.4rem;
    padding: 0.5rem 1.1rem;
    border-radius: var(--radius-sm);
    font-family: inherit;
    font-size: 0.875rem;
    font-weight: 600;
    cursor: pointer;
    border: none;
    text-decoration: none;
    transition: all var(--transition);
    white-space: nowrap;
}

.btn-gold { background: var(--gold); color: #0A1A0C; }
.btn-gold:hover { background: var(--gold-dark); transform: translateY(-1px); box-shadow: 0 4px 15px rgba(245,200,66,0.3); }

.btn-outline {
    background: transparent;
    color: var(--text2);
    border: 1px solid var(--border);
}
.btn-outline:hover { border-color: var(--border-hover); color: var(--text); }

.btn-ghost { background: var(--card); color: var(--text2); }
.btn-ghost:hover { background: var(--card-hover); color: var(--text); }

.btn-danger { background: rgba(239,68,68,0.15); color: #FCA5A5; border: 1px solid rgba(239,68,68,0.2); }
.btn-danger:hover { background: rgba(239,68,68,0.25); }

.btn-sm { padding: 0.35rem 0.75rem; font-size: 0.8rem; }
.btn-lg { padding: 0.75rem 1.75rem; font-size: 1rem; }
.btn-full { width: 100%; }

/* ==========================================
   CARDS
   ========================================== */
.card {
    background: var(--card);
    border: 1px solid var(--border);
    border-radius: var(--radius);
    transition: all var(--transition);
}

.card-hover:hover {
    border-color: var(--border-hover);
    background: var(--card-hover);
    box-shadow: var(--shadow);
    transform: translateY(-2px);
}

/* ==========================================
   BADGES
   ========================================== */
.badge {
    display: inline-flex;
    align-items: center;
    gap: 0.3rem;
    padding: 0.2rem 0.65rem;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 700;
}
.badge-live { background: var(--badge-live); color: var(--badge-live-text); }
.badge-upcoming { background: var(--badge-upcoming); color: var(--badge-upcoming-text); }
.badge-finished { background: var(--badge-finished); color: var(--badge-finished-text); }
.badge-gold { background: var(--gold-glow); color: var(--gold); }
.badge-coin { background: rgba(255,215,0,0.15); color: var(--coin); }

/* ==========================================
   FORMS
   ========================================== */
.form-group { margin-bottom: 1.1rem; }

label {
    display: block;
    font-size: 0.85rem;
    color: var(--text2);
    margin-bottom: 0.4rem;
    font-weight: 500;
}

input[type=text], input[type=email], input[type=password], input[type=number], select, textarea {
    width: 100%;
    padding: 0.7rem 1rem;
    background: var(--input-bg);
    border: 1px solid var(--border);
    border-radius: var(--radius-sm);
    color: var(--text);
    font-family: inherit;
    font-size: 0.9rem;
    outline: none;
    transition: border-color var(--transition), box-shadow var(--transition);
}

input:focus, select:focus, textarea:focus {
    border-color: var(--gold);
    box-shadow: 0 0 0 3px var(--gold-glow);
}

/* ==========================================
   ALERTS
   ========================================== */
.alert {
    padding: 0.85rem 1.1rem;
    border-radius: var(--radius-sm);
    margin-bottom: 1rem;
    font-size: 0.875rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}
.alert-error { background: rgba(239,68,68,0.12); border: 1px solid rgba(239,68,68,0.25); color: #FCA5A5; }
.alert-success { background: rgba(34,197,94,0.12); border: 1px solid rgba(34,197,94,0.25); color: #86EFAC; }
.alert-info { background: var(--gold-glow); border: 1px solid var(--border-hover); color: var(--gold); }
.alert-warning { background: rgba(251,191,36,0.1); border: 1px solid rgba(251,191,36,0.2); color: #FCD34D; }

/* ==========================================
   COINS DISPLAY
   ========================================== */
.coin-icon { color: var(--coin); }

.coin-chip {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    background: rgba(255,215,0,0.1);
    border: 1px solid rgba(255,215,0,0.2);
    border-radius: 20px;
    padding: 0.3rem 0.75rem;
    font-size: 0.85rem;
    font-weight: 700;
    color: var(--coin);
}

/* ==========================================
   THEME & LANG TOGGLES
   ========================================== */
.icon-btn {
    width: 36px;
    height: 36px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: var(--card);
    border: 1px solid var(--border);
    cursor: pointer;
    font-size: 1rem;
    color: var(--text2);
    transition: all var(--transition);
    text-decoration: none;
}
.icon-btn:hover { border-color: var(--border-hover); color: var(--text); }

/* ==========================================
   NOTIFICATION BELL
   ========================================== */
.notif-btn {
    position: relative;
}
.notif-dot {
    position: absolute;
    top: 5px;
    right: 5px;
    width: 8px;
    height: 8px;
    background: var(--red);
    border-radius: 50%;
    border: 2px solid var(--bg);
}

/* ==========================================
   MAIN LAYOUT
   ========================================== */
main {
    max-width: 1280px;
    margin: 0 auto;
    padding: 2rem 1.5rem;
    position: relative;
    z-index: 1;
}

/* ==========================================
   HAMBURGER MENU (mobile)
   ========================================== */
.hamburger {
    display: none;
    flex-direction: column;
    gap: 5px;
    cursor: pointer;
    padding: 5px;
}
.hamburger span {
    display: block;
    width: 22px;
    height: 2px;
    background: var(--text2);
    border-radius: 2px;
    transition: all 0.3s;
}

.mobile-menu {
    display: none;
    position: fixed;
    top: 64px;
    right: 0; left: 0;
    background: var(--nav-bg);
    backdrop-filter: blur(20px);
    border-bottom: 1px solid var(--border);
    padding: 1rem 1.5rem;
    z-index: 199;
    flex-direction: column;
    gap: 0.5rem;
}

.mobile-menu.open { display: flex; }
.mobile-menu a {
    color: var(--text2);
    text-decoration: none;
    padding: 0.6rem 0.5rem;
    border-radius: var(--radius-sm);
    font-size: 0.95rem;
    font-weight: 500;
    transition: all var(--transition);
    border-bottom: 1px solid var(--border);
}
.mobile-menu a:last-child { border-bottom: none; }
.mobile-menu a.active, .mobile-menu a:hover { color: var(--gold); background: var(--gold-glow); }

/* ==========================================
   RESPONSIVE
   ========================================== */
@media (max-width: 768px) {
    .nav-links { display: none; }
    .hamburger { display: flex; }
    main { padding: 1rem; }
    .hide-mobile { display: none !important; }
}

@media (max-width: 480px) {
    .logo em { display: none; }
    .btn-lg { padding: 0.65rem 1.2rem; font-size: 0.9rem; }
}

/* ==========================================
   SCROLL BAR
   ========================================== */
::-webkit-scrollbar { width: 6px; }
::-webkit-scrollbar-track { background: var(--bg2); }
::-webkit-scrollbar-thumb { background: var(--green-mid); border-radius: 3px; }
::-webkit-scrollbar-thumb:hover { background: var(--green-light); }

/* ==========================================
   TOAST NOTIFICATION
   ========================================== */
.toast-container {
    position: fixed;
    bottom: 1.5rem;
    <?= $rtl ? 'right' : 'left' ?>: 1.5rem;
    z-index: 9999;
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.toast {
    padding: 0.8rem 1.2rem;
    border-radius: var(--radius-sm);
    font-size: 0.875rem;
    font-weight: 500;
    box-shadow: var(--shadow);
    animation: toastIn 0.3s ease, toastOut 0.3s ease 3.5s forwards;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.toast-success { background: var(--green-mid); border: 1px solid rgba(34,197,94,0.3); color: #86EFAC; }
.toast-error { background: rgba(60,0,0,0.9); border: 1px solid rgba(239,68,68,0.3); color: #FCA5A5; }
.toast-info { background: var(--bg3); border: 1px solid var(--border-hover); color: var(--gold); }

@keyframes toastIn { from { opacity:0; transform: translateY(20px); } to { opacity:1; transform: translateY(0); } }
@keyframes toastOut { to { opacity:0; transform: translateY(20px); } }
</style>
</head>
<body data-theme="<?= isset($_COOKIE['theme']) ? e($_COOKIE['theme']) : 'dark' ?>">

<!-- NAVBAR -->
<nav>
    <div class="nav-inner">
        <a href="<?= SITE_URL ?>/index.php" class="logo">
            ⚽ <?= t('جام','WC') ?><em><?= t('جهانی ۲۰۲۶',' 2026') ?></em>
        </a>

        <ul class="nav-links">
            <li><a href="<?= SITE_URL ?>/index.php" class="<?= $page==='index'?'active':'' ?>"><?= t('بازی‌ها','Matches') ?></a></li>
            <li><a href="<?= SITE_URL ?>/leaderboard.php" class="<?= $page==='leaderboard'?'active':'' ?>"><?= t('رتبه‌بندی','Leaderboard') ?></a></li>
            <?php if ($user): ?>
            <li><a href="<?= SITE_URL ?>/predictions.php" class="<?= $page==='predictions'?'active':'' ?>"><?= t('پیش‌بینی‌هام','My Predictions') ?></a></li>
            <li><a href="<?= SITE_URL ?>/wallet.php" class="<?= $page==='wallet'?'active':'' ?>"><?= t('کیف پول','Wallet') ?></a></li>
            <?php if (getSetting('subscription_required','0')==='1' && ($user['subscription_status']??'free')==='free'): ?>
            <li><a href="<?= SITE_URL ?>/subscription.php" style="color:var(--gold);background:var(--gold-glow)">💎 <?= t('خرید اشتراک','Get Premium') ?></a></li>
            <?php endif; ?>
            <?php if (isAdmin()): ?>
            <li><a href="<?= SITE_URL ?>/admin/index.php" class="<?= strpos($page,'admin')!==false?'active':'' ?>" style="color:var(--gold)">⚙️ <?= t('ادمین','Admin') ?></a></li>
            <?php endif; ?>
            <?php endif; ?>
        </ul>

        <div class="nav-actions">
            <!-- تغییر زبان -->
            <a href="?lang=<?= $lang==='fa'?'en':'fa' ?>" class="icon-btn" title="<?= t('English','فارسی') ?>">
                <?= $lang==='fa' ? 'EN' : 'FA' ?>
            </a>

            <!-- تغییر تم -->
            <button class="icon-btn" id="themeToggle" title="<?= t('تم روشن','Dark mode') ?>">
                <span id="themeIcon">🌙</span>
            </button>

            <?php if ($user): ?>
                <!-- نوتیفیکیشن -->
                <a href="<?= SITE_URL ?>/notifications.php" class="icon-btn notif-btn" title="<?= t('اعلان‌ها','Notifications') ?>">
                    🔔
                    <?php if ($unreadNotifs > 0): ?>
                    <span class="notif-dot"></span>
                    <?php endif; ?>
                </a>

                <!-- کیف پول -->
                <a href="<?= SITE_URL ?>/wallet.php" class="coin-chip hide-mobile">
                    🪙 <?= number_format($user['coins']) ?>
                </a>

                <!-- خروج -->
                <a href="<?= SITE_URL ?>/logout.php" class="btn btn-outline btn-sm hide-mobile"><?= t('خروج','Logout') ?></a>
            <?php else: ?>
                <a href="<?= SITE_URL ?>/login.php" class="btn btn-outline btn-sm"><?= t('ورود','Login') ?></a>
                <a href="<?= SITE_URL ?>/register.php" class="btn btn-gold btn-sm"><?= t('ثبت‌نام','Register') ?></a>
            <?php endif; ?>

            <!-- همبرگر -->
            <div class="hamburger" id="hamburger">
                <span></span><span></span><span></span>
            </div>
        </div>
    </div>
</nav>

<!-- موبایل منو -->
<div class="mobile-menu" id="mobileMenu">
    <a href="<?= SITE_URL ?>/index.php" class="<?= $page==='index'?'active':'' ?>">⚽ <?= t('بازی‌ها','Matches') ?></a>
    <a href="<?= SITE_URL ?>/leaderboard.php" class="<?= $page==='leaderboard'?'active':'' ?>">🏆 <?= t('رتبه‌بندی','Leaderboard') ?></a>
    <?php if ($user): ?>
    <a href="<?= SITE_URL ?>/predictions.php">🎯 <?= t('پیش‌بینی‌هام','My Predictions') ?></a>
    <a href="<?= SITE_URL ?>/wallet.php">🪙 <?= t('کیف پول','Wallet') ?> (<?= number_format($user['coins']) ?>)</a>
    <a href="<?= SITE_URL ?>/notifications.php">🔔 <?= t('اعلان‌ها','Notifications') ?><?= $unreadNotifs>0?" ($unreadNotifs)":'' ?></a>
    <?php if (getSetting('subscription_required','0')==='1' && ($user['subscription_status']??'free')==='free'): ?>
    <a href="<?= SITE_URL ?>/subscription.php" style="color:var(--gold)">💎 <?= t('خرید اشتراک','Get Premium') ?></a>
    <?php endif; ?>
    <?php if (isAdmin()): ?>
    <a href="<?= SITE_URL ?>/admin/index.php" style="color:var(--gold)">⚙️ <?= t('پنل ادمین','Admin Panel') ?></a>
    <?php endif; ?>
    <a href="<?= SITE_URL ?>/logout.php">🚪 <?= t('خروج','Logout') ?></a>
    <?php else: ?>
    <a href="<?= SITE_URL ?>/login.php">🔑 <?= t('ورود','Login') ?></a>
    <a href="<?= SITE_URL ?>/register.php">✍️ <?= t('ثبت‌نام','Register') ?></a>
    <?php endif; ?>
</div>

<main>

<script>
// Theme toggle
const themeToggle = document.getElementById('themeToggle');
const themeIcon = document.getElementById('themeIcon');
const body = document.body;

function applyTheme(theme) {
    body.setAttribute('data-theme', theme);
    themeIcon.textContent = theme === 'dark' ? '☀️' : '🌙';
    document.cookie = `theme=${theme};path=/;max-age=31536000`;
}

applyTheme(body.getAttribute('data-theme') || 'dark');

themeToggle.addEventListener('click', () => {
    const current = body.getAttribute('data-theme');
    applyTheme(current === 'dark' ? 'light' : 'dark');
});

// Hamburger
const hamburger = document.getElementById('hamburger');
const mobileMenu = document.getElementById('mobileMenu');
hamburger.addEventListener('click', () => mobileMenu.classList.toggle('open'));
document.addEventListener('click', e => {
    if (!hamburger.contains(e.target) && !mobileMenu.contains(e.target))
        mobileMenu.classList.remove('open');
});
</script>
