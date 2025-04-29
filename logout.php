<?php session_start();

session_destroy();

$redirectUrl = $_SERVER["DOCUMENT_URI"];

echo "<script>alert('로그아웃 되었습니다.');location.href='$redirectUrl/';</script>";
exit;
?>