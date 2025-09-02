<?php
router->api('GET', '');
try {
  $x = isset($_GET['x']) ? $_GET['x'] : 0;
  $y = isset($_GET['y']) ? $_GET['y'] : 0;

  //TODO validate x, y values

  include '.lib/KakaoApi.php';
  $api = new KakaoApi();
  $result = $api->geoTranscoord($x, $y);

  if (isset($result->documents) && is_array($result->documents) && count($result->documents) > 0) {
    $API = $result->documents[0];
    return;
  }
  

} catch (Exception $e) {

}
router->error(400);
/*
curl -H "Accept: application/json" https://dev-3.arasoft.kr/~sml/sm-board-06/api/kakao?x=1234&y=234

*/
?>