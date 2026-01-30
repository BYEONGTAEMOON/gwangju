<?php
require_once dirname(__DIR__) . '/config.php';

// 인증 확인
if (!checkAuth()) {
    jsonResponse([
        'success' => false,
        'message' => '인증이 필요합니다.'
    ], 401);
}

// GET 요청 - 이미지 설정 조회
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        // 지역 파라미터 가져오기 (메인만 운영)
        $region = 'main'; // 메인으로 고정
        // $region = isset($_GET['region']) ? $_GET['region'] : 'busan'; // 주석처리
        
        // 지역 접근 권한 확인
        if (!checkRegionAccess($region)) {
            jsonResponse([
                'success' => false,
                'message' => '해당 지역에 대한 접근 권한이 없습니다.'
            ], 403);
        }
        
        $imageFile = DATA_DIR . '/images-' . $region . '.json';
        
        // 파일이 없으면 기본 파일 사용
        if (!file_exists($imageFile)) {
            $imageFile = IMAGES_FILE;
        }
        
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
        error_log('이미지 설정 조회 오류: ' . $e->getMessage());
        jsonResponse([
            'success' => false,
            'message' => '조회 중 오류가 발생했습니다.'
        ], 500);
    }
}

// POST 요청 - 이미지 설정 저장
elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        error_log('=== 이미지 설정 저장 요청 ===');
        
        $pc = [];
        $mobile = [];
        $region = 'main'; // 메인으로 고정 (지역별 운영 주석처리)
        // $region = isset($_GET['region']) ? $_GET['region'] : 'seoul'; // 주석처리
        
        error_log('Region: ' . $region);
        
        // FormData 방식 (우선)
        if (isset($_POST['pc']) && isset($_POST['mobile'])) {
            error_log('✅ FormData 방식으로 데이터 수신');
            $pc = json_decode($_POST['pc'], true);
            $mobile = json_decode($_POST['mobile'], true);
            
            if (!is_array($pc)) $pc = [];
            if (!is_array($mobile)) $mobile = [];
            
            error_log('PC 이미지: ' . count($pc) . '개');
            error_log('Mobile 이미지: ' . count($mobile) . '개');
        }
        // JSON 방식 (대체)
        else {
            error_log('✅ JSON 방식 시도');
            $input = file_get_contents('php://input');
            $data = json_decode($input, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                error_log('❌ JSON 파싱 오류: ' . json_last_error_msg());
                jsonResponse([
                    'success' => false,
                    'message' => 'JSON 파싱 오류: ' . json_last_error_msg()
                ], 400);
            }
            
            $pc = isset($data['pc']) ? $data['pc'] : [];
            $mobile = isset($data['mobile']) ? $data['mobile'] : [];
        }
        
        // 지역 접근 권한 확인
        if (!checkRegionAccess($region)) {
            error_log('❌ 권한 없음: ' . $region);
            jsonResponse([
                'success' => false,
                'message' => '해당 지역에 대한 접근 권한이 없습니다.'
            ], 403);
        }
        
        // 배열 타입 확인
        if (!is_array($pc)) {
            $pc = [];
        }
        if (!is_array($mobile)) {
            $mobile = [];
        }
        
        $imageFile = DATA_DIR . '/images-' . $region . '.json';
        error_log('저장 경로: ' . $imageFile);
        
        // 파일이 없으면 기본 파일 사용
        if (!file_exists($imageFile)) {
            $imageFile = IMAGES_FILE;
        }
        
        $config = [
            'pc' => $pc,
            'mobile' => $mobile
        ];
        
        // 파일에 저장
        file_put_contents($imageFile, json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        
        jsonResponse([
            'success' => true,
            'message' => '이미지 설정이 저장되었습니다.'
        ], 200);
        
    } catch (Exception $e) {
        error_log('이미지 설정 저장 오류: ' . $e->getMessage());
        jsonResponse([
            'success' => false,
            'message' => '저장 중 오류가 발생했습니다.'
        ], 500);
    }
}

// 지원하지 않는 메서드
else {
    jsonResponse([
        'success' => false,
        'message' => 'GET 또는 POST 요청만 허용됩니다.'
    ], 405);
}

