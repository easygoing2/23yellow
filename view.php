<!-- 캠핑장 상세페이지 -->
<?php

include $_SERVER['DOCUMENT_ROOT'] . "/dbconfig.php";

$contentId = $_GET["contentId"];
$result = $mysqli->query("select * from gogocamping where contentId=" . $contentId) or die("query error => " . $mysqli->error);
$rs = $result->fetch_object();
// echo '<pre>';
// print_r($rs);
// echo '</pre>';
// 이미지 목록 가져오기
$images = [];
$sqlImages = "SELECT * FROM gogocamping_image WHERE contentId = " . (int)$contentId . " ORDER BY id ASC";
$resultImages = $mysqli->query($sqlImages);
if ($resultImages) {
  $images = $resultImages->fetch_all(MYSQLI_ASSOC);
}

// 블로그 목록 가져오기
$sqlBlogs = "SELECT * FROM gogocamping_blogs WHERE contentId = " . (int)$contentId . " ORDER BY id DESC LIMIT 10";
$resultBlogs = $mysqli->query($sqlBlogs);
$blogs = $resultBlogs ? $resultBlogs->fetch_all(MYSQLI_ASSOC) : [];

?>

<?php
include $_SERVER["DOCUMENT_ROOT"] . "/ajax.php";
include $_SERVER["DOCUMENT_ROOT"] . "/header.php";
?>

<div class="view-page">
  <div class="contents">
    <div class="view-content-wrap">
      <h1 class="view-title"><?php echo $rs->facltNm; ?></h1>
      
      <!-- 상단 지도 영역 -->
      <div class="view-map-container">
        <div id="viewMap" style="width:100%; height:400px;"></div>
      </div>

      <div class="view-info">
        <div class="faclt-txt">
          <div class="faclt-status">
            <?php if ($rs->manageSttus): ?>
            <span>운영상태 : </span>
            <span><?php echo $rs->manageSttus ?> 중</span>
            <?php endif; ?>
          </div>
          <div class="faclt-addr">
            <span>주소 : </span>
            <span><?php echo $rs->addr1; ?> <?php echo $rs->addr2; ?></span>
          </div>
          <div class="faclt-tel">
            <span>TEL : </span>
            <span><?php echo $rs->tel ?: '정보 없음'; ?></span>
          </div>
          <div class="faclt-homepage">
            <span>H.P : </span>
            <span>
              <?php if ($rs->homepage): ?>
                <a href="<?php echo $rs->homepage; ?>" target="_blank"><?php echo $rs->homepage; ?></a>
              <?php else: ?>
                정보 없음
              <?php endif; ?>
            </span>
          </div>
        </div>
      </div>

      <?php if ($rs->intro && $rs->intro !== '정보없음'): ?>
      <div class="faclt-add-txt">
        <div class="txt-wrap">
          <div class="txt"><?php echo nl2br($rs->intro); ?></div>
        </div>
      </div>
      <?php endif; ?>

      <!-- 하단 이미지 갤러리 영역 -->
      <div class="view-gallery-section">
        <h3>캠핑장 사진</h3>
        
        <?php if (count($images) > 0): ?>
        <!-- Swiper 갤러리 -->
        <div class="swiper viewImageSwiper">
          <div class="swiper-wrapper">
            <?php foreach ($images as $img): 
              $imageUrl = $img['local_image_path'] ?: 'static/img/no-image.png';
            ?>
            <div class="swiper-slide">
              <img src="<?php echo $_SERVER["DOCUMENT_URI"] . '/' . $imageUrl; ?>" alt="캠핑장 이미지">
            </div>
            <?php endforeach; ?>
          </div>
          
          <!-- 페이지네이션 -->
          <div class="swiper-pagination"></div>
          
          <!-- 네비게이션 버튼 -->
          <div class="swiper-button-next"></div>
          <div class="swiper-button-prev"></div>
        </div>
        
        <!-- 이미지 카운터 -->
        <div id="viewImageCounter" class="image-counter"></div>
        <?php else: ?>
        <div class="no-images-message">
          <p>등록된 이미지가 없습니다.</p>
          <?php if ($rs->firstImageUrl): ?>
          <div class="single-image">
            <img src="<?php echo $rs->firstImageUrl; ?>" alt="캠핑장 대표 이미지">
          </div>
          <?php endif; ?>
        </div>
        <?php endif; ?>
      </div>
      
      <!-- 블로그 리뷰 영역 -->
      <?php if (count($blogs) > 0): ?>
      <?php 
        /* echo '<pre>';
        print_r($blogs);
        echo '</pre>'; */
      ?>
      <div class="view-blogs-section">
        <h3>블로그 리뷰</h3>
        <div class="faclt-review-wrap" style="height:auto; padding-right:15px;">
          <?php foreach ($blogs as $blog): ?>
          <div class="contents-wrap">
            <div class="contents">
              <img src="<?php echo $rs->firstImageUrl ?: $_SERVER["DOCUMENT_URI"].'/static/img/no-image.png' ?>" alt="blog image">
              <div class="review-wrap">
                <div class="tit"><?php echo htmlspecialchars($blog['blogTitle']); ?></div>
                <div class="txt">
                  <?php echo nl2br(htmlspecialchars($blog['blogDescription'])); ?>
                </div>
                <div class="link">
                  <div class="date">작성일: <?php echo htmlspecialchars($blog['blogPostdate']); ?></div>
                  <a href="<?php echo htmlspecialchars($blog['blogLink']); ?>" target="_blank">블로그 글 바로가기</a>
                </div>
              </div>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
      <?php endif; ?>

      <div class="view-navigation">
        <button type="button" class="btn btn-back" onclick="location.href='index'">목록으로 돌아가기</button>
      </div>
    </div>
  </div>
