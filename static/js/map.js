console.log("map.js 로드!");

// 카카오 지도 스크립트
var mapContainer = document.getElementById("map"); //지도를 담을 영역의 DOM 레퍼런스
var mapOption = {
	//지도를 생성할 때 필요한 기본 옵션
	center: new kakao.maps.LatLng(39.450701, 126.570667), //지도의 중심좌표.
	level: 9, //지도의 레벨(확대, 축소 정도)
};

var map = new kakao.maps.Map(mapContainer, mapOption);

var circle = null;
var radius = 15; // 초기 랜딩 시 호출하는 반지름 너비 기본값 15km. (map.js에서도 수정해야 함)

// 전역 변수로 마커 배열 추가
var markers = [];

// ✅ 모든 마커를 제거하는 함수 추가
function clearMarkers() {
	markers.forEach(function (marker) {
		marker.setMap(null);
	});
	markers = [];
}

// ✅ 검색 결과에서 주소를 클릭하면 모든 캠핑장을 지도에 표시하는 함수
function displayAllCampings() {
	console.log("displayAllCampings 함수 실행!");
	// 기존 마커들을 모두 제거
	clearMarkers();
	// 모든 주소 링크 요소를 선택
	// document.querySelectorAll(".address-link").forEach(function (link) {
	// 	var mapX = link.getAttribute("data-mapx");
	// 	var mapY = link.getAttribute("data-mapy");
	// 	var name = link.getAttribute("data-name");
	// 	if (mapX && mapY) {
	// 		var position = new kakao.maps.LatLng(mapY, mapX);
	// 		var marker = new kakao.maps.Marker({
	// 			map: map,
	// 			position: position,
	// 		});
	// 		// 인포윈도우 생성
	// 		var infowindow = new kakao.maps.InfoWindow({
	// 			content: '<div style="padding:5px;">' + name + "</div>",
	// 			removable: true,
	// 		});
	// 		// 마커 클릭 이벤트
	// 		kakao.maps.event.addListener(marker, "click", function () {
	// 			infowindow.open(map, marker);
	// 		});
	// 		// 마커를 배열에 추가
	// 		markers.push(marker);
	// 	}
	// });

	// 모든 마커가 보이도록 지도 범위 재설정
	if (markers.length > 0) {
		var bounds = new kakao.maps.LatLngBounds();
		markers.forEach(function (marker) {
			bounds.extend(marker.getPosition());
		});
		map.setBounds(bounds);
	}
}

// 현재 위치 마커와 인포윈도우를 저장할 변수
var currentLocationMarker = null;
var currentLocationInfowindow = null;

// 캠핑장 마커와 인포윈도우를 저장할 변수
var campingMarker = null;
var campingInfowindow = null;

// ✅ 마커 표시 함수
function displayMarker(locPosition, message, isCurrentLocation) {
	console.log("displayMarker 함수 실행!");
	var marker, infowindow;

	if (isCurrentLocation) {
		// 현재 위치 마커 생성 또는 위치 업데이트
		if (currentLocationMarker) {
			currentLocationMarker.setPosition(locPosition);
		} else {
			marker = new kakao.maps.Marker({
				map: map,
				position: locPosition,
			});
			currentLocationMarker = marker;
		}

		// 현재 위치 인포윈도우 생성 또는 내용 업데이트
		if (currentLocationInfowindow) {
			currentLocationInfowindow.setContent(message);
		} else {
			infowindow = new kakao.maps.InfoWindow({
				content: message,
				removable: true,
			});
			currentLocationInfowindow = infowindow;
		}
		currentLocationInfowindow.open(map, currentLocationMarker);
	} else {
		// 기존 캠핑장 마커와 인포윈도우가 있다면 제거
		if (campingMarker) {
			campingMarker.setMap(null);
		}
		if (campingInfowindow) {
			campingInfowindow.close();
		}

		// 새 캠핑장 마커 생성
		marker = new kakao.maps.Marker({
			map: map,
			position: locPosition,
		});

		infowindow = new kakao.maps.InfoWindow({
			content: message,
			removable: true,
		});

		infowindow.open(map, marker);

		// 캠핑장 마커와 인포윈도우 업데이트
		campingMarker = marker;
		campingInfowindow = infowindow;
	}

	// 지도 중심 이동
	map.panTo(locPosition);
}

