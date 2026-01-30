<?php
require_once dirname(__DIR__) . '/config.php';

// 인증 확인
if (!checkAuth()) {
    jsonResponse([
        'success' => false,
        'message' => '인증이 필요합니다.'
    ], 401);
}

// GET 요청: 이메일 설정 불러오기
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        if (!file_exists(EMAIL_SETTINGS_FILE)) {
            // 기본 설정 생성
            $defaultSettings = [
                'enabled' => false,
                'email_to' => '',
                'email_subject' => '[허니문박람회] 새로운 신청이 접수되었습니다'
            ];
            file_put_contents(EMAIL_SETTINGS_FILE, json_encode($defaultSettings, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        }
        
        $settings = json_decode(file_get_contents(EMAIL_SETTINGS_FILE), true);
        
        jsonResponse([
            'success' => true,
            'data' => $settings
        ], 200);
    } catch (Exception $e) {
        error_log('이메일 설정 로드 오류: ' . $e->getMessage());
        jsonResponse([
            'success' => false,
            'message' => '이메일 설정을 불러오는 중 오류가 발생했습니다.'
        ], 500);
    }
}

// POST 요청: 이메일 설정 저장
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // FormData와 JSON 둘 다 지원
        $enabled = false;
        $email_to = '';
        $email_subject = '';
        
        if (isset($_POST['enabled'])) {
            $enabled = $_POST['enabled'] === 'true' || $_POST['enabled'] === '1';
            $email_to = isset($_POST['email_to']) ? trim($_POST['email_to']) : '';
            $email_subject = isset($_POST['email_subject']) ? trim($_POST['email_subject']) : '[허니문박람회] 새로운 신청이 접수되었습니다';
        } else {
            $input = file_get_contents('php://input');
            if (!empty($input)) {
                $data = json_decode($input, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($data)) {
                    $enabled = isset($data['enabled']) ? (bool)$data['enabled'] : false;
                    $email_to = isset($data['email_to']) ? trim($data['email_to']) : '';
                    $email_subject = isset($data['email_subject']) ? trim($data['email_subject']) : '[허니문박람회] 새로운 신청이 접수되었습니다';
                }
            }
        }
        
        // 이메일 형식 검증 (활성화된 경우)
        if ($enabled && !empty($email_to)) {
            $emails = array_map('trim', explode(',', $email_to));
            foreach ($emails as $email) {
                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    jsonResponse([
                        'success' => false,
                        'message' => "잘못된 이메일 형식입니다: {$email}"
                    ], 400);
                }
            }
        }
        
        $settings = [
            'enabled' => $enabled,
            'email_to' => $email_to,
            'email_subject' => $email_subject
        ];
        
        file_put_contents(EMAIL_SETTINGS_FILE, json_encode($settings, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        
        error_log('✅ 이메일 설정 저장 완료');
        
        jsonResponse([
            'success' => true,
            'message' => '이메일 설정이 저장되었습니다.',
            'data' => $settings
        ], 200);
    } catch (Exception $e) {
        error_log('이메일 설정 저장 오류: ' . $e->getMessage());
        jsonResponse([
            'success' => false,
            'message' => '이메일 설정 저장 중 오류가 발생했습니다.'
        ], 500);
    }
}

// 다른 메서드는 허용하지 않음
jsonResponse([
    'success' => false,
    'message' => 'GET 또는 POST 요청만 허용됩니다.'
], 405);
?>
