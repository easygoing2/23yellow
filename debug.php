<h3 style="text-align: center;">JSON DATA</h3>
<div class="var-dump">
  <div class="php-debug">
    <?php
      echo '<pre>';
      print_r($rscAll);
      echo '</pre>';
      ?>
  </div>
</div>

<h3 style="text-align: center;">var_dump DATA</h3>
<div class="var-dump">
  <div class="php-debug">
    <?php foreach ($rscAll as $row) { ?>
    <div>
      <?php foreach ($row as $key => $value) { ?>
      <div>
        <span><?= htmlspecialchars($key) ?> : </span>
        <span><?= htmlspecialchars($value) ?></span>
      </div>
      <?php } ?>
    </div>
    <?php } ?>
  </div>
</div>
</div>