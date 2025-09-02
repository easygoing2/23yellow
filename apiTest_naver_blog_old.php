<?php
// 네이버 API 클라이언트 정보
$client_id = "CRZ2bOUL_QOLxjvHpw8b"; // 네이버 개발자 센터에서 발급받은 Client ID
$client_secret = "82_obsaVD1"; // 네이버 개발자 센터에서 발급받은 Client Secret

// 검색할 키워드 설정
$query = "달마당 캠핑장"; // 검색어
$encText = urlencode($query);

// 블로그 검색 API 엔드포인트
$url = "https://openapi.naver.com/v1/search/blog.json?query=" . $encText;

// 추가 파라미터 (옵션)
$url .= "&display=10"; // 검색 결과 출력 개수 (기본값 10, 최대 100)
$url .= "&start=1"; // 검색 시작 위치 (기본값 1, 최대 1000)
$url .= "&sort=date"; // 정렬 옵션: sim (유사도순), date (날짜순)

// cURL 초기화
$ch = curl_init();

// cURL 옵션 설정
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

// 헤더 설정 (인증 정보 포함)
$headers = array(
    "X-Naver-Client-Id: " . $client_id,
    "X-Naver-Client-Secret: " . $client_secret
);
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

// SSL 인증서 검증 해제 (문제가 있을 경우 주석 해제)
// curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

// API 요청 실행
$response = curl_exec($ch);

// HTTP 상태 코드 확인
$status_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

// cURL 세션 종료
curl_close($ch);

// 결과 처리
if ($status_code == 200) {
    // JSON 응답을 배열로 디코딩
    $result = json_decode($response, true);
    
    // 검색 결과 출력
    echo "<h1>캠핑장 블로그 검색 결과</h1>";
    echo "<ul>";
    foreach ($result['items'] as $item) {
        echo "<li>";
        
        // <b> 태그 제거
        echo "<strong>" . htmlspecialchars(strip_tags($item['title'])) . "</strong><br>";
        
        // 블로그 제목과 링크
        echo "블로그 제목: <a href='" . htmlspecialchars($item['link']) . "' target='_blank'>" . htmlspecialchars($item['bloggername']) . "</a><br>";
        
        // 포스트 링크
        echo "포스트 링크: <a href='" . htmlspecialchars($item['link']) . "' target='_blank'>바로가기</a><br>";
        
        // 포스트 내용 (요약)
        $description = strip_tags($item['description']);
        echo "요약: " . htmlspecialchars($description) . "<br>";
        
        // 포스트 작성일
        echo "작성일: " . htmlspecialchars($item['postdate']) . "<br>";
        
        echo "</li><br>";
    }
    echo "</ul>";
} else {
    // 에러 발생 시 메시지 출력
    echo "Error 내용: " . htmlspecialchars($response);
}
?>
