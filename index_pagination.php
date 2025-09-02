<?php
include $_SERVER["DOCUMENT_ROOT"] . "/header.php";

// GPS좌표값을 카카오맵 카텍좌표값으로 변환해주는 API
include '.lib/KakaoApi.php';
// $api = new KakaoApi();
// $resultApi = $api->geoTranscoord(128.3753007, 35.1365006);  // 여기에 GPS좌료값 넣으면 됨. geoTranscoord(128.3753007, 35.1365006)
// print_r($resultApi);

// PHP Console.log
function Console_log($data) {
  if (is_array($data) || is_object($data)) {
    $data = json_encode($data, JSON_UNESCAPED_UNICODE);
  } else {
    $data = json_encode($data, JSON_UNESCAPED_UNICODE);
  }
  echo "<script>console.log(JSON.parse('" . addslashes($data) . "'));</script>";
};
console_log("헬로 월드!");


$page = (int)($_GET['page'] ?? '1');  // url에 ...?page=1,2,3... 페이지 숫자를 가져옴. 페이지가 null이면 1을 반환. 
if ($page < 1) {  // $page가 1보다 작으면 1을 반환한다. 
  $page = 1;
}

$ROWS = 100;
$OFFSET = ($page - 1) * $ROWS;  // 이 줄을 추가
$rsc = [];
$total = 0;
$tpage = 1;

// 검색어 받기
$searchText = isset($_GET['searchText']) ? $mysqli->real_escape_string($_GET['searchText']) : '';

// JavaScript에서 전달받은 현재 위치 좌표
$currentLat = isset($_GET['lat']) ? floatval($_GET['lat']) : null;
$currentLng = isset($_GET['lng']) ? floatval($_GET['lng']) : null;
$radius = isset($_GET['radius']) ? intval($_GET['radius']) : 5; // 기본값 5km

// WHERE 절 생성 (캠핑장명과 주소 모두 검색)
$whereClause = '';
if($searchText) {
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
      )
    ) <= $radius";
  }
}

// 페이지네이션을 위한 전체 게시물 수 계산
$sqlCount = "SELECT COUNT(*) total FROM gogocamping" . $whereClause;
$result = $mysqli->query($sqlCount);
$rs = $result->fetch_object();
$total = $rs->total;

// 총 페이지 수 계산
$tpage = (int)($total / $ROWS);
if ($total % $ROWS > 0) {
  $tpage++;
}

// 검색 결과 쿼리 수정 (거리순으로 정렬)
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
  LIMIT $OFFSET, $ROWS";
} else {
  $sql = "SELECT * FROM gogocamping{$whereClause} ORDER BY contentId ASC LIMIT $OFFSET, $ROWS";
}
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
          $i = 1;
          foreach ($rsc as $r) {
          ?>
          <tr>
            <td>
              <div class="thumbnail" style="background-image: url('<?php echo $r->firstImageUrl ?: $_SERVER["DOCUMENT_URI"].'/static/img/no-image.png' ?>')" title="<?php echo $r->facltNm ?>"></div>
            </td>
            <td class="content">
              <!-- <a href="view?contentId=<?php echo $r->contentId; ?>"><?php echo $r->facltNm ?></a><br> // 상세페이지로 이동 -->
              <a href="#" alt="캠핑장명" class="place-name"><?php echo $r->facltNm ?></a><br>
              <a href="#" alt="주소" class="address-link" 
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
          <?php } ?>
        </tbody>
      </table>
      <div class="bottom-wrap">
        <div class="left"></div>
        <div class="pagination">
          <?php
          $VPAGE = 3;
          $start_page = (int) (($page - 1) / $VPAGE) * $VPAGE + 1;
          $end_page = $start_page + $VPAGE - 1;
          if ($end_page > $tpage) {
            $end_page = $tpage;
          }
          if ($start_page > 1) {
            echo "<a href=\"?page=1\">1</a>\n";
            //echo "&nbsp...&nbsp;\n";
            echo "<a href=\"?page=" . ($start_page - 1) . "\">Prev</a>\n";
            //echo "&nbsp...&nbsp;\n";
          }
          for ($i = $start_page; $i <= $end_page; $i++) {
            if ($i == $page) {
              echo "<a><b>$i</b></a>\n";
            } else {
              echo "<a href=\"?page=$i\">$i</a>\n";
            }
          }
          if ($end_page < $tpage) {
            //echo "&nbsp...&nbsp;\n";
            echo "<a href=\"?page=" . ($end_page + 1) . "\">Next</a>\n";
            //echo "&nbsp...&nbsp;\n";
            echo "<a href=\"?page=$tpage\">$tpage</a>\n";
          }
          ?>
        </div>
      </div>
      <div class="mt-5 text-sm text-[#a3a3a3]"><?php echo 'Total posts: ' . $total . '<br>'; ?></div>
    </div>

    <!-- 2. 카카오 지도 -->
    <div id="mapWrap">
      <div id="map"></div>
    </div>
    <div id="mapDetails" style="display:none;">
      <iframe id="iframeMap" style="width:100%;height:100%;" frameborder="0" src="https://map.kakao.com/"></iframe>
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
      <div class="faclt-add-txt">
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
              $imageUrl = $img['local_image_path'] ?: $_SERVER["DOCUMENT_URI"].'/static/img/no-image.png';
          ?>
            <div class="faclt-image">
              <img src="<?php echo $imageUrl; ?>" alt="캠핑장 이미지">
            </div>
          <?php 
            } 
          } else { 
            ?>
            <div class="no-image">
              <!-- 이미지가 없다면 기본 이미지를 1개만 표시 -->
              <img src="<?php echo $firstCamp->firstImageUrl ?: $_SERVER["DOCUMENT_URI"].'/static/img/no-image.png'; ?>" alt="기본 이미지">
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

<?php
include $_SERVER["DOCUMENT_ROOT"] . "/footer.php";
?>
</div>

<!-- 검색 관련 js -->
<script src="<?php echo $_SERVER["DOCUMENT_URI"] ?>/static/js/search.js"></script>
<!-- 카카오 지도 관련 js -->
<script src="<?php echo $_SERVER["DOCUMENT_URI"] ?>/static/js/map.js"></script>
<!-- 게시판 관련 js -->
<script src="<?php echo $_SERVER["DOCUMENT_URI"] ?>/static/js/board.js"></script>
<!-- 네비 관련 js -->
<script src="<?php echo $_SERVER["DOCUMENT_URI"] ?>/static/js/navi.js"></script>

<script>

// 페이지 완전히 로드되고 나서 아래 함수 실행
document.addEventListener('DOMContentLoaded', function() {
  console.log("페이지 로드 시 모든 캠핑장 표시!");
  //debugger;
  const urlParams = new URLSearchParams(window.location.search);
  if (urlParams.has('radius')) {
    radius = parseInt(urlParams.get('radius'));
  }
  handleGeolocation();
  displayAllCampings();
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


// (추가) XSS 안전처리를 위한 간단 함수
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


// GPS좌표를 카텍좌표계로 변환하는 함수.
// 프론트에서 생성된 내GPS 좌표를 서버(PHP)로 요청을 보내서 비동기로 카텍 좌표값을 받아와서 iframeSrc변수에 적용함.
function geoTransCoord(position, func) {
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
</script>
</body>
</html>