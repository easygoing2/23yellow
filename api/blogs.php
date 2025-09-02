<?php
// 예시: api/blogs.php
header('Content-Type: application/json; charset=utf-8');

// DB 연결
include $_SERVER["DOCUMENT_ROOT"]."/dbconfig.php";
$dbConn = new mysqli($hostname, $dbuserid, $dbpasswd, $dbname, $dbport);
$dbConn->set_charset("utf8mb4");

$contentId = isset($_GET['contentId']) ? (int)$_GET['contentId'] : 0;
if ($contentId < 1) {
  echo json_encode([]);
  exit;
}

// gogocamping_blogs 테이블에서 contentId가 같은 블로그 글 조회
$sql = "SELECT * FROM gogocamping_blogs WHERE contentId = $contentId ORDER BY id DESC LIMIT 10";
$result = $dbConn->query($sql);

$blogs = [];
if ($result) {
  $blogs = $result->fetch_all(MYSQLI_ASSOC);
}

$dbConn->close();

// JSON 형태로 반환
echo json_encode($blogs, JSON_UNESCAPED_UNICODE);
exit;
