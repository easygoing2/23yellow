<?php
// 공공 API 키 설정
$apiKey = "a6o1csoBikLfTlFWwlW0ELSQD%2F4Ia5q3f7chas3mn6xkwsLABinMbeyOoHVv2Y%2BAycX0R4C%2FY0dZ1vJyMr3Mrw%3D%3D";

// 최대 numOfRows 값으로 설정 (한 번에 가져올 수 있는 최대 행 수)
$maxRows = 100;

// 데이터베이스 연결 정보
$hostname = "dev.arasoft.kr";
$dbuserid = "smlee";
$dbpasswd = "tkdalselql";
$dbname = "smlee1";
$dbport = "34591";

// 데이터베이스에 연결
$conn = new mysqli($hostname, $dbuserid, $dbpasswd, $dbname, $dbport);

// 연결 확인
if ($conn->connect_error) {
    die("연결 실패: " . $conn->connect_error);
}



// SQL 준비 및 바인딩 (INSERT용)
$sql = "REPLACE INTO smlee1.gogocamping (contentId, addr1, facltNm, createdtime, modifiedtime) VALUES (?, ?, ?, ?, ?)";
// sql 구문에 문제가 없는지 확인하고 준비하는 부분
$stmt = $conn->prepare($sql);

$page = 1;
while (true) {
    // API 호출 URL 설정
    $apiUrl = "https://apis.data.go.kr/B551011/GoCamping/basedList?numOfRows=" . $maxRows . "&pageNo=" . $page . "&MobileOS=WIN&MobileApp=gocam&serviceKey=" . $apiKey . "&_type=json";

    // API에서 데이터 가져오기
    $response = file_get_contents($apiUrl);
    $data = json_decode($response, true);

    // 데이터가 제대로 가져와졌는지 확인 (null이거나 items.item이 비어있는지 확인)
    if ($data === null || !isset($data['response']['body']['items']['item']) || empty($data['response']['body']['items']['item'])) {
        break;
    }

    // 데이터 삽입
    foreach ($data['response']['body']['items']['item'] as $item) {
        echo "<pre>";
        echo $item['contentId'] . " " . $item['addr1'] . " " . $item['facltNm'] . " " . $item['createdtime'] . " " . $item['modifiedtime'] . "\n";
        //var_dump($item['contentId'], $item['addr1'], $item['facltNm'], $item['createdtime'], $item['modifiedtime']);
        // $item에서 값을 바인딩함. 쿼리문을 실행하기 전.
        $stmt->bind_param("issss", $item['contentId'], $item['addr1'], $item['facltNm'], $item['createdtime'], $item['modifiedtime']);
        // 쿼리문을 실행하는 부분
        $stmt->execute();
    }

    echo "Page $page 데이터가 성공적으로 삽입되었습니다!\n";

    // 다음 페이지로 이동
    $page++;
}

// 스테이트먼트와 연결 닫기
$stmt->close();
$conn->close();

echo "모든 페이지 데이터가 성공적으로 삽입되었습니다!";