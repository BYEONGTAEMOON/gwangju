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
    // 세션 삭제
    $_SESSION = [];
    
    // 세션 쿠키 삭제
    if (isset($_COOKIE[session_name()])) {
        setcookie(session_name(), '', time() - 3600, '/');
    }
    
    // 세션 파괴
    session_destroy();
    
    jsonResponse([
        'success' => true,
        'message' => '로그아웃 되었습니다.'
    ], 200);
    
} catch (Exception $e) {
    error_log('로그아웃 오류: ' . $e->getMessage());
    jsonResponse([
        'success' => false,
        'message' => '서버 오류가 발생했습니다.'
    ], 500);
}

