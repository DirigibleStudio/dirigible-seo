<?php
/*
 Plugin Name: Dirigible SEO
 Plugin URI: https://dirigiblestudio.com/wordpress/plugins/
 description: Dead simple SEO Control for Wordpress. Requires ACF.
 Version: 2.3.28
 Author: Dirigible Studio
 Author URI: https://dirigiblestudio.com
*/

defined('ABSPATH') or exit;
define('DS_SEO_VERSION', '2.3.27');
define('DS_SEO_NAME', 'Dirigible SEO');
define('DS_SEO_SLUG', 'dirigible-seo');
define('DS_SEO_PATH', __FILE__);
define('DS_SEO_DIR', dirname(__FILE__));

// Licensing Data
$licensing_data = [
  'name' => DS_SEO_NAME,
  'version' => DS_SEO_VERSION,
  'slug' => DS_SEO_SLUG,
  'path' => DS_SEO_PATH,
  'dir' => DS_SEO_DIR,
  'is_plugin' => true,
  'is_theme' => false,
];


// Require licensing + updates.
include 'inc/licensing.php';

require_once 'src/DirigibleSEO.php';
require_once 'src/ajax.php';
$SEO = new DirigibleSEO(__FILE__, DS_SEO_VERSION);
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
