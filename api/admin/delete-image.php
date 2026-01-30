<?php
require_once dirname(__DIR__) . '/config.php';

// 인증 확인
if (!checkAuth()) {
    jsonResponse([
        'success' => false,
        'message' => '인증이 필요합니다.'
    ], 401);
}

// POST 또는 DELETE 요청만 허용
if ($_SERVER['REQUEST_METHOD'] !== 'DELETE' && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse([
        'success' => false,
        'message' => 'DELETE 또는 POST 요청만 허용됩니다.'
    ], 405);
}

try {
    error_log('=== 이미지 삭제 요청 ===');
    
    $imagePath = '';
    $type = '';
    $region = 'seoul';
    
    // FormData 방식 (POST + _method=DELETE)
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['_method']) && $_POST['_method'] === 'DELETE') {
        error_log('✅ FormData 방식으로 삭제 요청');
        $imagePath = isset($_POST['path']) ? trim($_POST['path']) : '';
        $type = isset($_POST['type']) ? trim($_POST['type']) : '';
        $region = isset($_POST['region']) ? trim($_POST['region']) : 'seoul';
    }
    // JSON 방식 (DELETE 메서드)
    else {
        error_log('✅ JSON DELETE 방식');
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            jsonResponse([
                'success' => false,
                'message' => '잘못된 JSON 형식입니다.'
            ], 400);
        }
        
        $imagePath = isset($data['path']) ? $data['path'] : '';
        $type = isset($data['type']) ? $data['type'] : '';
        $region = isset($data['region']) ? $data['region'] : 'seoul';
    }
    
    error_log('Image Path: ' . $imagePath);
    error_log('Type: ' . $type);
    error_log('Region: ' . $region);
    
    // 지역 접근 권한 확인
    if (!checkRegionAccess($region)) {
        error_log('❌ 권한 없음: ' . $region);
        jsonResponse([
            'success' => false,
            'message' => '해당 지역에 대한 접근 권한이 없습니다.'
        ], 403);
    }
    
    if (empty($imagePath) || empty($type)) {
        error_log('❌ path 또는 type 누락');
        jsonResponse([
            'success' => false,
            'message' => 'path와 type이 필요합니다.'
        ], 400);
    }
    
    // 실제 파일 경로
    $realPath = dirname(dirname(__DIR__)) . $imagePath;
    
    // 파일이 존재하면 삭제
    if (file_exists($realPath)) {
        if (unlink($realPath)) {
            error_log('✅ 파일 삭제 성공: ' . $realPath);
        } else {
            error_log('❌ 파일 삭제 실패: ' . $realPath);
        }
    } else {
        error_log('⚠️ 파일이 존재하지 않음: ' . $realPath);
    }
    
    // 지역별 images.json에서 제거
    $imageFile = DATA_DIR . '/images-' . $region . '.json';
    
    if (!file_exists($imageFile)) {
        $imageFile = IMAGES_FILE;
    }
    
    $imagesConfig = json_decode(file_get_contents($imageFile), true);
    if (!is_array($imagesConfig)) {
        $imagesConfig = ['pc' => [], 'mobile' => []];
    }
    
    if (isset($imagesConfig[$type]) && is_array($imagesConfig[$type])) {
        $beforeCount = count($imagesConfig[$type]);
        $imagesConfig[$type] = array_values(array_filter($imagesConfig[$type], function($path) use ($imagePath) {
            return $path !== $imagePath;
        }));
        $afterCount = count($imagesConfig[$type]);
        error_log('이미지 목록에서 제거: ' . ($beforeCount - $afterCount) . '개');
    }
    
    // 저장
    file_put_contents($imageFile, json_encode($imagesConfig, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    error_log('✅ 이미지 설정 저장 완료');
    
    jsonResponse([
        'success' => true,
        'message' => '이미지가 삭제되었습니다.'
    ], 200);
    
} catch (Exception $e) {
    error_log('이미지 삭제 오류: ' . $e->getMessage());
    jsonResponse([
        'success' => false,
        'message' => '삭제 중 오류가 발생했습니다.'
    ], 500);
}

