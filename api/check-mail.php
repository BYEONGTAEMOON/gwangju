<?php
// 간단한 비밀번호 보호
$password = $_GET['pass'] ?? '';
if ($password !== 'check123') {
    die('Access Denied');
}

echo "<h1>📧 서버 이메일 설정 확인</h1>";
echo "<hr>";

// PHP 버전
echo "<h2>PHP 정보</h2>";
echo "<strong>PHP Version:</strong> " . phpversion() . "<br>";

// mail() 함수 사용 가능 여부
echo "<strong>mail() 함수:</strong> ";
if (function_exists('mail')) {
    echo "✅ 사용 가능<br>";
} else {
    echo "❌ 사용 불가<br>";
}

// sendmail 경로 확인
echo "<h2>Sendmail 설정</h2>";
echo "<strong>sendmail_path:</strong> " . ini_get('sendmail_path') . "<br>";
echo "<strong>SMTP:</strong> " . ini_get('SMTP') . "<br>";
echo "<strong>smtp_port:</strong> " . ini_get('smtp_port') . "<br>";

// 이메일 설정 파일 확인
echo "<h2>이메일 설정 파일</h2>";
$settingsFile = dirname(__DIR__) . '/data/email-settings.json';
echo "<strong>파일 경로:</strong> " . $settingsFile . "<br>";
echo "<strong>파일 존재:</strong> " . (file_exists($settingsFile) ? '✅ YES' : '❌ NO') . "<br>";

if (file_exists($settingsFile)) {
    $settings = json_decode(file_get_contents($settingsFile), true);
    echo "<strong>설정 내용:</strong><br>";
    echo "<pre>" . json_encode($settings, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "</pre>";
}

// 테스트 이메일 발송
echo "<h2>테스트 이메일 발송</h2>";
$testEmail = $_GET['email'] ?? '';

if (!empty($testEmail)) {
    echo "발송 대상: <strong>{$testEmail}</strong><br><br>";
    
    $subject = '=?UTF-8?B?' . base64_encode('[테스트] 허니문박람회 메일 테스트') . '?=';
    $message = "이것은 테스트 메일입니다.\n\n";
    $message .= "이 메일을 받으셨다면 서버 메일 설정이 정상입니다.\n\n";
    $message .= "발송 시간: " . date('Y-m-d H:i:s') . "\n";
    
    $headers = array();
    $headers[] = "From: 허니문박람회 <noreply@honeyfair.co.kr>";
    $headers[] = "Content-Type: text/plain; charset=UTF-8";
    $headers[] = "Content-Transfer-Encoding: 8bit";
    $headers[] = "MIME-Version: 1.0";
    
    $result = @mail($testEmail, $subject, $message, implode("\r\n", $headers));
    
    if ($result) {
        echo "✅ <strong>이메일 발송 성공!</strong><br>";
        echo "메일함(또는 스팸함)을 확인해주세요.<br>";
    } else {
        echo "❌ <strong>이메일 발송 실패!</strong><br>";
        echo "서버가 mail() 함수를 지원하지 않거나 sendmail이 설정되지 않았습니다.<br>";
    }
} else {
    echo "<p>테스트하려면 URL에 <code>&email=your@email.com</code>을 추가하세요.</p>";
    echo "<p>예: <code>check-mail.php?pass=check123&email=admin@example.com</code></p>";
}

echo "<hr>";
echo "<h2>🔧 문제 해결 방법</h2>";
echo "<ol>";
echo "<li><strong>mail() 함수가 작동하지 않는 경우:</strong><br>";
echo "- 호스팅 업체에 문의하여 mail() 함수 활성화 요청<br>";
echo "- 또는 SMTP 방식으로 변경 (PHPMailer 사용)</li>";
echo "<li><strong>이메일이 스팸함에 들어가는 경우:</strong><br>";
echo "- SPF, DKIM 레코드 설정 (도메인 관리)<br>";
echo "- 신뢰할 수 있는 SMTP 서버 사용 (Gmail, Naver 등)</li>";
echo "<li><strong>로컬 환경에서 테스트:</strong><br>";
echo "- localhost에서는 이메일이 발송되지 않습니다<br>";
echo "- 실제 서버(honeyfair.co.kr)에 업로드 후 테스트하세요</li>";
echo "</ol>";
?>
