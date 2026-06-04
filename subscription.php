<?php
require_once 'includes/config.php';
if (!isLoggedIn()) redirect('login.php?redirect=subscription.php');
$pageTitle = t('خرید اشتراک','Buy Subscription');

$db   = getDB();
$user = getCurrentUser();

// پلن‌های فعال
$plans = $db->query("SELECT * FROM subscription_plans WHERE is_active=1 ORDER BY price_rial ASC")->fetch_all(MYSQLI_ASSOC);

// وضعیت اشتراک فعلی
$hasPremium = $user['subscription_status'] === 'premium'
    && $user['subscription_expires']
    && strtotime($user['subscription_expires']) > time();

$daysLeft = $hasPremium
    ? ceil((strtotime($user['subscription_expires']) - time()) / 86400)
    : 0;

include 'includes/header.php';
?>
<style>
.sub-hero { text-align:center; padding:2.5rem 0 2rem; }
.sub-hero h1 { font-size:clamp(1.6rem,3vw,2.4rem); font-weight:900; color:var(--gold); }
.sub-hero p  { color:var(--text2); margin-top:.4rem; }

.current-sub {
    max-width:600px; margin:0 auto 2rem;
    padding:1.2rem 1.5rem;
    border-radius:var(--radius);
    background:rgba(139,92,246,.1);
    border:1px solid rgba(139,92,246,.3);
    display:flex; align-items:center; gap:1rem; flex-wrap:wrap;
}
.current-sub .icon { font-size:2rem; }
.current-sub .info { flex:1; }
.current-sub .title { font-weight:700; color:#C4B5FD; }
.current-sub .sub   { font-size:.85rem; color:var(--text2); margin-top:.2rem; }

.plans-grid {
    display:grid;
    grid-template-columns:repeat(auto-fit,minmax(260px,1fr));
    gap:1.5rem;
    max-width:900px;
    margin:0 auto 3rem;
}

.plan-card {
    padding:2rem 1.5rem;
    border-radius:var(--radius);
    border:1px solid var(--border);
    background:var(--card);
    text-align:center;
    position:relative;
    transition:all .3s;
}
.plan-card:hover { border-color:var(--border-hover); transform:translateY(-4px); box-shadow:var(--shadow); }
.plan-card.popular {
    border-color:var(--gold);
    background:linear-gradient(135deg, rgba(245,200,66,.08), var(--card));
}
.popular-badge {
    position:absolute; top:-12px; left:50%; transform:translateX(-50%);
    background:var(--gold); color:#000; font-size:.75rem; font-weight:800;
    padding:.25rem .9rem; border-radius:20px; white-space:nowrap;
}
.plan-icon  { font-size:2.5rem; margin-bottom:1rem; }
.plan-name  { font-size:1.2rem; font-weight:800; margin-bottom:.3rem; }
.plan-desc  { font-size:.85rem; color:var(--text2); margin-bottom:1.5rem; min-height:2.5rem; }
.plan-price {
    font-size:2rem; font-weight:900; color:var(--gold);
    margin-bottom:.3rem; line-height:1;
}
.plan-price span { font-size:1rem; font-weight:500; color:var(--text2); }
.plan-duration { font-size:.85rem; color:var(--text2); margin-bottom:1.5rem; }
.plan-features { text-align:right; margin-bottom:1.5rem; }
.plan-feature {
    display:flex; align-items:center; gap:.5rem;
    font-size:.875rem; color:var(--text2); margin-bottom:.5rem;
}
.plan-feature::before { content:"✅"; font-size:.8rem; flex-shrink:0; }

/* No plans */
.no-plans {
    text-align:center; padding:4rem 2rem;
    max-width:500px; margin:0 auto;
}
.no-plans .icon { font-size:3rem; margin-bottom:1rem; }

/* Gateway section */
.gateway-section {
    max-width:600px; margin:0 auto;
    background:var(--card);
    border:1px solid var(--border);
    border-radius:var(--radius);
    padding:2rem;
    display:none;
}
.gateway-section.open { display:block; }
.gateway-title { font-size:1.1rem; font-weight:700; margin-bottom:1rem; }

.payment-methods { display:flex; gap:.75rem; flex-wrap:wrap; margin-bottom:1.5rem; }
.pay-method {
    flex:1; min-width:120px; padding:1rem;
    border:2px solid var(--border); border-radius:var(--radius-sm);
    text-align:center; cursor:pointer; transition:all .2s;
    background:var(--bg2);
}
.pay-method:hover, .pay-method.selected {
    border-color:var(--gold); background:var(--gold-glow);
}
.pay-method .pm-icon { font-size:1.5rem; margin-bottom:.3rem; }
.pay-method .pm-name { font-size:.8rem; font-weight:600; }

.coming-soon-box {
    background:rgba(245,200,66,.06);
    border:1px dashed rgba(245,200,66,.3);
    border-radius:var(--radius-sm);
    padding:1.5rem; text-align:center;
}
.coming-soon-box .cs-icon { font-size:2.5rem; margin-bottom:.5rem; }
.coming-soon-box h3 { color:var(--gold); font-weight:700; margin-bottom:.5rem; }
.coming-soon-box p { color:var(--text2); font-size:.875rem; line-height:1.6; }

.contact-box {
    margin-top:1rem;
    background:var(--bg2);
    border-radius:var(--radius-sm);
    padding:1rem 1.25rem;
    font-size:.875rem;
    color:var(--text2);
}
.contact-box a { color:var(--gold); text-decoration:none; }
</style>

<div class="sub-hero">
    <h1>💎 <?= t('اشتراک پریمیوم','Premium Subscription') ?></h1>
    <p><?= t('پیش‌بینی نامحدود، سکه بیشتر، رتبه بالاتر!','Unlimited predictions, more coins, higher rank!') ?></p>
</div>

<?php if ($hasPremium): ?>
<!-- کاربر قبلاً اشتراک داره -->
<div class="current-sub" style="max-width:600px;margin:0 auto 2rem">
    <div class="icon">💎</div>
    <div class="info">
        <div class="title"><?= t('اشتراک پریمیوم فعال','Premium Active') ?></div>
        <div class="sub">
            <?= t("$daysLeft روز باقی‌مانده تا","$daysLeft days remaining until") ?>
            <?= formatDate($user['subscription_expires']) ?>
        </div>
    </div>
    <span class="badge" style="background:rgba(139,92,246,.2);color:#C4B5FD">✓ Active</span>
</div>
<?php endif; ?>

<?php if (empty($plans)): ?>
<!-- هنوز پلنی تعریف نشده -->
<div class="no-plans">
    <div class="icon">🔧</div>
    <h2 style="font-size:1.2rem;font-weight:700;color:var(--text);margin-bottom:.5rem">
        <?= t('پلن اشتراک در دسترس نیست','No Subscription Plans Available') ?>
    </h2>
    <p style="color:var(--text2);font-size:.9rem">
        <?= t('ادمین هنوز پلن اشتراکی تعریف نکرده. لطفاً بعداً مراجعه کنید.',
               'No plans have been defined yet. Please check back later.') ?>
    </p>
    <?php if (isAdmin()): ?>
    <a href="admin/subscriptions.php" class="btn btn-gold" style="margin-top:1.5rem">
        ⚙️ <?= t('تعریف پلن اشتراک','Define Plans') ?>
    </a>
    <?php endif; ?>
</div>

<?php else: ?>
<!-- پلن‌های اشتراک -->
<div class="plans-grid">
    <?php foreach ($plans as $i => $plan): ?>
    <div class="plan-card <?= $i===1?'popular':'' ?>" onclick="selectPlan(<?= $plan['id'] ?>, '<?= e(t($plan['name_fa'],$plan['name_en'])) ?>', <?= $plan['price_rial'] ?>)">
        <?php if ($i===1): ?>
        <div class="popular-badge">⭐ <?= t('پرطرفدار','Most Popular') ?></div>
        <?php endif; ?>
        <div class="plan-icon">💎</div>
        <div class="plan-name"><?= e(t($plan['name_fa'], $plan['name_en'])) ?></div>
        <div class="plan-desc"><?= e(t($plan['description_fa'], $plan['description_en'])) ?></div>
        <div class="plan-price">
            <?= number_format($plan['price_rial']) ?>
            <span><?= t('تومان','Rial') ?></span>
        </div>
        <div class="plan-duration">📅 <?= $plan['duration_days'] ?> <?= t('روز','days') ?></div>
        <div class="plan-features">
            <div class="plan-feature"><?= t('پیش‌بینی نامحدود','Unlimited predictions') ?></div>
            <div class="plan-feature"><?= t('دریافت سکه برای پیش‌بینی درست','Earn coins for correct predictions') ?></div>
            <div class="plan-feature"><?= t('رتبه‌بندی در لیدربورد','Leaderboard ranking') ?></div>
            <div class="plan-feature"><?= t('اعلان‌های زنده بازی‌ها','Live match notifications') ?></div>
        </div>
        <button class="btn btn-gold btn-full" style="<?= $i===1?'':'background:var(--card);color:var(--gold);border:1px solid var(--gold)' ?>">
            <?= t('انتخاب این پلن','Choose This Plan') ?> →
        </button>
    </div>
    <?php endforeach; ?>
</div>

<!-- بخش پرداخت -->
<div class="gateway-section" id="gatewaySection">
    <div class="gateway-title">
        💳 <?= t('پرداخت برای','Payment for') ?>:
        <strong id="selectedPlanName" style="color:var(--gold)"></strong>
        — <span id="selectedPlanPrice" style="color:var(--coin)"></span>
        <?= t('تومان','Rial') ?>
    </div>

    <div class="coming-soon-box">
        <div class="cs-icon">🔧</div>
        <h3><?= t('درگاه پرداخت در حال راه‌اندازی است','Payment Gateway Coming Soon') ?></h3>
        <p>
            <?= t(
                'زیرساخت اشتراک کاملاً آماده است. برای خرید اشتراک فعلاً از طریق کانال‌های زیر با ادمین تماس بگیرید:',
                'The subscription infrastructure is ready. To purchase a subscription, please contact admin via the channels below:'
            ) ?>
        </p>
        <div class="contact-box" style="margin-top:1rem;text-align:<?= isRTL()?'right':'left' ?>">
            📱 <?= t('تلگرام ادمین:','Admin Telegram:') ?>
            <a href="https://t.me/youradmin" target="_blank">@youradmin</a><br>
            📧 <?= t('ایمیل:','Email:') ?>
            <a href="mailto:admin@yoursite.com">admin@yoursite.com</a><br><br>
            <span style="color:var(--text3);font-size:.8rem">
                🔒 <?= t(
                    'بعد از پرداخت، ادمین اشتراک شما را فعال می‌کند.',
                    'After payment, admin will activate your subscription.'
                ) ?>
            </span>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
function selectPlan(id, name, price) {
    document.getElementById('selectedPlanName').textContent = name;
    document.getElementById('selectedPlanPrice').textContent = price.toLocaleString();
    const section = document.getElementById('gatewaySection');
    section.classList.add('open');
    section.scrollIntoView({ behavior: 'smooth', block: 'center' });
}
</script>

<?php include 'includes/footer.php'; ?>
