<?php

class KakaoApi {      // https://developers.kakao.com/docs/latest/ko/local/dev-guide
  const ApiKey = "31d25af2076aba32b544fe982c87ba08";
  const ApiUrl = "https://dapi.kakao.com/v2/local/";
   const ApiType = ".json";      // .json, .xml

  private $curl;

  function __construct() {
    $this->curl = curl_init();
    curl_setopt_array($this->curl, [
      CURLOPT_TIMEOUT         => 10,
      CURLOPT_CONNECTTIMEOUT   => 2,
      CURLOPT_AUTOREFERER      => true,
      CURLOPT_FAILONERROR      => true,
      CURLOPT_FOLLOWLOCATION   => true,
      CURLOPT_RETURNTRANSFER   => true,
      CURLOPT_SSL_VERIFYHOST   => false,
      CURLOPT_SSL_VERIFYPEER   => false,
      //CURLOPT_USERAGENT      => $_SERVER['HTTP_USER_AGENT'],
      CURLOPT_HTTPHEADER      => [
        'Authorization: KakaoAK '.self::ApiKey
      ]
    ]);
  }
   // https://dapi.kakao.com/v2/local/search/address.${FORMAT}
   // https://dapi.kakao.com/v2/local/search/keyword.${FORMAT}
   // https://dapi.kakao.com/v2/local/search/category.${FORMAT}
   // https://dapi.kakao.com/v2/local/geo/coord2regioncode.${FORMAT}
   // https://dapi.kakao.com/v2/local/geo/coord2address.${FORMAT}
   // https://dapi.kakao.com/v2/local/geo/transcoord.${FORMAT}

   public function geoTranscoord($x, $y, $in='WGS84', $out='WCONGNAMUL') {   // WGS84, WCONGNAMUL, CONGNAMUL, WTM, TM, KTM, UTM, BESSEL, WKTM, WUTM
      // https://dapi.kakao.com/v2/local/geo/transcoord.json?x=127.6892996&y=37.6963001&input_coord=WGS84&output_coord=WCONGNAMUL
      $params = [
        'x' => $x,
        'y' => $y,
        'input_coord' => $in,
        'output_coord' => $out
      ];
      curl_setopt($this->curl, CURLOPT_URL, self::ApiUrl."geo/transcoord".self::ApiType.'?'.http_build_query($params));
      if ($data = curl_exec($this->curl)) {
        return json_decode($data);
      }
      return null;
  }
}
?>