var MyCurrentPosition = {
	WSG84: {
		x: 126.570667,
		y: 33.450701,
	},
	WCONGNAMUL: {
		x: 0,
		y: 0,
	},
};

// geoTransCoord 함수 수정 - 캐싱 추가
const coordCache = {}; // 좌표 변환 결과를 캐싱할 객체

function geoTransCoord(position, func) {
	console.log('🚧 geoTransCoord 함수 실행(GPS좌표를 카텍좌표계로 변환하는 함수)');

	// 캐시 키 생성
	const cacheKey = `${position.x},${position.y}`;

	// 캐시에 결과가 있으면 바로 반환
	if (coordCache[cacheKey]) {
		console.log('캐시된 좌표 변환 결과 사용');
		func(coordCache[cacheKey]);
		return;
	}

	let result = {
		ok: false,
		status: 0,
		data: null
	};

	fetch('api/kakao?x=' + position.x + '&y=' + position.y, {
		method: 'GET',
		headers: { 'Accept': 'application/json' }
	}).then(function (response) {
		result.ok = response.ok;
		result.status = response.status;
		result.headers = response.headers;
		return response.json();
	}).then(function (data) {
		result.data = data;
		// 결과 캐싱
		coordCache[cacheKey] = result;
		func(result);
	}).catch(function (error) {
		result.error = error;
		result.data = { msg: result.status ? "서비스" : "네트워크" };
		result.data.msg += " 오류.\n잠시 후 다시 시도해 주시기 바랍니다.";
		func(result);
	});
}

// ✅ 위치 정보 처리를 위한 단일 함수
function handleGeolocation() {
	console.log("handleGeolocation 함수 실행!");
	if (navigator.geolocation) {
		// 로딩 오버레이 표시
		showLoadingOverlay("현재 위치를 확인 중입니다...");

		navigator.geolocation.getCurrentPosition(
			function (position) {
				const lat = position.coords.latitude;
				const lng = position.coords.longitude;
				console.log("handleGeolocation().lat : ", lat);
				console.log("handleGeolocation().lng : ", lng);

				// 전역 변수 업데이트
				MyCurrentPosition.WSG84.x = lng;
				MyCurrentPosition.WSG84.y = lat;

				// URL 파라미터 체크
				const urlParams = new URLSearchParams(window.location.search);
				console.log("urlParams~~~ : ", urlParams);
				if (!urlParams.has("lat") && !urlParams.has("lng") && !urlParams.has("searchText")) {
					// URL 파라미터에 현재 위치 추가 및 페이지 새로고침
					const currentUrl = new URL(window.location.href);
					console.log("currentUrl : ", currentUrl);
					console.log("URL 파라미터에 현재 위치 추가 및 페이지 새로고침");
					currentUrl.searchParams.set("lat", lat);
					currentUrl.searchParams.set("lng", lng);
					currentUrl.searchParams.set("radius", radius); // 기본 radius 값 추가 (전역 변수 radius 사용)

					// 로딩 메시지 업데이트
					updateLoadingMessage("현재 위치로 이동합니다...");

					// 약간의 지연 후 페이지 새로고침
					setTimeout(function () {
						window.location.href = currentUrl.toString();	// 새로고침
					}, 1000); // 1초 지연
				} else {
					// else 아래 코드는 '반경00km' 버튼 클릭 후 실행됨.
					// 위치 정보(MyCurrentPosition.WSG84)가 있다면 지도만 업데이트.
					geoTransCoord(MyCurrentPosition.WSG84, function (result) {
						if (result.ok) {
							console.log("handleGeolocation()함수 위치정보가 있음", result);
							MyCurrentPosition.WCONGNAMUL = result.data;
							drawKakaoMap('<div style="padding:5px;">나의 현재 위치</div>');
							// 지도 레벨 조정 및 범위 설정
							if (radius === 50) {
								console.log("반경 50km 버튼 클릭 후 실행됨. 지도 레벨 조정 및 범위 설정");
								map.setLevel(11);
							} else if (radius === 15) {
								console.log("반경 15km 버튼 클릭 후 실행됨. 지도 레벨 조정 및 범위 설정");
								map.setLevel(9);
							} else if (radius === 5) {
								console.log("반경 5km 버튼 클릭 후 실행됨. 지도 레벨 조정 및 범위 설정");
								map.setLevel(8);
							}
							// 로딩 오버레이 숨기기
							hideLoadingOverlay();
						}
					});
				}
			},
			function (error) {
				console.error("위치 정보를 가져오는데 실패했습니다:", error);
				// 오류 메시지 표시
				hideLoadingOverlay();
				showAlert("위치 정보를 가져오는데 실패했습니다. 위치 권한을 확인해주세요.");
			},
		);
	}
}

