<?php
require_once dirname(__DIR__) . '/config.php';

// POST 요청만 허용
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse([
        'success' => false,
        'message' => 'POST 요청만 허용됩니다.'
    ], 405);
}

try {
    // FormData와 JSON 둘 다 지원
    $username = '';
    $password = '';
    
    error_log('=== 로그인 요청 수신 ===');
    error_log('Request Method: ' . $_SERVER['REQUEST_METHOD']);
    error_log('Content-Type: ' . ($_SERVER['CONTENT_TYPE'] ?? 'not set'));
    
    // 방법 1: FormData 방식 (우선 - 서버 WAF 호환)
    if (isset($_POST['username']) && isset($_POST['password'])) {
        $username = trim($_POST['username']);
        $password = trim($_POST['password']);
        error_log('✅ FormData 방식으로 데이터 수신');
        error_log('Username: ' . $username);
        error_log('Password length: ' . strlen($password));
    } 
    // 방법 2: JSON 방식 (대체)
    else {
        $input = file_get_contents('php://input');
        error_log('php://input length: ' . strlen($input));
        
        if (!empty($input)) {
            $data = json_decode($input, true);
            
            if (json_last_error() === JSON_ERROR_NONE && is_array($data)) {
                if (isset($data['username']) && isset($data['password'])) {
                    $username = trim($data['username']);
                    $password = trim($data['password']);
                    error_log('✅ JSON 방식으로 데이터 수신');
                    error_log('Username: ' . $username);
                    error_log('Password length: ' . strlen($password));
                }
            } else {
                error_log('❌ JSON 파싱 실패: ' . json_last_error_msg());
            }
        }
    }
    
    // 데이터 검증
    if (empty($username) || empty($password)) {
        error_log('❌ 아이디 또는 비밀번호 누락');
        jsonResponse([
            'success' => false,
            'message' => '아이디와 비밀번호를 입력해주세요.'
        ], 400);
    }
    
    // 관리자 목록에서 사용자 확인
    $admins = ADMINS;
    
    if (isset($admins[$username])) {
        $admin = $admins[$username];
        
        // 비밀번호 확인
        if ($password === $admin['password']) {
            // 세션에 로그인 정보 및 권한 저장
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_username'] = $username;
            $_SESSION['admin_role'] = $admin['role'];
            $_SESSION['admin_regions'] = $admin['regions'];
            $_SESSION['login_time'] = time();
            
            error_log('✅ 로그인 성공: ' . $username . ' (Role: ' . $admin['role'] . ')');
            
            jsonResponse([
                'success' => true,
                'message' => '로그인 성공',
                'role' => $admin['role'],
                'regions' => $admin['regions']
            ], 200);
        } else {
            error_log('❌ 비밀번호 불일치: ' . $username);
            
            jsonResponse([
                'success' => false,
                'message' => '아이디 또는 비밀번호가 올바르지 않습니다.'
            ], 401);
        }
    } else {
        error_log('❌ 존재하지 않는 사용자: ' . $username);
        
        jsonResponse([
            'success' => false,
            'message' => '아이디 또는 비밀번호가 올바르지 않습니다.'
        ], 401);
    }
    
} catch (Exception $e) {
    error_log('❌ 로그인 예외 발생: ' . $e->getMessage());
    
    jsonResponse([
        'success' => false,
        'message' => '서버 오류가 발생했습니다.'
    ], 500);
}

