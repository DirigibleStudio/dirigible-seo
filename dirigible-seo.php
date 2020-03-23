<?php
/*
 Plugin Name: Dirigible SEO
 Plugin URI: https://dirigiblestudio.com
 description: Dead simple SEO Control for Wordpress. Requires ACF.
 Version: 1.0.0
 Author: Dirigible Studio
 Author URI: https://dirigiblestudio.com
*/

defined( 'ABSPATH' ) OR exit;
require_once 'src/DirigibleSEO.php';
$SEO = new DirigibleSEO(__FILE__);
