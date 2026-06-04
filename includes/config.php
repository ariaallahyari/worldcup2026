<?php
// ==========================================
// تنظیمات اصلی - اینا رو با اطلاعات خودت عوض کن
// ==========================================
define('DB_HOST', 'localhost'); 
define('DB_USER', ''); 
define('DB_PASS', '');
define('DB_NAME', '');
define('SITE_URL', 'http://localhost/wc2026');
define('BASE_PATH', __DIR__ . '/..');

// TheSportsDB - رایگان، بدون API Key
define('API_FOOTBALL_KEY', '');
define('API_FOOTBALL_URL', 'https://www.thesportsdb.com/api/v1/json/3');
// League ID جام جهانی در TheSportsDB = 4429

// امنیت
define('SECRET_KEY', 'change_this_to_random_string_32chars');

session_start();

// ==========================================
// اتصال دیتابیس
// ==========================================
function getDB(): mysqli {
    static $conn = null;
    if ($conn === null) {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        $conn->set_charset('utf8mb4');
        if ($conn->connect_error) {
            die(json_encode(['error' => 'DB connection failed']));
        }
    }
    return $conn;
}

// ==========================================
// توابع کمکی
// ==========================================
function getSetting(string $key, string $default = ''): string {
    static $cache = [];
    if (!isset($cache[$key])) {
        $db = getDB();
        $k = $db->real_escape_string($key);
        $r = $db->query("SELECT `value` FROM settings WHERE `key`='$k'");
        $cache[$key] = $r && $r->num_rows ? $r->fetch_assoc()['value'] : $default;
    }
    return $cache[$key];
}

function e(string $str): string {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

function redirect(string $url): void {
    header("Location: $url"); exit;
}

function isLoggedIn(): bool {
    return isset($_SESSION['user_id']);
}

function isAdmin(): bool {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function getCurrentUser(): ?array {
    if (!isLoggedIn()) return null;
    $db = getDB();
    $id = (int)$_SESSION['user_id'];
    $r = $db->query("SELECT * FROM users WHERE id=$id AND is_active=1");
    return $r ? $r->fetch_assoc() : null;
}

function getLang(): string {
    if (isset($_SESSION['lang'])) return $_SESSION['lang'];
    if (isset($_COOKIE['lang'])) return $_COOKIE['lang'];
    return 'fa';
}

function t(string $fa, string $en): string {
    return getLang() === 'fa' ? $fa : $en;
}

function isRTL(): bool {
    return getLang() === 'fa';
}

function formatDate(string $datetime, bool $timeOnly = false): string {
    $ts = strtotime($datetime);
    if (getLang() === 'fa') {
        if ($timeOnly) return date('H:i', $ts);
        return date('Y/m/d H:i', $ts); // می‌تونی تبدیل شمسی اضافه کنی
    }
    return $timeOnly ? date('H:i', $ts) : date('M j, Y H:i', $ts);
}

function getMatchStatus(string $status, ?int $minute): string {
    $map = [
        'NS'     => t('برگزار نشده','Not Started'),
        '1H'     => t('نیمه اول','1st Half') . ($minute ? " {$minute}'" : ''),
        'HT'     => t('نیمه وقت','Half Time'),
        '2H'     => t('نیمه دوم','2nd Half') . ($minute ? " {$minute}'" : ''),
        'ET'     => t('وقت اضافه','Extra Time'),
        'PEN'    => t('ضربات پنالتی','Penalty'),
        'FT'     => t('پایان','Full Time'),
        'AET'    => t('پایان با وقت اضافه','AET'),
        'PEN_FT' => t('پایان با پنالتی','Penalties'),
    ];
    return $map[$status] ?? $status;
}

function isMatchPredictable(array $match): bool {
    if (!isLoggedIn()) return false;
    // بسته اگر بازی تموم شده
    if (in_array($match['status'], ['FT','AET','PEN_FT'])) return false;
    // بسته اگر دقیقه ۷۵ گذشته
    if (in_array($match['status'], ['1H','HT','2H','ET','PEN']) && $match['minute'] >= 75) return false;
    return true;
}

function canUserPredict(): array {
    $user = getCurrentUser();
    if (!$user) return ['can' => false, 'reason' => 'login'];
    if (getSetting('maintenance_mode') === '1') return ['can' => false, 'reason' => 'maintenance'];
    if (getSetting('prediction_open') !== '1') return ['can' => false, 'reason' => 'closed'];
    // بررسی اشتراک (اگه فعال شد)
    if (getSetting('subscription_required') === '1') {
        if ($user['subscription_status'] !== 'premium') return ['can' => false, 'reason' => 'subscription'];
        if ($user['subscription_expires'] && strtotime($user['subscription_expires']) < time())
            return ['can' => false, 'reason' => 'expired'];
    }
    return ['can' => true, 'reason' => ''];
}

function addCoins(int $userId, int $amount, string $type, string $desc): void {
    $db = getDB();
    $db->query("UPDATE users SET coins = coins + $amount WHERE id = $userId");
    $stmt = $db->prepare("INSERT INTO coin_transactions (user_id, amount, type, description) VALUES (?,?,?,?)");
    $stmt->bind_param('iiss', $userId, $amount, $type, $desc);
    $stmt->execute();
}

function sendNotification(int $userId, string $titleFa, string $titleEn, string $msgFa, string $msgEn): void {
    $db = getDB();
    $stmt = $db->prepare("INSERT INTO notifications (user_id,title_fa,title_en,message_fa,message_en) VALUES (?,?,?,?,?)");
    $stmt->bind_param('issss', $userId, $titleFa, $titleEn, $msgFa, $msgEn);
    $stmt->execute();
}

// تغییر زبان
if (isset($_GET['lang']) && in_array($_GET['lang'], ['fa','en'])) {
    $_SESSION['lang'] = $_GET['lang'];
    setcookie('lang', $_GET['lang'], time()+86400*365, '/');
    $back = $_SERVER['HTTP_REFERER'] ?? SITE_URL . '/index.php';
    redirect($back);
}
