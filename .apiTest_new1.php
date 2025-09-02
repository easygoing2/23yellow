<?php
// 공공 API 키 설정
const ApiKey = "a6o1csoBikLfTlFWwlW0ELSQD%2F4Ia5q3f7chas3mn6xkwsLABinMbeyOoHVv2Y%2BAycX0R4C%2FY0dZ1vJyMr3Mrw%3D%3D";

// 최대 numOfRows 값으로 설정 (한 번에 가져올 수 있는 최대 행 수)
const MaxRows = 1000;

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

// 쿼리 준비 (SELECT, INSERT, UPDATE)
$sStmt = $dbConn->prepare('SELECT modifiedtime FROM smlee1.gogocamping WHERE contentId=?');
$iStmt = $dbConn->prepare('INSERT INTO smlee1.gogocamping (addr1, facltNm, createdtime, modifiedtime, contentId) VALUES (?, ?, ?, ?, ?)');
$uStmt = $dbConn->prepare('UPDATE smlee1.gogocamping SET addr1=?, facltNm=?, createdtime=?, modifiedtime=? WHERE contentId=?');
$time = microtime(true);

// 모든 contentID를 저장할 배열
$allContentId = array();

// status 업데이트를 위한 prepared statement 추가
$dStmt = $dbConn->prepare('UPDATE smlee1.gogocamping SET status = 0 WHERE contentId = ?');

for ($page = 1; $rows == MaxRows; $page++ ) {
  $resp = file_get_contents(ApiUrl . $page);
  $data = json_decode($resp, true);
  $list = $data['response']['body']['items']['item'];
  $rows = count($list);

  echo "rows: " . $rows . " / page = $page\n";
  foreach ($list as $item) {
     // contentId를 배열에 추가
    $allContentIds[] = $item['contentId'];
    
    // db handling
    updateDatabase($item);
    
  }
}

// API에 없는 데이터 처리
updateDeletedRows($allContentIds);

echo "=================\n";

echo "time = " . (microtime(true) - $time) . "\n";
echo "insert = " . $iCount . "\n";
echo "update = " . $uCount . "\n";
echo "deleted = " . $dCount . "\n";


$sStmt->close();
$iStmt->close();
$uStmt->close();
$dStmt->close();
$dbConn->close();

function updateDatabase($item) {
  global $dbConn;
  global $sStmt;
  global $iStmt;
  global $uStmt;
  global $iCount;
  global $uCount;

  //$item['contentId'] = 12345;
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
      $uStmt->bind_param('ssssi', $item['addr1'], $item['facltNm'], $item['createdtime'], $item['modifiedtime'], $item['contentId']);
      if ($uStmt->execute()) {
        $uCount++;
      }
    }
  }
}

function updateDeletedRows($allContentIds) {
  global $dbConn, $dStmt, $dCount;
  
  // 데이터베이스의 모든 contentId 가져오기
  $result = $dbConn->query("SELECT contentId FROM smlee1.gogocamping WHERE status != 0");
  
  while ($row = $result->fetch_assoc()) {
    if (!in_array($row['contentId'], $allContentIds)) {
      $dStmt->bind_param('i', $row['contentId']);
      if ($dStmt->execute()) {
        $dCount++;
      }
    }
  }
}