<?php
require __DIR__ . '/config.php';

// ── 로그인 상태 ──────────────────────────────────────────
$user = currentUser(); // null 이면 비로그인

// ── 게시판 목록 (페이지네이션, 10개씩) ───────────────────
$perPage = 10;
$page = max(1, (int)($_GET['page'] ?? 1));
$offset = ($page - 1) * $perPage;

$totalPosts = (int)$pdo->query('SELECT COUNT(*) FROM posts')->fetchColumn();
$totalPages = max(1, (int)ceil($totalPosts / $perPage));

$stmt = $pdo->prepare(
    'SELECT p.id, p.title, p.views, p.created_at, u.username,
            EXISTS(SELECT 1 FROM files f WHERE f.post_id = p.id) AS has_file
     FROM posts p
     JOIN users u ON u.id = p.user_id
     ORDER BY p.id DESC
     LIMIT :limit OFFSET :offset'
);
$stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$posts = $stmt->fetchAll();

$currentUsername = $user['username'] ?? null;
?>
<!DOCTYPE html>
<html lang="ko">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>EST PENSION | JEJU</title>
<!-- 외부 CSS 파일 연결 -->
<link rel="stylesheet" href="style.css">
</head>
<body data-logged-in="<?= $user ? '1' : '0' ?>" data-username="<?= htmlspecialchars($user['username'] ?? '', ENT_QUOTES) ?>">
<header id="header">
  <a href="#home" class="logo">EST<small>JEJU PENSION</small></a>
  <nav>
    <a href="#reservation">예약</a>
    <a href="#rooms">객실보기</a>
    <a href="#special">주변여행지</a>
    <a href="#location">오시는길</a>
    <a href="#contact">문의사항</a>
    <a href="#" id="loginNav" style="<?= $user ? 'display:none' : '' ?>">로그인</a>
    <a href="#" id="signupNav" style="<?= $user ? 'display:none' : '' ?>">회원가입</a>
  </nav>
<div class="user-nav" id="userNav" style="<?= $user ? 'display:block' : 'display:none' ?>">
  <?php if ($user): ?>
    <?= htmlspecialchars($user['username'], ENT_QUOTES) ?> · <a href="#" id="logoutBtn">로그아웃</a>
  <?php endif; ?>
</div>
</header>

<main>
<section class="hero" id="home">
  <div class="hero-content">
    <div class="eyebrow">A QUIET MOMENT IN JEJU</div>
    <h1>EST</h1>
    <p>제주의 바다와 가장 가까운 곳에서, 가장 느린 하루를 만납니다.</p>
    <a class="btn" href="#rooms">DISCOVER OUR ROOMS</a>
  </div>
  <div class="scroll">SCROLL</div>
</section>

<section id="about">
  <div class="intro">
    <div class="visual" style="background-image: url('Image/Welcome.jpg'); background-size: cover; background-position: center; background-repeat: no-repeat;">
      <div class="stamp">EST<br>JEJU</div>
    </div>
    <div class="intro-text">
      <div class="kicker">WELCOME TO EST</div>
      <h3>머무는 시간마저<br>여행이 되는 공간</h3>
      <p>EST는 제주의 푸른 바다와 함께하는 편안한 공간입니다. 
        아름다운 대자연 속에서 여유를 느껴보세요.</p>
    </div>
  </div>
</section>

<section class="rooms-wrap" id="rooms">
  <div class="rooms-inner">
    <div class="section-head">
      <div class="kicker">Preview</div>
      <h2>EST ROOMS</h2>
    </div>
    <div class="rooms">
      <article class="room">
        <div class="room-image" style="background-image: url('Image/OceanA.png');"></div>
        <div class="room-info">
          <h3>Ocean A</h3>
          <p>푸른 바다를 가장 가까이 담아내는 오션뷰 스튜디오</p>
          <div class="meta"><span>2-4 PERSONS</span><span>QUEEN BED · TERRACE</span></div>
        </div>
      </article>
      <article class="room">
        <div class="room-image" style="background-image: url('Image/GardenB.png');"></div>
        <div class="room-info">
          <h3>Garden B</h3>
          <p>제주의 초록빛 정원을 바라보며 쉬어가는 아늑한 객실</p>
          <div class="meta"><span>2-3 PERSONS</span><span>QUEEN BED · BATH </span></div>
        </div>
      </article>
      <article class="room">
        <div class="room-image" style="background-image: url('Image/SuiteC.png');"></div>
        <div class="room-info">
          <h3>Suite C</h3>
          <p>더 넓고 여유로운 공간에서 즐기는 프라이빗 스테이</p>
          <div class="meta"><span>2-8 PERSONS</span><span>LIVING · KITCHEN</span></div>
        </div>
      </article>
    </div>
  </div>
