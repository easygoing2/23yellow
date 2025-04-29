<?php
// 에러 핸들링 설정
error_reporting(E_ALL);
ini_set('display_errors', 0);
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
});
// 페이지당 표시할 아이템 수 상수 정의
const ITEMS_PER_PAGE = 20;
include $_SERVER['DOCUMENT_ROOT'] . "/dbconfig.php";
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

    // 무한스크롤 추가 데이터 생성 부분
    $html = '';
    foreach ($items as $r) {
      $html .= '<tr alt="무한스크롤 추가 데이터">
        <td>
          <div class="thumbnail" style="background-image: url(\'' . 
          ($r->firstImageUrl ? safe_string($r->firstImageUrl) : $_SERVER["DOCUMENT_URI"].'/static/img/no-image.png') . 
          '\')" title="' . safe_string($r->facltNm) . '"></div>
        </td>
        <td class="content">
          <a href="#" alt="캠핑장명" class="place-name">' . 
          safe_string($r->facltNm) . '</a><br>
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

include $_SERVER["DOCUMENT_ROOT"] . "/header.php";
// GPS좌표값을 카카오맵 카텍좌표값으로 변환해주는 API
include '.lib/KakaoApi.php';

// 검색어 받기
$searchText = isset($_GET['searchText']) ? $mysqli->real_escape_string($_GET['searchText']) : '';

// JavaScript에서 전달받은 현재 위치 좌표
$currentLat = isset($_GET['lat']) ? floatval($_GET['lat']) : null;
$currentLng = isset($_GET['lng']) ? floatval($_GET['lng']) : null;
$radius = isset($_GET['radius']) ? intval($_GET['radius']) : 15; // 기본값 15km (map.js에서도 수정해줘야 함)

// WHERE 절 생성 (캠핑장명과 주소 모두 검색)
$whereClause = '';
if($searchText) { // 사용자가 검색어를 입력한 경우
  $whereClause = " WHERE facltNm LIKE '%{$searchText}%' OR addr1 LIKE '%{$searchText}%'";
} else {
  // 현재 위치 좌표가 있는 경우에만 거리 기반 검색 실행
  if ($currentLat !== null && $currentLng !== null) {
    $whereClause = " WHERE (
      6371 * ACOS(
        LEAST(1, COS(RADIANS($currentLat)) * 
        COS(RADIANS(mapY)) * 
        COS(RADIANS(mapX) - RADIANS($currentLng)) + 
        SIN(RADIANS($currentLat)) * 
        SIN(RADIANS(mapY)))
      )) <= $radius";
  }
}

// 전체 게시물 수 계산 (페이지네이션을 위함)
$sqlCount = "SELECT COUNT(*) total FROM gogocamping" . $whereClause;
$result = $mysqli->query($sqlCount);
$rs = $result->fetch_object();
$total = $rs->total;

// 검색 결과 쿼리 수정 (거리순으로 정렬)
$sql = "SELECT * FROM gogocamping{$whereClause} ORDER BY contentId ASC LIMIT " . ITEMS_PER_PAGE;
$result = $mysqli->query($sql);
$rsc = [];
while ($rs = $result->fetch_object()) {
  array_push($rsc, $rs);
}

?>

