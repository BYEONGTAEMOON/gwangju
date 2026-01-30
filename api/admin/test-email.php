<?php
require_once dirname(__DIR__) . '/config.php';

// 인증 확인
if (!checkAuth()) {
    jsonResponse([
        'success' => false,
        'message' => '인증이 필요합니다.'
    ], 401);
}

// POST 요청만 허용
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse([
        'success' => false,
        'message' => 'POST 요청만 허용됩니다.'
    ], 405);
}

try {
    // 지역 정보 가져오기
    $region = isset($_GET['region']) ? trim($_GET['region']) : 'seoul';
    
    error_log('=== 테스트 이메일 발송 시작 ===');
    error_log('Region: ' . $region);
    
    // 이메일 설정 확인
    if (!file_exists(EMAIL_SETTINGS_FILE)) {
        jsonResponse([
            'success' => false,
            'message' => '이메일 설정이 없습니다. 먼저 이메일 설정을 저장해주세요.'
        ], 400);
    }
    
    $settings = json_decode(file_get_contents(EMAIL_SETTINGS_FILE), true);
    
    if (!$settings || !isset($settings['enabled']) || !$settings['enabled']) {
        jsonResponse([
            'success' => false,
            'message' => '이메일 알림이 비활성화되어 있습니다.'
        ], 400);
    }
    
    if (empty($settings['email_to'])) {
        jsonResponse([
            'success' => false,
            'message' => '받는 사람 이메일이 설정되지 않았습니다.'
        ], 400);
    }
    
    // 테스트 신청 데이터 생성
    $testSubmission = [
        'name' => '홍길동 (테스트)',
        'phone' => '010-1234-5678',
        'date' => '테스트일자',
        'region' => $region,
        'agreement' => true,
        'createdAt' => date('c')
    ];
    
    // 이메일 전송 시도
    $result = sendEmailNotification($testSubmission);
    
    if ($result) {
        error_log('✅ 테스트 이메일 전송 성공');
        jsonResponse([
            'success' => true,
            'message' => '테스트 이메일이 발송되었습니다! 메일함을 확인해주세요.'
        ], 200);
    } else {
        error_log('❌ 테스트 이메일 전송 실패');
        jsonResponse([
            'success' => false,
            'message' => '테스트 이메일 발송에 실패했습니다. 서버 로그를 확인해주세요.'
        ], 500);
    }
    
} catch (Exception $e) {
    error_log('테스트 이메일 발송 중 오류: ' . $e->getMessage());
    jsonResponse([
        'success' => false,
        'message' => '서버 오류가 발생했습니다: ' . $e->getMessage()
    ], 500);
}
?>
