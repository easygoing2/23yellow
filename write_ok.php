<?php session_start();
include $_SERVER['DOCUMENT_ROOT'] . "/dbconfig.php";

$redirectUrl = $_SERVER["DOCUMENT_URI"];

if(!$_SESSION['UID']) {
  echo "<script>alert('회원 전용 게시판입니다.');location.href='$redirectUrl';</script>";
  exit;
}

// echo "<pre>";
// print_r($_POST);

$userId = trim($_SESSION['UID'] ?? '');
$subject = trim($_POST["subject"] ?? '');
$content = trim($_POST["content"]);
$status = 1; //status는 1이면 true, 0이면 false이다.

if (empty($userId)) {
  echo "<script>alert('userId 실패했습니다.');history.back();</script>";
  exit;
}
if (empty($subject)) {
  echo "<script>alert('subject 실패했습니다.');history.back();</script>";
  exit;
}
if (empty($content)) {
  echo "<script>alert('content 실패했습니다.');history.back();</script>";
  exit;
}
// echo "<br>------<br>";

// echo "제목=>".$subject."<br>";
// echo "내용=>".$content;

//$userId = str_replace('\'', '\'\'', $userId);
//$subject = str_replace('\'', '\'\'', $subject);
//$content = str_replace('\'', '\'\'', $content);

//$sql = "insert into gocamping (userId,subject,content) values ('" . $userId . "','" . $subject . "','" . $content . "')";
$sql = "insert into gocamping (userId,subject,content) values (?,?,?)";

//echo $sql;
//exit;

//$result = $mysqli->query($sql) or die($mysqli->error);
$stmt = $mysqli->prepare($sql);
$stmt->bind_param('sss', $userId, $subject, $content);


if ($stmt->execute() && $stmt->affected_rows > 0) {
  echo "<script>location.href='index';</script>";
  exit;
} else {
  echo "<script>alert('글등록에 실패했습니다.');history.back();</script>";
  exit;
}