<?php
require_once dirname(__DIR__) . '/config.php';

// 인증 확인
if (!checkAuth()) {
    jsonResponse([
        'success' => false,
        'message' => '인증이 필요합니다.'
    ], 401);
}

// GET 요청 - 차단 목록 조회
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        $blocked = json_decode(file_get_contents(BLOCKED_FILE), true);
        
        if (!is_array($blocked)) {
            $blocked = [];
        }
        
        jsonResponse([
            'success' => true,
            'data' => $blocked
        ], 200);
        
    } catch (Exception $e) {
        error_log('차단 목록 조회 오류: ' . $e->getMessage());
        jsonResponse([
            'success' => false,
            'message' => '차단 목록을 불러올 수 없습니다.'
        ], 500);
    }
}

// POST 요청으로 DELETE 처리 (서버 WAF 호환)
elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['_method']) && $_POST['_method'] === 'DELETE') {
    try {
        error_log('=== 차단 해제 요청 (FormData 방식) ===');
        
        $id = isset($_POST['id']) ? trim($_POST['id']) : '';
        error_log('차단 해제 ID: ' . $id);
        
        if (empty($id)) {
            error_log('❌ ID 누락');
            jsonResponse([
                'success' => false,
                'message' => 'ID가 필요합니다.'
            ], 400);
        }
        
        // 기존 차단 목록 읽기
        $blocked = json_decode(file_get_contents(BLOCKED_FILE), true);
        
        if (!is_array($blocked)) {
            $blocked = [];
        }
        
        $beforeCount = count($blocked);
        
        // 해당 ID 제외하고 필터링
        $filteredBlocked = array_filter($blocked, function($item) use ($id) {
            return $item['id'] !== $id;
        });
        
        // 인덱스 재정렬
        $filteredBlocked = array_values($filteredBlocked);
        
        $afterCount = count($filteredBlocked);
        
        if ($beforeCount === $afterCount) {
            error_log('⚠️ 삭제할 항목을 찾지 못함');
        }
        
        // 파일에 저장
        file_put_contents(BLOCKED_FILE, json_encode($filteredBlocked, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        
        error_log('✅ 차단 해제 성공');
        
        jsonResponse([
            'success' => true,
            'message' => '차단이 해제되었습니다.'
        ], 200);
        
    } catch (Exception $e) {
        error_log('❌ 차단 해제 오류: ' . $e->getMessage());
        jsonResponse([
            'success' => false,
            'message' => '차단 해제 중 오류가 발생했습니다.'
        ], 500);
    }
}

// POST 요청 - 차단 추가
elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        error_log('=== 차단 추가 요청 ===');
        
        $phone = '';
        $reason = '';
        
        // FormData 방식 (우선)
        if (isset($_POST['phone'])) {
            $phone = trim($_POST['phone']);
            $reason = isset($_POST['reason']) ? trim($_POST['reason']) : '';
            error_log('✅ FormData 방식으로 데이터 수신');
        }
        // JSON 방식 (대체)
        else {
            $input = file_get_contents('php://input');
            if (!empty($input)) {
                $data = json_decode($input, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($data)) {
                    $phone = isset($data['phone']) ? trim($data['phone']) : '';
                    $reason = isset($data['reason']) ? trim($data['reason']) : '';
                    error_log('✅ JSON 방식으로 데이터 수신');
                }
            }
        }
        
        error_log('Phone: ' . $phone);
        error_log('Reason: ' . $reason);
        
        if (empty($phone)) {
            jsonResponse([
                'success' => false,
                'message' => '전화번호가 필요합니다.'
            ], 400);
        }
        
        // 기존 차단 목록 읽기
        $blocked = json_decode(file_get_contents(BLOCKED_FILE), true);
        
        if (!is_array($blocked)) {
            $blocked = [];
        }
        
        // 이미 차단된 번호인지 확인
        foreach ($blocked as $item) {
            if ($item['phone'] === $phone) {
                jsonResponse([
                    'success' => false,
                    'message' => '이미 차단된 전화번호입니다.'
                ], 400);
            }
        }
        
        // 새 차단 항목 추가
        $blocked[] = [
            'id' => generateUUID(),
            'phone' => $phone,
            'reason' => $reason,
            'createdAt' => date('c')
        ];
        
        // 파일에 저장
        file_put_contents(BLOCKED_FILE, json_encode($blocked, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        
        jsonResponse([
            'success' => true,
            'message' => '차단 목록에 추가되었습니다.'
        ], 200);
        
    } catch (Exception $e) {
        error_log('차단 추가 오류: ' . $e->getMessage());
        jsonResponse([
            'success' => false,
            'message' => '차단 추가 중 오류가 발생했습니다.'
        ], 500);
    }
}

// DELETE 요청 - 차단 해제
elseif ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    try {
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            jsonResponse([
                'success' => false,
                'message' => '잘못된 JSON 형식입니다.'
            ], 400);
        }
        
        $id = isset($data['id']) ? $data['id'] : '';
        
        if (empty($id)) {
            jsonResponse([
                'success' => false,
                'message' => 'ID가 필요합니다.'
            ], 400);
        }
        
        // 기존 차단 목록 읽기
        $blocked = json_decode(file_get_contents(BLOCKED_FILE), true);
        
        if (!is_array($blocked)) {
            $blocked = [];
        }
        
        // 해당 ID 제외하고 필터링
        $filteredBlocked = array_filter($blocked, function($item) use ($id) {
            return $item['id'] !== $id;
        });
        
        // 인덱스 재정렬
        $filteredBlocked = array_values($filteredBlocked);
        
        // 파일에 저장
        file_put_contents(BLOCKED_FILE, json_encode($filteredBlocked, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        
        jsonResponse([
            'success' => true,
            'message' => '차단이 해제되었습니다.'
        ], 200);
        
    } catch (Exception $e) {
        error_log('차단 해제 오류: ' . $e->getMessage());
        jsonResponse([
            'success' => false,
            'message' => '차단 해제 중 오류가 발생했습니다.'
        ], 500);
    }
}

// 지원하지 않는 메서드
else {
    jsonResponse([
        'success' => false,
        'message' => 'GET, POST, DELETE 요청만 허용됩니다.'
    ], 405);
}











