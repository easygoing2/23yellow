<?php
// 공공 API 키 설정
$apiKey = "a6o1csoBikLfTlFWwlW0ELSQD%2F4Ia5q3f7chas3mn6xkwsLABinMbeyOoHVv2Y%2BAycX0R4C%2FY0dZ1vJyMr3Mrw%3D%3D";

// 최대 numOfRows 값으로 설정 (한 번에 가져올 수 있는 최대 행 수)
$maxRows = 10;

// 페이지 번호 가져오기
$page = (int)($_GET['page'] ?? 1);
if ($page < 1) {
    $page = 1;
}

// API 호출 URL 설정
$apiUrl = "https://apis.data.go.kr/B551011/GoCamping/basedList?numOfRows=" . $maxRows . "&pageNo=" . $page . "&MobileOS=win&MobileApp=gocam&serviceKey=" . $apiKey . "&_type=json";

// cURL 초기화
$curl = curl_init();

// cURL 옵션 설정
curl_setopt($curl, CURLOPT_URL, $apiUrl);
curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

// API 호출 및 응답 데이터 받기
$response = curl_exec($curl);
$responseData = json_decode($response, true);
curl_close($curl);

$data = $responseData["response"];
$totalCount = $data["body"]["totalCount"];
$tpage = ceil($totalCount / $maxRows);

$rsc = $data["body"]["items"]["item"];


print_r($data["body"]["totalCount"]); // 총 데이터 수
echo "<br>";
print_r($data["body"]["pageNo"]); // 페이지 번호
echo "<br>";
print_r($data["body"]["numOfRows"]); // 페이지 행 수
echo "<br>";
print_r($data["header"]["resultCode"]); // 결과 코드
echo "<br>";
print_r($data["header"]["resultMsg"]); // 결과 메시지
echo "<br>";
echo "<br>";
?>