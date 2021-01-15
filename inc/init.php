<?php 

require 'plugin-update-checker/plugin-update-checker.php';


$dirigibleBlocksPlugin = Puc_v4_Factory::buildUpdateChecker(
	$licensing->api_root.'/api/updates/'.$licensing->slug,
	plugin_dir_path( dirname( __FILE__ ) ).'/'.$licensing->slug.'.php', //Full path to the main plugin file or functions.php.
  $licensing->slug	
);
 

// 1. Attach our license to the request to the licensing server.
$dirigibleBlocksPlugin->addQueryArgFilter(function($queryArgs) use ($licensing){
  return ds_filter_update_checks($queryArgs, $licensing);
});

// 2. Attach our license to the request to the licensing server.

if(!function_exists('ds_filter_update_checks')){
  function ds_filter_update_checks($queryArgs, $licensing) {
    $key = $licensing->get_license();
    if ( !empty($key) ) {  $queryArgs['licenseKey'] = $key; }
    return $queryArgs;
  }
}

// Suppress API Metadata Errors because we're taking over error handling.
add_filter('puc_show_trigger_api_response_error', function() {
  return false;
});

// Handles dirigible specific API errors.
add_action('ds_handle_api_error', function($error) use ($licensing) {
  return  $licensing->set_api_error($error);
}, 10, 2 );