// ✅ 로딩 오버레이 표시 함수
function showLoadingOverlay(message) {
	// 기존 오버레이가 있으면 제거
	hideLoadingOverlay();

	// 새 오버레이 생성
	const overlay = document.createElement('div');
	overlay.id = 'loadingOverlay';
	overlay.style.cssText = `
		position: fixed;
		top: 0;
		left: 0;
		width: 100%;
		height: 100%;
		background-color: rgba(0, 0, 0, 0.5);
		display: flex;
		flex-direction: column;
		justify-content: center;
		align-items: center;
		z-index: 9999;
	`;

	// 로딩 스피너
	const spinner = document.createElement('div');
	spinner.style.cssText = `
		border: 5px solid #f3f3f3;
		border-top: 5px solid #3498db;
		border-radius: 50%;
		width: 50px;
		height: 50px;
		animation: spin 2s linear infinite;
		margin-bottom: 20px;
	`;

	// 애니메이션 스타일 추가
	const style = document.createElement('style');
	style.textContent = `
		@keyframes spin {
			0% { transform: rotate(0deg); }
			100% { transform: rotate(360deg); }
		}
	`;
	document.head.appendChild(style);

	// 메시지 요소
	const messageElement = document.createElement('div');
	messageElement.id = 'loadingMessage';
	messageElement.style.cssText = `
		color: white;
		font-size: 18px;
		text-align: center;
		padding: 10px 20px;
		background-color: rgba(0, 0, 0, 0.7);
		border-radius: 5px;
	`;
	messageElement.textContent = message;

	// 요소들 조합
	overlay.appendChild(spinner);
	overlay.appendChild(messageElement);
	document.body.appendChild(overlay);
}

// ✅ 로딩 메시지 업데이트 함수
function updateLoadingMessage(message) {
	const messageElement = document.getElementById('loadingMessage');
	if (messageElement) {
		messageElement.textContent = message;
	}
}

// ✅ 로딩 오버레이 숨기기 함수
function hideLoadingOverlay() {
	const overlay = document.getElementById('loadingOverlay');
	if (overlay) {
		overlay.remove();
	}
}

// ✅ 알림 메시지 표시 함수
function showAlert(message) {
	const alertBox = document.createElement('div');
	alertBox.style.cssText = `
		position: fixed;
		top: 20px;
		left: 50%;
		transform: translateX(-50%);
		background-color: #f8d7da;
		color: #721c24;
		padding: 15px 20px;
		border-radius: 5px;
		box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
		z-index: 9999;
		text-align: center;
		max-width: 80%;
	`;
	alertBox.textContent = message;

	document.body.appendChild(alertBox);

	// 5초 후 알림 제거
	setTimeout(() => {
		alertBox.remove();
	}, 5000);
}