<div class="container-board">
  <div class="contents">
    <!-- 1. 게시판 -->
    <div class="board">
      <table class="table table-striped">
        <tbody>
          <?php
          $i = 0;
          foreach ($rsc as $r) {
          ?>
          <tr alt="미리 세팅되는 20개 데이터">
            <td>
              <div class="thumbnail" style="background-image: url('<?php echo $r->firstImageUrl ?: $_SERVER["DOCUMENT_URI"].'/static/img/no-image.png' ?>')" title="<?php echo $r->facltNm ?>"></div>
            </td>
            <td class="content">
              <?php echo $i + 1; ?>.
              <a href="#" alt="캠핑장명" class="place-name"><?php echo $r->facltNm ?></a>
              <a class="detail-link" href="view?contentId=<?php echo $r->contentId; ?>">자세히보기</a>
              <a class="address-link" href="#" alt="주소" 
                data-mapx="<?php echo $r->mapX ?>"
                data-transformedX="<?php echo $r->transformedX ?>"
                data-mapy="<?php echo $r->mapY ?>"
                data-transformedY="<?php echo $r->transformedY ?>"
                data-name="<?php echo $r->facltNm ?>"
                data-tel="<?php echo $r->tel ?>"
                data-homepage="<?php echo $r->homepage ?>"
                data-contentid="<?php echo $r->contentId ?>"
                data-intro="<?php echo $r->intro ?>"
              >
                <?php echo $r->addr1 ?>
              </a>
            </td>
          </tr>
          <?php $i++; ?>
          <?php } ?>
        </tbody>
      </table>
      <div id="loading" style="display: none; text-align: center; padding: 20px;">
          Loading...
      </div>
    </div>

    <!-- 2. 카카오 지도 -->
    <div id="mapWrap">
      <div id="map"></div>
    </div>

    <!-- 모달로 변경된 지도 경로 표시 -->
    <div id="mapModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background-color:rgba(0,0,0,0.7); z-index:1000; justify-content:center; align-items:center;">
      <div style="position:relative; width:90%; height:90%; max-width:1000px; background-color:white; border-radius:8px; overflow:hidden;">
        <button id="closeMapModal" style="position:absolute; top:10px; right:10px; z-index:1001; background-color:#ff4500; color:white; border:none; border-radius:50%; width:30px; height:30px; font-size:16px; cursor:pointer;">×</button>
        <iframe id="iframeMap" style="width:100%; height:100%;" frameborder="0" src="https://map.kakao.com/"></iframe>
      </div>
    </div>
    
    <!-- 3. 캠핑장 상세 정보 -->
    <div class="faclt-detail-wrap">
      <?php
      // 1) 첫 번째 캠핑장
      if (!empty($rsc)) {
        $firstCamp = $rsc[0];
        //Console_log($firstCamp); // 구조 확인

        // 2) 첫 번째 캠핑장 이미지 목록 가져오기
        $images = [];
        $sqlImages = "SELECT * FROM gogocamping_image WHERE contentId = " . (int)$firstCamp->contentId . " ORDER BY id ASC";
        $resultImages = $mysqli->query($sqlImages);
        if ($resultImages) {
          $images = $resultImages->fetch_all(MYSQLI_ASSOC);
        }
          // 3) 블로그 목록 가져오기
        $sqlBlogs = "
          SELECT * FROM gogocamping_blogs WHERE contentId = " . (int)$firstCamp->contentId . " ORDER BY id DESC LIMIT 10";
        $resultBlogs = $mysqli->query($sqlBlogs);
        // 에러 확인
        if (!$resultBlogs) {
          echo "Blog query error => " . $mysqli->error;
        }
        // 결과 세트 받아오기 (없는 경우 빈 배열)
        $blogs = $resultBlogs ? $resultBlogs->fetch_all(MYSQLI_ASSOC) : [];
        $mysqli->close();
      ?>

      <div class="faclt-info-wrap">
        <div class="faclt-thumb">
          <div class="img" style="background-image: url('<?php echo $firstCamp->firstImageUrl ?: $_SERVER["DOCUMENT_URI"].'/static/img/no-image.png' ?>')" title="<?php echo $firstCamp->facltNm ?>"></div>
        </div>
        <div class="faclt-txt">
          <div class="faclt-tit">
            <?php echo $firstCamp->facltNm ?>
            <span>(<?php echo $firstCamp->manageSttus ?> 중)</span>
          </div>
          <div class="faclt-addr">주소 : <?php echo $firstCamp->addr1 ?></div>
          <div class="faclt-tel">TEL : <?php echo $firstCamp->tel ?: '정보 없음' ?></div>
          <div class="faclt-tel"><span>H.P : </span><a href="<?php echo $firstCamp->homepage ?: '#' ?>" target="_blank" ><?php echo $firstCamp->homepage ?: '정보 없음' ?></a></div>
        </div>
      </div>
      <div class="faclt-add-txt" <?php echo ($firstCamp->intro === '정보없음' || empty($firstCamp->intro)) ? 'style="display: none;"' : ''; ?>>
        <div class="txt-wrap">
          <div class="txt"><?php echo $firstCamp->intro ?: '정보없음' ?></div>
        </div>
      </div>
      <!-- (수정) gogocamping_image DB에서 불러온 이미지를 출력. -->
      <div class="faclt-img-wrap">
        <div class="detail-img">
          <?php if (count($images) > 0) {
            // 이미지가 있다면 갯수만큼 표시
            foreach ($images as $img) { 
              // imageUrl, created_time, modified_time 등이 있을 수 있음. 
              $imageUrl = $img['local_image_path'] ?: 'static/img/no-image.png';
          ?>
            <div class="faclt-image">
              <img src="<?php echo $_SERVER["DOCUMENT_URI"] . '/' . $imageUrl; ?>" alt="캠핑장 이미지">
            </div>
          <?php 
            } 
          } else { 
            $imageUrl = $firstCamp->firstImageUrl ?: 'static/img/no-image.png';
            ?>
            <div class="no-image">
              <!-- 이미지가 없다면 기본 이미지를 1개만 표시 -->
              <img src="<?php echo $_SERVER["DOCUMENT_URI"] . '/' . $imageUrl; ?>" alt="기본 이미지">
            </div>
          <?php } ?>
        </div>
      </div>
      <!-- (중요) 블로그 글 영역: faclt-review-wrap -->
      <div class="faclt-review-wrap">
        <?php if (count($blogs) > 0) { ?>
          <?php foreach ($blogs as $blog) { ?>
            <div class="contents-wrap">
              <div class="contents">
                <!-- 블로그에 썸네일 이미지가 없다면, 캠핑장 기본 이미지를 재사용하거나 no-image로 처리 -->
                <img src="<?php echo $firstCamp->firstImageUrl ?: $_SERVER["DOCUMENT_URI"].'/static/img/no-image.png' ?>" alt="blog image">
                <div class="review-wrap">
                  <!-- 블로그 제목 -->
                  <div class="tit"><?php echo htmlspecialchars($blog['blogTitle']); ?></div>
                  <!-- 블로그 글 내용/요약 -->
                  <div class="txt">
                    <?php echo nl2br(htmlspecialchars($blog['blogDescription'])); ?>
                  </div>
                  <!-- 작성일 및 블로그 링크 -->

                  <div class="link">
                    <div class="date">작성일: <?php echo htmlspecialchars($blog['blogPostdate']); ?></div>
                    <a href="<?php echo htmlspecialchars($blog['blogLink']); ?>" target="_blank">블로그 글 바로가기</a>
                  </div>
                </div>
              </div>
            </div>
          <?php } ?>
        <?php } else { ?>
          <p>블로그 리뷰가 없습니다.</p>
        <?php } ?>
      </div>
      <?php } ?>
    </div>
  </div>
