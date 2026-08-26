<?php
/**
 * board.php
 * AJAX 전용 엔드포인트.
 *
 * POST action=create  (multipart/form-data: title, content, file[optional]) - 로그인 필요
 * GET  action=view&id=N                                                     - 조회수 +1 후 상세 반환
 */

require __DIR__ . '/config.php';

$action = $_POST['action'] ?? $_GET['action'] ?? '';

// 첨부파일 업로드 디렉토리 (웹에서 직접 접근 가능하지만 PHP 실행은 되지 않도록 .htaccess 로 차단)
define('UPLOAD_DIR', __DIR__ . '/uploads/attachments/');
define('UPLOAD_URL', 'uploads/attachments/');
define('MAX_UPLOAD_BYTES', 5 * 1024 * 1024); // 5MB

switch ($action) {

    case 'create': {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            jsonResponse(['ok' => false, 'message' => '잘못된 요청입니다.'], 405);
        }

        $user = currentUser();
        if (!$user) {
            jsonResponse(['ok' => false, 'message' => '로그인 세션이 만료되었습니다. 다시 로그인해 주세요.'], 401);
        }

        $title   = trim($_POST['title'] ?? '');
        $content = trim($_POST['content'] ?? '');

        if ($title === '' || $content === '') {
            jsonResponse(['ok' => false, 'message' => '제목과 내용을 입력해 주세요.']);
        }

        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare('INSERT INTO posts (user_id, title, content) VALUES (:uid, :title, :content)');
            $stmt->execute([':uid' => $user['id'], ':title' => $title, ':content' => $content]);
            $postId = (int)$pdo->lastInsertId();

            $attachment = null;

            if (!empty($_FILES['file']) && $_FILES['file']['error'] !== UPLOAD_ERR_NO_FILE) {
                $file = $_FILES['file'];

                if ($file['error'] !== UPLOAD_ERR_OK) {
                    throw new RuntimeException('파일 업로드 중 오류가 발생했습니다.');
                }
                if ($file['size'] > MAX_UPLOAD_BYTES) {
                    throw new RuntimeException('파일 용량은 5MB를 초과할 수 없습니다.');
                }
                if (!is_dir(UPLOAD_DIR)) {
                    mkdir(UPLOAD_DIR, 0755, true);
                }

                $originalName = $file['name'];
                $ext  = pathinfo($originalName, PATHINFO_EXTENSION);
                $safeName = bin2hex(random_bytes(16)) . ($ext ? '.' . preg_replace('/[^a-zA-Z0-9]/', '', $ext) : '');
                $destPath = UPLOAD_DIR . $safeName;

                if (!move_uploaded_file($file['tmp_name'], $destPath)) {
                    throw new RuntimeException('파일 저장에 실패했습니다.');
                }

                $mimeType = mime_content_type($destPath) ?: $file['type'];

                $stmt = $pdo->prepare(
                    'INSERT INTO files (user_id, post_id, filename, filepath, mime_type)
                     VALUES (:uid, :pid, :fname, :fpath, :mime)'
                );
                $stmt->execute([
                    ':uid'   => $user['id'],
                    ':pid'   => $postId,
                    ':fname' => $originalName,
                    ':fpath' => UPLOAD_URL . $safeName,
                    ':mime'  => $mimeType,
                ]);

                $attachment = [
                    'name' => $originalName,
                    'url'  => UPLOAD_URL . $safeName,
                    'isImage' => str_starts_with($mimeType, 'image/'),
                ];
            }

            $pdo->commit();
        } catch (Throwable $e) {
            $pdo->rollBack();
            jsonResponse(['ok' => false, 'message' => $e->getMessage()], 500);
        }

        jsonResponse([
            'ok' => true,
            'message' => '문의글이 등록되었습니다.',
            'post' => [
                'id'         => $postId,
                'title'      => $title,
                'authorMasked' => maskAuthorId($user['username'], $user['username']),
                'date'       => date('Y.m.d'),
                'hasFile'    => $attachment !== null,
            ],
        ]);
        break;
    }

    case 'view': {
        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) {
            jsonResponse(['ok' => false, 'message' => '잘못된 요청입니다.'], 400);
        }

        // 조회수 +1
        $pdo->prepare('UPDATE posts SET views = views + 1 WHERE id = :id')->execute([':id' => $id]);

        $stmt = $pdo->prepare(
            'SELECT p.id, p.title, p.content, p.views, p.created_at, u.username
             FROM posts p JOIN users u ON u.id = p.user_id
             WHERE p.id = :id'
        );
        $stmt->execute([':id' => $id]);
        $post = $stmt->fetch();

        if (!$post) {
            jsonResponse(['ok' => false, 'message' => '존재하지 않는 게시글입니다.'], 404);
        }

        $stmt = $pdo->prepare('SELECT filename, filepath, mime_type FROM files WHERE post_id = :id LIMIT 1');
        $stmt->execute([':id' => $id]);
        $file = $stmt->fetch();

        $user = currentUser();
        $currentUsername = $user['username'] ?? null;

        jsonResponse([
            'ok' => true,
            'post' => [
                'title'   => $post['title'],
                'author'  => maskAuthorId($post['username'], $currentUsername),
                'date'    => date('Y.m.d', strtotime($post['created_at'])),
                'content' => $post['content'],
                'views'   => (int)$post['views'],
                'attachment' => $file ? [
                    'name'    => $file['filename'],
                    'url'     => $file['filepath'],
                    'isImage' => str_starts_with((string)$file['mime_type'], 'image/'),
                ] : null,
            ],
        ]);
        break;
    }

    default:
        jsonResponse(['ok' => false, 'message' => '알 수 없는 요청입니다.'], 400);
}
