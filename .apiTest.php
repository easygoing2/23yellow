<?php
// 공공 API 키 설정
const ApiKey = "a6o1csoBikLfTlFWwlW0ELSQD%2F4Ia5q3f7chas3mn6xkwsLABinMbeyOoHVv2Y%2BAycX0R4C%2FY0dZ1vJyMr3Mrw%3D%3D";

// 최대 numOfRows 값으로 설정 (한 번에 가져올 수 있는 최대 행 수)
const MaxRows = 1000;

// insert, update 데이터 갯수
$iCount = 0;
$uCount = 0;

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
$sStmt = $dbConn->prepare('SELECT modifiedtime FROM smlee1.gogocamping2 WHERE contentId=?');
$iStmt = $dbConn->prepare('INSERT INTO smlee1.gogocamping2 (addr1, facltNm, createdtime, modifiedtime, contentId) VALUES (?, ?, ?, ?, ?)');
$uStmt = $dbConn->prepare('UPDATE smlee1.gogocamping2 SET addr1=?, facltNm=?, createdtime=?, modifiedtime=? WHERE contentId=?');
$time = microtime(true);



for ($page = 1; $rows == MaxRows; $page++ ) {
  $resp = file_get_contents(ApiUrl . $page);
  $data = json_decode($resp, true);
  $list = $data['response']['body']['items']['item'];
  $rows = count($list);

  echo "rows: " . $rows . " / page = $page\n";
  foreach ($list as $item) {
    // db handling
    updateDatabase($item);
    
  }
}

echo "=================\n";

echo "time = " . (microtime(true) - $time) . "\n";
echo "insert = " . $iCount . "\n";
echo "update = " . $uCount . "\n";


$sStmt->close();
$iStmt->close();
$uStmt->close();

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