</div>

<!-- 이미지 갤러리 모달 -->
<div id="imageModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background-color:rgba(0,0,0,0.7); z-index:1001; justify-content:center; align-items:center;">
  <div class="modal-content" style="position:relative; width:90%; max-width:1200px;">
    <button id="closeImageModal" style="position:absolute; top:-40px; right:0; z-index:1002; background-color:#ff4500; color:white; border:none; border-radius:50%; width:30px; height:30px; font-size:16px; cursor:pointer;">×</button>
    
    <!-- Swiper -->
    <div class="swiper imageSwiper">
      <div class="swiper-wrapper">
        <!-- 스와이퍼 슬라이드는 JavaScript에서 동적으로 추가됩니다 -->
      </div>
      
      <!-- 페이지네이션 -->
      <div class="swiper-pagination"></div>
      
      <!-- 네비게이션 버튼 -->
      <div class="swiper-button-next"></div>
      <div class="swiper-button-prev"></div>
    </div>
    
    <!-- 이미지 카운터 추가 -->
    <div id="imageCounter" style="color:white; text-align:center; margin-top:15px; font-size:16px;"></div>
  </div>
</div>

<?php
include $_SERVER["DOCUMENT_ROOT"] . "/footer.php";
?>
</div>

<!-- 카카오 지도 관련 js -->
<script src="<?php echo $_SERVER["DOCUMENT_URI"] ?>/static/js/map.js"></script>
<!-- 게시판 관련 js -->
<script src="<?php echo $_SERVER["DOCUMENT_URI"] ?>/static/js/board.js"></script>
<!-- 검색 관련 js -->
<script src="<?php echo $_SERVER["DOCUMENT_URI"] ?>/static/js/search.js"></script>
<!-- 로딩 애니메이션션 관련 js -->
<script src="<?php echo $_SERVER["DOCUMENT_URI"] ?>/static/js/loading.js"></script>
<!-- 이미지 갤러리 관련 js -->
<script src="<?php echo $_SERVER["DOCUMENT_URI"] ?>/static/js/image-gallery.js"></script>

<!-- Swiper.js CSS -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css" />
<!-- Swiper.js JavaScript -->
<script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>

<style>
  .swiper {
    width: 100%;
    height: 100%;
  }
  
  .swiper-slide {
    text-align: center;
    background: transparent;
  }
  
  .swiper-button-next,
  .swiper-button-prev {
    color: #ffffff;
  }
  
  .swiper-pagination-bullet {
    background: #ffffff;
  }
</style>

<script>
let currentPage = 1;
let isLoading = false;
let hasMore = true;
const campingTable = document.querySelector('.table tbody');
const loadingDiv = document.getElementById('loading');



const addedMarkers = []; // 이미 추가된 마커의 contentId를 저장

