<?php
include $_SERVER["DOCUMENT_ROOT"] . "/header.php";
?>

  <div class="container_board">
    <form class="contents" method="post" action="signup_ok">
      <div class="s-name">
        <label for="validationCustom01" class="form-label">이름</label>
        <input type="text" class="form-control" id="userame" name="username" placeholder="" required>
      </div>
      <div class="s-id">
        <label for="validationCustom02" class="form-label">아이디</label>
        <input type="text" class="form-control" id="userid" name="userid" placeholder="" required>
      </div>
      <div class="s-pw">
        <label for="validationCustom02" class="form-label">비밀번호</label>
        <input type="password" class="form-control" id="passwd" name="passwd" placeholder="" required>
      </div>
      <div class="s-email">
        <label for="validationCustomUsername" class="form-label">이메일</label>
          <span class="input-group-text" id="inputGroupPrepend">@</span>
          <input type="email" class="form-control" id="email" name="email" placeholder="" required>
      </div>
      <div class="">
        <button class="btn btn-primary" type="submit">가입하기</button>
      </div>
    </form>
  </div>
  <?php
  include $_SERVER["DOCUMENT_ROOT"] . "/footer.php";
  ?>
</div>
</body>

</html>