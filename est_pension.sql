-- =========================================================
--  EST PENSION 웹사이트 데이터베이스 스키마
--  Rocky Linux 10.2 + MariaDB 기준
--  기존 est_pension.sql 을 확장 (예약 테이블 / 게시글-첨부파일 연결 / 조회수 추가)
-- =========================================================

DROP DATABASE IF EXISTS webproject;

CREATE DATABASE webproject
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE webproject;

-- ---------------------------------------------------------
-- 1. 회원 (users)
-- ---------------------------------------------------------
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,   -- 로그인/회원가입 폼의 "아이디"
    password VARCHAR(255) NOT NULL,         -- password_hash() 로 저장 (평문 저장 금지)
    email VARCHAR(100) DEFAULT NULL,
    role VARCHAR(20) DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ---------------------------------------------------------
-- 2. 문의 게시판 (posts)
-- ---------------------------------------------------------
CREATE TABLE posts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    title VARCHAR(200) NOT NULL,
    content TEXT NOT NULL,
    views INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE
);

-- ---------------------------------------------------------
-- 3. 첨부파일 (files) - 문의글(posts)에 1:1 첨부되도록 post_id 추가
-- ---------------------------------------------------------
CREATE TABLE files (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    post_id INT DEFAULT NULL,
    filename VARCHAR(255) NOT NULL,     -- 원본 파일명
    filepath VARCHAR(500) NOT NULL,     -- 서버에 저장된 상대 경로 (uploads/attachments/...)
    mime_type VARCHAR(100) DEFAULT NULL,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    FOREIGN KEY (post_id) REFERENCES posts(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE
);


-- ---------------------------------------------------------
-- 초기 데이터
-- ---------------------------------------------------------

-- 비밀번호는 PHP password_hash()(bcrypt)로 저장됩니다.
-- admin  / 평문 비밀번호: admin1234
-- testuser / 평문 비밀번호: test1234
INSERT INTO users (username, password, email, role)
VALUES
('admin',    '$2b$10$9WE.QUnEsUqjagihxGUIkOCJzPXFHpv9F4q4gLFy5sFKODwrTCvI2', 'admin@test.local', 'admin'),
('testuser', '$2b$10$bxyxSiX5QyK2okXQApF9Y.LuKqY.cvPgkpl0S0GukzUfe4WYvCBoG', 'test@test.local',  'user');

INSERT INTO posts (user_id, title, content, views)
VALUES
(1, '바비큐 이용 문의드립니다', '바비큐 이용 시간과 추가 비용이 있는지 문의드립니다.', 12),
(2, '루프탑에서', '라면 끓여 먹어도 돼요? 된다면 대여용 버너가 있는지요?^^', 21),
(2, '단체 예약 문의', '가족 22명도 수용 가능할까요?', 34);
