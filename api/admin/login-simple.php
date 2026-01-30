<?php
// 간단한 폼 기반 로그인 (디버깅용)
require_once dirname(__DIR__) . '/config.php';

error_log('=== SIMPLE LOGIN START ===');

// GET 요청일 경우 폼 표시
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>간단 로그인 테스트</title>
        <style>
            body { font-family: Arial, sans-serif; max-width: 400px; margin: 50px auto; padding: 20px; }
            input { width: 100%; padding: 10px; margin: 10px 0; box-sizing: border-box; }
            button { width: 100%; padding: 12px; background: #dc834e; color: white; border: none; cursor: pointer; }
            .result { margin-top: 20px; padding: 15px; background: #f5f5f5; border-radius: 5px; }
        </style>
    </head>
    <body>
        <h2>간단 로그인 테스트</h2>
        <form method="POST" action="">
            <input type="text" name="username" placeholder="아이디" required>
            <input type="password" name="password" placeholder="비밀번호" required>
            <button type="submit">로그인</button>
        </form>
        <div class="result">
            <p>테스트용 계정:</p>
            <ul>
                <li>admin / ansqudxo12!</li>
                <li>seoul_admin / seoul7391</li>
            </ul>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// POST 요청 처리
try {
    $username = isset($_POST['username']) ? trim($_POST['username']) : '';
    $password = isset($_POST['password']) ? trim($_POST['password']) : '';
    
    error_log('Username: ' . $username);
    error_log('Password length: ' . strlen($password));
    
    if (empty($username) || empty($password)) {
        throw new Exception('아이디와 비밀번호를 입력해주세요.');
    }
    
    $admins = ADMINS;
    
    if (isset($admins[$username])) {
        $admin = $admins[$username];
        
        if ($password === $admin['password']) {
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_username'] = $username;
            $_SESSION['admin_role'] = $admin['role'];
            $_SESSION['admin_regions'] = $admin['regions'];
            $_SESSION['login_time'] = time();
            
            error_log('Login successful for: ' . $username);
            
            ?>
            <!DOCTYPE html>
            <html>
            <head>
                <meta charset="UTF-8">
                <title>로그인 성공</title>
                <style>
                    body { font-family: Arial, sans-serif; max-width: 400px; margin: 50px auto; padding: 20px; }
                    .success { padding: 20px; background: #d4edda; color: #155724; border-radius: 5px; }
                    a { display: inline-block; margin-top: 20px; padding: 10px 20px; background: #dc834e; color: white; text-decoration: none; border-radius: 5px; }
                </style>
            </head>
            <body>
                <div class="success">
                    <h2>✅ 로그인 성공!</h2>
                    <p><strong>사용자:</strong> <?php echo htmlspecialchars($username); ?></p>
                    <p><strong>역할:</strong> <?php echo htmlspecialchars($admin['role']); ?></p>
                    <p><strong>지역:</strong> <?php echo htmlspecialchars(implode(', ', $admin['regions'])); ?></p>
                </div>
                <a href="/admin.html">관리자 페이지로 이동</a>
            </body>
            </html>
            <?php
            exit;
        } else {
            error_log('Password mismatch for: ' . $username);
            throw new Exception('아이디 또는 비밀번호가 올바르지 않습니다.');
        }
    } else {
        error_log('User not found: ' . $username);
        throw new Exception('아이디 또는 비밀번호가 올바르지 않습니다.');
    }
    
} catch (Exception $e) {
    error_log('Login error: ' . $e->getMessage());
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>로그인 실패</title>
        <style>
            body { font-family: Arial, sans-serif; max-width: 400px; margin: 50px auto; padding: 20px; }
            .error { padding: 20px; background: #f8d7da; color: #721c24; border-radius: 5px; }
            a { display: inline-block; margin-top: 20px; padding: 10px 20px; background: #dc834e; color: white; text-decoration: none; border-radius: 5px; }
        </style>
    </head>
    <body>
        <div class="error">
            <h2>❌ 로그인 실패</h2>
            <p><?php echo htmlspecialchars($e->getMessage()); ?></p>
        </div>
        <a href="?">다시 시도</a>
    </body>
    </html>
    <?php
    exit;
}
?>
