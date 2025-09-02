<?php

$file_log = count($argv) > 1 ? true : false;

// 공공 API 키 설정
const ApiKey = "a6o1csoBikLfTlFWwlW0ELSQD%2F4Ia5q3f7chas3mn6xkwsLABinMbeyOoHVv2Y%2BAycX0R4C%2FY0dZ1vJyMr3Mrw%3D%3D";

// 최대 numOfRows 값으로 설정 (한 번에 가져올 수 있는 최대 행 수)
const MaxRows = 100;

// insert, update 데이터 갯수
$iCount = 0;
$uCount = 0;
$dCount = 0;

$page = 1;
$rows = MaxRows;

const ApiUrl = 'https://apis.data.go.kr/B551011/GoCamping/basedList?numOfRows=' . MaxRows . '&MobileOS=WIN&MobileApp=gocam&serviceKey=' . ApiKey . '&_type=json&pageNo=';

// DB 연결 정보
$hostname = "dev.arasoft.kr";
$dbuserid = "smlee";
$dbpasswd = "tkdalselql";
$dbname = "smlee1";
$dbport = "34591";

// 데이터베이스에 연결
$dbConn = new mysqli($hostname, $dbuserid, $dbpasswd, $dbname, $dbport);

// 연결 확인
if ($dbConn->connect_error) {
    die("연결 실패: " . $dbConn->connect_error);
}

$updatedTime = null;
$stmt = $dbConn->prepare('SELECT NOW() updatedTime');
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows > 0) {
    $updatedTime = $result->fetch_assoc()['updatedTime'];
} else {
  echo 'updatedTime fail..';
  return;
}
$stmt->close();



// 쿼리 준비 (SELECT, INSERT, MODIFY, UPDATE)
$sStmt = $dbConn->prepare('SELECT modifiedtime FROM smlee1.gogocamping2 WHERE contentId=?');
$iStmt = $dbConn->prepare("INSERT INTO smlee1.gogocamping2 (addr1, facltNm, createdtime, modifiedtime, contentId, status, updatedTime) VALUES (?, ?, ?, ?, ?, 1, '$updatedTime')");
$mStmt = $dbConn->prepare("UPDATE smlee1.gogocamping2 SET addr1=?, facltNm=?, createdtime=?, modifiedtime=?, status=1, updatedTime='$updatedTime' WHERE contentId=?");
$uStmt = $dbConn->prepare("UPDATE smlee1.gogocamping2 SET status=1, updatedTime='$updatedTime' WHERE contentId=?");
$time = microtime(true);

// 모든 contentID를 저장할 배열
$allContentIds = array();

// status 업데이트를 위한 prepared statement 추가
$countLength = strlen(''.MaxRows);
$countBack = str_repeat(chr(8), $countLength);

for ($page = 1; $rows == MaxRows; $page++) {
  $resp = file_get_contents(ApiUrl . $page);
  $data = json_decode($resp, true);
  $list = $data['response']['body']['items']['item'];
  $rows = count($list);

  echo "rows: " . $rows . " / page = $page ... ";
  $i = 0;
  foreach ($list as $item) {
    if (!$file_log)
      echo str_pad(++$i, $countLength, ' ', STR_PAD_LEFT);
    // contentId를 배열에 추가
    $allContentIds[] = $item['contentId'];

    // db handling
    updateDatabase($item);

    if (!$file_log)
      echo $countBack;
  }
  
  if (!$file_log)
    echo $i.'%';
  
  echo "\n";
}
$sStmt->close();
$iStmt->close();
$mStmt->close();

// API에 없는 데이터 처리
// DB에는 존재하지만 API에서 존재하지 않는 데이터는 updatedTime을 업데이트하고 status를 0으로 변경한다.
$dStmt = $dbConn->prepare("UPDATE smlee1.gogocamping2 SET status=0, updatedTime='$updatedTime' WHERE status=1 AND updatedTime<>'$updatedTime'");
//$dCount = $dStmt->execute();
if ($dStmt->execute()) {
  $dCount = $dStmt->affected_rows;
}
$dStmt->close();

echo "=================\n";

echo "time = " . (microtime(true) - $time) . "\n";
echo "insert = " . $iCount . "\n";
echo "update = " . $uCount . "\n";
echo "delete = " . $dCount . "\n";

$dbConn->close();

function updateDatabase($item) {
  global $sStmt;
  global $iStmt;
  global $mStmt;
  global $uStmt;
  global $iCount;
  global $uCount;

  $sStmt->bind_param("i", $item['contentId']);
  $sStmt->execute();
  $result = $sStmt->get_result();
  if ($result->num_rows == 0) {
    $iStmt->bind_param('ssssi', $item['addr1'], $item['facltNm'], $item['createdtime'], $item['modifiedtime'], $item['contentId']);
    if ($iStmt->execute()) {
      $iCount++;
    }
  } else {
    $data = $result->fetch_assoc();
    if ($data['modifiedtime'] != $item['modifiedtime']) {
      $mStmt->bind_param('ssssi', $item['addr1'], $item['facltNm'], $item['createdtime'], $item['modifiedtime'], $item['contentId']);
      if ($mStmt->execute()) {
        $uCount++;
      }
    } else {
      // modifiedtime이 같으면 status를 1로 설정
      $uStmt->bind_param('i', $item['contentId']);
      if ($uStmt->execute()) {
      //  $uCount++;
      }
    }
  }
}

?>