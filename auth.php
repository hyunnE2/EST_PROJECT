<?php
/**
 * auth.php
 * AJAX 전용 엔드포인트. script.js 의 fetch() 요청을 받아 JSON으로 응답합니다.
 *
 * POST action=signup  { id, password, passwordConfirm }
 * POST action=login    { id, password }
 * POST action=logout   (본문 불필요)
 */

require __DIR__ . '/config.php';

$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {

    case 'signup': {
        $id       = trim($_POST['id'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm  = $_POST['passwordConfirm'] ?? '';

        // 서버측 검증 (클라이언트 검증만 믿지 않음) - main.html 의 signupId pattern 과 동일
        if (!preg_match('/^[A-Za-z][A-Za-z0-9_]{3,}$/', $id)) {
            jsonResponse(['ok' => false, 'message' => '아이디는 영문으로 시작해야 하며, 영문·숫자·밑줄(_)만 사용할 수 있습니다.']);
        }
        if (strlen($password) < 6) {
            jsonResponse(['ok' => false, 'message' => '비밀번호는 6자 이상이어야 합니다.']);
        }
        if ($password !== $confirm) {
            jsonResponse(['ok' => false, 'message' => '비밀번호가 일치하지 않습니다.']);
        }

        $stmt = $pdo->prepare('SELECT id FROM users WHERE username = :u');
        $stmt->execute([':u' => $id]);
        if ($stmt->fetch()) {
            jsonResponse(['ok' => false, 'message' => '이미 사용 중인 아이디입니다.']);
        }

        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare('INSERT INTO users (username, password) VALUES (:u, :p)');
        $stmt->execute([':u' => $id, ':p' => $hash]);

        jsonResponse(['ok' => true, 'message' => '회원가입이 완료되었습니다. 로그인해 주세요.']);
        break;
    }

    case 'login': {
        $id       = trim($_POST['id'] ?? '');
        $password = $_POST['password'] ?? '';

        $stmt = $pdo->prepare('SELECT id, username, password, role FROM users WHERE username = :u');
        $stmt->execute([':u' => $id]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['password'])) {
            jsonResponse(['ok' => false, 'message' => '아이디 또는 비밀번호가 올바르지 않습니다.']);
        }

        // 세션에 로그인 정보 저장 (localStorage estCurrentUser 대체)
        $_SESSION['user_id']  = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role']     = $user['role'];

        jsonResponse(['ok' => true, 'message' => '로그인되었습니다.', 'user' => ['id' => $user['username']]]);
        break;
    }

    case 'logout': {
        $_SESSION = [];
        session_destroy();
        jsonResponse(['ok' => true]);
        break;
    }

    default:
        jsonResponse(['ok' => false, 'message' => '알 수 없는 요청입니다.'], 400);
}
