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

function addColumnIfNotExists($conn, $tableName, $columnName, $columnType) {
    $checkQuery = "SHOW COLUMNS FROM `$tableName` LIKE '$columnName'";
    $result = $conn->query($checkQuery);
    if ($result->num_rows == 0) {
        $alterQuery = "ALTER TABLE `$tableName` ADD COLUMN `$columnName` $columnType";
        $conn->query($alterQuery);
    }
}

$page = 1;
while (true) {
    // API 호출 URL 설정
    $apiUrl = "https://apis.data.go.kr/B551011/GoCamping/basedList?numOfRows=" . $maxRows . "&pageNo=" . $page . "&MobileOS=WIN&MobileApp=gocam&serviceKey=" . $apiKey . "&_type=json";

    // API에서 데이터 가져오기
    $response = file_get_contents($apiUrl);
    $data = json_decode($response, true);

    // 데이터가 제대로 가져와졌는지 확인
    if ($data === null || !isset($data['response']['body']['items']['item']) || empty($data['response']['body']['items']['item'])) {
        break;
    }

    // 데이터 삽입
    foreach ($data['response']['body']['items']['item'] as $item) {
        $columns = [];
        $placeholders = [];
        $values = [];
        $types = '';

        foreach ($item as $key => $value) {
            // 컬럼이 데이터베이스에 존재하지 않으면 추가
            addColumnIfNotExists($conn, 'gogocamping', $key, 'VARCHAR(255)');
            $columns[] = $key;
            $placeholders[] = '?';
            $values[] = $value;
            $types .= 's';
        }

        $sql = "INSERT INTO gogocamping (" . implode(',', $columns) . ") VALUES (" . implode(',', $placeholders) . ")";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param($types, ...$values);
        $stmt->execute();
    }

    echo "Page $page 데이터가 성공적으로 삽입되었습니다!\n";

    // 다음 페이지로 이동
    $page++;
}

// 연결 닫기
$conn->close();

echo "모든 페이지 데이터가 성공적으로 삽입되었습니다!";
?>