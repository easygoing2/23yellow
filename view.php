<?php
include $_SERVER['DOCUMENT_ROOT'] . "/dbconfig.php";

$contentId = $_GET["contentId"];
$result = $mysqli->query("select * from gogocamping where contentId=" . $contentId) or die("query error => " . $mysqli->error);
$rs = $result->fetch_object();

// echo "<pre>";
// print_r($rs);

?>

<?php
include $_SERVER["DOCUMENT_ROOT"] . "/header.php";
?>

<div class="container_board">
  <div class="contents">
    <h1 class="fw-bold">PHP 게시판 <?php echo $rs->contentId; ?>번 게시물 view.php</h1>
  </div>

  <article class="blog-post">
    <h2 class="blog-post-title"><?php echo $rs->facltNm; ?></h2>

    <hr>
    <p>
      <?php echo $rs->addr1; ?>
      <?php echo $rs->addr2; ?>
    </p>
    <hr>
  </article>

  <nav class="blog-pagination" aria-label="Pagination">
    <button type="button" class="btn" onclick="location.href='index'">목록</button>
    <!-- <a class="btn btn-outline-secondary" href="#">답글</a> -->
  </nav>
</div>
<?php
include $_SERVER["DOCUMENT_ROOT"] . "/footer.php";
?>
</div>
</body>

</html>