// =========================================================
// EST PENSION - script.js (DB 연동 버전)
// 기존 localStorage 기반 로직을 서버(PHP+MySQL) API 호출로 교체했습니다.
// main.php 가 렌더링 시 body 태그에 심어준 data-logged-in / data-username 값으로
// 최초 로그인 상태를 판단합니다 (localStorage 대신 서버 세션 사용).
// =========================================================

// [스크롤 감지 시 헤더 스타일 변경]
const header = document.getElementById("header");
window.addEventListener("scroll", () =>
  header.classList.toggle("scrolled", window.scrollY > 40),
);

// [중앙 부드러운 스크롤 이동 처리]
document.querySelectorAll('a[href^="#"]').forEach((anchor) => {
  anchor.addEventListener("click", function (e) {
    const targetId = this.getAttribute("href");
    if (
      targetId &&
      targetId !== "#" &&
      !this.id.includes("Nav") &&
      !this.id.includes("Btn") &&
      !this.id.includes("go")
    ) {
      const targetElement = document.querySelector(targetId);
      if (targetElement) {
        e.preventDefault();
        targetElement.scrollIntoView({ behavior: "smooth", block: "center" });
      }
    }
  });
});

// [현재 로그인 상태 - 서버에서 렌더링 시 주입된 값을 읽음]
function getSessionUser() {
  const loggedIn = document.body.dataset.loggedIn === "1";
  const username = document.body.dataset.username || "";
  return loggedIn ? { id: username } : null;
}

// [DOM 요소 변수 선언]
const modal = document.getElementById("modal");
const modalTitle = document.getElementById("modalTitle");
const modalAuthor = document.getElementById("modalAuthor");
const modalDate = document.getElementById("modalDate");
const modalContent = document.getElementById("modalContent");
const modalAttachment = document.getElementById("modalAttachment");
const writeForm = document.getElementById("writeForm");
const postCount = document.getElementById("postCount");
const boardBody = document.getElementById("boardBody");

// [모달 열기/닫기 제어 함수]
function openModal(mode) {
  modal.classList.add("show");
  document.getElementById("writeArea").style.display =
    mode === "write" ? "block" : "none";
  document.getElementById("viewArea").style.display =
    mode === "view" ? "block" : "none";
}
function closeModal() {
  modal.classList.remove("show");
}

// [작성자 아이디 마스킹 함수] - 서버(board.php)에서도 동일 로직으로 처리하지만, 클라이언트 표시용으로도 유지
function maskAuthorId(authorId, currentUserId) {
  if (authorId === currentUserId) return authorId;
  if (authorId.length <= 3) return "***";
  return authorId.slice(0, -3) + "***";
}

// [글작성 버튼 클릭 이벤트]
document.getElementById("openWrite").addEventListener("click", () => {
  const currentUser = getSessionUser();
  if (!currentUser) {
    alert("문의글 작성은 로그인 후 이용 가능합니다.");
    showAuth("login");
    return;
  }
  document.getElementById("writer").value = currentUser.id;
  document.getElementById("modalHeading").textContent = "문의글 작성";
  openModal("write");
});

document.querySelector(".close-modal").addEventListener("click", closeModal);
modal.addEventListener("click", (e) => {
  if (e.target === modal) closeModal();
});

// [게시글 제목 클릭 -> 서버(board.php action=view)에서 상세/조회수 조회]
function bindPostRowEvents() {
  document.querySelectorAll(".post-title[data-id]").forEach((btn) => {
    // 중복 바인딩 방지
    if (btn.dataset.bound) return;
    btn.dataset.bound = "1";
    btn.addEventListener("click", async () => {
      const id = btn.dataset.id;
      try {
        const res = await fetch(
          `board.php?action=view&id=${encodeURIComponent(id)}`,
        );
        const data = await res.json();
        if (!data.ok) {
          alert(data.message || "게시글을 불러오지 못했습니다.");
          return;
        }
        const post = data.post;
        document.getElementById("modalHeading").textContent = "문의사항";
        modalTitle.textContent = post.title;
        modalAuthor.textContent = post.author;
        modalDate.textContent = post.date;
        modalContent.textContent = post.content;

        if (post.attachment) {
          modalAttachment.style.display = "block";
          let attachHTML = `<strong>첨부파일:</strong> <a href="${post.attachment.url}" download="${post.attachment.name}">${post.attachment.name}</a>`;
          if (post.attachment.isImage) {
            attachHTML += `<br><img src="${post.attachment.url}" alt="${post.attachment.name}">`;
          }
          modalAttachment.innerHTML = attachHTML;
        } else {
          modalAttachment.style.display = "none";
          modalAttachment.innerHTML = "";
        }

        openModal("view");

        // 화면에 표시된 조회수도 갱신
        const row = btn.closest("tr");
        if (row) {
          const viewsCell = row.children[4];
          if (viewsCell) viewsCell.textContent = post.views;
        }
      } catch (err) {
        alert("게시글을 불러오는 중 오류가 발생했습니다.");
      }
    });
  });
}
bindPostRowEvents();

