<?php
/**
 * reservation.php
 * AJAX 전용 엔드포인트 (POST). 실시간 예약 폼(#reservationForm) 제출을 처리합니다.
 * 기존 script.js 는 날짜만 검증하고 아무 데도 저장하지 않았지만,
 * 여기서는 reservations 테이블에 실제로 저장하고 같은 방/기간 중복 여부까지 확인합니다.
 */

require __DIR__ . '/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['ok' => false, 'message' => '잘못된 요청입니다.'], 405);
}

$room     = trim($_POST['room'] ?? '');
$guests   = trim($_POST['guests'] ?? '');
$checkIn  = $_POST['checkIn'] ?? '';
$checkOut = $_POST['checkOut'] ?? '';

$validRooms = ['Ocean A', 'Garden B', 'Suite C'];
if (!in_array($room, $validRooms, true)) {
    jsonResponse(['ok' => false, 'message' => '올바른 객실을 선택해 주세요.']);
}

// 날짜 형식 검사
$dIn  = DateTime::createFromFormat('Y-m-d', $checkIn);
$dOut = DateTime::createFromFormat('Y-m-d', $checkOut);
if (!$dIn || !$dOut) {
    jsonResponse(['ok' => false, 'message' => '날짜를 올바르게 선택해 주세요.']);
}

$today = new DateTime('today');

if ($dIn < $today) {
    jsonResponse(['ok' => false, 'message' => '체크인 날짜는 오늘 날짜 이전으로 선택할 수 없습니다.']);
}
if ($dOut < $today) {
    jsonResponse(['ok' => false, 'message' => '체크아웃 날짜는 오늘 날짜 이전으로 선택할 수 없습니다.']);
}
if ($dOut <= $dIn) {
    jsonResponse(['ok' => false, 'message' => '체크아웃 날짜는 체크인 날짜보다 늦어야 합니다.']);
}

// 같은 객실에 날짜가 겹치는 기존 예약이 있는지 확인
$stmt = $pdo->prepare(
    'SELECT COUNT(*) FROM reservations
     WHERE room = :room
       AND check_in < :checkOut
       AND check_out > :checkIn'
);
$stmt->execute([
    ':room'      => $room,
    ':checkIn'   => $checkIn,
    ':checkOut'  => $checkOut,
]);

if ((int)$stmt->fetchColumn() > 0) {
    jsonResponse([
        'ok' => false,
        'message' => "선택하신 날짜에는 {$room} 객실이 이미 예약되어 있습니다. 다른 날짜를 선택해 주세요.",
    ]);
}

$user = currentUser();
$stmt = $pdo->prepare(
    'INSERT INTO reservations (user_id, room, guests, check_in, check_out)
     VALUES (:user_id, :room, :guests, :checkIn, :checkOut)'
);
$stmt->execute([
    ':user_id'  => $user['id'] ?? null,
    ':room'     => $room,
    ':guests'   => $guests,
    ':checkIn'  => $checkIn,
    ':checkOut' => $checkOut,
]);

jsonResponse([
    'ok' => true,
    'message' => "{$room} · {$guests} · {$checkIn} ~ {$checkOut} 예약 가능합니다. 결제 문의 : Tel. 064-333-0831",
]);
