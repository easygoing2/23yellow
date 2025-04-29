<?php
// ===== [1] 네이버 API 클라이언트 정보 =====
$client_id = "CRZ2bOUL_QOLxjvHpw8b";    // 네이버 개발자 센터에서 발급받은 Client ID
$client_secret = "82_obsaVD1";         // 네이버 개발자 센터에서 발급받은 Client Secret

// ===== [2] DB 연결 (index.php의 config.php와 동일하게) =====
include $_SERVER["DOCUMENT_ROOT"] . "dbconfig.php";

$dbConn = new mysqli($hostname, $dbuserid, $dbpasswd, $dbname, $dbport);
if ($dbConn->connect_error) {
  throw new Exception("데이터베이스 연결 실패: " . $dbConn->connect_error);
}
$dbConn->set_charset("utf8mb4");

// ===== [3] updatedtime 확보 =====
$stmt = $dbConn->prepare('SELECT NOW() AS updatedtime');
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows > 0) {
	$updatedtime = $result->fetch_assoc()['updatedtime'];
  echo "updatedtime: " . $updatedtime . "<br>\n";
} else {
    echo 'updatedtime fail..';
  exit;
}
$stmt->close();

// ----------------------------------------------------------------------------------
//  (A) gogocamping 테이블에서 캠핑장 목록( contentId, facltNm ) 전부 가져오기
// ----------------------------------------------------------------------------------
$sql = "SELECT contentId, facltNm 
        FROM smlee1.gogocamping
        WHERE facltNm IS NOT NULL AND facltNm <> ''"; 
         // 필요 시 조건 추가 가능 (예: AND contentId IS NOT NULL)
$campStmt = $dbConn->prepare($sql);
$campStmt->execute();
$campRes = $campStmt->get_result();

// 만약 캠핑장 데이터가 없으면 종료
if ($campRes->num_rows === 0) {
	echo "gogocamping 테이블에 캠핑장 데이터가 없습니다.<br>\n";
	exit;
}

// 모든 캠핑장을 배열로
$allCamps = $campRes->fetch_all(MYSQLI_ASSOC);
$campStmt->close();

echo "총 " . count($allCamps) . "개의 캠핑장에 대해 블로그 검색을 진행합니다.<br>\n";

// ----------------------------------------------------------------------------------
//  (B) 각 캠핑장에 대해 블로그 검색 & 저장
// ----------------------------------------------------------------------------------
foreach ($allCamps as $camp) {
    // 1) 캠핑장 정보
    $contentId = $camp['contentId'];
    $facltNm   = $camp['facltNm'];
    
    // 2) 검색할 키워드 (facltNm)
    //    캠핑장 이름만 쓰면 관련 블로그가 나올 수 있지만,
    //    필요하다면 지역 정보나 "캠핑장" 등의 문자열을 조합할 수도 있습니다.
    //    ex) $query = $facltNm . " 캠핑장";
    $query = $facltNm;

    // 3) 네이버 블로그 검색 API URL 구성
    $encText = urlencode($query);
    $apiUrl  = "https://openapi.naver.com/v1/search/blog.json?query=" . $encText;
    $apiUrl .= "&display=10";  // 검색 결과 10개
    $apiUrl .= "&start=1";     // 검색 시작 위치
    $apiUrl .= "&sort=date";   // 날짜순 정렬

    // -- 진행상황 표시용
    echo "<hr>";
    echo "<strong>캠핑장: {$facltNm} (contentId = {$contentId})</strong><br>\n";
    echo "API 호출: {$apiUrl}<br>\n";

    // 4) cURL로 API 호출
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $apiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $headers = array(
        "X-Naver-Client-Id: " . $client_id,
        "X-Naver-Client-Secret: " . $client_secret
    );
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    // 필요 시 인증서 검증 비활성화
    // curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

    $response   = curl_exec($ch);
    $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    // 5) 응답 처리
    if ($statusCode == 200) {
        $resultData = json_decode($response, true);

        if (isset($resultData['items']) && is_array($resultData['items'])) {
            $itemsCount = count($resultData['items']);
            echo "→ 검색 결과 수: {$itemsCount}<br>\n";

            foreach ($resultData['items'] as $item) {
                // (i) HTML 태그 제거
                $blogTitle       = strip_tags($item['title']);
                $blogLink        = $item['link'];
                $blogDescription = strip_tags($item['description']);
                $bloggerName     = strip_tags($item['bloggername']);
                $postdate        = $item['postdate']; // 'YYYYMMDD'

                // (ii) 'YYYYMMDD' → 'YYYY-MM-DD'
                if (preg_match("/^\d{8}$/", $postdate)) {
                    $year     = substr($postdate, 0, 4);
                    $month    = substr($postdate, 4, 2);
                    $day      = substr($postdate, 6, 2);
                    $postdate = "$year-$month-$day";
                }

                // (iii) DB Insert
                //   - 중복 체크( blogLink ) 후 Insert/Update 하는 방식으로 확장 가능
                $iStmt = $dbConn->prepare("
                    INSERT INTO gogocamping_blogs 
                        (contentId, blogTitle, blogLink, blogDescription, bloggerName, blogPostdate, updatedtime)
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                ");
                $iStmt->bind_param(
                    "issssss",
                    $contentId,
                    $blogTitle,
                    $blogLink,
                    $blogDescription,
                    $bloggerName,
                    $postdate,
                    $updatedtime
                );
                $iStmt->execute();
                $iStmt->close();

                // (출력) 확인용
                echo "   → [INSERT] {$blogTitle} <br>\n";
            }

        } else {
            echo "→ 블로그 검색 결과가 없습니다.<br>\n";
        }
    } else {
        echo "→ API Error, statusCode: {$statusCode}<br>\n";
        echo "   응답 내용: " . htmlspecialchars($response) . "<br>\n";
    }

    // (선택) 너무 빠른 API 연속 호출을 피하고 싶다면 쉬는 시간 추가
    sleep(1);
} // end foreach

// ----------------------------------------------------------------------------------
//  (C) DB 연결 종료
// ----------------------------------------------------------------------------------
$dbConn->close();
echo "----------------------------------------------------\n";
echo "모든 캠핑장에 대한 블로그 검색이 완료되었습니다.\n";
echo "----------------------------------------------------\n";
?>
