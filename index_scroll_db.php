<?php

ini_set('display_errors', 1);   // 에러를 화면에 출력할지 여부.
ini_set('display_startup_errors', 1);   // PHP 시작 시 발생하는 에러를 표시할지 여부.
error_reporting(E_ALL); // 모든 에러, 경고, 공지 등을 표시

include $_SERVER['DOCUMENT_ROOT'] . "/dbconfig.php";

class CampingDataFetcher {
    private $mysqli;

    public const ITEMS_PER_PAGE = 20;

    public function __construct($mysqli) {
        $this->mysqli = $mysqli;
    }

    public function fetchPage($page = 1, $sortBy = 'facltNm') {
        try {
            // 페이지네이션 계산
            $limit = self::ITEMS_PER_PAGE;
            $offset = ($page - 1) * $limit;

            // 정렬 기준 설정
            $validSortColumns = ['facltNm', 'prmisnDe'];
            if (!in_array($sortBy, $validSortColumns)) {
                $sortBy = 'facltNm';
            }
    
            // SQL 쿼리 작성
            $sql = "SELECT * FROM gogocamping ORDER BY $sortBy LIMIT ? OFFSET ?";
            $stmt = $this->mysqli->prepare($sql);
    
            if (!$stmt) {
                throw new Exception('SQL 준비 오류: ' . $this->mysqli->error);
            }
    
            // LIMIT과 OFFSET 값 바인딩
            $stmt->bind_param("ii", $limit, $offset);
    
            // 실행
            $stmt->execute();
    
            // 결과 가져오기
            $result = $stmt->get_result();
            $data = $result->fetch_all(MYSQLI_ASSOC);
    
            // 전체 데이터 개수 가져오기
            $totalCountResult = $this->mysqli->query("SELECT COUNT(*) as total FROM gogocamping");
            $totalCountRow = $totalCountResult->fetch_assoc();
            $totalCount = $totalCountRow['total'] ?? 0;
    
            return [
                'success' => true,
                'data' => $data,
                'totalCount' => $totalCount,
                'numOfRows' => self::ITEMS_PER_PAGE,
                'currentPage' => $page
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'data' => []
            ];
        }
    }
    

    public function renderCampingItem($item) {
        return '
        <div class="camping-item border rounded-lg p-4 mb-4 shadow-sm hover:shadow-md transition-shadow">
            <h3 class="text-lg font-bold mb-2">' . htmlspecialchars($item['facltNm'] ?? '정보없음') . '</h3>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <p class="text-gray-600 text-sm"><span class="text-sm">주소:</span> ' . htmlspecialchars($item['addr1'] ?? '정보없음') . '</p>
                    <p class="text-gray-600 text-sm"><span class="text-sm">전화:</span> ' . htmlspecialchars($item['tel'] ?? '정보없음') . '</p>
                    <p class="text-gray-600 text-sm"><span class="text-sm">인허가날짜:</span> ' . htmlspecialchars($item['prmisnDe'] ?? '정보없음') . '</p>
                </div>
                <div>
                    <p class="text-gray-600 text-sm"><span class="text-sm">캠핑장 환경:</span> ' . htmlspecialchars($item['lctCl'] ?? '정보없음') . '</p>
                    <p class="text-gray-600 text-sm"><span class="text-sm">캠핑장 유형:</span> ' . htmlspecialchars($item['induty'] ?? '정보없음') . '</p>
                    <p class="text-gray-600 text-sm"><span class="text-sm">운영여부:</span> ' . htmlspecialchars($item['manageSttus'] ?? '정보없음') . '</p>
                </div>
                
            </div>
        </div>';
    }
}

// AJAX 요청 처리
if (isset($_GET['ajax']) && $_GET['ajax'] === 'true') {
    header('Content-Type: application/json');

    try {
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $sortBy = isset($_GET['sortBy']) ? $_GET['sortBy'] : 'facltNm';
        $fetcher = new CampingDataFetcher($mysqli);
        $result = $fetcher->fetchPage($page, $sortBy);

        if ($result['success']) {
            $html = '';
            foreach ($result['data'] as $item) {
                $html .= $fetcher->renderCampingItem($item);
            }
            echo json_encode([
                'success' => true,
                'html' => $html,
                'totalCount' => $result['totalCount'],
                'currentPage' => $result['currentPage'],
                'hasMore' => ($page * CampingDataFetcher::ITEMS_PER_PAGE) < $result['totalCount']
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'error' => $result['error']
            ]);
        }
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'error' => '서버 오류: ' . $e->getMessage()
        ]);
    }
    exit;
}

// 초기 데이터 가져오기
$sortBy = isset($_GET['sortBy']) ? $_GET['sortBy'] : 'facltNm';
$fetcher = new CampingDataFetcher($mysqli);
$initialData = $fetcher->fetchPage(1, $sortBy);
?>

<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="<?php echo $_SERVER["DOCUMENT_URI"] ?>/static/img/favicon.ico">
    <title>camping board</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="static/css/style.css" rel="stylesheet">
    <link href="static/css/font.css" rel="stylesheet">
