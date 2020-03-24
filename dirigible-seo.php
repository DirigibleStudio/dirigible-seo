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
if ( ! function_exists( 'is_plugin_active' ) ) {
    function is_plugin_active( $plugin ) {
        return in_array( $plugin, (array) get_option( 'active_plugins', array() ) );
    }
}

require_once 'src/DirigibleSEO.php';
require_once 'src/ajax.php';
$SEO = new DirigibleSEO(__FILE__);
