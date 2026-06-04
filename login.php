<?php /* login.php */
require_once 'includes/config.php';
if (isLoggedIn()) redirect('index.php');
$pageTitle = t('ورود','Login');
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login = trim($_POST['login'] ?? '');
    $pass  = $_POST['password'] ?? '';
    $db = getDB();
    $l = $db->real_escape_string($login);
    $u = $db->query("SELECT * FROM users WHERE (username='$l' OR email='$l') AND is_active=1")->fetch_assoc();
    if ($u && password_verify($pass, $u['password'])) {
        $_SESSION['user_id'] = $u['id'];
        $_SESSION['role'] = $u['role'];
        redirect('index.php');
    } else {
        $error = t('اطلاعات ورود اشتباه است','Invalid credentials');
    }
}
include 'includes/header.php';
?>
<style>
.auth-wrap{max-width:420px;margin:2rem auto;}
.auth-title{text-align:center;margin-bottom:2rem;}
.auth-title h1{font-size:1.9rem;font-weight:900;color:var(--gold);}
.auth-title p{color:var(--text2);margin-top:0.3rem;}
.auth-footer{text-align:center;margin-top:1.5rem;color:var(--text2);font-size:.875rem;}
.auth-footer a{color:var(--gold);text-decoration:none;}
</style>
<div class="auth-wrap">
    <div class="auth-title">
        <h1>🔑 <?= t('ورود','Login') ?></h1>
        <p><?= t('خوش برگشتی قهرمان!','Welcome back, champion!') ?></p>
    </div>
    <?php if ($error): ?>
    <div class="alert alert-error">⚠️ <?= e($error) ?></div>
    <?php endif; ?>
    <div class="card" style="padding:1.75rem">
        <form method="POST">
            <div class="form-group">
                <label><?= t('نام کاربری یا ایمیل','Username or Email') ?></label>
                <input type="text" name="login" required autofocus>
            </div>
            <div class="form-group">
                <label><?= t('رمز عبور','Password') ?></label>
                <input type="password" name="password" required>
            </div>
            <button type="submit" class="btn btn-gold btn-full btn-lg" style="margin-top:.5rem">
                <?= t('ورود','Login') ?> →
            </button>
        </form>
    </div>
    <div class="auth-footer">
        <?= t('هنوز ثبت‌نام نکردی؟','No account?') ?>
        <a href="register.php"><?= t('ثبت‌نام کن','Register') ?></a>
    </div>
</div>
<?php include 'includes/footer.php'; ?>
