<!-- 캠핑장 상세페이지 -->
<?php

include $_SERVER['DOCUMENT_ROOT'] . "/dbconfig.php";

$contentId = $_GET["contentId"];
$result = $mysqli->query("select * from gogocamping where contentId=" . $contentId) or die("query error => " . $mysqli->error);
$rs = $result->fetch_object();

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

<div class="container-board">
  <div class="contents">
    <div class="view-content-wrap">
      <h1 class="view-title"><?php echo $rs->facltNm; ?></h1>
      
      <!-- 상단 지도 영역 -->
      <div class="view-map-container">
        <div id="viewMap" style="width:100%; height:400px;"></div>
      </div>

      <div class="faclt-info-wrap view-info">
        <div class="faclt-thumb">
          <div class="img" style="background-image: url('<?php echo $rs->firstImageUrl ?: $_SERVER["DOCUMENT_URI"].'/static/img/no-image.png' ?>')"></div>
        </div>
        <div class="faclt-txt">
          <div class="faclt-tit">
            <?php echo $rs->facltNm ?>
            <?php if ($rs->manageSttus): ?>
            <span>(<?php echo $rs->manageSttus ?> 중)</span>
            <?php endif; ?>
          </div>
          <div class="faclt-addr">주소 : <?php echo $rs->addr1; ?> <?php echo $rs->addr2; ?></div>
          <div class="faclt-tel">TEL : <?php echo $rs->tel ?: '정보 없음'; ?></div>
          <div class="faclt-tel"><span>H.P : </span>
            <?php if ($rs->homepage): ?>
              <a href="<?php echo $rs->homepage; ?>" target="_blank"><?php echo $rs->homepage; ?></a>
            <?php else: ?>
              정보 없음
            <?php endif; ?>
          </div>
        </div>
      </div>

      <?php if ($rs->intro && $rs->intro !== '정보없음'): ?>
      <div class="faclt-add-txt" style="height:auto; margin-bottom:20px;">
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
  .container-wrap {
    height: auto !important;
  }
  .container-board {
    height: auto !important;
  }
  .contents {
    height: auto !important;
  }
  .view-content-wrap {
    width: 100%;
    max-width: 1200px;
    margin: 0 auto;
    background: #fff;
    border-radius: 10px;
    padding: 20px;
    box-shadow: 0 1px 2px 0 rgb(0 0 0 / 0.05);
  }
  
  .view-title {
    font-size: 24px;
    font-weight: bold;
    margin-bottom: 20px;
    color: #333;
    border-bottom: 2px solid #66c57f;
    padding-bottom: 10px;
  }
  
  .view-map-container {
    margin-bottom: 20px;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 1px 2px rgba(0,0,0,0.1);
  }
  
  .view-info {
    margin-bottom: 15px !important;
  }
  
  .view-gallery-section, 
  .view-blogs-section {
    margin-bottom: 30px;
  }
  
  .view-gallery-section h3,
  .view-blogs-section h3 {
    font-size: 18px;
    color: #2c3e50;
    margin: 0 0 15px 0;
    padding-bottom: 10px;
    border-bottom: 1px dashed #66c57f;
  }
  
  .swiper {
    width: 100%;
    height: 400px;
    margin: 20px 0;
    border-radius: 8px;
    overflow: hidden;
    background: #000;
  }
  
  .swiper-slide {
    display: flex;
    justify-content: center;
    align-items: center;
  }
  
  .swiper-slide img {
    max-width: 100%;
    max-height: 100%;
    object-fit: contain;
  }
  
  .swiper-button-next,
  .swiper-button-prev {
    color: #ffffff;
    background: rgba(0,0,0,0.3);
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    justify-content: center;
    align-items: center;
  }
  
  .swiper-button-next:after,
  .swiper-button-prev:after {
    font-size: 18px;
  }
  
  .swiper-pagination-bullet {
    background: #ffffff;
    opacity: 0.7;
  }
  
  .swiper-pagination-bullet-active {
    opacity: 1;
    background: #66c57f;
  }
  
  .image-counter {
    text-align: center;
    margin-top: 10px;
    font-size: 14px;
    color: #555;
  }
  
  .no-images-message {
    text-align: center;
    padding: 30px;
    color: #777;
    background: #f9f9f9;
    border-radius: 8px;
  }
  
  .single-image {
    max-width: 600px;
    margin: 20px auto;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 1px 2px rgba(0,0,0,0.1);
  }
  
  .single-image img {
    width: 100%;
    height: auto;
  }
  
  .view-navigation {
    display: flex;
    justify-content: center;
    margin-top: 30px;
  }
  
  .btn-back {
    background-color: #66c57f;
    color: white;
    border: none;
    padding: 10px 20px;
    border-radius: 5px;
    cursor: pointer;
    font-size: 14px;
    transition: background-color 0.3s;
  }
  
  .btn-back:hover {
    background-color: #4ca365;
  }
  
  @media screen and (max-width: 768px) {
    .view-content-wrap {
      padding: 15px;
    }
    
    .swiper {
      height: 300px;
    }
  }
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