console.log("image-gallery.js 로드!");

// 이미지 갤러리 관련 변수
let galleryImages = [];
let imageSwiper = null;

// 이미지 모달 초기화 함수
function initImageGallery() {
  // 이미지 클릭 이벤트 리스너 등록
  document.addEventListener('click', function (e) {
    const target = e.target;

    // 캠핑장 이미지 클릭 시
    if (target.closest('.faclt-image img')) {
      e.preventDefault();

      // 모든 이미지 요소 수집
      const imageElements = document.querySelectorAll('.faclt-image img');
      galleryImages = Array.from(imageElements).map(img => img.src);

      // 클릭한 이미지의 인덱스 찾기
      const currentImageIndex = galleryImages.indexOf(target.src);
      if (currentImageIndex === -1) currentImageIndex = 0;

      // 모달에 이미지 표시
      openImageModal(currentImageIndex);
    }
  });

  // 모달 닫기 버튼 이벤트
  document.getElementById('closeImageModal').addEventListener('click', closeImageModal);

  // 모달 바깥 클릭 시 닫기
  document.getElementById('imageModal').addEventListener('click', function (e) {
    if (e.target === this) {
      closeImageModal();
    }
  });

  // ESC 키로 모달 닫기
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape' && document.getElementById('imageModal').style.display === 'flex') {
      closeImageModal();
    }
  });
}

// 이미지 모달 열기
function openImageModal(initialIndex) {
  if (galleryImages.length === 0) return;

  // Swiper 슬라이드 생성
  const swiperWrapper = document.querySelector('.swiper-wrapper');
  swiperWrapper.innerHTML = ''; // 기존 슬라이드 제거

  // 각 이미지에 대한 슬라이드 생성
  galleryImages.forEach(imgSrc => {
    const slide = document.createElement('div');
    slide.className = 'swiper-slide';
    slide.style.display = 'flex';
    slide.style.alignItems = 'center';
    slide.style.justifyContent = 'center';

    const img = document.createElement('img');
    img.src = imgSrc;
    img.alt = '캠핑장 이미지';
    img.style.maxWidth = '100%';
    img.style.maxHeight = '80vh';
    img.style.objectFit = 'contain';

    // 이미지 로딩 오류 처리
    img.onerror = function () {
      this.onerror = null;
      this.src = 'static/img/no-image.png';
    };

    slide.appendChild(img);
    swiperWrapper.appendChild(slide);
  });

  // 모달 표시
  document.getElementById('imageModal').style.display = 'flex';

  // 배경 스크롤 방지
  document.body.style.overflow = 'hidden';

  // Swiper 초기화 또는 업데이트
  if (imageSwiper) {
    imageSwiper.destroy();
  }

  imageSwiper = new Swiper('.imageSwiper', {
    initialSlide: initialIndex,
    slidesPerView: 1,
    spaceBetween: 30,
    loop: galleryImages.length > 1,
    keyboard: {
      enabled: true,
    },
    pagination: {
      el: '.swiper-pagination',
      clickable: true,
      dynamicBullets: true,
    },
    navigation: {
      nextEl: '.swiper-button-next',
      prevEl: '.swiper-button-prev',
    },
    effect: 'slide', // 'fade', 'cube', 'coverflow', 'flip' 등으로 변경 가능
    speed: 400, // 트랜지션 속도
    grabCursor: true,
    zoom: {
      maxRatio: 3,
      toggle: true,
    },
    on: {
      init: function () {
        // 초기 카운터 업데이트
        updateImageCounter(this.realIndex);
      },
      slideChange: function () {
        // 슬라이드 변경 시 카운터 업데이트
        updateImageCounter(this.realIndex);
      }
    }
  });
}

// 모달 닫기
function closeImageModal() {
  document.getElementById('imageModal').style.display = 'none';

  // 배경 스크롤 복원
  document.body.style.overflow = '';
}

// 동적으로 추가된 이미지에 대한 갤러리 기능 초기화
function refreshImageGallery() {
  // 이미지가 동적으로 로드된 후 호출
  galleryImages = Array.from(document.querySelectorAll('.faclt-image img')).map(img => img.src);
}

// 이미지 카운터 업데이트 함수 추가
function updateImageCounter(index) {
  const counter = document.getElementById('imageCounter');
  if (counter) {
    counter.textContent = `${index + 1} / ${galleryImages.length}`;
  }
}

// 페이지 로드 시 이미지 갤러리 초기화
document.addEventListener('DOMContentLoaded', initImageGallery); 