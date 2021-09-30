<?php
/*
 Plugin Name: Dirigible SEO
 Plugin URI: https://dirigiblestudio.com/wordpress/plugins/
 description: Dead simple SEO Control for Wordpress. Requires ACF.
 Version: 2.3.4
 Author: Dirigible Studio
 Author URI: https://dirigiblestudio.com
*/

defined('ABSPATH') or exit;
include_once(ABSPATH . 'wp-admin/includes/plugin.php');

// Require licensing + updates.
require_once 'inc/dirigible-licensing/src/DirigibleLicensing.php';
$licensing = new DirigibleLicensing(__FILE__, __DIR__);
require_once 'inc/init.php';

require_once 'src/DirigibleSEO.php';
require_once 'src/ajax.php';
$SEO = new DirigibleSEO(__FILE__);
add_action('wp_head', 'ds_seo_print_header');

if (!function_exists('ds_seo_print_header')) {
  function ds_seo_print_header($SEO)
  {
    global $SEO;
    if ($SEO && $SEO instanceof DirigibleSEO) {
      $SEO->seoHeaderHook();
    }
  }
}