</div>

<?php
include $_SERVER["DOCUMENT_ROOT"] . "/footer.php";
?>

<!-- Swiper.js CSS -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css" />
<!-- Swiper.js JavaScript -->
<script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>

<style>
  
</style>

<script>
  document.addEventListener('DOMContentLoaded', function() {
    // 카카오맵 초기화
    const mapContainer = document.getElementById('viewMap');
    const mapOption = {
      center: new kakao.maps.LatLng(<?php echo $rs->mapY; ?>, <?php echo $rs->mapX; ?>),
      level: 3
    };
    
    const map = new kakao.maps.Map(mapContainer, mapOption);
    
    // 마커 생성
    const markerPosition = new kakao.maps.LatLng(<?php echo $rs->mapY; ?>, <?php echo $rs->mapX; ?>);
    const marker = new kakao.maps.Marker({
      position: markerPosition
    });
    
    // 마커 지도에 표시
    marker.setMap(map);
    
    // 인포윈도우 생성
    const infowindow = new kakao.maps.InfoWindow({
      content: '<div style="padding:5px;font-size:12px;"><?php echo $rs->facltNm; ?></div>'
    });
    
    // 인포윈도우 표시
    infowindow.open(map, marker);
    
    // 지도 크기 변경 시 중심 재설정
    window.addEventListener('resize', function() {
      map.setCenter(markerPosition);
    });
    
    // Swiper 초기화 (이미지가 있을 경우에만)
    <?php if (count($images) > 0): ?>
    const swiper = new Swiper('.viewImageSwiper', {
      slidesPerView: 1,
      spaceBetween: 30,
      loop: <?php echo count($images) > 1 ? 'true' : 'false'; ?>,
      pagination: {
        el: '.swiper-pagination',
        clickable: true,
        dynamicBullets: true
      },
      navigation: {
        nextEl: '.swiper-button-next',
        prevEl: '.swiper-button-prev',
      },
      keyboard: {
        enabled: true,
      },
      on: {
        init: function() {
          updateImageCounter(this.realIndex, this.slides.length);
        },
        slideChange: function() {
          updateImageCounter(this.realIndex, this.slides.length);
        }
      }
    });
    
    // 이미지 카운터 업데이트 함수
    function updateImageCounter(currentIndex, totalSlides) {
      document.getElementById('viewImageCounter').textContent = 
        `${currentIndex + 1} / ${totalSlides}`;
    }
    <?php endif; ?>
  });
</script>
</body>
</html>