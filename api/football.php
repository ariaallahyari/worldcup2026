<?php
require_once __DIR__ . '/../includes/config.php';

class FootballAPI {
    private string $baseUrl = 'https://www.thesportsdb.com/api/v1/json/3';
    private mysqli $db;

    // ID لیگ جام جهانی در TheSportsDB = 4429
    // فصل جام جهانی ۲۰۲۶
    private string $leagueId = '4429';

    public function __construct() {
        $this->db = getDB();
        // league id از تنظیمات (پیش‌فرض 4429 = FIFA World Cup)
        $lid = getSetting('api_league_id', '4429');
        if ($lid) $this->leagueId = $lid;
    }

    // ==========================================
    // درخواست به API با cache
    // ==========================================
    private function request(string $endpoint, int $cacheMins = 30): ?array {
        $url      = $this->baseUrl . $endpoint;
        $cacheKey = md5($url);

        // بررسی cache
        $ck = $this->db->real_escape_string($cacheKey);
        $cached = $this->db->query(
            "SELECT data FROM api_cache WHERE cache_key='$ck' AND expires_at > NOW()"
        );
        if ($cached && $cached->num_rows > 0) {
            return json_decode($cached->fetch_assoc()['data'], true);
        }

        // درخواست جدید
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_USERAGENT      => 'Mozilla/5.0 WC2026App/1.0',
            CURLOPT_HTTPHEADER     => ['Accept: application/json'],
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErr  = curl_error($ch);
        curl_close($ch);

        if ($curlErr || $httpCode !== 200 || !$response) return null;

        $data = json_decode($response, true);
        if (!$data) return null;

        // ذخیره در cache
        $expires = date('Y-m-d H:i:s', time() + $cacheMins * 60);
        $escaped = $this->db->real_escape_string($response);
        $this->db->query(
            "INSERT INTO api_cache (cache_key, data, expires_at)
             VALUES ('$ck','$escaped','$expires')
             ON DUPLICATE KEY UPDATE data='$escaped', expires_at='$expires'"
        );

        return $data;
    }

    // ==========================================
    // همگام‌سازی کامل - endpoint تایید شده از دیباگ
    // /eventsseason.php?id=4429&s=2026
    // ==========================================
    public function syncFixtures(): array {
        $season = getSetting('api_season', '2026');
        $data   = $this->request("/eventsseason.php?id={$this->leagueId}&s=$season", 60);

        if (!$data || empty($data['events'])) {
            return ['success' => false, 'message' => "No matches found for league {$this->leagueId} season $season"];
        }

        $synced = 0;
        foreach ($data['events'] as $event) {
            $this->upsertTeamByName($event['strHomeTeam'] ?? '', $event['strHomeTeamBadge'] ?? '');
            $this->upsertTeamByName($event['strAwayTeam'] ?? '', $event['strAwayTeamBadge'] ?? '');
            $this->upsertEvent($event);
            $synced++;
        }

        return ['success' => true, 'synced' => $synced];
    }

    // ==========================================
    // بروزرسانی بازی‌های زنده - cache کمتر
    // ==========================================
    public function syncLive(): array {
        $season = getSetting('api_season', '2026');
        $data   = $this->request("/eventsseason.php?id={$this->leagueId}&s=$season", 2);

        if (!$data || empty($data['events'])) {
            return ['success' => false, 'count' => 0];
        }

        $count = 0;
        foreach ($data['events'] as $ev) {
            $status = strtoupper($ev['strStatus'] ?? 'NS');
            if (in_array($status, ['1H','HT','2H','ET','PEN','FT','FINISHED','IN_PLAY','PAUSED'])) {
                $this->upsertTeamByName($ev['strHomeTeam'] ?? '', $ev['strHomeTeamBadge'] ?? '');
                $this->upsertTeamByName($ev['strAwayTeam'] ?? '', $ev['strAwayTeamBadge'] ?? '');
                $this->upsertEvent($ev);
                $count++;
            }
        }

        return ['success' => true, 'count' => $count];
    }

