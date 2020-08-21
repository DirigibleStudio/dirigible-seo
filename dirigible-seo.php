<?php
/*
 Plugin Name: Dirigible SEO
 Plugin URI: https://dirigiblestudio.com/wordpress/plugins/
 description: Dead simple SEO Control for Wordpress. Requires ACF.
 Version: 1.1.3
 Author: Dirigible Studio
 Author URI: https://dirigiblestudio.com
*/

defined( 'ABSPATH' ) OR exit;  
include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
require_once 'src/DirigibleSEO.php';
require_once 'src/ajax.php';
$SEO = new DirigibleSEO(__FILE__);