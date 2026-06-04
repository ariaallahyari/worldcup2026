<?php /* wallet.php */
require_once 'includes/config.php';
if (!isLoggedIn()) redirect('login.php');
$pageTitle = t('کیف پول','Wallet');

$db = getDB();
$user = getCurrentUser();
$uid = (int)$user['id'];

$msg = '';
// درخواست برداشت
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['withdraw'])) {
    $coins = (int)$_POST['coins'];
    $bank  = trim($_POST['bank_info'] ?? '');
    $rate  = (int)getSetting('coins_to_rial_rate','1000');
    if ($coins < 100) {
        $msg = ['type'=>'error','text'=>t('حداقل ۱۰۰ سکه برای برداشت لازم است','Minimum 100 coins to withdraw')];
    } elseif ($coins > $user['coins']) {
        $msg = ['type'=>'error','text'=>t('سکه کافی ندارید','Not enough coins')];
    } elseif (empty($bank)) {
        $msg = ['type'=>'error','text'=>t('اطلاعات بانکی وارد کنید','Enter bank information')];
    } else {
        $rial = $coins * $rate;
        $stmt = $db->prepare("INSERT INTO withdrawal_requests (user_id,coins,amount_rial,bank_info) VALUES (?,?,?,?)");
        $stmt->bind_param('iiis', $uid, $coins, $rial, $bank);
        $stmt->execute();
        $msg = ['type'=>'success','text'=>t('درخواست برداشت ثبت شد. ادمین بررسی خواهد کرد.','Withdrawal request submitted. Admin will review.')];
    }
    $user = getCurrentUser(); // refresh
}

$transactions = $db->query("SELECT * FROM coin_transactions WHERE user_id=$uid ORDER BY created_at DESC LIMIT 30")->fetch_all(MYSQLI_ASSOC);
$pendingReqs = $db->query("SELECT * FROM withdrawal_requests WHERE user_id=$uid ORDER BY created_at DESC LIMIT 10")->fetch_all(MYSQLI_ASSOC);
$rate = (int)getSetting('coins_to_rial_rate','1000');

include 'includes/header.php';
?>
<style>
.wallet-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:1.5rem;margin-bottom:2rem;}
.wallet-card{padding:1.5rem;text-align:center;}
.wallet-card .num{font-size:2.5rem;font-weight:900;margin:.5rem 0;}
.wallet-card .lbl{color:var(--text2);font-size:.875rem;}
.section-title{font-size:1.1rem;font-weight:700;color:var(--text);margin:1.5rem 0 1rem;display:flex;align-items:center;gap:.5rem;}
.tx-row{display:flex;align-items:center;justify-content:space-between;padding:.8rem 1rem;border-bottom:1px solid var(--border);}
.tx-row:last-child{border-bottom:none;}
.tx-amount{font-weight:700;}
.tx-earn{color:var(--success);}
.tx-spend{color:var(--red);}
</style>

<div class="wallet-grid">
    <div class="card wallet-card">
        <div class="lbl">🪙 <?= t('سکه‌های شما','Your Coins') ?></div>
        <div class="num" style="color:var(--coin)"><?= number_format($user['coins']) ?></div>
        <div class="lbl">≈ <?= number_format($user['coins'] * $rate) ?> <?= t('تومان','Rial') ?></div>
    </div>
    <div class="card wallet-card">
        <div class="lbl">💰 <?= t('ارزش هر سکه','Coin Value') ?></div>
        <div class="num" style="font-size:1.8rem;color:var(--gold)"><?= number_format($rate) ?></div>
        <div class="lbl"><?= t('تومان','Rial') ?></div>
    </div>
</div>

<?php if (is_array($msg)): ?>
<div class="alert alert-<?= $msg['type'] ?>"><?= e($msg['text']) ?></div>
<?php endif; ?>

<!-- فرم برداشت -->
<div class="section-title">💸 <?= t('درخواست برداشت','Withdrawal Request') ?></div>
<div class="card" style="padding:1.5rem;max-width:500px">
    <?php if ($user['coins'] < 100): ?>
    <div class="alert alert-warning"><?= t('برای برداشت حداقل ۱۰۰ سکه نیاز دارید','You need at least 100 coins to withdraw') ?></div>
    <?php else: ?>
    <form method="POST">
        <div class="form-group">
            <label><?= t('تعداد سکه برای برداشت','Coins to withdraw') ?> (<?= t('حداقل','Min') ?> 100)</label>
            <input type="number" name="coins" min="100" max="<?= $user['coins'] ?>" required>
        </div>
        <div class="form-group">
            <label><?= t('شماره شبا یا اطلاعات بانکی','Bank account / IBAN') ?></label>
            <textarea name="bank_info" rows="3" style="background:var(--input-bg);border:1px solid var(--border);border-radius:var(--radius-sm);color:var(--text);font-family:inherit;padding:.7rem 1rem;width:100%;outline:none" required></textarea>
        </div>
        <button type="submit" name="withdraw" class="btn btn-gold"><?= t('ثبت درخواست','Submit Request') ?></button>
    </form>
    <?php endif; ?>
</div>

<!-- تاریخچه -->
<div class="section-title">📋 <?= t('تاریخچه تراکنش‌ها','Transaction History') ?></div>
<div class="card" style="padding:0">
    <?php if (empty($transactions)): ?>
    <div style="padding:2rem;text-align:center;color:var(--text3)"><?= t('تراکنشی وجود ندارد','No transactions yet') ?></div>
    <?php else: ?>
    <?php foreach ($transactions as $tx): ?>
    <div class="tx-row">
        <div>
            <div style="font-size:.875rem"><?= e($tx['description'] ?: $tx['type']) ?></div>
            <div style="font-size:.75rem;color:var(--text3)"><?= formatDate($tx['created_at']) ?></div>
        </div>
        <div class="tx-amount <?= $tx['amount']>0?'tx-earn':'tx-spend' ?>">
            <?= $tx['amount']>0?'+':'' ?><?= $tx['amount'] ?> 🪙
        </div>
    </div>
    <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>
