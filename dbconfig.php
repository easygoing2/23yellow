<?php
// ini_set('display_errors', '0'); //혹시나 warning 메세지가 뜨는 사람들을 위해 추가
$hostname = "dev.arasoft.kr";
$dbuserid = "smlee";
$dbpasswd = "tkdalselql";
$dbname = "smlee1";
$dbport = "34591";

$mysqli = new mysqli($hostname, $dbuserid, $dbpasswd, $dbname, $dbport);

if ($mysqli->connect_errno) {
  die('Connect Error: ' . $mysqli->connect_error);
}
