<?php
// kakao_mobility.php

// API 키 설정
const KAKAO_MOBILITY_API_KEY = '31d25af2076aba32b544fe982c87ba08';

// 경로 조회 함수
function getDirections($startX, $startY, $endX, $endY) {
    $url = "https://apis-navi.kakaomobility.com/v1/directions?" . http_build_query([
        'origin' => "$startX,$startY",
        'destination' => "$endX,$endY",
        'priority' => 'RECOMMEND'
    ]);

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Authorization: KakaoAK ' . KAKAO_MOBILITY_API_KEY,
            'Content-Type: application/json'
        ]
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200) {
        return ['error' => '경로 조회 실패', 'code' => $httpCode];
    }

    return json_decode($response, true);
}

// 결과 및 에러 변수 초기화
$result = null;
$error = null;

// POST 요청 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $startX = $_POST['startX'] ?? '';
    $startY = $_POST['startY'] ?? '';
    $endX = $_POST['endX'] ?? '';
    $endY = $_POST['endY'] ?? '';

    if (!$startX || !$startY || !$endX || !$endY) {
        $error = '모든 좌표를 입력해주세요.';
    } else {
        $result = getDirections($startX, $startY, $endX, $endY);
        if (isset($result['error'])) {
            $error = $result['error'];
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ko">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>카카오 모빌리티 API 테스트</title>
  <script type="text/javascript" src="//dapi.kakao.com/v2/maps/sdk.js?appkey=881810a289df580e6c39afea41ce074d"></script>
  <style>
  #map {
    width: 100%;
    height: 400px;
    margin-top: 20px;
  }

  .form-group {
    margin-bottom: 10px;
  }

  .result {
    margin-top: 20px;
  }

  .error {
    color: red;
    margin-top: 10px;
  }
  </style>
</head>

<body>
  <h1>카카오 모빌리티 API 경로 찾기</h1>

  <form method="post">
    <div class="form-group">
      <label>출발지 좌표(경도, 위도) :</label>
      <input id="start1" type="text" name="startX" placeholder="경도" onfocus="this.value='126.970833';"
        value="<?= htmlspecialchars($_POST['startX'] ?? '') ?>">
      <input id="start2" type="text" name="startY" placeholder="위도" onfocus="this.value='37.555833';"
        value="<?= htmlspecialchars($_POST['startY'] ?? '') ?>">
    </div>
    <div class="form-group">
      <label>도착지 좌표(경도, 위도) :</label>
      <input type="text" name="endX" placeholder="경도" onfocus="this.value='127.028003';"
        value="<?= htmlspecialchars($_POST['endX'] ?? '') ?>">
      <input type="text" name="endY" placeholder="위도" onfocus="this.value='37.498135';"
        value="<?= htmlspecialchars($_POST['endY'] ?? '') ?>">
    </div>
    <button type="submit">경로 찾기</button>
  </form>

  <?php if ($error): ?>
  <div class="error">
    <?= htmlspecialchars($error) ?>
  </div>
  <?php endif; ?>

  <?php if ($result && !isset($result['error'])): ?>
  <div class="result">
    <h2>경로 정보</h2>
    <p>총 거리: <?= number_format($result['routes'][0]['summary']['distance'] / 1000, 1) ?> km</p>
    <p>예상 소요 시간: <?= round($result['routes'][0]['summary']['duration'] / 60) ?> 분</p>
  </div>
  <div id="map"></div>
  <?php endif; ?>

  <?php if ($result && !isset($result['error'])): ?>
  <script>
  var map;
  var markers = [];
  var polylines = [];



  function initMap() {
    var mapContainer = document.getElementById('map');
    var mapOption = {
      center: new kakao.maps.LatLng(<?= $_POST['startY'] ?>, <?= $_POST['startX'] ?>),
      level: 3
    };

    map = new kakao.maps.Map(mapContainer, mapOption);

    // 마커 생성
    var startMarker = new kakao.maps.Marker({
      position: new kakao.maps.LatLng(<?= $_POST['startY'] ?>, <?= $_POST['startX'] ?>),
      map: map
    });

    var endMarker = new kakao.maps.Marker({
      position: new kakao.maps.LatLng(<?= $_POST['endY'] ?>, <?= $_POST['endX'] ?>),
      map: map
    });

    markers.push(startMarker, endMarker);

    // 경로 그리기
    var path = [
      <?php
                foreach ($result['routes'][0]['sections'][0]['roads'] as $road) {
                    for ($i = 0; $i < count($road['vertexes']); $i += 2) {
                        echo "new kakao.maps.LatLng({$road['vertexes'][$i+1]}, {$road['vertexes'][$i]}),";
                    }
                }
                ?>
    ];

    var polyline = new kakao.maps.Polyline({
      path: path,
      strokeWeight: 4,
      strokeColor: '#FF00FF',
      strokeOpacity: 0.7,
      strokeStyle: 'solid'
    });

    polyline.setMap(map);

    // 지도 범위 재설정
    var bounds = new kakao.maps.LatLngBounds();
    path.forEach(function(point) {
      bounds.extend(point);
    });
    map.setBounds(bounds);
  }

  window.onload = initMap;
  </script>
  <?php endif; ?>
</body>

</html>