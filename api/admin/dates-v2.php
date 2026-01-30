<?php
// 새 버전 - 캐싱 우회용 (dates.php와 동일한 로직)
require_once dirname(__DIR__) . '/config.php';

// 인증 확인
if (!checkAuth()) {
    jsonResponse([
        'success' => false,
        'message' => '인증이 필요합니다.'
    ], 401);
}

// GET 요청 - 날짜 목록 조회
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        // 지역 파라미터 가져오기
        $region = isset($_GET['region']) ? $_GET['region'] : 'seoul';
        
        // 지역 접근 권한 확인
        if (!checkRegionAccess($region)) {
            jsonResponse([
                'success' => false,
                'message' => '해당 지역에 대한 접근 권한이 없습니다.'
            ], 403);
        }
        
        $datesFile = DATA_DIR . '/dates-' . $region . '.json';
        
        // 파일이 없으면 빈 배열 반환
        if (!file_exists($datesFile)) {
            $dates = [];
        } else {
            $dates = json_decode(file_get_contents($datesFile), true);
            if (!is_array($dates)) {
                $dates = [];
            }
        }
        
        jsonResponse([
            'success' => true,
            'data' => $dates
        ], 200);
        
    } catch (Exception $e) {
        error_log('날짜 목록 조회 오류: ' . $e->getMessage());
        jsonResponse([
            'success' => false,
            'message' => '날짜 목록을 불러올 수 없습니다.'
        ], 500);
    }
}

// POST 요청 - 날짜 목록 저장
elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        error_log('=== [V2] 날짜 저장 요청 수신 ===');
        error_log('Content-Type: ' . ($_SERVER['CONTENT_TYPE'] ?? 'not set'));
        
        // 지역 파라미터 가져오기
        $region = isset($_GET['region']) ? $_GET['region'] : 'seoul';
        error_log('Region: ' . $region);
        
        // 지역 접근 권한 확인
        if (!checkRegionAccess($region)) {
            error_log('❌ 권한 없음: ' . $region);
            jsonResponse([
                'success' => false,
                'message' => '해당 지역에 대한 접근 권한이 없습니다.'
            ], 403);
        }
        
        $dates = null;
        
        // 1. FormData 방식 확인 (우선순위)
        if (isset($_POST['dates']) && !empty($_POST['dates'])) {
            error_log('✅ [V2] FormData 방식 감지');
            error_log('$_POST[dates]: ' . substr($_POST['dates'], 0, 200));
            $dates = json_decode($_POST['dates'], true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                error_log('❌ [V2] FormData JSON 파싱 오류: ' . json_last_error_msg());
                jsonResponse([
                    'success' => false,
                    'message' => '[V2] FormData JSON 파싱 오류: ' . json_last_error_msg()
                ], 400);
            }
            error_log('✅ [V2] FormData 파싱 성공, 날짜 개수: ' . count($dates));
        }
        // 2. JSON 방식 확인
        else {
            $input = file_get_contents('php://input');
            error_log('✅ [V2] JSON 방식 시도');
            error_log('php://input 길이: ' . strlen($input));
            error_log('php://input 내용: ' . substr($input, 0, 200));
            
            if (empty($input)) {
                error_log('❌ [V2] 요청 본문이 비어있음');
                jsonResponse([
                    'success' => false,
                    'message' => '[V2] 요청 데이터가 없습니다.'
                ], 400);
            }
            
            $data = json_decode($input, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                error_log('❌ [V2] JSON 파싱 오류: ' . json_last_error_msg());
                jsonResponse([
                    'success' => false,
                    'message' => '[V2] JSON 파싱 오류: ' . json_last_error_msg()
                ], 400);
            }
            
            if (!isset($data['dates'])) {
                error_log('❌ [V2] dates 키가 없음. 받은 데이터: ' . json_encode($data));
                jsonResponse([
                    'success' => false,
                    'message' => '[V2] dates 필드가 필요합니다.'
                ], 400);
            }
            
            $dates = $data['dates'];
            error_log('✅ [V2] JSON 파싱 성공, 날짜 개수: ' . count($dates));
        }
        
        if (!is_array($dates)) {
            error_log('❌ [V2] dates가 배열이 아님: ' . gettype($dates));
            jsonResponse([
                'success' => false,
                'message' => '[V2] 날짜 목록은 배열이어야 합니다.'
            ], 400);
        }
        
        $datesFile = DATA_DIR . '/dates-' . $region . '.json';
        error_log('저장 경로: ' . $datesFile);
        
        // 디렉토리 확인 및 생성
        if (!is_dir(DATA_DIR)) {
            mkdir(DATA_DIR, 0755, true);
            error_log('✅ [V2] data 디렉토리 생성');
        }
        
        // 파일에 저장
        $jsonContent = json_encode($dates, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        $result = file_put_contents($datesFile, $jsonContent);
        
        if ($result === false) {
            error_log('❌ [V2] 파일 쓰기 실패: ' . $datesFile);
            jsonResponse([
                'success' => false,
                'message' => '[V2] 파일 저장에 실패했습니다. 권한을 확인해주세요.'
            ], 500);
        }
        
        error_log('✅ [V2] 날짜 저장 성공: ' . $result . ' bytes');
        
        jsonResponse([
            'success' => true,
            'message' => '✅ V2로 날짜 목록이 저장되었습니다!'
        ], 200);
        
    } catch (Exception $e) {
        error_log('❌ [V2] 날짜 목록 저장 예외: ' . $e->getMessage());
        error_log('Stack trace: ' . $e->getTraceAsString());
        jsonResponse([
            'success' => false,
            'message' => '[V2] 서버 오류: ' . $e->getMessage()
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
?>
