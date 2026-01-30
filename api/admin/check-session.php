<?php
require_once dirname(__DIR__) . '/config.php';

// GET 요청만 허용
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonResponse([
        'success' => false,
        'message' => 'GET 요청만 허용됩니다.'
    ], 405);
}

try {
    // 세션 확인
    if (checkAuth()) {
        jsonResponse([
            'success' => true,
            'username' => $_SESSION['admin_username'],
            'role' => $_SESSION['admin_role'] ?? 'regional',
            'regions' => $_SESSION['admin_regions'] ?? []
        ], 200);
    } else {
        jsonResponse([
            'success' => false,
            'message' => '로그인이 필요합니다.'
        ], 401);
    }
    
} catch (Exception $e) {
    error_log('세션 확인 오류: ' . $e->getMessage());
    jsonResponse([
        'success' => false,
        'message' => '서버 오류가 발생했습니다.'
    ], 500);
}

