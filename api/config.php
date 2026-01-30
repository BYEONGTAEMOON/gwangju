<?php
// 에러 로깅 활성화 (임시로 켜서 문제 확인)
error_reporting(E_ALL);
ini_set('display_errors', 0); // 화면에는 표시하지 않음
ini_set('log_errors', 1);
ini_set('error_log', dirname(__DIR__) . '/error.log');

// 세션 시작 (에러 처리 추가)
try {
    if (session_status() === PHP_SESSION_NONE) {
        // 세션 저장 경로 확인 및 설정
        $session_path = sys_get_temp_dir();
        if (!is_writable($session_path)) {
            // 프로젝트 내 세션 디렉토리 사용
            $session_path = dirname(__DIR__) . '/sessions';
            if (!file_exists($session_path)) {
                mkdir($session_path, 0755, true);
            }
            session_save_path($session_path);
        }
        
        session_start();
    }
} catch (Exception $e) {
    error_log('세션 시작 오류: ' . $e->getMessage());
    // JSON 응답으로 에러 반환
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'success' => false,
        'message' => '세션 초기화 실패: ' . $e->getMessage()
    ]);
    exit;
}

// CORS 헤더 설정
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json; charset=utf-8');

// OPTIONS 메서드 처리 (CORS preflight)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// 관리자 계정 정보 (실제 사용시 환경변수나 별도 파일로 관리)
// 각 관리자의 username => [password, regions, role]
// 메인만 운영 - 나머지 지역 관리자 주석처리
define('ADMINS', [
    'admin' => [
        'password' => 'admin1234',
        'regions' => ['main'], // 메인만 운영
        'role' => 'super'
    ],
    // 지역별 관리자 (주석처리 - 메인만 운영)
    /*
    'seoul_admin' => [
        'password' => 'seoul7391',
        'regions' => ['seoul'],
        'role' => 'regional'
    ],
    'incheon_admin' => [
        'password' => 'incheon4826',
        'regions' => ['incheon'],
        'role' => 'regional'
    ],
    'suwon_admin' => [
        'password' => 'suwon9154',
        'regions' => ['suwon'],
        'role' => 'regional'
    ],
    'daegu_admin' => [
        'password' => 'daegu2687',
        'regions' => ['daegu'],
        'role' => 'regional'
    ],
    'busan_admin' => [
        'password' => 'busan6049',
        'regions' => ['busan'],
        'role' => 'regional'
    ],
    'ulsan_admin' => [
        'password' => 'ulsan8572',
        'regions' => ['ulsan'],
        'role' => 'regional'
    ],
    'gwangju_admin' => [
        'password' => 'gwangju3918',
        'regions' => ['gwangju'],
        'role' => 'regional'
    ],
    'jeju_admin' => [
        'password' => 'jeju7465',
        'regions' => ['jeju'],
        'role' => 'regional'
    ]
    */
]);

// 데이터 파일 경로
define('DATA_DIR', dirname(__DIR__) . '/data');
define('SUBMISSIONS_FILE', DATA_DIR . '/submissions.json');
define('IMAGES_FILE', DATA_DIR . '/images.json');
define('DATES_FILE', DATA_DIR . '/dates.json');
define('BLOCKED_FILE', DATA_DIR . '/blocked.json');
define('EMAIL_SETTINGS_FILE', DATA_DIR . '/email-settings.json');