// [문의글 작성 제출 처리 -> board.php action=create 로 전송 (파일 포함 multipart/form-data)]
writeForm.addEventListener("submit", async function (e) {
  e.preventDefault();
  const currentUser = getSessionUser();
  if (!currentUser) {
    alert("로그인 세션이 만료되었습니다. 다시 로그인해 주세요.");
    closeModal();
    showAuth("login");
    return;
  }

  const title = document.getElementById("newTitle").value.trim();
  const content = document.getElementById("newContent").value.trim();
  const fileInput = document.getElementById("newFile");

  if (!title || !content) return;

  const formData = new FormData();
  formData.append("action", "create");
  formData.append("title", title);
  formData.append("content", content);
  if (fileInput.files[0]) {
    formData.append("file", fileInput.files[0]);
  }

  try {
    const res = await fetch("board.php", { method: "POST", body: formData });
    const data = await res.json();
    if (!data.ok) {
      alert(data.message || "등록 중 오류가 발생했습니다.");
      return;
    }
    alert("문의글이 등록되었습니다.");
    closeModal();
    this.reset();
    // 최신 목록/조회수/페이지네이션을 정확히 반영하기 위해 새로고침 (1페이지 기준)
    window.location.href = "main.php#contact";
  } catch (err) {
    alert("등록 중 오류가 발생했습니다.");
  }
});

// [회원가입 및 로그인 모달 제어]
const authModal = document.getElementById("authModal");
const loginNav = document.getElementById("loginNav");
const signupNav = document.getElementById("signupNav");
const authClose = document.getElementById("authClose");
const authTabs = document.querySelectorAll(".auth-tab");
const loginForm = document.getElementById("loginForm");
const signupForm = document.getElementById("signupForm");
const loginMessage = document.getElementById("loginMessage");
const signupMessage = document.getElementById("signupMessage");
const userNav = document.getElementById("userNav");

function showAuth(tab) {
  authModal.classList.add("show");
  authTabs.forEach((btn) =>
    btn.classList.toggle("active", btn.dataset.tab === tab),
  );
  loginForm.classList.toggle("active", tab === "login");
  signupForm.classList.toggle("active", tab === "signup");
  loginMessage.style.display = "none";
  signupMessage.style.display = "none";
}
function hideAuth() {
  authModal.classList.remove("show");
}
function showMessage(element, text) {
  element.textContent = text;
  element.style.display = "block";
}

loginNav.addEventListener("click", (e) => {
  e.preventDefault();
  showAuth("login");
});
signupNav.addEventListener("click", (e) => {
  e.preventDefault();
  showAuth("signup");
});
authClose.addEventListener("click", hideAuth);
authModal.addEventListener("click", (e) => {
  if (e.target === authModal) hideAuth();
});
authTabs.forEach((btn) =>
  btn.addEventListener("click", () => showAuth(btn.dataset.tab)),
);
document.getElementById("goSignup").addEventListener("click", (e) => {
  e.preventDefault();
  showAuth("signup");
});
document.getElementById("goLogin").addEventListener("click", (e) => {
  e.preventDefault();
  showAuth("login");
});

// [회원가입 처리 -> auth.php action=signup]
signupForm.addEventListener("submit", async function (e) {
  e.preventDefault();
  const id = document.getElementById("signupId").value.trim();
  const password = document.getElementById("signupPassword").value;
  const confirm = document.getElementById("signupPasswordConfirm").value;

  if (!/^[A-Za-z][A-Za-z0-9_]{3,}$/.test(id)) {
    showMessage(
      signupMessage,
      "아이디는 영문으로 시작해야 하며, 영문·숫자·밑줄(_)만 사용할 수 있습니다.",
    );
    return;
  }
  if (password !== confirm) {
    showMessage(signupMessage, "비밀번호가 일치하지 않습니다.");
    return;
  }

  const formData = new FormData();
  formData.append("action", "signup");
  formData.append("id", id);
  formData.append("password", password);
  formData.append("passwordConfirm", confirm);

  try {
    const res = await fetch("auth.php", { method: "POST", body: formData });
    const data = await res.json();
    showMessage(signupMessage, data.message);
    if (data.ok) {
      this.reset();
      setTimeout(() => showAuth("login"), 1000);
    }
  } catch (err) {
    showMessage(signupMessage, "회원가입 중 오류가 발생했습니다.");
  }
});

// [로그인 처리 -> auth.php action=login (서버 세션 생성)]
loginForm.addEventListener("submit", async function (e) {
  e.preventDefault();
  const id = document.getElementById("loginId").value.trim();
  const password = document.getElementById("loginPassword").value;

  const formData = new FormData();
  formData.append("action", "login");
  formData.append("id", id);
  formData.append("password", password);

  try {
    const res = await fetch("auth.php", { method: "POST", body: formData });
    const data = await res.json();

    if (!data.ok) {
      showMessage(loginMessage, data.message);
      return;
    }

    hideAuth();
    this.reset();
    alert("로그인되었습니다.");
    // 서버 세션 기준으로 nav/게시판 마스킹을 다시 렌더링하기 위해 새로고침
    window.location.reload();
  } catch (err) {
    showMessage(loginMessage, "로그인 중 오류가 발생했습니다.");
  }
});

// [로그아웃 처리 -> auth.php action=logout]
document.addEventListener("click", async (e) => {
  if (e.target && e.target.id === "logoutBtn") {
    e.preventDefault();
    try {
      await fetch("auth.php", {
        method: "POST",
        body: new URLSearchParams({ action: "logout" }),
      });
    } finally {
      window.location.reload();
    }
  }
});
