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
    // 파일 업로드 확인
    if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
        jsonResponse([
            'success' => false,
            'message' => '파일 업로드에 실패했습니다.'
        ], 400);
    }
    
    $file = $_FILES['image'];
    $type = isset($_POST['type']) ? $_POST['type'] : 'pc'; // pc 또는 mobile
    $region = isset($_POST['region']) ? $_POST['region'] : 'busan'; // 지역 정보
    
    // 지역 접근 권한 확인
    if (!checkRegionAccess($region)) {
        jsonResponse([
            'success' => false,
            'message' => '해당 지역에 대한 접근 권한이 없습니다.'
        ], 403);
    }
    
    // 파일 타입 검증 (이미지만 허용)
    $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
    $fileType = mime_content_type($file['tmp_name']);
    
    if (!in_array($fileType, $allowedTypes)) {
        jsonResponse([
            'success' => false,
            'message' => '이미지 파일만 업로드 가능합니다. (jpg, png, gif, webp)'
        ], 400);
    }
    
    // 파일 크기 검증 (5MB 제한)
    $maxFileSize = 5 * 1024 * 1024; // 5MB
    if ($file['size'] > $maxFileSize) {
        jsonResponse([
            'success' => false,
            'message' => '파일 크기는 5MB 이하여야 합니다.'
        ], 400);
    }
    
    // 업로드 디렉토리 설정 (지역별)
    $uploadDir = dirname(dirname(__DIR__)) . '/images/' . $region . '/';
    
    // 디렉토리가 없으면 생성
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    // 파일명 생성 (중복 방지)
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $prefix = $type === 'mobile' ? 'm_' : '';
    $filename = $prefix . uniqid() . '_' . time() . '.' . $extension;
    $filePath = $uploadDir . $filename;
    
    // 파일 이동
    if (!move_uploaded_file($file['tmp_name'], $filePath)) {
        jsonResponse([
            'success' => false,
            'message' => '파일 저장에 실패했습니다.'
        ], 500);
    }
    
    // 웹 경로 생성
    $webPath = '/images/' . $region . '/' . $filename;
    
    // 지역별 images.json 파일 경로
    $imageFile = DATA_DIR . '/images-' . $region . '.json';
    
    // 파일이 없으면 생성
    if (!file_exists($imageFile)) {
        $imageFile = IMAGES_FILE;
    }
    
    // images.json 파일에 자동 추가
    $imagesConfig = json_decode(file_get_contents($imageFile), true);
    if (!is_array($imagesConfig)) {
        $imagesConfig = ['pc' => [], 'mobile' => []];
    }
    
    if (!isset($imagesConfig[$type])) {
        $imagesConfig[$type] = [];
    }
    
    // 이미지 경로 추가
    $imagesConfig[$type][] = $webPath;
    
    // 저장
    file_put_contents($imageFile, json_encode($imagesConfig, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    
    jsonResponse([
        'success' => true,
        'message' => '이미지가 업로드되었습니다.',
        'data' => [
            'filename' => $filename,
            'path' => $webPath,
            'type' => $type
        ]
    ], 200);
    
} catch (Exception $e) {
    error_log('이미지 업로드 오류: ' . $e->getMessage());
    jsonResponse([
        'success' => false,
        'message' => '업로드 중 오류가 발생했습니다.'
    ], 500);
}

