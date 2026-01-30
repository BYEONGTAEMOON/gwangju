<?php
// 에러 로그 뷰어 (보안을 위해 실제 운영 시 삭제하거나 비밀번호 보호 필요)
$password = 'view123'; // 간단한 비밀번호

if (!isset($_GET['pass']) || $_GET['pass'] !== $password) {
    die('Access denied. Use ?pass=view123');
}

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>에러 로그 뷰어</title>
    <style>
        body { font-family: monospace; margin: 20px; background: #1e1e1e; color: #d4d4d4; }
        h1 { color: #dc834e; }
        pre { background: #2d2d2d; padding: 15px; border-radius: 5px; overflow-x: auto; line-height: 1.5; }
        .error { color: #f48771; }
        .warning { color: #dcdcaa; }
        .info { color: #4fc1ff; }
        button { padding: 10px 20px; background: #dc834e; color: white; border: none; cursor: pointer; margin: 10px 5px; border-radius: 5px; }
        button:hover { background: #c67341; }
        .timestamp { color: #608b4e; }
    </style>
</head>
<body>
    <h1>🔍 에러 로그 뷰어</h1>
    <button onclick="location.reload()">새로고침</button>
    <button onclick="window.open('/api/test.php', '_blank')">서버 테스트</button>
    <button onclick="window.open('/api/admin/login-simple.php', '_blank')">간단 로그인</button>
    
    <h2>📋 Error Log</h2>
    <?php
    $log_file = dirname(__DIR__) . '/error.log';
    
    if (file_exists($log_file)) {
        $log_content = file_get_contents($log_file);
        
        if (empty($log_content)) {
            echo "<p style='color: #608b4e;'>✅ 로그가 비어있습니다 (에러 없음)</p>";
        } else {
            // 로그 하이라이팅
            $log_content = htmlspecialchars($log_content);
            $log_content = preg_replace('/\[(\d{2}-\w{3}-\d{4}[^\]]+)\]/', '<span class="timestamp">[$1]</span>', $log_content);
            $log_content = preg_replace('/(ERROR|error|Error)/i', '<span class="error">$1</span>', $log_content);
            $log_content = preg_replace('/(WARNING|warning|Warning)/i', '<span class="warning">$1</span>', $log_content);
            $log_content = preg_replace('/(===.*===)/', '<span class="info">$1</span>', $log_content);
            
            // 최근 50줄만 표시
            $lines = explode("\n", $log_content);
            $recent_lines = array_slice($lines, -100); // 최근 100줄
            
            echo "<pre>" . implode("\n", $recent_lines) . "</pre>";
            echo "<p style='color: #608b4e;'>총 " . count($lines) . "줄 (최근 100줄 표시)</p>";
        }
        
        echo "<p><strong>로그 파일 경로:</strong> " . htmlspecialchars($log_file) . "</p>";
        echo "<p><strong>파일 크기:</strong> " . number_format(filesize($log_file)) . " bytes</p>";
        echo "<p><strong>마지막 수정:</strong> " . date('Y-m-d H:i:s', filemtime($log_file)) . "</p>";
    } else {
        echo "<p style='color: #f48771;'>❌ 로그 파일이 없습니다: " . htmlspecialchars($log_file) . "</p>";
        echo "<p>에러 로그가 아직 생성되지 않았거나, 경로 설정에 문제가 있을 수 있습니다.</p>";
    }
    ?>
    
    <h2>📁 PHP Info</h2>
    <pre><?php
    echo "PHP Version: " . phpversion() . "\n";
    echo "Session Save Path: " . session_save_path() . "\n";
    echo "Session Save Path Writable: " . (is_writable(session_save_path()) ? 'YES' : 'NO') . "\n";
    echo "allow_url_fopen: " . (ini_get('allow_url_fopen') ? 'ON' : 'OFF') . "\n";
    echo "error_log: " . ini_get('error_log') . "\n";
    echo "log_errors: " . (ini_get('log_errors') ? 'ON' : 'OFF') . "\n";
    echo "display_errors: " . (ini_get('display_errors') ? 'ON' : 'OFF') . "\n";
    ?></pre>
</body>
</html>
