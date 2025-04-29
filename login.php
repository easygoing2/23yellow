<?php
include $_SERVER["DOCUMENT_ROOT"] . "/header.php";
?>
<div class="container_board">
  <form class="contents" method="post" action="login_ok">
    <div class="l-id">
      <label for="validationCustom02" class="form-label">아이디</label>
      <input type="text" class="form-control" id="userid" name="userid" placeholder="" required>
    </div>
    <div class="l-pw">
      <label for="validationCustom02" class="form-label">비밀번호</label>
      <input type="password" class="form-control" id="passwd" name="passwd" placeholder="" required>
    </div>

    <div class="">
      <button class="btn btn-primary" type="submit">로그인</button>
    </div>
  </form>
</div>
<?php
include $_SERVER["DOCUMENT_ROOT"] . "/footer.php";
?>
</div>
</body>

</html>