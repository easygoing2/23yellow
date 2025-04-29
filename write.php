<?php
include $_SERVER["DOCUMENT_ROOT"] . "/header.php";
if (!$_SESSION['UID']) {
  echo "<script>alert('회원 전용 게시판입니다.');history.back();</script>";
  exit;
}

$userId = trim($_SESSION['UID'] ?? '');
$status = 1; //status는 1이면 true, 0이면 false이다.

echo "아이디 => ".$userId."<br>";
?>
<div class="container_board">
  <div class="contents">
    <h1 class="fw-bold">PHP 게시판 글쓰기 페이지 write.php</h1>
  </div>
  <div class="write_form">
    <form method="post" action="write_ok">
      <div class="w-name">
        <label for="exampleFormControlInput1" class="form-label">이름</label>
        <input type="text" name="userid" class="form-control" id="exampleFormControlInput1" placeholder="제목을 입력하세요.">
      </div>
      <div class="w-subject">
        <label for="exampleFormControlInput2" class="form-label">제목</label>
        <input type="text" name="subject" class="form-control" id="exampleFormControlInput2" placeholder="제목을 입력하세요.">
      </div>
      <div class="w-contents">
        <label for="exampleFormControlTextarea1" class="form-label">내용</label>
        <textarea class="form-control" id="exampleFormControlTextarea1" name="content" rows="3"></textarea>
      </div>
      <button type="submit" class="btn btn-primary">등록</button>
    </form>
  </div>
</div>
<?php
include $_SERVER["DOCUMENT_ROOT"] . "/footer.php";
?>
</div>

</body>

</html>