<?php

// AJAX 요청 처리 추가
if (isset($_GET['ajax']) && $_GET['ajax'] === 'true') {
  // 디버깅을 위한 로그 추가
  error_log('AJAX request received. Page: ' . $_GET['page']);
  header('Content-Type: application/json');
  $page = (int)($_GET['page'] ?? '1');
  $ROWS = ITEMS_PER_PAGE;
  $OFFSET = ($page - 1) * $ROWS;
  
  try {
    // 검색어와 현재 위치 처리는 그대로 유지
    $searchText = isset($_GET['searchText']) ? $mysqli->real_escape_string($_GET['searchText']) : '';
    $currentLat = isset($_GET['lat']) ? floatval($_GET['lat']) : null;
    $currentLng = isset($_GET['lng']) ? floatval($_GET['lng']) : null;
    
    // WHERE 절 생성
    $whereClause = '';
    if($searchText) {
        $whereClause = " WHERE facltNm LIKE '%{$searchText}%' OR addr1 LIKE '%{$searchText}%'";
    } else if ($currentLat && $currentLng) {
        // 반경 파라미터 가져오기 (기본값 5km)
        $radius = isset($_GET['radius']) ? intval($_GET['radius']) : 5;
        $whereClause = " WHERE (
            6371 * ACOS(
                LEAST(1, COS(RADIANS($currentLat)) * 
                COS(RADIANS(mapY)) * 
                COS(RADIANS(mapX) - RADIANS($currentLng)) + 
                SIN(RADIANS($currentLat)) * 
                SIN(RADIANS(mapY)))
            )) <= $radius";
    }
    
    // 쿼리 실행
    if ($currentLat && $currentLng) {
        $sql = "SELECT *,
            (6371 * ACOS(
              LEAST(1, COS(RADIANS($currentLat)) * 
              COS(RADIANS(mapY)) *  
              COS(RADIANS(mapX) - RADIANS($currentLng)) + 
              SIN(RADIANS($currentLat)) * 
              SIN(RADIANS(mapY)))
            )) AS distance
          FROM gogocamping
          $whereClause 
          ORDER BY distance ASC 
          LIMIT $OFFSET, " . ITEMS_PER_PAGE;
    } else {
        $sql = "SELECT * FROM gogocamping{$whereClause} 
                ORDER BY contentId ASC 
                LIMIT $OFFSET, " . ITEMS_PER_PAGE;
    }
    
    error_log('SQL Query: ' . $sql); // SQL 쿼리 로깅
    
    $result = $mysqli->query($sql);
    if (!$result) {
        throw new Exception($mysqli->error);
    }
    
    $items = [];
    while ($rs = $result->fetch_object()) {
        $items[] = $rs;
    }

    // NULL 값을 안전하게 처리하는 헬퍼 함수 추가
    function safe_string($str) {
      return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
    }

    // 무한스크롤 추가(+) 데이터 생성 부분
    $html = '';
    // 현재 페이지 번호를 가져옵니다. (AJAX 요청 시 'page' 파라미터로 전달됨)
    $currentPageForNumbering = (int)($_GET['page'] ?? '1'); 
    // 각 아이템에 대한 순번을 계산하기 위한 시작 번호
    // 첫 페이지는 0부터 시작하므로, (페이지번호 - 1) * 페이지당 아이템 수
    $itemNumberStart = ($currentPageForNumbering - 1) * ITEMS_PER_PAGE;

    foreach ($items as $index => $r) { // $index를 사용하여 각 아이템의 순번을 가져옵니다.
      $itemNumber = $itemNumberStart + $index + 1; // 실제 표시될 번호
      $html .= '<tr alt="무한스크롤 추가 데이터">
        <td>
          <div class="thumbnail" style="background-image: url(\'' . 
          ($r->firstImageUrl ? safe_string($r->firstImageUrl) : $_SERVER["DOCUMENT_URI"].'/static/img/no-image.png') . 
          '\')" title="' . safe_string($r->facltNm) . '"></div>
        </td>
        <td class="content">
          ' . $itemNumber . '. ' . // <--- 여기에 번호를 추가합니다.
          '<a href="#" alt="캠핑장명" class="place-name">' . 
          safe_string($r->facltNm) . '</a>
          <a class="detail-link" href="view?contentId=' . safe_string($r->contentId) . '">자세히보기</a>
          <a href="#" alt="주소" class="address-link" 
            data-mapx="' . safe_string($r->mapX) . '"
            data-mapy="' . safe_string($r->mapY) . '"
            data-transformedX="' . safe_string($r->transformedX) . '"
            data-transformedY="' . safe_string($r->transformedY) . '"
            data-name="' . safe_string($r->facltNm) . '"
            data-tel="' . safe_string($r->tel) . '"
            data-homepage="' . safe_string($r->homepage) . '"
            data-contentid="' . safe_string($r->contentId) . '"
            data-intro="' . safe_string($r->intro) . '">
            ' . safe_string($r->addr1) . '
          </a>
        </td>
      </tr>';
    }
    
    echo json_encode([
        'success' => true,
        'html' => $html,
        'hasMore' => count($items) === ITEMS_PER_PAGE
    ]);
    
  } catch (Exception $e) {
    error_log('Error in AJAX request: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
  }
  exit;
}

?>