</section>

<section>
  <div class="section-head">
    <div class="kicker">SPECIAL</div>
    <h2>EST만의 특별함</h2>
    <p class="desc">다양한 실내/실외 시설</p>
  </div>
  <div class="facility">
    <div class="facility-art" style="background-image: url('Image/Special.png');"></div>
    <div class="facility-list">
      <div><strong>BBQ</strong><span>프라이빗 바비큐 공간</span></div>
      <div><strong>Rooftop</strong><span>루프탑에서 맞는 시원한 바람</span></div>
      <div><strong>Service</strong><span>보드게임, 자전거 대여, 낚싯대 대여</span></div>
      <div><strong>EstCafe</strong><span>무료 커피 & 스낵바</span></div>
    </div>
  </div>
</section>

<section id="special">
  <div class="section-head"><div class="kicker">TRAVEL</div><h2>안녕, 제주</h2><p class="desc">EST와 함께 누리는 제주 여행지</p></div>
  <div class="facility">
    <div class="facility-art" style="background-image: url('Image/Travel.png');"></div>
    <div class="facility-list">
      <div><strong>한담해안산책로</strong><span>푸른 애월의 바다를 느껴보세요</span></div>
      <div><strong>중문관광단지</strong><span>즐길 거리 가득한 종합 휴양지</span></div>
      <div><strong>용머리해안</strong><span>웅장한 사암 절벽을 품은 바닷길</span></div>
      <div><strong>오설록 티뮤지엄</strong><span>넓은 녹차밭과 차 문화 공간</span></div>
    </div>
  </div>
</section>

<section class="location" id="location">
  <div class="location-inner">
    <div>
      <div class="kicker">LOCATION</div>
      <h2>오시는 길</h2>
      <p class="desc">공항에서 시작되는 제주 여행의 끝, EST에서 편안한 휴식을 시작하세요.</p>
      <div class="address">
        <p><b>ADDRESS</b>제주특별자치도 제주시 애월읍 EST 해안로 101</p>
        <p><b>CAR</b>제주국제공항에서 약 30분</p>
        <p><b>PHONE</b>064-333-0831</p>
      </div>
    </div>
  </div>
</section>

<section class="contact" id="contact">
  <div class="contact-grid">
    <div class="contact-copy">
      <div class="kicker">CONTACT</div>
      <h3>궁금한 점이 있으신가요?</h3>
      <p>문의를 남겨주세요. 확인 후 안내해 드립니다.</p>
      <div class="tel">064.333.0831</div>
      <p>AM 08:00 - PM 22:00</p>
    </div>
    <div>
      <div class="board-toolbar">
        <div class="board-count">총 <b id="postCount"><?= $totalPosts ?></b>개의 문의가 있습니다.</div>
        <button type="button" class="write-btn" id="openWrite">문의글 작성</button>
      </div>

      <div class="board-wrap">
        <table class="board-table">
          <thead>
            <tr>
              <th>번호</th>
              <th>제목</th>
              <th>작성자</th>
              <th>작성일</th>
              <th>조회</th>
            </tr>
          </thead>
          <tbody id="boardBody">
            <?php foreach ($posts as $p): ?>
            <tr>
              <td><?= (int)$p['id'] ?></td>
              <td>
                <button class="post-title" data-id="<?= (int)$p['id'] ?>">
                  <?= htmlspecialchars($p['title'], ENT_QUOTES) ?><?= $p['has_file'] ? ' 📎' : '' ?>
                  <span class="lock"></span>
                </button>
              </td>
              <td class="author-cell" data-author-id="<?= htmlspecialchars($p['username'], ENT_QUOTES) ?>">
                <?= htmlspecialchars(maskAuthorId($p['username'], $currentUsername), ENT_QUOTES) ?>
              </td>
              <td><?= date('Y.m.d', strtotime($p['created_at'])) ?></td>
              <td><?= (int)$p['views'] ?></td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($posts)): ?>
            <tr><td colspan="5">등록된 문의글이 없습니다.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>

      <div class="pagination">
        <a href="main.php?page=<?= max(1, $page - 1) ?>#contact"><button type="button"<?= $page <= 1 ? ' disabled' : '' ?>>‹</button></a>
        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
          <a href="main.php?page=<?= $i ?>#contact"><button type="button" class="<?= $i === $page ? 'active' : '' ?>"><?= $i ?></button></a>
        <?php endfor; ?>
        <a href="main.php?page=<?= min($totalPages, $page + 1) ?>#contact"><button type="button"<?= $page >= $totalPages ? ' disabled' : '' ?>>›</button></a>
      </div>
    </div>
  </div>
