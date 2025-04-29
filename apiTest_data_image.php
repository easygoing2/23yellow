<?php
/************************************************************
 * apiTest_data_image.php
 * 
 * (1) gogocamping 테이블에서 contentId, facltNm을 페이지네이션으로 나누어 SELECT
 * (2) 각 contentId 별로 이미지 API 호출
 * (3) JSON 파싱 후, DB에 INSERT
 * (4) 이미지를 static/files/images 폴더에 저장
 * (5) updatetime 필드에 데이터를 가져온 날짜와 시간 추가
 ************************************************************/

// --------------------------------------------------
// [A] DB 연결
// --------------------------------------------------
include $_SERVER["DOCUMENT_ROOT"] . "dbconfig.php"; // 실제 위치/경로에 맞게 수정
$dbConn = new mysqli($hostname, $dbuserid, $dbpasswd, $dbname, $dbport);
if ($dbConn->connect_error) {
    die("데이터베이스 연결 실패: " . $dbConn->connect_error);
}
$dbConn->set_charset("utf8mb4");

// --------------------------------------------------
// [B] API 정보 설정
// --------------------------------------------------
$serviceKey = "a6o1csoBikLfTlFWwlW0ELSQD%2F4Ia5q3f7chas3mn6xkwsLABinMbeyOoHVv2Y%2BAycX0R4C%2FY0dZ1vJyMr3Mrw%3D%3D";

// --------------------------------------------------
// [C] 페이지네이션 관련 변수
// --------------------------------------------------
$limit = 100;      // 한 번에 가져올 캠핑장 수 (예: 100개씩)
$page = 1;        // 시작 페이지
$totalProcessed = 0; // 총 처리 건수 카운트

// 이미지 저장 경로 설정
$imageSavePath = $_SERVER["DOCUMENT_ROOT"]."static/files/images/";
if (!is_dir($imageSavePath)) {
    mkdir($imageSavePath, 0777, true); // 폴더가 없으면 생성
}

echo "캠핑장 이미지 수집 시작 (local_image_path가 null인 이미지 처리)\n";

// local_image_path가 null인 이미지 데이터 조회
$sql = "SELECT gi.contentId, gi.serialnum, gi.imageUrl, g.facltNm 
        FROM gogocamping_image gi
        JOIN gogocamping g ON gi.contentId = g.contentId
        WHERE gi.local_image_path IS NULL OR gi.local_image_path = ''
        ORDER BY gi.contentId, gi.serialnum
        LIMIT ?, ?";

while (true) {
    $offset = ($page - 1) * $limit;
    $stmt = $dbConn->prepare($sql);
    $stmt->bind_param("ii", $offset, $limit);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        echo "더 이상 처리할 이미지가 없습니다. (총 {$totalProcessed}건 처리 완료)\n";
        break;
    }

    echo "------------------------------------------------------------\n";
    echo "페이지 {$page} (Offset: {$offset}, Limit: {$limit})\n";
    echo "------------------------------------------------------------\n";

    while ($row = $result->fetch_assoc()) {
        $contentId = $row['contentId'];
        $serialnum = $row['serialnum'];
        $imageUrl = $row['imageUrl'];
        $facltNm = $row['facltNm'];

        echo "처리 중: contentId={$contentId}, serialnum={$serialnum}, facltNm={$facltNm}\n";

        // 이미지 파일명과 경로 설정
        $imageFileName = "{$contentId}_{$serialnum}.jpg";
        $localImagePath = "static/files/images/" . $imageFileName;
        $fullImagePath = $imageSavePath . $imageFileName;

        try {
            // 원격 이미지 다운로드
            $ch = curl_init($imageUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            $imageData = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode === 200 && $imageData) {
                // 이미지 파일 저장
                if (file_put_contents($fullImagePath, $imageData)) {
                    // DB 업데이트
                    $updateSql = "UPDATE gogocamping_image 
                                SET local_image_path = ?, 
                                    update_time = NOW() 
                                WHERE contentId = ? 
                                AND serialnum = ?";
                    
                    $updateStmt = $dbConn->prepare($updateSql);
                    $updateStmt->bind_param("sis", $localImagePath, $contentId, $serialnum);
                    
                    if ($updateStmt->execute()) {
                        echo "성공: 이미지 저장 및 DB 업데이트 완료 ({$localImagePath})\n";
                        $totalProcessed++;
                    } else {
                        echo "실패: DB 업데이트 실패 - " . $updateStmt->error . "\n";
                    }
                    $updateStmt->close();
                } else {
                    echo "실패: 이미지 파일 저장 실패 ({$fullImagePath})\n";
                }
            } else {
                echo "실패: 이미지 다운로드 실패 (HTTP Code: {$httpCode})\n";
            }
        } catch (Exception $e) {
            echo "에러 발생: " . $e->getMessage() . "\n";
        }

        // API 과부하 방지를 위한 짧은 대기
        usleep(100000); // 0.1초 대기
    }

    $stmt->close();
    $page++;
}

$dbConn->close();

echo "--------------------------------------------------------------------------------\n";
echo "모든 이미지 처리가 완료되었습니다. 총 처리 건수: {$totalProcessed}\n";
echo "--------------------------------------------------------------------------------\n";
?>