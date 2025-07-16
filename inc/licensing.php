<?php
// inc/licensing.php
if (!isset($licensing_data)) {
  return;
}

add_action('admin_init', function () use ($licensing_data) {
  if (
    class_exists('DirigibleLicensing') &&
    class_exists('Puc_v4_Factory')
  ) {
    try {
      $licensing = new DirigibleLicensing(
        __FILE__,
        __DIR__,
        $licensing_data
      );

      $plugin = Puc_v4_Factory::buildUpdateChecker(
        $licensing->api_root . '/api/updates/' . $licensing->slug,
        plugin_dir_path(dirname(__FILE__)) . '/' . $licensing->slug . '.php',
        $licensing->slug
      );

      // Attach license key to update check query
      $plugin->addQueryArgFilter(function ($queryArgs) use ($licensing) {
        return ds_filter_update_checks($queryArgs, $licensing);
      });

      // Suppress default PUC error notices
      add_filter('puc_show_trigger_api_response_error', '__return_false');

      // Handle custom Dirigible API error
      add_action('ds_handle_api_error', function ($error) use ($licensing) {
        return $licensing->set_api_error($error);
      }, 10, 2);
    } catch (\Throwable $th) {
      error_log('[Dirigible Licensing Error] ' . $th->getMessage());
    }
  }
});

// Global license query arg filter
if (!function_exists('ds_filter_update_checks')) {
  function ds_filter_update_checks($queryArgs, $licensing)
  {
    $key = $licensing->get_license();
    if (!empty($key)) {
      $queryArgs['licenseKey'] = $key;
    }
    return $queryArgs;
  }
}