// ✅ 지도에 Circle 그려주는 함수
function drawKakaoMap(message) {
	console.log("drawKakaoMap 함수 실행!");
	console.log("나의 현재 위치 : ", MyCurrentPosition);
	let locPosition = new kakao.maps.LatLng(MyCurrentPosition.WSG84.y, MyCurrentPosition.WSG84.x);
	// 기존 원이 있다면 제거
	if (circle) {
		circle.setMap(null);
	}
	// 현재 radius 값을 사용하여 원 생성
	circle = new kakao.maps.Circle({
		center: locPosition,
		radius: radius * 1000, // km를 미터로 변환 (5km -> 5000m)
		strokeWeight: 2,
		strokeColor: "#FF4500",
		strokeOpacity: 0.8,
		fillColor: "#FF4500",
		fillOpacity: 0.2,
	});
	// 지도에 원 표시
	circle.setMap(map);
	displayMarker(locPosition, message, true);
}

// ✅ iframe에 카카오맵 경로를 표시하는 함수
function showMapInIframe(destination, transformedX, transformedY) {
	console.log("showMapInIframe 함수 실행!");
	// 현재 나의 위치 가져오기
	if (navigator.geolocation) {
		navigator.geolocation.getCurrentPosition(function (position) {
			var lat = position.coords.latitude,
				lon = position.coords.longitude;

			var geocoder = new kakao.maps.services.Geocoder();
			geocoder.coord2Address(lon, lat, function (result, status) {
				if (status === kakao.maps.services.Status.OK) {
					var detailAddr = result[0].road_address ? result[0].road_address.address_name : result[0].address.address_name;

					let detailAddrEnc = encodeURIComponent(detailAddr); // 내 위치
					let destinationEnc = encodeURIComponent(destination); // 목적지
					let fromLabel = encodeURIComponent("내 위치"); // 네비 화면에서 '출발지'를 '내 위치'로 설정

					console.log("detailAddrEnc : ", decodeURIComponent(detailAddrEnc)); // 지금 내 위치를 한글 주소로~
					console.log("showMapInIframe().lat : ", lat);
					console.log("showMapInIframe().lon : ", lon);
					console.log("showMapInIframe().transformedY : ", transformedY);
					console.log("showMapInIframe().transformedX : ", transformedX);
					var iframeSrc = `https://map.kakao.com/?map_type=TYPE_MAP&target=car&rt=${MyCurrentPosition.WCONGNAMUL.x},${MyCurrentPosition.WCONGNAMUL.y}%2C${transformedX}%2C${transformedY}&rt1=${fromLabel}&rt2=${destinationEnc}`;

					// iframe src 업데이트 및 표시
					var iframe = document.getElementById("iframeMap");
					iframe.src = iframeSrc;

					// 모달 표시
					document.getElementById("mapModal").style.display = "flex";
				}
			});
		});
	} else {
		alert("Geolocation is not supported by this browser.");
	}
}

// ✅ 모달 닫기 함수 추가
function closeMapModal() {
	document.getElementById("mapModal").style.display = "none";
}

// ✅ 현재 위치를 지도에 마커로 표시하는 함수
function addCurrentLocationMarker() {
	console.log("addCurrentLocationMarker 함수 실행!");
	if (navigator.geolocation) {
		console.log("현재 위치를 지도에 마커로 표시하는 함수 : addCurrentLocationMarker");
		navigator.geolocation.getCurrentPosition(
			function (position) {
				const lat = position.coords.latitude; // 위도
				const lng = position.coords.longitude; // 경도
				// 현재 위치를 Kakao Maps LatLng 객체로 변환
				const locPosition = new kakao.maps.LatLng(lat, lng);
				// 인포윈도우에 표시될 메시지
				const message = '<div style="padding:5px;">내 위치</div>';
				// 지도에 마커와 인포윈도우 표시
				const marker = new kakao.maps.Marker({
					position: locPosition,
					map: map, // 전역 Kakao 지도 객체
				});
				const infowindow = new kakao.maps.InfoWindow({
					content: message,
					removable: true,
				});
				infowindow.open(map, marker);
				// 지도 중심을 현재 위치로 이동
				map.setCenter(locPosition);
				console.log("현재 위치에 마커가 추가되었습니다:", lat, lng);
			},
			function (error) {
				console.error("위치 정보를 가져오는데 실패했습니다:", error);
			},
		);
	} else {
		console.error("Geolocation을 지원하지 않는 브라우저입니다.");
	}
}