// 데이터 디렉토리 확인 및 생성
function ensureDataDir() {
    if (!file_exists(DATA_DIR)) {
        mkdir(DATA_DIR, 0755, true);
    }
    
    // submissions.json 파일 생성
    if (!file_exists(SUBMISSIONS_FILE)) {
        file_put_contents(SUBMISSIONS_FILE, json_encode([], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }
    
    // images.json 파일 생성 (기본값 포함)
    if (!file_exists(IMAGES_FILE)) {
        $defaultImages = [
            'pc' => [
                '/images/all/index_01.jpg',
                '/images/all/index_02.jpg',
                '/images/all/index_03.jpg',
                '/images/all/index_04.jpg',
                '/images/all/index_05.jpg',
                'https://www.youtube.com/embed/q9O3Pu4gPKM',
                '/images/all/index_06.jpg',
                '/images/all/index_07.jpg',
                '/images/all/index_08.jpg',
                '/images/all/index_09.jpg',
                '/images/all/index_10.jpg'
            ],
            'mobile' => [
                '/images/all/m_index_01.jpg',
                '/images/all/m_index_02.jpg',
                '/images/all/m_index_03.jpg',
                '/images/all/m_index_04.jpg',
                '/images/all/m_index_05.jpg',
                'https://www.youtube.com/embed/q9O3Pu4gPKM',
                '/images/all/m_index_06.jpg',
                '/images/all/m_index_07.jpg',
                '/images/all/m_index_08.jpg',
                '/images/all/m_index_09.jpg',
                '/images/all/m_index_10.jpg'
            ]
        ];
        file_put_contents(IMAGES_FILE, json_encode($defaultImages, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }
    
    // dates.json 파일 생성 (기본값 포함)
    if (!file_exists(DATES_FILE)) {
        $defaultDates = [
            ['value' => '1월3일(토)', 'enabled' => true],
            ['value' => '1월4일(일)', 'enabled' => true]
        ];
        file_put_contents(DATES_FILE, json_encode($defaultDates, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }
    
    // blocked.json 파일 생성
    if (!file_exists(BLOCKED_FILE)) {
        file_put_contents(BLOCKED_FILE, json_encode([], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }
    
    // 메인 페이지용 데이터 파일 생성
    $mainSubmissionsFile = DATA_DIR . '/submissions-main.json';
    $mainImagesFile = DATA_DIR . '/images-main.json';
    $mainDatesFile = DATA_DIR . '/dates-main.json';
    
    if (!file_exists($mainSubmissionsFile)) {
        file_put_contents($mainSubmissionsFile, json_encode([], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }
    
    if (!file_exists($mainImagesFile)) {
        $defaultImages = [
            'pc' => [],
            'mobile' => []
        ];
        file_put_contents($mainImagesFile, json_encode($defaultImages, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }
    
    if (!file_exists($mainDatesFile)) {
        $defaultDates = [
            ['value' => '1월3일(토)', 'enabled' => true],
            ['value' => '1월4일(일)', 'enabled' => true]
        ];
        file_put_contents($mainDatesFile, json_encode($defaultDates, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }
}

// 인증 확인 함수
function checkAuth() {
    return isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
}

// 지역 접근 권한 확인 함수
function checkRegionAccess($region) {
    if (!checkAuth()) {
        return false;
    }
    
    // 슈퍼 관리자는 모든 지역 접근 가능
    if (isset($_SESSION['admin_role']) && $_SESSION['admin_role'] === 'super') {
        return true;
    }
    
    // 지역 관리자는 할당된 지역만 접근 가능
    if (isset($_SESSION['admin_regions'])) {
        return in_array($region, $_SESSION['admin_regions']);
    }
    
    return false;
}

// JSON 응답 함수
function jsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

// UUID 생성 함수 (PHP 버전이 낮은 경우)
function generateUUID() {
    return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand(0, 0xffff), mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0x0fff) | 0x4000,
        mt_rand(0, 0x3fff) | 0x8000,
        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
    );
}

// 이메일 알림 전송 함수
function sendEmailNotification($submissionData) {
    try {
        error_log('=== 이메일 전송 시작 ===');
        
        if (!file_exists(EMAIL_SETTINGS_FILE)) {
            error_log('❌ 이메일 설정 파일이 없습니다: ' . EMAIL_SETTINGS_FILE);
            return false;
        }
        
        $settings = json_decode(file_get_contents(EMAIL_SETTINGS_FILE), true);
        error_log('이메일 설정: ' . json_encode($settings));
        
        if (!$settings || !isset($settings['enabled']) || !$settings['enabled']) {
            error_log('❌ 이메일 알림이 비활성화되어 있습니다.');
            return false;
        }
        
        if (empty($settings['email_to'])) {
            error_log('❌ 받는 사람 이메일이 설정되지 않았습니다.');
            return false;
        }
        
        // 지역 이름 매핑 (메인만 운영 - 나머지 지역 주석처리)
        $regionNames = [
            'main' => '메인'
            // 지역별 매핑 (주석처리)
            /*
            'seoul' => '서울',
            'incheon' => '인천',
            'suwon' => '수원',
            'daegu' => '대구',
            'busan' => '부산',
            'ulsan' => '울산',
            'gwangju' => '광주',
            'jeju' => '제주'
            */
        ];
        $regionName = $regionNames[$submissionData['region']] ?? $submissionData['region'];
        
        // 이메일 제목 (UTF-8 인코딩)
        $subject = $settings['email_subject'] ?? '[허니문박람회] 새로운 신청이 접수되었습니다';
        $subject = '=?UTF-8?B?' . base64_encode($subject) . '?=';
        
        // 이메일 내용 구성
        $message = "━━━━━━━━━━━━━━━━━━━━━━━━━\n";
        $message .= "📝 신청 정보\n";
        $message .= "━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";
        $message .= "👤 성함: {$submissionData['name']}\n";
        $message .= "📞 연락처: {$submissionData['phone']}\n";
        $message .= "📅 참가일자: {$submissionData['date']}\n";
        $message .= "🌏 지역: {$regionName}\n";
        $message .= "⏰ 신청시간: {$submissionData['createdAt']}\n\n";
        $message .= "━━━━━━━━━━━━━━━━━━━━━━━━━\n";
        $message .= "관리자 페이지에서 확인하기:\n";
        $message .= "http://honeyfair.co.kr/admin.html\n";
        $message .= "━━━━━━━━━━━━━━━━━━━━━━━━━\n";
        
        // 헤더 설정 (개선)
        $headers = array();
        $headers[] = "From: 허니문박람회 <noreply@honeyfair.co.kr>";
        $headers[] = "Reply-To: noreply@honeyfair.co.kr";
        $headers[] = "Content-Type: text/plain; charset=UTF-8";
        $headers[] = "Content-Transfer-Encoding: 8bit";
        $headers[] = "X-Mailer: PHP/" . phpversion();
        $headers[] = "MIME-Version: 1.0";
        
        // 여러 이메일 주소 처리
        $emails = array_map('trim', explode(',', $settings['email_to']));
        $successCount = 0;
        $failCount = 0;
        
        foreach ($emails as $email) {
            if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                error_log("이메일 발송 시도: {$email}");
                
                // mail() 함수 호출
                $result = @mail($email, $subject, $message, implode("\r\n", $headers));
                
                if ($result) {
                    error_log("✅ 이메일 전송 성공: {$email}");
                    $successCount++;
                } else {
                    error_log("❌ 이메일 전송 실패: {$email}");
                    $failCount++;
                }
            } else {
                error_log("❌ 잘못된 이메일 형식: {$email}");
                $failCount++;
            }
        }
        
        error_log("이메일 발송 결과: 성공 {$successCount}건, 실패 {$failCount}건");
        
        return $successCount > 0;
    } catch (Exception $e) {
        error_log('❌ 이메일 전송 중 예외 발생: ' . $e->getMessage());
        return false;
    }
}

// 초기화
ensureDataDir();

