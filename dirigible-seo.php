<?php
/*
 Plugin Name: Dirigible SEO
 Plugin URI: https://dirigiblestudio.com/wordpress/plugins/
 description: Dead simple SEO Control for Wordpress. Requires ACF.
 Version: 2.2.2
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
