<?php
require_once __DIR__ . '/config.php';

// GET 요청만 허용
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonResponse([
        'success' => false,
        'message' => 'GET 요청만 허용됩니다.'
    ], 405);
}

try {
    // 지역 파라미터 가져오기 (메인만 운영)
    $region = 'main'; // 메인으로 고정
    // $region = isset($_GET['region']) ? $_GET['region'] : 'busan'; // 주석처리
    $imageFile = DATA_DIR . '/images-' . $region . '.json';
    
    // 파일이 없으면 기본 파일 사용
    if (!file_exists($imageFile)) {
        $imageFile = IMAGES_FILE;
    }
    
    // 이미지 설정 파일 읽기
    if (!file_exists($imageFile)) {
        jsonResponse([
            'success' => true,
            'data' => [
                'pc' => [],
                'mobile' => []
            ]
        ], 200);
    }
    
    $images = json_decode(file_get_contents($imageFile), true);
    
    if (!is_array($images)) {
        $images = ['pc' => [], 'mobile' => []];
    }
    
    jsonResponse([
        'success' => true,
        'data' => $images
    ], 200);
    
} catch (Exception $e) {
    error_log('이미지 목록 조회 오류: ' . $e->getMessage());
    jsonResponse([
        'success' => false,
        'message' => '이미지 목록 조회 중 오류가 발생했습니다.'
    ], 500);
}

