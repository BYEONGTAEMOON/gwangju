<?php
require_once dirname(__DIR__) . '/config.php';

// 인증 확인
if (!checkAuth()) {
    jsonResponse([
        'success' => false,
        'message' => '인증이 필요합니다.'
    ], 401);
}

// GET 요청 - 신청 내역 조회
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
        
        $submissionsFile = DATA_DIR . '/submissions-' . $region . '.json';
        
        // 파일이 없으면 기본 파일 사용
        if (!file_exists($submissionsFile)) {
            $submissionsFile = SUBMISSIONS_FILE;
        }
        
        $submissions = json_decode(file_get_contents($submissionsFile), true);
        
        if (!is_array($submissions)) {
            $submissions = [];
        }
        
        // 최신순으로 정렬 (역순)
        $submissions = array_reverse($submissions);
        
        jsonResponse([
            'success' => true,
            'data' => $submissions
        ], 200);
        
    } catch (Exception $e) {
        error_log('신청 내역 조회 오류: ' . $e->getMessage());
        jsonResponse([
            'success' => false,
            'message' => '데이터 조회 중 오류가 발생했습니다.'
        ], 500);
    }
}

// POST 요청으로 DELETE 처리 (서버 WAF 호환)
elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['_method']) && $_POST['_method'] === 'DELETE') {
    try {
        error_log('=== 신청 내역 삭제 요청 (FormData 방식) ===');
        
        $id = isset($_POST['id']) ? trim($_POST['id']) : '';
        $region = 'main'; // 메인으로 고정 (지역별 운영 주석처리)
        // $region = isset($_GET['region']) ? $_GET['region'] : 'seoul'; // 주석처리
        
        error_log('삭제 ID: ' . $id);
        error_log('Region: ' . $region);
        
        // 지역 접근 권한 확인
        if (!checkRegionAccess($region)) {
            error_log('❌ 권한 없음: ' . $region);
            jsonResponse([
                'success' => false,
                'message' => '해당 지역에 대한 접근 권한이 없습니다.'
            ], 403);
        }
        
        if (empty($id)) {
            error_log('❌ ID 누락');
            jsonResponse([
                'success' => false,
                'message' => 'ID가 필요합니다.'
            ], 400);
        }
        
        $submissionsFile = DATA_DIR . '/submissions-' . $region . '.json';
        error_log('파일 경로: ' . $submissionsFile);
        
        // 파일이 없으면 빈 배열
        if (!file_exists($submissionsFile)) {
            error_log('❌ 파일이 존재하지 않음');
            jsonResponse([
                'success' => false,
                'message' => '파일이 존재하지 않습니다.'
            ], 404);
        }
        
        // 기존 데이터 읽기
        $submissions = json_decode(file_get_contents($submissionsFile), true);
        
        if (!is_array($submissions)) {
            $submissions = [];
        }
        
        $beforeCount = count($submissions);
        error_log('삭제 전 개수: ' . $beforeCount);
        
        // 해당 ID 제외하고 필터링
        $filteredSubmissions = array_filter($submissions, function($item) use ($id) {
            return $item['id'] !== $id;
        });
        
        // 인덱스 재정렬
        $filteredSubmissions = array_values($filteredSubmissions);
        
        $afterCount = count($filteredSubmissions);
        error_log('삭제 후 개수: ' . $afterCount);
        
        if ($beforeCount === $afterCount) {
            error_log('⚠️ 삭제할 항목을 찾지 못함');
            jsonResponse([
                'success' => false,
                'message' => '삭제할 항목을 찾을 수 없습니다.'
            ], 404);
        }
        
        // 파일에 저장
        file_put_contents($submissionsFile, json_encode($filteredSubmissions, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        
        error_log('✅ 삭제 성공');
        
        jsonResponse([
            'success' => true,
            'message' => '삭제되었습니다.'
        ], 200);
        
    } catch (Exception $e) {
        error_log('❌ 신청 내역 삭제 오류: ' . $e->getMessage());
        jsonResponse([
            'success' => false,
            'message' => '삭제 중 오류가 발생했습니다.'
        ], 500);
    }
}

// 기존 DELETE 메서드 지원 (하위 호환성)
elseif ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    try {
        // JSON 데이터 받기
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            jsonResponse([
                'success' => false,
                'message' => '잘못된 JSON 형식입니다.'
            ], 400);
        }
        
        $id = isset($data['id']) ? $data['id'] : '';
        $region = 'main'; // 메인으로 고정 (지역별 운영 주석처리)
        // $region = isset($_GET['region']) ? $_GET['region'] : 'seoul'; // 주석처리
        
        // 지역 접근 권한 확인
        if (!checkRegionAccess($region)) {
            jsonResponse([
                'success' => false,
                'message' => '해당 지역에 대한 접근 권한이 없습니다.'
            ], 403);
        }
        
        if (empty($id)) {
            jsonResponse([
                'success' => false,
                'message' => 'ID가 필요합니다.'
            ], 400);
        }
        
        $submissionsFile = DATA_DIR . '/submissions-' . $region . '.json';
        
        // 파일이 없으면 기본 파일 사용
        if (!file_exists($submissionsFile)) {
            $submissionsFile = SUBMISSIONS_FILE;
        }
        
        // 기존 데이터 읽기
        $submissions = json_decode(file_get_contents($submissionsFile), true);
        
        if (!is_array($submissions)) {
            $submissions = [];
        }
        
        // 해당 ID 제외하고 필터링
        $filteredSubmissions = array_filter($submissions, function($item) use ($id) {
            return $item['id'] !== $id;
        });
        
        // 인덱스 재정렬
        $filteredSubmissions = array_values($filteredSubmissions);
        
        // 파일에 저장
        file_put_contents($submissionsFile, json_encode($filteredSubmissions, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        
        jsonResponse([
            'success' => true,
            'message' => '삭제되었습니다.'
        ], 200);
        
    } catch (Exception $e) {
        error_log('신청 내역 삭제 오류: ' . $e->getMessage());
        jsonResponse([
            'success' => false,
            'message' => '삭제 중 오류가 발생했습니다.'
        ], 500);
    }
}

// 지원하지 않는 메서드
else {
    jsonResponse([
        'success' => false,
        'message' => 'GET 또는 DELETE 요청만 허용됩니다.'
    ], 405);
}