    // ==========================================
    // همگام‌سازی یک بازی خاص
    // endpoint: /lookupevent.php?id={eventId}
    // ==========================================
    public function syncFixture(int $fixtureId): bool {
        $data = $this->request("/lookupevent.php?id=$fixtureId", 2);
        if (!$data || empty($data['events'][0])) return false;

        $ev = $data['events'][0];
        $this->upsertTeamByName($ev['strHomeTeam'] ?? '', $ev['strHomeTeamBadge'] ?? '');
        $this->upsertTeamByName($ev['strAwayTeam'] ?? '', $ev['strAwayTeamBadge'] ?? '');
        $this->upsertEvent($ev);
        return true;
    }

    // ==========================================
    // ذخیره تیم با نام
    // TheSportsDB ساختار تیم:
    // strHomeTeam: "Iran", strHomeTeamBadge: "https://...png"
    // ==========================================
    private function upsertTeamByName(string $name, string $badgeUrl): void {
        if (!$name) return;

        $nameEn  = $this->db->real_escape_string(trim($name));
        $nameFa  = $this->db->real_escape_string($this->translateTeamName($name));
        $flagUrl = $this->db->real_escape_string(trim($badgeUrl));

        // بررسی وجود تیم با نام
        $exists = $this->db->query("SELECT id FROM teams WHERE name_en='$nameEn'")->fetch_assoc();
        if ($exists) {
            // فقط لوگو رو آپدیت کن
            $this->db->query("UPDATE teams SET flag_url='$flagUrl', name_fa='$nameFa' WHERE name_en='$nameEn'");
        } else {
            $this->db->query(
                "INSERT INTO teams (name_en, name_fa, flag_url)
                 VALUES ('$nameEn', '$nameFa', '$flagUrl')"
            );
        }
    }

    // ==========================================
    // ذخیره/آپدیت بازی
    // ساختار TheSportsDB event:
    // {
    //   "idEvent": "1234567",
    //   "strEvent": "Iran vs USA",
    //   "strHomeTeam": "Iran",
    //   "strAwayTeam": "USA",
    //   "strHomeTeamBadge": "https://...png",
    //   "strAwayTeamBadge": "https://...png",
    //   "dateEvent": "2026-06-15",
    //   "strTime": "20:00:00",
    //   "strTimestamp": "2026-06-15T20:00:00+00:00",
    //   "intHomeScore": null,
    //   "intAwayScore": null,
    //   "strStatus": "NS",        // NS | 1H | HT | 2H | ET | PEN | FT
    //   "intProgress": null,      // دقیقه بازی
    //   "strRound": "Group Stage",
    //   "strVenue": "AT&T Stadium"
    // }
    // ==========================================
    private function upsertEvent(array $ev): void {
        if (empty($ev['idEvent'])) return;

        $apiId     = (int)$ev['idEvent'];
        $homeName  = trim($ev['strHomeTeam'] ?? '');
        $awayName  = trim($ev['strAwayTeam'] ?? '');
        if (!$homeName || !$awayName) return;

        // تاریخ و ساعت
        $timestamp = $ev['strTimestamp'] ?? ($ev['dateEvent'] . 'T' . ($ev['strTime'] ?? '00:00:00'));
        $matchDate = $this->db->real_escape_string(
            date('Y-m-d H:i:s', strtotime($timestamp))
        );

        $venue  = $this->db->real_escape_string($ev['strVenue'] ?? '');
        $round  = $this->db->real_escape_string($ev['strRound'] ?? $ev['intRound'] ?? 'Group Stage');
        $stage  = $this->db->real_escape_string($this->translateStage($round));
        $status = $this->db->real_escape_string($this->mapStatus($ev['strStatus'] ?? 'NS'));
        $minute = (int)($ev['intProgress'] ?? 0);

        // نتایج
        $homeScore = ($ev['intHomeScore'] !== null && $ev['intHomeScore'] !== '')
            ? (int)$ev['intHomeScore'] : null;
        $awayScore = ($ev['intAwayScore'] !== null && $ev['intAwayScore'] !== '')
            ? (int)$ev['intAwayScore'] : null;
        $homeSQL = $homeScore !== null ? $homeScore : 'NULL';
        $awaySQL = $awayScore !== null ? $awayScore : 'NULL';

        // دریافت ID تیم‌ها
        $hn = $this->db->real_escape_string($homeName);
        $an = $this->db->real_escape_string($awayName);
        $homeRow = $this->db->query("SELECT id FROM teams WHERE name_en='$hn'")->fetch_assoc();
        $awayRow = $this->db->query("SELECT id FROM teams WHERE name_en='$an'")->fetch_assoc();
        if (!$homeRow || !$awayRow) return;

        $homeId = $homeRow['id'];
        $awayId = $awayRow['id'];
        $now    = date('Y-m-d H:i:s');

        $this->db->query(
            "INSERT INTO matches
               (api_fixture_id, home_team_id, away_team_id, match_date,
                venue, stage, home_score, away_score, minute, status, last_synced)
             VALUES
               ($apiId, $homeId, $awayId, '$matchDate',
                '$venue', '$stage', $homeSQL, $awaySQL, $minute, '$status', '$now')
             ON DUPLICATE KEY UPDATE
               match_date='$matchDate', venue='$venue', stage='$stage',
               home_score=$homeSQL, away_score=$awaySQL,
               minute=$minute, status='$status', last_synced='$now'"
        );

        // محاسبه سکه اگه بازی تموم شد
        if (in_array($status, ['FT', 'AET', 'PEN_FT'])) {
            $row = $this->db->query(
                "SELECT id FROM matches WHERE api_fixture_id=$apiId"
            )->fetch_assoc();
            if ($row) $this->calculateCoins((int)$row['id']);
        }
    }