// ✅ 카카오맵(Kakao Map) API를 사용하여 지도에 마커를 추가하는 역할
function addMarkerToMap(mapX, mapY, name, contentId) {
  if (addedMarkers.includes(contentId)) return;

  const markerPosition = new kakao.maps.LatLng(mapY, mapX);
  const marker = new kakao.maps.Marker({
    position: markerPosition,
    title: name
  });
  marker.setMap(window.map);
  addedMarkers.push(contentId);
}

// ✅ initializeNewElements 함수는 동적으로 생성된 HTML 요소에 이벤트 리스너를 초기화하는 역할
function initializeNewElements() {
  const newAddressLinks = document.querySelectorAll('.address-link:not([data-initialized])');
  newAddressLinks.forEach(link => {
    link.setAttribute('data-initialized', 'true');
    link.addEventListener('click', function(e) {
      e.preventDefault();
      const mapX = this.getAttribute('data-mapx');
      const mapY = this.getAttribute('data-mapy');
      const name = this.getAttribute('data-name');
      if (typeof updateMap === 'function') {
          updateMap(mapX, mapY, name);
      }
    });

    const mapX = link.getAttribute('data-mapx');
    const mapY = link.getAttribute('data-mapy');
    const name = link.getAttribute('data-name');
    const contentId = link.getAttribute('data-contentid');
    if (mapX && mapY && name && contentId) {
      addMarkerToMap(parseFloat(mapX), parseFloat(mapY), name, contentId);
    }
  });

  const newPlaceNames = document.querySelectorAll('.place-name:not([data-initialized])');
  newPlaceNames.forEach(link => {
    link.setAttribute('data-initialized', 'true');
    link.addEventListener('click', function(e) {
      e.preventDefault();
      const addressLink = this.parentElement.querySelector('.address-link');
      if (addressLink) {
        addressLink.click();
      }
    });
  });
}

// ✅ 페이지 완전히 로드되고 나서 아래 함수 실행
// 1. 전체 캠핑장 표시
// 2. 캠핑장 상세 정보 표시
// 3. 카카오 지도 표시
document.addEventListener('DOMContentLoaded', function() {
  initializeNewElements();
  console.log("페이지 완전히 로드됨. 아래 함수 실행!");
  const urlParams = new URLSearchParams(window.location.search);
  console.log("urlParams : ", urlParams);
  if (urlParams.has('radius')) {
    radius = parseInt(urlParams.get('radius'));
    console.log("radius : ", radius);
  }
  // debugger;
  // 현재 위치 표시 + 반경 5km 이내 캠핑장 표시
  handleGeolocation();
  // 현재 위치 표시
  // addCurrentLocationMarker();
  // 캠핑장 전체 표시
  // displayAllCampings();
});

// 검색 이벤트에 displayAllCampings 함수 연결
document.getElementById('searchButton').addEventListener('click', function() {
  // 기존 검색 로직 실행 후
  setTimeout(displayAllCampings, 500); // 검색 결과가 로드된 후 마커 표시
});

// 엔터키 검색 이벤트에도 추가
document.getElementById('search').addEventListener('keypress', function(e) {
  if (e.key === 'Enter') {
    setTimeout(displayAllCampings, 500);
  }
});

// ✅ 무한 스크롤(Infinite Scroll) 기능 : 추가 데이터 로드
async function loadMoreData(retryCount = 0) {
    if (isLoading || !hasMore) return;
    
    const maxRetries = 3;
    const retryDelay = 2000; // 2초 후 재시도
    
    isLoading = true;
    loadingDiv.style.display = 'block';
    
    try {
        const searchParams = new URLSearchParams(window.location.search);
        searchParams.set('ajax', 'true');
        searchParams.set('page', currentPage + 1);  // 다음페이지 요청
        
        console.log('Fetching URL:', `?${searchParams.toString()}`); // URL 로깅
        
        const response = await fetch(`?${searchParams.toString()}`);
        
        // 429 에러 처리
        if (response.status === 429) {
            if (retryCount < maxRetries) {
                console.log(`Rate limit 도달, ${retryDelay/1000}초 후 재시도 (${retryCount + 1}/${maxRetries})`);
                setTimeout(() => {
                    isLoading = false;
                    loadMoreData(retryCount + 1);
                }, retryDelay);
                return;
            } else {
                throw new Error('요청 한도를 초과했습니다. 잠시 후 다시 시도해주세요.');
            }
        }
        
        const contentType = response.headers.get('content-type');
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        if (!contentType || !contentType.includes('application/json')) {
            throw new Error(`Expected JSON but got ${contentType}`);
        }
        
        const data = await response.json(); // 서버에서 받은 데이터를 JSON으로 변환
        
        if (data.success) {
            campingTable.insertAdjacentHTML('beforeend', data.html); // 테이블에 추가 데이터 삽입
            hasMore = data.hasMore; // 더 많은 데이터가 있는지 여부
            currentPage++; // 페이지 번호 증가
            initializeNewElements(); // 새로운 요소에 이벤트 리스너 추가
        } else if (data.error) {
            throw new Error(data.error); // 에러 발생시 예외 처리
        }
    } catch (error) {
        console.error('데이터 로드 중 오류:', error);
        const errorDiv = document.createElement('div');
        errorDiv.className = 'alert alert-danger';
        errorDiv.textContent = '데이터를 불러오는 중 오류가 발생했습니다. 페이지를 새로고침해주세요.';
        campingTable.parentNode.insertBefore(errorDiv, campingTable.nextSibling);
    } finally {
        isLoading = false;
        loadingDiv.style.display = 'none';
    }
}

