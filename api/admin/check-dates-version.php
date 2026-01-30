<?php
// 비밀번호 보호
$password = $_GET['pass'] ?? '';
if ($password !== 'check123') {
    die('Access Denied');
}

echo "<h1>📋 dates.php 파일 버전 확인</h1>";
echo "<hr>";

$datesPhpPath = __DIR__ . '/dates.php';

echo "<h2>파일 정보</h2>";
echo "<strong>경로:</strong> " . $datesPhpPath . "<br>";
echo "<strong>존재 여부:</strong> " . (file_exists($datesPhpPath) ? '✅ YES' : '❌ NO') . "<br>";

if (file_exists($datesPhpPath)) {
    echo "<strong>파일 크기:</strong> " . filesize($datesPhpPath) . " bytes<br>";
    echo "<strong>수정 시간:</strong> " . date('Y-m-d H:i:s', filemtime($datesPhpPath)) . "<br>";
    
    // 파일 내용 확인 (처음 500자)
    $content = file_get_contents($datesPhpPath);
    echo "<strong>파일 길이:</strong> " . strlen($content) . " bytes<br>";
    
    echo "<h2>버전 확인</h2>";
    
    // 새 버전 확인 (상세한 에러 메시지가 있는지)
    if (strpos($content, 'FormData JSON 파싱 오류') !== false) {
        echo "✅ <span style='color: green; font-weight: bold;'>새 버전 (2026-01-27)</span><br>";
        echo "→ FormData 지원 + 상세 로그 포함<br>";
    } elseif (strpos($content, '잘못된 JSON 형식입니다') !== false) {
        echo "❌ <span style='color: red; font-weight: bold;'>이전 버전 (구버전)</span><br>";
        echo "→ 이 파일을 교체해야 합니다!<br>";
    } else {
        echo "⚠️ <span style='color: orange;'>알 수 없는 버전</span><br>";
    }
    
    echo "<h2>파일 내용 미리보기 (처음 1000자)</h2>";
    echo "<pre style='background: #f5f5f5; padding: 15px; overflow: auto; max-height: 400px;'>";
    echo htmlspecialchars(substr($content, 0, 1000));
    echo "</pre>";
    
    // OPcache 상태 확인
    echo "<h2>PHP OPcache 상태</h2>";
    if (function_exists('opcache_get_status')) {
        $status = opcache_get_status();
        if ($status !== false) {
            echo "✅ OPcache 활성화됨<br>";
            echo "<strong>캐시된 스크립트 수:</strong> " . $status['opcache_statistics']['num_cached_scripts'] . "<br>";
            
            // OPcache 리셋 버튼
            if (isset($_GET['reset_cache']) && $_GET['reset_cache'] === 'yes') {
                if (function_exists('opcache_reset')) {
                    opcache_reset();
                    echo "<div style='background: #d4edda; padding: 10px; margin: 10px 0; border-radius: 5px;'>";
                    echo "✅ OPcache가 리셋되었습니다!";
                    echo "</div>";
                }
            } else {
                echo "<br><a href='?pass=check123&reset_cache=yes' style='display: inline-block; background: #dc834e; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin-top: 10px;'>🔄 OPcache 리셋하기</a>";
            }
        } else {
            echo "❌ OPcache가 비활성화되어 있습니다.<br>";
        }
    } else {
        echo "ℹ️ OPcache가 설치되어 있지 않습니다.<br>";
    }
} else {
    echo "<strong>❌ 파일이 없습니다!</strong>";
}

echo "<hr>";
echo "<h2>🔧 해결 방법</h2>";
echo "<ol>";
echo "<li>FTP로 접속해서 <code>/api/admin/dates.php</code> 파일을 다시 업로드하세요.</li>";
echo "<li>업로드 후 위의 '🔄 OPcache 리셋하기' 버튼을 클릭하세요.</li>";
echo "<li>브라우저 캐시를 삭제하고 다시 테스트하세요.</li>";
echo "</ol>";
?>
