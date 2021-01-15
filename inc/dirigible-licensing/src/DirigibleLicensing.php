<?php

if(!class_exists('DirigibleLicensing')){

  class DirigibleLicensing {

    public $path = "";
    public $dir = "";

    function __construct($path, $dir) {
        $this->path = $path;
        $this->dir = $dir;
        $this->api_root = 'https://licensingbackend.dirigible.cloud';
        // $this->api_root = "https://bda864497bdf.ngrok.io";
        $this->api_validate_endpoint = $this->api_root . '/api/licenses/validate';
        $this->api_error = '';
        
        $this->url_path = str_replace($_SERVER['DOCUMENT_ROOT'], '', $this->dir);


        // Naming that is retrieved from whatever it's in, so this can be plug and play.
        $this->set_root_path();
        $this->basename = $this->get_data('Name');
        $this->slug = sanitize_title(strtolower( $this->basename ));
        $this->menu_slug = $this->slug . '-menu';




        // Register page
        add_action( 'admin_menu', [ $this, 'registerToolsPages' ], 11 );


        // Enqueue admin JS
        add_action( 'admin_enqueue_scripts', [ $this, 'plugin_tools_js' ] );

        // Validate Plugin Ajax
        add_action( 'wp_ajax_ds_'.$this->slug.'_validate_plugin', [ $this, 'validate_plugin_ajax' ] );

        // Unregistered plugin notices
        add_action("after_plugin_row_{$this->basename}",[$this, 'plugin_notice'], 10, 3);
        add_action("admin_notices", [$this,'plugin_notice'],  10, 3);
    }


    public function registerToolsPages() {
        if ( empty ( $GLOBALS['admin_page_hooks']['dirigibleAdminPage'] ) ) {
        $icon = "PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHZpZXdCb3g9IjAgMCA0NDYuNDQgMTgzLjg1Ij4KICA8cGF0aCBzdHJva2U9Im5vbmUiIGZpbGw9IndoaXRlIiBkPSJNMTMuMzQsMTYyLjYzbDQwLjM4LTUyLjM4TDAsNzAuNDNjNi4xMi0xLDEwLjczLTEuNzYsMTUuMzctMi40MUM0MC41Niw2NC41NCw2NS43OCw2MS4xNyw5MSw1Ny41NGExMCwxMCwwLDAsMSw4LjU5LDIuNTFjMy4zMiwyLjc1LDYuODgsNi45NCwxMC41LDcuMTFTMTE3LjM2LDYzLjI3LDEyMSw2MUMxNDUuMjIsNDUuNDQsMTcxLjc5LDM0Ljg4LDE5OSwyNS44NWMzMS43OC0xMC41NCw2NC4xOS0xOC42NCw5Ny40NC0yMi44QzMyOS0xLDM2MS42LTIuNCwzOTMuMzYsNy44MmExMDMuNDksMTAzLjQ5LDAsMCwxLDI4LDEzLjcxYzI2LjU1LDE4Ljg4LDM3LDU3LDYuMTksODguODgtMTYuNTgsMTcuMTgtMzYuOTQsMjguMDktNTguODMsMzYuNzNhMTMuNCwxMy40LDAsMCwwLTUuOTEsNS4yNGMtMi42Miw0LjIzLTQuMjYsOS4wNi02LjkzLDEzLjI1YTEwLjA2LDEwLjA2LDAsMCwxLTUuOTUsNC4yNWMtMjYuNjcsNC43NC01My40LDkuMTEtODAuMDgsMTMuODEtMy4zMS41OC00LjgzLS4zNi02LjI1LTMuMTEtMi42OC01LjE3LTUuNDktMTAuMy04LjYzLTE1LjE5YTcuMzIsNy4zMiwwLDAsMC00Ljg4LTMuMWMtNDMuNTMtMi05NC45NC0xMS45MS0xMjguODktMjguMTMtMy40Niw0LjI4LTYuODUsOC44LTEwLjYyLDEzYTEwLjU3LDEwLjU3LDAsMCwxLTUuNjgsMy40MkM3NS4zMiwxNTQuODMsNDUuNjgsMTU4LjksMTYsMTYzQTE3LjE5LDE3LjE5LDAsMCwxLDEzLjM0LDE2Mi42M1oiLz4KPC9zdmc+Cg==";
        add_menu_page(
        'Dirigible Options', // Page Title
        'Dirigible', // Menu Title
        'manage_options', // Capability
        'dirigible/tools', // menu_slug
            [$this, 'dirigibleAdminPage'], // output function 
        'data:image/svg+xml;base64,' . $icon, // icon
        99 // position
        );
      }
      add_submenu_page(
        'dirigible/tools',
        $this->basename, // page_title
        $this->basename, // menu title
        'manage_options', // capability
        $this->menu_slug, // slug
        [$this, 'options_page_content' ] // id
      );
      }

    public function dirigibleAdminPage() {
      ?>
      <div class='wrap dirigible-page'>
        <h1>Dirigible Studio</h1>
        <div class="dirigible-tools">
          <p>For more information, please visit <a href="http://dirigiblestudio.com">Dirigible Studio</a>.</p>
        </div>
      </div>
      <?php
    }



    public function imports() {
      ?>
      <style>
        .ds_plugin_page h1 {
            font-size: 36px;
            margin-top: 8px;
          }
          .ds_plugin_page h1 svg {
            height: 26px;
            margin-right: 8px;
          }
          .dirigible_plugin {
            padding-top: 16px;
            display: flex;
            flex-wrap: wrap;
          }
          .ds_plugin_section {
            box-sizing: border-box;
            padding: 32px;
            background: #fff;;
            box-shadow: 0px 0px 16px 0px rgba(0,0,0,.1);
            max-width: 400px;
            min-width: 100%;
            margin: 0px 32px 32px 0px;
          }
          @media (min-width:700px)  {
            .ds_plugin_section {
              min-width: 40%;
            }
          }
          .ds_plugin_section .license-input {
            height: 36px;
            border-radius: 4px;
            margin-right: 22px;
            padding-left: 8px;
          }
          .ds_plugin_section .license-input.valid {
            border: 2px solid #48bb78; 
          }
          .ds_plugin_section .license-input.invalid {
            border: 2px solid #e53e3e; 
          }
          .activation-button {
            height:36px !important;
            line-height: 36px !important;
          }
          .ds_plugin_section input { margin-bottom: 0px; }
          .ds_plugin_section h3 { margin-top: 0px;  }
          .ds-notification {
            margin-top: 8px;
          background: #2ecc71;
            border: 1px solid #27ae60;
            padding: 16px;
            color: #fff;
          }
          .ds-notification-error {
            margin-top: 8px;
            background: #e74c3c;
            padding: 16px;
            color: #fff;
          }
          .ds-notification a, .ds-notification-error a {
            color: #fff !important;
          }
      </style>
      <?php
    }

    public function create_plugin_section($title, $description, $button = "", $id = "", $html = "", $slug = "") {
      if($button) {
        echo "<div class='ds_plugin_section' data-slug='{$slug}'>";
      }
      else {
        echo "<div class='ds_plugin_section' id='{$id}' >";
      }
        echo "<h3>{$title}</h3>";
        echo "<p>{$description}</p>";
        if($html) {
          echo $html;
        }
        $license = $this->get_license();
        if($button && $license['isValid'] == 0) {
          echo '<input class="license-input invalid" type="text" placeholder="license key. . ."></input>';
          echo "<a class='button activation-button' id='{$id}'>{$button}</a>";
        }
        else {
          
          echo '<input class="license-input valid" type="text" placeholder="license key. . ." value="'. $license['license'].'"></input>';
          echo "<a class='button activation-button' id='{$id}'>{$button}</a>";
          echo "<p>You are activated and ready to go!</p>";
        }

      echo '</div>';
    }


    public function options_page_content() {

      $this->imports();

      echo "<div class='wrap ds_tools_page'>";
        echo "<h1> </h1>";
        echo '<div class="dirigible_tools">';

          $this->create_plugin_section(
                    'Activate', // title
                    'Input your license below.', // desc
                    'Activate',
                    // this must be unqiue in each plugin
                    'ds_'.$this->slug.'_activate',
                    '',
                    $this->slug
          );


        echo '</div>';
      echo "</div>";
    }

    public function plugin_tools_js() {
    //  wp_register_script($this->slug . '-ds-tools-js', $this->url_path.'/src/js/admin.js', array('jquery'), NULL, true);
      // this will need some logic to detect if its in a theme or plugin

      // Conditionally register scripts for each

      // Path Data
      $_path_data = explode( '/', $this->path ) ;
      // Plugins basename
      $_plugin_root = basename( WP_PLUGIN_DIR );


      // if is a plugin
      if(in_array($_plugin_root,$_path_data) || in_array('mu-plugins', $_path_data)){
        wp_register_script('ds-tools-js', plugin_dir_url(__FILE__) . 'js/admin.js', array('jquery'));
        wp_localize_script('ds-tools-js', 'slug',  $this->slug );
        wp_enqueue_script( 'ds-tools-js' );
      }
      else {
        wp_register_script('ds-tools-js', get_template_directory_uri().'/inc/dirigible-licensing/src/js/admin.js', array('jquery'));
        wp_localize_script('ds-tools-js', 'slug',  $this->slug );
        wp_enqueue_script( 'ds-tools-js' );
      }

    }

    public function modify_plugin_update_message( $error ) {
        // display message
        echo '<br />';
        echo $error;
    }


    public function validate_plugin_ajax() {
        $license = $_POST['licenseKey'];
        $url = get_site_url();
        $return = array();
        $error = '';
    
        $endpoint = add_query_arg(array(
              'key' => $license,
              'slug' => $this->slug,
              'url' => $url
          ), $this->api_validate_endpoint);
    
        // 1. Send inputted key to licensing server. 
        $res = wp_remote_post($endpoint);
    
        // 2. Decode json response
        $res = json_decode($res['body']);

    
        //3. Resolve request by either alerting the user to an error, or saving license and toggling isValid in options.
        if(isset($res->error)) {
          $error = $res->error;
          $this->set_license('', false);
          $return[] = "<div class='ds-notification-error' style='margin-top: 8px;'>". $error->body . "</div>";
        }
    
        if(isset($res->valid)) {
          $this->set_license($license, true);
          // We have revalidated our license, stop telling the user it's broke.
          $this->clear_api_error();
          $return[] = "<div class='ds-notification'>Activation successful! Thank you!</div>";
        }

        echo json_encode($return);

        wp_die();
    }


    public function plugin_notice() {
      if( is_main_site()) {
        if($this->get_api_error()) {

        $api_error = $this->get_api_error();
        echo '<tr class="active"><td>&nbsp;</td><td colspan="2">
            <div class="update-message notice notice-error is-dismissible" style="padding: 8px 16px;">There was an error updating '.$this->basename.': '. $api_error.' </div>
            </td></tr>';
        }
        if(!$this->is_plugin_valid()){
          echo '<tr class="active"><td>&nbsp;</td><td colspan="2">
          <div class="update-message notice notice-error is-dismissible" style="padding: 8px 16px;">Please <a href="'.admin_url().'/admin.php?page='.$this->menu_slug.'">register</a> '.$this->basename.' to receive updates and support. </div>
          </td></tr>';
        }
      }
        
    }

    public function set_api_error($error) {
      $option = 'ds_'.$this->slug.'_api_error';
      update_option($option, $error['body']);
    }
    
    public function clear_api_error() {
      $option = 'ds_'.$this->slug.'_api_error';
      update_option($option, '');
    }

    public function get_api_error() {
      $option = 'ds_'.$this->slug.'_api_error';
      return get_option($option);
    }

    // Utils
  public function get_license() {

      $license = array(
          'license' => false,
          'isValid' => 0
      );

      $option = 'ds_'.$this->slug.'_license';
      $license['license'] = get_option($option);

      $option = 'ds_'.$this->slug.'_isValid';
      $license['isValid'] = get_option($option);

      return $license;
    }

    public function set_license($license, $isValid){
      $option = 'ds_'.$this->slug.'_license';
      update_option($option, $license);

      $option = 'ds_'.$this->slug.'_isValid';
      update_option($option, $isValid);
      return;
    }

    public function is_plugin_valid() {
    $option = 'ds_'.$this->slug.'_isValid';
    $isValid = get_option($option);

    return $isValid;
    }


  /**
   * Plugin root
   * @return Full Path to plugin folder
   */
  public function set_root_path() 
  {


      $_path = trailingslashit( str_replace( basename( __FILE__ ), "", plugin_basename( __FILE__ ) ) );
      // Allow overriding the location
      $_path = apply_filters( __CLASS__.'_root', $_path );

      return $this->_path = $_path;
  }


  function log($item) {
      echo '<h1>'.json_encode($item).'</h1>';
  }


  /**
   * Gets the data of the base 'theme' / 'plugin' / 'wpmuplugin'
   * Performance: average loading time on a local (not vanilla) install for 1.000 runs: 0.0042 sec.
   * 
   * @param (mixed) $value
   * @return (array) $value | Theme/Plugin comment header data OR false on failure | default: 'Version'
   */
  public function get_data( $value = 'Version' )
  {
      // Class basename - String to Array

      $_path_data = explode( '/', $this->path ) ;

      // mimic trailingslashit
      array_push($_path_data, '/');
      // Get rid of the last element, as it's only a trailing slash
      array_pop( $_path_data );
      // reverse for faster processing
      krsort( $_path_data );

      // Themes basename
      $theme_roots = get_theme_roots();
      // In case some used register_theme_directory(); before
      // Might not work if an additional themes directory will be registered later
      // Thanks to @Thomas Scholz <http://toscho.de> for the hint
      if ( is_array( $theme_roots ) )
      {    
          foreach ( $_path_data as $_path_part )
          {
              foreach( $theme_roots as $root )
              {
                  if ( in_array( $root, $_path_data ) )
                      $_theme_root = $root;
              }
          }
      }
      else 
      {
          // Get rid of the leading slash
          $_theme_root = str_replace( '/', '', $theme_roots );
      }

      // Plugins basename
      $_plugin_root = basename( WP_PLUGIN_DIR );


      # >>>> get file & load data
      $base_file = '';
      // Themes
      if ( in_array( $_theme_root, $_path_data ) )
      {



          foreach ( search_theme_directories() as $folder => $data )
          {
              foreach ( $_path_data as $_path_part )
              {
                  if ( $_path_part == $folder )

                      $base_file = trailingslashit( $data['theme_root'] );
              }
          }

          $file_data = wp_get_theme(get_template(), $base_file );
      }
      // Plugins
      elseif( in_array( $_plugin_root, $_path_data ) )
      {

          $plugins = get_plugins();
          foreach ( $plugins as $plugin_file => $plugin_info )
          {
              $data   = explode( '/', $plugin_file );
              $file   = $data[1];


              foreach ( $_path_data as $_path_part )
              {

                  if ( $_path_part == $file )
                      $base_file = WP_CONTENT_DIR.'/plugins/'.$data[0].'/'.$data[1];

              }
          }

          $file_data = get_plugin_data( $base_file );
      }
      // WPMU Plugins
      else
      {


          // MU plugins basename - compatible for older MU too
          // Thanks (again) to @Thomas Scholz <http://toscho.de> for the hint that mu plugins really exists
          $mu_plugin_dir = ! version_compare( $GLOBALS['wp_version'], '3.0.0', '>=' ) ? MUPLUGINDIR : WPMU_PLUGIN_DIR;
          $_mu_plugin_root = basename( $mu_plugin_dir );

          if ( ! in_array( $_mu_plugin_root, $_path_data ) )
              return false;

          $mu_plugins = get_mu_plugins();
          foreach ( $mu_plugins as $mu_plugin_file => $mu_plugin_info )
          {
              $data   = explode( '/', $mu_plugin_file );
              $file   = $data[1];
              foreach ( $_path_data as $_path_part )
              {
                  if ( $_path_part !== $file )
                      $base_file = WP_CONTENT_DIR.'/mu-plugins/'.$data[0].'/'.$data[1];
              }
          }

          $file_data = get_plugin_data( $base_file );
      }
      # <<<< get file & load data

      // return
      if ( ! empty ( $file_data ) )
          return $file_data[ $value ];

      // return false to determine that we couldn't load the comment header data
      return false;
  }


  }

}

?>