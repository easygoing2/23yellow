<?php
// 예시: /api/images.php
header('Content-Type: application/json; charset=utf-8');

include $_SERVER["DOCUMENT_ROOT"] . "/dbconfig.php";
$contentId = isset($_GET['contentId']) ? (int)$_GET['contentId'] : 0;

if ($contentId < 1) {
    echo json_encode([]);
    exit;
}

$images = [];
$sql = "SELECT * FROM gogocamping_image WHERE contentId = $contentId ORDER BY id ASC";
$res = $mysqli->query($sql);
if ($res) {
    $images = $res->fetch_all(MYSQLI_ASSOC);
}
echo json_encode($images);