</section>
</main>

<div class="auth-modal" id="authModal">
  <div class="auth-box">
    <button type="button" class="auth-close" id="authClose">×</button>
    <div class="auth-logo">EST</div>
    <div class="auth-sub">JEJU PRIVATE PENSION</div>

    <div class="auth-tabs">
      <button type="button" class="auth-tab active" data-tab="login">로그인</button>
      <button type="button" class="auth-tab" data-tab="signup">회원가입</button>
    </div>

    <form class="auth-form active" id="loginForm">
      <input type="text" id="loginId" placeholder="아이디" required>
      <input type="password" id="loginPassword" placeholder="비밀번호" required>
      <button type="submit" class="auth-submit">LOGIN</button>
      <div class="auth-message" id="loginMessage"></div>
      <div class="auth-help">계정이 없으신가요? <a href="#" id="goSignup">회원가입</a></div>
    </form>

    <form class="auth-form" id="signupForm">
      <input type="text" id="signupId" placeholder="아이디 (영문으로 시작, 4자 이상)" required minlength="4" pattern="^[A-Za-z][A-Za-z0-9_]{3,}$" title="아이디는 영문으로 시작해야 하며, 영문·숫자·밑줄(_)만 사용할 수 있습니다.">
      <input type="password" id="signupPassword" placeholder="비밀번호 (6자 이상)" required minlength="6">
      <input type="password" id="signupPasswordConfirm" placeholder="비밀번호 확인" required>
      <button type="submit" class="auth-submit">SIGN UP</button>
      <div class="auth-message" id="signupMessage"></div>
      <div class="auth-help">이미 계정이 있으신가요? <a href="#" id="goLogin">로그인</a></div>
    </form>
  </div>
</div>

<div class="modal" id="modal">
  <div class="modal-box">
    <button class="close-modal" type="button">×</button>
    <h3 id="modalHeading">문의사항</h3>
    
    <div id="viewArea">
      <h3 id="modalTitle" style="font-size:24px;margin-bottom:12px"></h3>
      <div class="modal-meta"><span id="modalAuthor"></span><span id="modalDate"></span></div>
      <div class="modal-content" id="modalContent"></div>
      <div id="modalAttachment" class="modal-attachment" style="display:none;"></div>
    </div>
    
    <div id="writeArea">
      <form id="writeForm">
        <input type="text" id="writer" placeholder="작성자" readonly required>
        <input type="text" id="newTitle" placeholder="제목" required>
        <textarea id="newContent" placeholder="문의 내용을 입력해주세요." required></textarea>
        
        <div class="file-upload-box">
          <label for="newFile" style="display:block;margin-bottom:6px;font-weight:bold;">첨부파일</label>
          <input type="file" id="newFile">
        </div>

        <button type="submit">문의글 등록</button>
      </form>
    </div>
  </div>
</div>

<footer>
  <div class="footer-inner">
    <div><strong>EST</strong><br>JEJU PRIVATE PENSION</div>
    <div>EST PENSION · 제주특별자치도 제주시 애월읍 EST 해안로 101<br>Tel. 064-333-0831 </div>
  </div>
</footer>

<!-- 외부 JS 파일 연결 -->
<script src="script.js"></script>
</body>
</html>
