<?php

add_action('admin_init', function () {
  require_once 'src/DirigibleLicensing.php';
  $licensing = new DirigibleLicensing(__FILE__, __DIR__);
});
