<?php
require_once __DIR__ . '/config.php';

// POST 요청만 허용
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse([
        'success' => false,
        'message' => 'POST 요청만 허용됩니다.'
    ], 405);
}

try {
    error_log('=== 신청 요청 수신 ===');
    error_log('Method: ' . $_SERVER['REQUEST_METHOD']);
    error_log('Content-Type: ' . ($_SERVER['CONTENT_TYPE'] ?? 'not set'));
    
    // FormData와 JSON 둘 다 지원
    $name = '';
    $phone = '';
    $date = '';
    $agreement = false;
    $region = 'main'; // 메인으로 고정 (지역별 운영 주석처리)
    
    // 방법 1: FormData 방식 (우선 - 서버 WAF 호환)
    if (isset($_POST['name']) && isset($_POST['phone'])) {
        $name = trim($_POST['name']);
        $phone = trim($_POST['phone']);
        $date = isset($_POST['date']) ? trim($_POST['date']) : '';
        $agreement = isset($_POST['agreement']) && ($_POST['agreement'] === 'true' || $_POST['agreement'] === 'on');
        $region = 'main'; // 메인으로 고정 (지역별 운영 주석처리)
        // $region = isset($_POST['region']) ? trim($_POST['region']) : 'seoul'; // 주석처리
        error_log('✅ FormData 방식으로 데이터 수신');
    }
    // 방법 2: JSON 방식 (대체)
    else {
        $input = file_get_contents('php://input');
        if (!empty($input)) {
            $data = json_decode($input, true);
            
            if (json_last_error() === JSON_ERROR_NONE && is_array($data)) {
                $name = isset($data['name']) ? trim($data['name']) : '';
                $phone = isset($data['phone']) ? trim($data['phone']) : '';
                $date = isset($data['date']) ? trim($data['date']) : '';
                $agreement = isset($data['agreement']) ? $data['agreement'] : false;
                $region = 'main'; // 메인으로 고정 (지역별 운영 주석처리)
                // $region = isset($data['region']) ? trim($data['region']) : 'seoul'; // 주석처리
                error_log('✅ JSON 방식으로 데이터 수신');
            }
        }
    }
    
    error_log('Name: ' . $name);
    error_log('Phone: ' . $phone);
    error_log('Date: ' . $date);
    error_log('Region: ' . $region);
    
    if (empty($name) || empty($phone) || empty($date) || !$agreement) {
        error_log('❌ 필수 필드 누락');
        jsonResponse([
            'success' => false,
            'message' => '모든 필드를 입력해주세요.'
        ], 400);
    }
    
    // 전화번호 형식 검증
    if (!preg_match('/^[0-9-]+$/', $phone)) {
        jsonResponse([
            'success' => false,
            'message' => '올바른 전화번호 형식이 아닙니다.'
        ], 400);
    }
    
    // 차단 목록 확인
    $blocked = json_decode(file_get_contents(BLOCKED_FILE), true);
    if (is_array($blocked)) {
        foreach ($blocked as $item) {
            if ($item['phone'] === $phone) {
                jsonResponse([
                    'success' => false,
                    'message' => '신청이 제한된 전화번호입니다.'
                ], 403);
            }
        }
    }
    
    // 지역별 파일 경로 설정
    $datesFile = DATA_DIR . '/dates-' . $region . '.json';
    $submissionsFile = DATA_DIR . '/submissions-' . $region . '.json';
    
    // 파일이 없으면 기본 파일 사용
    if (!file_exists($datesFile)) {
        $datesFile = DATES_FILE;
    }
    if (!file_exists($submissionsFile)) {
        $submissionsFile = SUBMISSIONS_FILE;
    }
    
    // 날짜 유효성 확인
    $availableDates = json_decode(file_get_contents($datesFile), true);
    if (is_array($availableDates)) {
        $isValidDate = false;
        foreach ($availableDates as $dateItem) {
            if (isset($dateItem['enabled']) && $dateItem['enabled'] === true && $dateItem['value'] === $date) {
                $isValidDate = true;
                break;
            }
        }
        if (!$isValidDate) {
            jsonResponse([
                'success' => false,
                'message' => '선택한 날짜는 현재 신청할 수 없습니다.'
            ], 400);
        }
    }
    
    // 기존 데이터 읽기
    $submissions = json_decode(file_get_contents($submissionsFile), true);
    if (!is_array($submissions)) {
        $submissions = [];
    }
    
    // 새 제출 데이터 생성
    $submission = [
        'id' => generateUUID(),
        'name' => $name,
        'phone' => $phone,
        'date' => $date,
        'region' => $region,
        'agreement' => $agreement,
        'createdAt' => date('c') // ISO 8601 형식
    ];
    
    // 데이터 추가
    $submissions[] = $submission;
    
    // 파일에 저장
    file_put_contents($submissionsFile, json_encode($submissions, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    
    error_log('✅ 신청 완료: ' . $name . ' / ' . $phone . ' / ' . $date . ' (' . $region . ')');
    
    // 이메일 알림 전송 시도 (실패해도 신청은 성공으로 처리)
    try {
        sendEmailNotification($submission);
    } catch (Exception $e) {
        error_log('이메일 알림 전송 실패: ' . $e->getMessage());
    }
    
    // 성공 응답
    jsonResponse([
        'success' => true,
        'message' => '신청이 완료되었습니다.',
        'data' => [
            'name' => $name,
            'phone' => $phone,
            'date' => $date,
            'region' => $region
        ]
    ], 200);
    
} catch (Exception $e) {
    error_log('신청 처리 중 오류: ' . $e->getMessage());
    jsonResponse([
        'success' => false,
        'message' => '서버 오류가 발생했습니다.'
    ], 500);
}

