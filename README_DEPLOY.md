# EST PENSION — Rocky Linux 10.2 배포 가이드

이 문서는 `index.php` 로 구동되는 EST 펜션 사이트를 **Rocky Linux 10.2** 서버에
Apache(httpd) + PHP + MariaDB 조합으로 배포하는 절차입니다.

---

## 0. 사전 준비

- Rocky Linux 10.2 서버 (root 또는 sudo 권한)
- 프로젝트 파일 전체 (`index.php`, `config.php`, `auth.php`, `board.php`,
  `script.js`, `style.css`, `est_pension.sql`, `uploads/`, 그리고 기존에 갖고 계신 `Image/` 폴더)

---

## 1. 패키지 설치

```bash
sudo dnf update -y

# 웹서버
sudo dnf install -y httpd

# PHP + 필요한 확장 모듈
sudo dnf install -y php php-mysqlnd php-mbstring php-pdo php-gd
sudo dnf install -y php-curl php-zip php-intl php-bcmath php-redis php-apcu php-xml
sudo dnf groupinstall "Development Tools" -y
sudo dnf install ImageMagick ImageMagick-devel -y
sudo dnf install php-pear php-devel -y

# MariaDB
sudo dnf install -y mariadb-server mariadb
```

> 이 사이트가 최소한으로 필요로 하는 확장은 `php-mysqlnd`(PDO MySQL 드라이버), `php-pdo`, `php-mbstring`(작성자 아이디 마스킹 처리) 뿐입니다.
> 나머지(`php-gd`, `ImageMagick`, `php-redis`, `php-apcu` 등)는 당장 코드에서 직접 쓰이진 않지만, 추후 이미지 리사이징·캐싱 등을 붙일 때 바로 활용할 수 있도록 함께 설치해 둔 것입니다.

서비스 활성화:

```bash
sudo systemctl enable --now httpd
sudo systemctl enable --now mariadb
```

버전 확인:

```bash
php -v
httpd -v
mariadb --version
```

---

## 2. DB 생성 및 스키마 임포트

제공된 `est_pension.sql` 을 그대로 임포트합니다 (users/posts/files 테이블과
초기 관리자 계정, 샘플 게시글이 함께 생성됩니다):

```bash
sudo mysql -u root < est_pension.sql
```

> ⚠️ **모의해킹 실습용 설정**: `root` 계정을 외부(모든 호스트, `%`)에서 비밀번호 `1234`로
> 접속 가능하도록 만들고 전체 권한을 부여합니다.

```bash
sudo mysql -u root
"
CREATE USER IF NOT EXISTS 'root'@'%' IDENTIFIED BY '1234';
GRANT ALL PRIVILEGES ON *.* TO 'root'@'%';
"
```

그 다음 `config.php` 의 접속 정보를 서버의 root 계정 정보로 맞춰줍니다.

```php
define('DB_HOST', '127.0.0.1');
define('DB_NAME', 'webproject');
define('DB_USER', 'root');
define('DB_PASS', '1234');
```

---

## 3. 프로젝트 파일 배치

디렉토리 구조 예시:

```
/var/www/html/est/
├── index.php
├── config.php
├── auth.php
├── board.php
├── script.js
├── style.css
├── Image/                # 기존 보유 중인 이미지 폴더 (Intro.png, Welcome.jpg 등)
└── uploads/
    └── attachments/      # 게시글 첨부파일이 저장되는 위치
```

소유권 및 권한 설정 (Apache 가 uploads 디렉토리에 쓸 수 있어야 합니다):

```bash
sudo chown -R apache:apache /var/www/html/est
sudo chmod -R 755 /var/www/html/est
sudo chmod -R 775 /var/www/html/est/uploads
```

---

## 4. Apache 가상호스트 설정 (index.php 를 기본 페이지로)

> ⚠️ **모의해킹 실습용 설정**: 아래 vhost는 VULN-05(디렉터리 리스팅) 실습을 위해
> `Options +Indexes` 를 켜 두었습니다. `uploads/`, `notices/` 등 하위 폴더 목록이
> 브라우저에서 그대로 보이게 됩니다. 실제 운영 배포 시에는 `-Indexes` 로 되돌리세요.

`/etc/httpd/conf.d/est.conf` 생성:

```apache
<VirtualHost *:80>
    ServerName est.example.com
    DocumentRoot /var/www/html/est
    DirectoryIndex index.php index.html

    <Directory /var/www/html/est>
        Options +Indexes
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog /var/log/httpd/est_error.log
    CustomLog /var/log/httpd/est_access.log combined
</VirtualHost>
```

설정 반영:

```bash
sudo apachectl configtest
sudo systemctl restart httpd
```

---

## 5. 접속 확인

브라우저에서 `http://서버IP주소/` 또는 설정한 도메인으로 접속하면
`index.php` 가 기본 페이지로 렌더링됩니다.

테스트 계정:
| 아이디 | 비밀번호 | 비고 |
|---|---|---|
| admin | admin1234 | |
| testuser | test1234 | |

확인할 기능:

1. 로그인 / 회원가입 (헤더 우측)
2. 문의글 작성 (로그인 필요) — 첨부파일 업로드 포함
3. 문의글 클릭 시 조회수 증가 및 상세 모달
4. 실시간 예약 폼 — 같은 객실/기간 중복 예약 시 안내 메시지

---

## 트러블슈팅

| 증상                            | 확인 사항                                                                                   |
| ------------------------------- | ------------------------------------------------------------------------------------------- |
| 500 에러 (DB 연결 오류)         | `config.php` 의 DB_HOST/DB_USER/DB_PASS 값, MariaDB 서비스 상태(`systemctl status mariadb`) |
| 첨부파일 업로드 실패            | `uploads/attachments` 디렉토리 권한(775) 확인                                               |
| 로그인 후에도 비로그인으로 보임 | PHP 세션 저장 디렉토리(`/var/lib/php/session`) 권한, `session.save_path` 설정 확인          |
| 이미지가 깨져서 나옴            | `Image/` 폴더가 `index.php` 와 같은 위치에 있는지, 파일명 대소문자가 코드와 일치하는지 확인 |