// ✅ 무한 스크롤 이벤트 최적화
let scrollTimeout;
let lastScrollTime = 0;
const scrollThrottleTime = 500; // 0.5초마다 스크롤 이벤트 처리

const boardContainer = document.querySelector('.table.table-striped');
boardContainer.addEventListener('scroll', function() {
    const now = Date.now();
    
    // 마지막 스크롤 이벤트로부터 일정 시간이 지나지 않았으면 무시
    if (now - lastScrollTime < scrollThrottleTime) return;
    
    lastScrollTime = now;
    
    if (boardContainer.scrollHeight - boardContainer.scrollTop <= boardContainer.clientHeight + 200) {
        console.log('스크롤 감지, 데이터 로드 시작');
        loadMoreData();
    }
});

// ✅ XSS 안전처리를 위한 간단 함수
function escapeHtml(str) {
  return str.replace(/[&<>"']/g, function(m) {
    return ({
      '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'
    })[m];
  });
}
function escapeAttr(str) {
  return str.replace(/"/g, '&quot;');
}


// ✅ GPS좌표를 카텍좌표계로 변환하는 함수.
// 프론트에서 생성된 내GPS 좌표를 서버(PHP)로 요청을 보내서 비동기로 카텍 좌표값을 받아와서 iframeSrc변수에 적용함.
function geoTransCoord(position, func) {
  console.log('🚧 geoTransCoord 함수 실행(GPS좌표를 카텍좌표계로 변환하는 함수)');
  let result = {
    ok: false,  // 요청 성공 여부를 저장.
    status: 0,  // 서버의 응답 상태 코드. (예: 200, 404 등)
    data: null  // 서버가 보낸 데이터를 저장.
  };
  fetch('api/kakao?x=' + position.x + '&y=' + position.y, {   // 🚧 서버에 요청보내기
    method: 'GET',
    headers: {'Accept': 'application/json'}
  }).then(function (response) {         // 🚧 서버응답처리
    result.ok = response.ok;            // 서버 요청이 성공했는지 확인.
    result.status = response.status;    // 서버에서 받은 응답코드 (예: 200)
    result.headers = response.headers;  // 응답의 헤더 정도. 
    return response.json();             // 서버의 응답 데이터를 JSON으로 변환.
  }).then(function (data) {     // 🚧 변환된 데이터 저장 및 처리.
    result.data = data;         // 서버에서 받은 데이터를 result 객체에 저장. 
    func(result);               // 결과를 처리할 함수를 호출하여 전달.
  }).catch(function (error) {   // 🚧 에러 처리
    result.error = error;       // 에러 정보를 result 객체에 저장.
    result.data = {msg: result.status ? "서비스" : "네트워크"};
    result.data.msg += " 오류.\n잠시 후 다시 시도해 주시기 바랍니다.";
    func(result);               // 에러 메세지와 함께 결과 처리 함수 호출.
  });
}

// 모달 닫기 버튼 이벤트 리스너
document.addEventListener('DOMContentLoaded', function() {
  // 기존 코드...
  
  // 모달 닫기 버튼 이벤트
  document.getElementById('closeMapModal').addEventListener('click', function() {
    closeMapModal();
  });
  
  // ESC 키로 모달 닫기
  document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape' && document.getElementById('mapModal').style.display === 'flex') {
      closeMapModal();
    }
  });
  
  // 모달 바깥 영역 클릭 시 닫기
  document.getElementById('mapModal').addEventListener('click', function(e) {
    if (e.target === this) {
      closeMapModal();
    }
  });
});
</script>
</body>
</html>