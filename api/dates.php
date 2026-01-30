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
    $datesFile = DATA_DIR . '/dates-' . $region . '.json';
    
    // 파일이 없으면 기본 파일 사용
    if (!file_exists($datesFile)) {
        $datesFile = DATES_FILE;
    }
    
    $dates = json_decode(file_get_contents($datesFile), true);
    
    if (!is_array($dates)) {
        $dates = [];
    }
    
    // 활성화된 날짜만 필터링
    $enabledDates = array_filter($dates, function($date) {
        return isset($date['enabled']) && $date['enabled'] === true;
    });
    
    // value만 추출
    $dateValues = array_map(function($date) {
        return $date['value'];
    }, $enabledDates);
    
    // 인덱스 재정렬
    $dateValues = array_values($dateValues);
    
    jsonResponse([
        'success' => true,
        'data' => $dateValues
    ], 200);
    
} catch (Exception $e) {
    error_log('날짜 목록 조회 오류: ' . $e->getMessage());
    jsonResponse([
        'success' => false,
        'message' => '날짜 목록을 불러올 수 없습니다.'
    ], 500);
}




