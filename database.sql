CREATE DATABASE IF NOT EXISTS wc2026 CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE wc2026;

-- کاربران
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    avatar VARCHAR(10) DEFAULT '⚽',
    coins INT DEFAULT 0,
    role ENUM('user','admin') DEFAULT 'user',
    subscription_status ENUM('free','premium') DEFAULT 'free',
    subscription_expires DATETIME DEFAULT NULL,
    is_active TINYINT(1) DEFAULT 1,
    lang VARCHAR(5) DEFAULT 'fa',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- تیم‌ها (از API پر میشه)
CREATE TABLE teams (
    id INT AUTO_INCREMENT PRIMARY KEY,
    api_id INT UNIQUE,
    name_fa VARCHAR(100),
    name_en VARCHAR(100),
    flag_url VARCHAR(255),
    flag_emoji VARCHAR(20),
    group_name VARCHAR(5)
);

-- بازی‌ها (از API پر میشه)
CREATE TABLE matches (
    id INT AUTO_INCREMENT PRIMARY KEY,
    api_fixture_id INT UNIQUE,
    home_team_id INT,
    away_team_id INT,
    match_date DATETIME NOT NULL,
    venue VARCHAR(200),
    stage VARCHAR(100) DEFAULT 'Group Stage',
    home_score INT DEFAULT NULL,
    away_score INT DEFAULT NULL,
    minute INT DEFAULT NULL,
    status ENUM('NS','1H','HT','2H','ET','PEN','FT','AET','PEN_FT') DEFAULT 'NS',
    last_synced DATETIME DEFAULT NULL,
    FOREIGN KEY (home_team_id) REFERENCES teams(id),
    FOREIGN KEY (away_team_id) REFERENCES teams(id)
);

-- پیش‌بینی‌ها
CREATE TABLE predictions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    match_id INT NOT NULL,
    home_score INT NOT NULL,
    away_score INT NOT NULL,
    coins_earned INT DEFAULT 0,
    is_correct TINYINT(1) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_pred (user_id, match_id),
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (match_id) REFERENCES matches(id)
);

-- تراکنش‌های سکه
CREATE TABLE coin_transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    amount INT NOT NULL,
    type ENUM('earned','withdrawn','bonus','refund') DEFAULT 'earned',
    description VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- درخواست برداشت سکه
CREATE TABLE withdrawal_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    coins INT NOT NULL,
    amount_rial BIGINT NOT NULL,
    bank_info TEXT,
    status ENUM('pending','approved','rejected') DEFAULT 'pending',
    admin_note VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- پلن‌های اشتراک (زیرساخت برای بعد)
CREATE TABLE subscription_plans (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name_fa VARCHAR(100),
    name_en VARCHAR(100),
    price_rial BIGINT DEFAULT 0,
    duration_days INT DEFAULT 30,
    description_fa TEXT,
    description_en TEXT,
    is_active TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- پرداخت‌ها (زیرساخت)
CREATE TABLE payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    plan_id INT,
    amount_rial BIGINT NOT NULL,
    status ENUM('pending','success','failed') DEFAULT 'pending',
    gateway VARCHAR(50),
    gateway_ref VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- تنظیمات سایت
CREATE TABLE settings (
    `key` VARCHAR(100) PRIMARY KEY,
    `value` TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- اطلاع‌رسانی‌ها
CREATE TABLE notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    title_fa VARCHAR(255),
    title_en VARCHAR(255),
    message_fa TEXT,
    message_en TEXT,
    is_read TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- API Cache
CREATE TABLE api_cache (
    cache_key VARCHAR(255) PRIMARY KEY,
    data LONGTEXT,
    expires_at DATETIME
);

-- داده‌های پیش‌فرض
INSERT INTO users (username, email, password, role, coins) VALUES
('admin', 'admin@wc2026.ir', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 0);
-- رمز پیش‌فرض ادمین: password (حتما عوضش کن!)

INSERT INTO settings (`key`, `value`) VALUES
('site_name_fa', 'جام جهانی ۲۰۲۶'),
('site_name_en', 'World Cup 2026'),
('prediction_open', '1'),
('coins_per_correct', '10'),
('coins_to_rial_rate', '1000'),
('subscription_required', '0'),
('api_football_key', ''),
('api_season', '2026'),
('maintenance_mode', '0');

INSERT INTO subscription_plans (name_fa, name_en, price_rial, duration_days, description_fa, description_en, is_active) VALUES
('اشتراک یک ماهه', 'Monthly Plan', 50000, 30, 'دسترسی کامل به پیش‌بینی برای یک ماه', 'Full prediction access for one month', 0),
('اشتراک سه ماهه', 'Quarterly Plan', 120000, 90, 'دسترسی کامل به پیش‌بینی برای سه ماه', 'Full prediction access for three months', 0);