    // ==========================================
    // تبدیل وضعیت TheSportsDB به فرمت داخلی
    // ==========================================
    private function mapStatus(string $s): string {
        $s = strtoupper(trim($s));
        return match($s) {
            'NS', 'TBD', 'SCHED', 'SCHEDULED' => 'NS',
            '1H', 'FIRST HALF'                 => '1H',
            'HT', 'HALF TIME', 'HALFTIME'      => 'HT',
            '2H', 'SECOND HALF'                => '2H',
            'ET', 'EXTRA TIME'                 => 'ET',
            'PEN', 'PENALTIES'                 => 'PEN',
            'FT', 'FINISHED', 'FULL TIME', 'AOT', 'AP' => 'FT',
            'AET'                              => 'AET',
            'PEN_FT'                           => 'PEN_FT',
            default                            => 'NS',
        };
    }

    // ==========================================
    // ترجمه مرحله بازی
    // ==========================================
    private function translateStage(string $round): string {
        $r = strtolower(trim($round));
        if (str_contains($r, 'group'))         return t('مرحله گروهی', 'Group Stage');
        if (str_contains($r, 'round of 16') || str_contains($r, '16'))
                                               return t('دور شانزدهم', 'Round of 16');
        if (str_contains($r, 'quarter'))       return t('یک‌چهارم نهایی', 'Quarter Finals');
        if (str_contains($r, 'semi'))          return t('نیمه‌نهایی', 'Semi Finals');
        if (str_contains($r, 'third') || str_contains($r, '3rd'))
                                               return t('رده‌بندی سوم', 'Third Place');
        if (str_contains($r, 'final'))         return t('فینال', 'Final');
        return $round ?: t('گروهی', 'Group Stage');
    }