</head>
<body class="bg-gray-100">
    <div class="img-header">
        <img src="<?php echo $_SERVER["DOCUMENT_URI"] ?>/static/img/img-header.png" alt="헤더 이미지">
        <a href="https://programmerdaddy.tistory.com/202" target="_blank">참고 : https://programmerdaddy.tistory.com/202</a>
    </div>
    <header>
        <h1>
            <a href="<?php echo $_SERVER["DOCUMENT_URI"] ?><?php echo $_SERVER["SCRIPT_NAME"] ?>">
            📝 한국관광공사_고캠핑 정보 조회서비스 실시간 API <span><?php echo $_SERVER["SCRIPT_NAME"] ?></span>
            </a>
        </h1>
    </header>
    <div class="container mx-auto px-4 py-8">
        <h1 class="text-3xl font-bold mb-5 text-center">캠핑장 목록</h1>
        <div class="flex items-center mb-4">
            <label for="sort-select" class="mr-2">정렬:</label>
            <select id="sort-select" class="flex-1 border border-gray-300 rounded p-1">
                <option value="facltNm" <?php echo $sortBy === 'facltNm' ? 'selected' : '' ?>>가나다라 순</option>
                <option value="prmisnDe" <?php echo $sortBy === 'prmisnDe' ? 'selected' : '' ?>>인허가일자 순</option>
            </select>
            <button id="search-button" class="bg-blue-500 hover:bg-blue-700 text-white font-bold ml-1 py-1 px-4">
                검색
            </button>
        </div>
        <div id="camping-list" class="space-y-4">
            <?php
            if ($initialData['success'] && !empty($initialData['data'])) {
                foreach ($initialData['data'] as $item) {
                    echo $fetcher->renderCampingItem($item);
                }
            }
            ?>
        </div>

        <!-- <div id="loading-skeleton" class="hidden">
            <p>Loading...</p>
        </div> -->
        <div id="loading-skeleton" class="hidden space-y-4">
            <!-- 스켈레톤 카드 (3개) -->
            <?php for ($i = 0; $i < 3; $i++): ?>
                <div class="border rounded-lg p-4 mb-4 shadow-sm">
                    <div class="h-6 w-3/4 mb-4 bg-gray-200 rounded animate-pulse"></div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <div class="h-4 w-full mb-2 bg-gray-200 rounded animate-pulse"></div>
                            <div class="h-4 w-full bg-gray-200 rounded animate-pulse"></div>
                        </div>
                        <div>
                            <div class="h-4 w-full mb-2 bg-gray-200 rounded animate-pulse"></div>
                            <div class="h-4 w-full bg-gray-200 rounded animate-pulse"></div>
                        </div>
                    </div>
                </div>
            <?php endfor; ?>
        </div>
    </div>

    <script>
    // 페이지 로드 시 최상단으로 스크롤
    document.addEventListener("DOMContentLoaded", function () {
        if ('scrollRestoration' in history) {
            history.scrollRestoration = 'manual'; // 스크롤 복원 비활성화
        }

        // 새로고침 시 항상 최상단으로 이동
        window.scrollTo(0, 0);
    });

    document.addEventListener("DOMContentLoaded", function () {
        let currentPage = 1;
        let isLoading = false;
        let hasMore = true;
        const campingList = document.getElementById('camping-list');
        const loadingSkeleton = document.getElementById('loading-skeleton');
        const sortSelect = document.getElementById('sort-select');
        const searchButton = document.getElementById('search-button');

        // 스켈레톤 UI 토글 함수
        function toggleSkeletonUI(show) {
            if (show) {
                loadingSkeleton.classList.remove('hidden');
            } else {
                loadingSkeleton.classList.add('hidden');
            }
        }

        // 데이터 로드 함수
        async function loadMoreData(resetList = false) {
            if (isLoading || (!hasMore && !resetList)) return;

            isLoading = true;
            if (resetList) {
                currentPage = 1;
                campingList.innerHTML = ''; // 목록 초기화
                hasMore = true;
            }
            
            toggleSkeletonUI(true);

            try {
                const sortBy = sortSelect.value;
                const response = await fetch(`?ajax=true&page=${currentPage}&sortBy=${sortBy}`);
                const data = await response.json();

                if (data.success) {
                    if (resetList) {
                        campingList.innerHTML = data.html;
                    } else {
                        campingList.insertAdjacentHTML('beforeend', data.html);
                    }
                    hasMore = data.hasMore;
                } else {
                    console.error(data.error);
                }
            } catch (error) {
                console.error(error);
            } finally {
                isLoading = false;
                toggleSkeletonUI(false);
            }
        }

        // 검색 버튼 클릭 이벤트
        searchButton.addEventListener('click', () => {
            loadMoreData(true); // true를 전달하여 목록 초기화 후 새로 로드
        });

        // 스크롤 이벤트로 데이터 로드
        window.addEventListener('scroll', () => {
            if (window.innerHeight + window.scrollY >= document.documentElement.scrollHeight - 100) {
                currentPage++;
                loadMoreData();
            }
        });

        // Enter 키 이벤트 추가
        sortSelect.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                loadMoreData(true);
            }
        });

        const ongoingRequests = new Set(); // 현재 진행 중인 요청을 저장
    });
    </script>
</body>
</html>
