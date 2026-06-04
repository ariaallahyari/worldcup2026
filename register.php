<?php
require_once 'includes/config.php';
if (isLoggedIn()) redirect('index.php');
$pageTitle = t('ثبت‌نام','Register');

$error = '';
$avatars = ['⚽','🏆','🦁','🐯','🦅','🔥','⭐','🌟','🎯','🥇'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $avatar   = in_array($_POST['avatar'] ?? '⚽', $avatars) ? $_POST['avatar'] : '⚽';

    if (strlen($username) < 3 || !preg_match('/^[a-zA-Z0-9_\x{0600}-\x{06FF}]+$/u', $username)) {
        $error = t('نام کاربری حداقل ۳ کاراکتر و فقط حروف/عدد','Username min 3 chars, letters/numbers only');
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = t('ایمیل معتبر وارد کنید','Enter a valid email');
    } elseif (strlen($password) < 6) {
        $error = t('رمز عبور حداقل ۶ کاراکتر','Password min 6 characters');
    } else {
        $db = getDB();
        $u = $db->real_escape_string($username);
        $e = $db->real_escape_string($email);
        if ($db->query("SELECT id FROM users WHERE username='$u' OR email='$e'")->num_rows > 0) {
            $error = t('این نام کاربری یا ایمیل قبلاً ثبت شده','Username or email already taken');
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $av = $db->real_escape_string($avatar);
            $lang = getLang();
            $stmt = $db->prepare("INSERT INTO users (username,email,password,avatar,lang) VALUES (?,?,?,?,?)");
            $stmt->bind_param('sssss', $username, $email, $hash, $avatar, $lang);
            $stmt->execute();
            $_SESSION['user_id'] = $db->insert_id;
            $_SESSION['role'] = 'user';
            redirect('index.php');
        }
    }
}
include 'includes/header.php';
?>
<style>
.auth-wrap { max-width:480px; margin:2rem auto; }
.auth-title { text-align:center; margin-bottom:2rem; }
.auth-title h1 { font-size:1.9rem; font-weight:900; color:var(--gold); }
.auth-title p { color:var(--text2); margin-top:0.3rem; }
.avatar-grid { display:flex; gap:0.5rem; flex-wrap:wrap; margin-top:0.4rem; }
.av-opt input { display:none; }
.av-opt label {
    font-size:1.7rem; cursor:pointer; padding:0.3rem; border-radius:10px;
    border:2px solid transparent; transition:all 0.2s; display:block; margin:0;
}
.av-opt input:checked + label { border-color:var(--gold); background:var(--gold-glow); }
.auth-footer { text-align:center; margin-top:1.5rem; color:var(--text2); font-size:0.875rem; }
.auth-footer a { color:var(--gold); text-decoration:none; }
</style>

<div class="auth-wrap">
    <div class="auth-title">
        <h1>✍️ <?= t('ثبت‌نام','Create Account') ?></h1>
        <p><?= t('به جمع پیش‌بینی‌کنندگان بپیوند!','Join the prediction community!') ?></p>
    </div>
    <?php if ($error): ?>
    <div class="alert alert-error">⚠️ <?= e($error) ?></div>
    <?php endif; ?>
    <div class="card" style="padding:1.75rem">
        <form method="POST">
            <div class="form-group">
                <label><?= t('آواتار','Avatar') ?></label>
                <div class="avatar-grid">
                    <?php foreach ($avatars as $i => $av): ?>
                    <div class="av-opt">
                        <input type="radio" name="avatar" id="av<?=$i?>" value="<?=$av?>" <?=$i===0?'checked':''?>>
                        <label for="av<?=$i?>"><?=$av?></label>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="form-group">
                <label><?= t('نام کاربری','Username') ?></label>
                <input type="text" name="username" placeholder="<?= t('مثلاً: star_player','e.g. star_player') ?>"
                       value="<?= e($_POST['username']??'') ?>" required>
            </div>
            <div class="form-group">
                <label><?= t('ایمیل','Email') ?></label>
                <input type="email" name="email" placeholder="you@example.com"
                       value="<?= e($_POST['email']??'') ?>" required>
            </div>
            <div class="form-group">
                <label><?= t('رمز عبور','Password') ?></label>
                <input type="password" name="password" placeholder="<?= t('حداقل ۶ کاراکتر','At least 6 characters') ?>" required>
            </div>
            <button type="submit" class="btn btn-gold btn-full btn-lg" style="margin-top:0.5rem">
                ⚽ <?= t('ثبت‌نام','Register') ?>
            </button>
        </form>
    </div>
    <div class="auth-footer">
        <?= t('قبلاً ثبت‌نام کردی؟','Already have an account?') ?>
        <a href="login.php"><?= t('وارد شو','Login') ?></a>
    </div>
</div>
<?php include 'includes/footer.php'; ?>
