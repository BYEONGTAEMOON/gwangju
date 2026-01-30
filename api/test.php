<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: text/html; charset=utf-8');

echo "<h1>서버 환경 테스트</h1>";
echo "<strong>PHP Version:</strong> " . phpversion() . "<br>";
echo "<strong>Session Path:</strong> " . session_save_path() . "<br>";
echo "<strong>Session Path Writable:</strong> " . (is_writable(session_save_path()) ? 'YES' : 'NO') . "<br>";

try {
    session_start();
    echo "<strong>Session Started:</strong> YES<br>";
    echo "<strong>Session ID:</strong> " . session_id() . "<br>";
} catch (Exception $e) {
    echo "<strong style='color:red'>Session Error:</strong> " . $e->getMessage() . "<br>";
}

$config_path = __DIR__ . '/config.php';
echo "<strong>Config Path:</strong> " . $config_path . "<br>";
echo "<strong>Config Exists:</strong> " . (file_exists($config_path) ? 'YES' : 'NO') . "<br>";

if (file_exists($config_path)) {
    try {
        require_once $config_path;
        echo "<strong>Config Loaded:</strong> YES<br>";
        echo "<strong>ADMINS defined:</strong> " . (defined('ADMINS') ? 'YES' : 'NO') . "<br>";
        
        if (defined('ADMINS')) {
            echo "<strong>Admin Accounts:</strong> " . count(ADMINS) . "<br>";
            echo "<ul>";
            foreach (ADMINS as $username => $info) {
                echo "<li>$username - Role: {$info['role']}</li>";
            }
            echo "</ul>";
        }
    } catch (Exception $e) {
        echo "<strong style='color:red'>Config Error:</strong> " . $e->getMessage() . "<br>";
    }
}

echo "<hr>";
echo "<h2>POST 테스트</h2>";
echo "<p>브라우저 콘솔에서 다음 코드를 실행하세요:</p>";
echo "<pre>";
echo "fetch('/api/test.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ test: 'data', username: 'admin', password: 'test123' })
})
.then(r => r.text())
.then(d => console.log(d));";
echo "</pre>";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo "<h3>POST 데이터 수신됨:</h3>";
    echo "<strong>Content-Type:</strong> " . ($_SERVER['CONTENT_TYPE'] ?? 'not set') . "<br>";
    
    $input = file_get_contents('php://input');
    echo "<strong>php://input:</strong> " . htmlspecialchars($input) . "<br>";
    
    echo "<strong>\$_POST:</strong> ";
    var_dump($_POST);
    echo "<br>";
    
    if (!empty($input)) {
        $json = json_decode($input, true);
        echo "<strong>JSON decoded:</strong> ";
        var_dump($json);
        echo "<br>";
        echo "<strong>JSON error:</strong> " . json_last_error_msg() . "<br>";
    }
}
?>
