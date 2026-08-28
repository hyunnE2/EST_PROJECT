<?php
/**
 * config.php
 * - DB 접속 정보 및 PDO 연결
 * - 세션 시작 (로그인 상태를 localStorage 대신 서버 세션으로 관리)
 * - est_pension.sql 의 webproject 스키마 기준
 */

// ── 세션 시작 ────────────────────────────────────────────
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ── DB 접속 정보 (환경에 맞게 수정하세요) ───────────────────
define('DB_HOST', '192.168.159.137');
define('DB_NAME', 'webproject');
define('DB_USER', 'root');
define('DB_PASS', '1234');
define('DB_CHARSET', 'utf8mb4');

$dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
} catch (PDOException $e) {
    http_response_code(500);
    // 운영 환경에서는 상세 에러를 노출하지 않는 것이 좋습니다.
    die('DB 연결 오류: ' . $e->getMessage());
}

// ── 공통 유틸 함수 ───────────────────────────────────────

/** 로그인 여부 확인 */
function isLoggedIn(): bool {
    return isset($_SESSION['user_id']);
}

/** 현재 로그인한 사용자 정보 반환 (없으면 null) */
function currentUser(): ?array {
    if (!isLoggedIn()) return null;
    return [
        'id'       => $_SESSION['user_id'],
        'username' => $_SESSION['username'],
        'role'     => $_SESSION['role'] ?? 'user',
    ];
}

/**
 * 작성자 아이디 마스킹
 * 본인 글이면 그대로, 아니면 뒤 3자리를 ***로 마스킹 (기존 script.js 의 maskAuthorId 로직 그대로 이식)
 */
function maskAuthorId(string $authorId, ?string $currentUserId): string {
    if ($currentUserId !== null && $authorId === $currentUserId) return $authorId;
    if (mb_strlen($authorId) <= 3) return '***';
    return mb_substr($authorId, 0, mb_strlen($authorId) - 3) . '***';
}

/** JSON 응답 후 종료 (AJAX 엔드포인트 공용) */
function jsonResponse($data, int $status = 200): void {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}
