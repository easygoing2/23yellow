console.log("search.js 로드!");

//  검색창 스크립트 (엔터키, 돋보기)
document.addEventListener("DOMContentLoaded", () => {
	const searchInput = document.getElementById("search");
	const searchButton = document.getElementById("searchButton");

	function performSearch() {
		const searchText = searchInput.value.trim();
		if (searchText) {
			window.location.href = `index?searchText=${encodeURIComponent(searchText)}`;
		}
	}

	// 엔터키 검색 이벤트
	searchInput.addEventListener("keypress", function (e) {
		if (e.key === "Enter") {
			performSearch();
		}
	});

	// 돋보기 버튼 클릭으로 검색
	searchButton.addEventListener("click", () => {
		performSearch();
	});
});

// 반경 버튼 클릭 이벤트 핸들러
document.querySelectorAll(".btn-wrap button").forEach(function (button) {
	button.addEventListener("click", function () {
		if (navigator.geolocation) {
			// 로딩 오버레이 표시
			showLoadingOverlay("현재 위치를 확인 중입니다...");

			navigator.geolocation.getCurrentPosition(function (position) {
				const lat = position.coords.latitude;
				const lng = position.coords.longitude;

				// 버튼 텍스트에서 숫자만 추출 (예: "반경 5km" -> 5)
				const radius = parseInt(button.textContent.match(/\d+/)[0]);

				// URL 파라미터 설정
				const currentUrl = new URL(window.location.href);
				currentUrl.searchParams.set("lat", lat);
				currentUrl.searchParams.set("lng", lng);
				currentUrl.searchParams.set("radius", radius);

				// 지도에 원 표시 및 현재 위치로 이동
				if (typeof map !== 'undefined') {
					// 전역 변수 업데이트
					MyCurrentPosition.WSG84.x = lng;
					MyCurrentPosition.WSG84.y = lat;

					// 기존 원이 있다면 제거
					if (circle) {
						circle.setMap(null);
					}

					// 현재 위치 좌표로 LatLng 객체 생성
					const locPosition = new kakao.maps.LatLng(lat, lng);

					// 새 원 생성
					circle = new kakao.maps.Circle({
						center: locPosition,
						radius: radius * 1000, // km를 미터로 변환
						strokeWeight: 2,
						strokeColor: "#FF4500",
						strokeOpacity: 0.8,
						fillColor: "#FF4500",
						fillOpacity: 0.2,
					});

					// 지도에 원 표시
					circle.setMap(map);

					// 현재 위치에 마커 표시
					displayMarker(locPosition, '<div style="padding:5px;">나의 현재 위치</div>', true);

					// 지도 중심을 현재 위치로 이동
					map.setCenter(locPosition);
				}

				// 로딩 메시지 업데이트
				updateLoadingMessage(`반경 ${radius}km 내 캠핑장을 검색합니다...`);

				// 반경 버튼 클릭 후 페이지 새로고침
				setTimeout(function () {
					window.location.href = currentUrl.toString();
				}, 1000); // 1초 지연
			}, function (error) {
				// 위치 정보 가져오기 실패 시
				hideLoadingOverlay();
				showAlert("위치 정보를 가져오는데 실패했습니다. 위치 권한을 확인해주세요.");
				console.error("위치 정보 오류:", error);
			});
		} else {
			showAlert("이 브라우저에서는 위치 정보를 지원하지 않습니다.");
		}
	});
});