    // ==========================================
    // محاسبه سکه بعد از پایان بازی
    // ==========================================
    public function calculateCoins(int $matchId): void {
        $match = $this->db->query(
            "SELECT * FROM matches WHERE id=$matchId"
        )->fetch_assoc();

        if (!$match || $match['home_score'] === null) return;

        $coinsPerCorrect = (int)getSetting('coins_per_correct', '10');
        $preds = $this->db->query(
            "SELECT * FROM predictions WHERE match_id=$matchId AND is_correct IS NULL"
        );

        while ($p = $preds->fetch_assoc()) {
            $coins     = 0;
            $isCorrect = 0;

            if ((int)$p['home_score'] === (int)$match['home_score'] &&
                (int)$p['away_score'] === (int)$match['away_score']) {
                $isCorrect = 1;
                $coins     = $coinsPerCorrect;
            } elseif (
                ($p['home_score'] > $p['away_score'] && $match['home_score'] > $match['away_score']) ||
                ($p['home_score'] < $p['away_score'] && $match['home_score'] < $match['away_score']) ||
                ($p['home_score'] == $p['away_score'] && $match['home_score'] == $match['away_score'])
            ) {
                $isCorrect = 1;
                $coins     = intdiv($coinsPerCorrect, 2);
            }

            $pid = (int)$p['id'];
            $uid = (int)$p['user_id'];
            $this->db->query(
                "UPDATE predictions SET is_correct=$isCorrect, coins_earned=$coins WHERE id=$pid"
            );
            if ($coins > 0) {
                addCoins($uid, $coins, 'earned', "پیش‌بینی درست بازی #$matchId");
                sendNotification($uid,
                    '🎉 پیش‌بینی درست!', '🎉 Correct Prediction!',
                    "تبریک! {$coins} سکه گرفتی.",
                    "Congratulations! You earned {$coins} coins."
                );
            }
        }
    }

    // ==========================================
    // ترجمه نام تیم‌ها به فارسی
    // ==========================================
    private function translateTeamName(string $name): string {
        $map = [
            'Iran' => 'ایران', 'IR Iran' => 'ایران',
            'Brazil' => 'برزیل', 'Argentina' => 'آرژانتین',
            'France' => 'فرانسه', 'Germany' => 'آلمان',
            'Spain' => 'اسپانیا', 'Portugal' => 'پرتغال',
            'England' => 'انگلیس', 'Netherlands' => 'هلند',
            'Belgium' => 'بلژیک', 'Morocco' => 'مراکش',
            'Japan' => 'ژاپن', 'Mexico' => 'مکزیک',
            'USA' => 'آمریکا', 'United States' => 'آمریکا',
            'Canada' => 'کانادا', 'Australia' => 'استرالیا',
            'South Korea' => 'کره جنوبی', 'Korea Republic' => 'کره جنوبی',
            'Saudi Arabia' => 'عربستان سعودی', 'Qatar' => 'قطر',
            'Senegal' => 'سنگال', 'Ghana' => 'غنا',
            'Cameroon' => 'کامرون', 'Nigeria' => 'نیجریه',
            'Ecuador' => 'اکوادور', 'Uruguay' => 'اروگوئه',
            'Colombia' => 'کلمبیا', 'Chile' => 'شیلی',
            'Croatia' => 'کرواسی', 'Serbia' => 'صربستان',
            'Poland' => 'لهستان', 'Switzerland' => 'سوئیس',
            'Denmark' => 'دانمارک', 'Sweden' => 'سوئد',
            'Italy' => 'ایتالیا', 'Turkey' => 'ترکیه',
            'Ukraine' => 'اوکراین', 'Tunisia' => 'تونس',
            'Costa Rica' => 'کاستاریکا', 'Panama' => 'پاناما',
            'Honduras' => 'هندوراس', 'Venezuela' => 'ونزوئلا',
            'Bolivia' => 'بولیوی', 'Paraguay' => 'پاراگوئه',
            'New Zealand' => 'نیوزلند', 'Indonesia' => 'اندونزی',
            'Austria' => 'اتریش', 'Hungary' => 'مجارستان',
            'Romania' => 'رومانی', 'Greece' => 'یونان',
            'Slovakia' => 'اسلواکی', 'Norway' => 'نروژ',
            'Iceland' => 'ایسلند', 'Finland' => 'فنلاند',
            'Scotland' => 'اسکاتلند', 'Wales' => 'ولز',
            'Egypt' => 'مصر', 'Algeria' => 'الجزایر',
            'Mali' => 'مالی', 'Ivory Coast' => 'ساحل عاج',
        ];
        return $map[$name] ?? $name;
    }
}
