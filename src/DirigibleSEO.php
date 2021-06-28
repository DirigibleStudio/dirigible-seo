<?php

class DirigibleSEO {
  public $path = "";
  public $dir = "";
  public $yoast = false;

  function __construct($args) {
    if ( 
      is_plugin_active('advanced-custom-fields-pro/acf.php') || 
      is_plugin_active('advanced-custom-fields/acf.php') || 
      !(class_exists('acf_pro') || class_exists('acf')) ) 
    {
      $this->path = $args;
      $this->dir = plugin_dir_path($args); 
      if ( is_plugin_active('wordpress-seo/wp-seo.php') ) {
        $this->yoast = true;
      }
      add_action( 'admin_enqueue_scripts', [ $this, 'registerStyle' ] );
      add_action( 'admin_enqueue_scripts', [ $this, 'registerScripts' ] );
      add_filter( 'document_title_parts', [ $this, 'dirigiblePageTitle' ] );
      add_action( 'customize_register', [ $this, 'registerCustomizer'], 999, 1 );
      add_action( 'acf/init',[ $this, 'registerFields' ] );

      if($this->yoast) {
        add_action( 'admin_notices', [ $this, 'nagYoast' ] );
      }
      add_action('wp_head', [ $this, 'readerHeaderHook' ], 1);
      add_action( 'admin_menu', [ $this, 'registerToolsPages' ], 11 );
    }
    else {
      add_action( 'admin_notices', [ $this, 'nagACF' ] );
    }
  }

  function registerCustomizer($wp_customize) {
    try {
      $Settings = New DirigibleSettings($this->dir.'src/seo.json');
      $Settings->registerCustomizer($wp_customize);
    }
    catch (\Exception $e) {}
  } 

  public function readerHeaderHook() {
    echo '<!-- Dirigible SEO -->';
    // the_post();
    if($this->yoast) { echo "<!--\n"; } // comment it out if yoast is active
    if($this->yoast) { echo "Please deactivate Yoast SEO in order to use Dirigible SEO.\n"; } // comment it out if yoast is active
    $id = get_the_id();
    $title = $this->stringFilters($this->metaTitle());
    $description = $this->stringFilters($this->metaDescription());
    $link = get_the_permalink();
    $name = get_bloginfo('name');
    echo '<meta property="og:title" content="'.$title.'">';
    echo '<meta property="og:url" content="'.$link.'">';
    echo '<meta property="og:site_name" content="'.$name.'">';
    echo "<meta property='og:type' content='website' />";
    echo '<meta property="og:description" content="'.$description.'">';
    echo '<meta name="description" content="'.$description.'">';

    if($id) {
      if(has_post_thumbnail()) {
        $thumbnail = wp_get_attachment_image_src(get_post_thumbnail_id($id), 'large');
        echo "<meta property='og:image' content='{$thumbnail[0]}' />";
      }
    }
    if($this->yoast) { echo "-->\n"; } // comment it out if yoast is active
    echo '<!-- End Dirigible SEO -->';
  }

  
  function dirigiblePageTitle($title_parts) {
    $newTitle = $this->stringFilters($this->metaTitle());
    if($newTitle) {
      $title_parts['title'] = $newTitle;
      $title_parts['tagline'] = '';
      $title_parts['site'] = ''; 
    }
    return $title_parts;
  }
  



  public function stringFilters($str) {
    if (strpos($str, '{') !== false) { 
      $term = get_queried_object();
      $title = get_the_title();
      $separator = get_theme_mod('ds_seo_separator') ?? '-';
      if( isset($term) ){
        $title = $term->name;
      }
      $site = get_bloginfo( 'name' );
      $str = str_replace(['{Title}', '{title}', '{page}', '{Page}'], $title, $str);
      $str = str_replace(['{Site}', '{site}'], $site, $str);
      $str = str_replace(['{Sep}', '{sep}', '{separator}', '{Separator}', '{-}', '{|}'], $separator, $str);
    }
    return $str;
  }

  public function metaDescription() {
    $seoDescription = "";
		$term = get_queried_object(); 
		if(is_home()) { // blog page
      
			$page_for_posts = get_option( 'page_for_posts' );
			$seoDescription = get_field('ds_seo_description', $page_for_posts);
			if($seoDescription) { return $seoDescription;	}
		}
		elseif( isset($term) ){
			if(function_exists('is_shop')) {
				if( is_shop() ) {
					$shop = get_option( 'woocommerce_shop_page_id' );
					$seoDescription = get_field('ds_seo_description', $shop);
					if($seoDescription) { return $seoDescription;	}
				}
				if( is_product() ) {
					$seoDescription = get_field('ds_seo_description', $term);
					if($seoDescription) { return $seoDescription;	}
					else return $this->getDefaultDescription();
				}
			}
      if($term instanceof WP_Post) {
        $seoDescription = get_field('ds_seo_description');
        if($seoDescription) { return $seoDescription;	}
        return $this->getDefaultDescription();
      }
      else {
        $seoDescription = get_field('ds_seo_description', $term);
			  if($seoDescription) { return $seoDescription;	}
			  else return "";
      }
		} 
		elseif (is_archive()) {
			$page_for_posts = get_option( 'page_for_posts' );
			$seoDescription = get_field('ds_seo_description', $page_for_posts);
			if($seoDescription) { return $seoDescription;	}
		}
		else {
			$seoDescription = get_field('ds_seo_description');
			if($seoDescription) { return $seoDescription;	}
		}
		return $this->getDefaultDescription();
  }


  function getDefaultDescription() {
    $excerpt = "";
    $page_id = get_the_id(); 
		$excerpt = apply_filters( 'the_excerpt', get_post_field( 'post_excerpt', $page_id, 'display' ) );
		if( empty($excerpt) ) {
      if($page_id) {
        $post = get_post($page_id);
        $excerpt =	apply_filters( 'the_excerpt',  wp_html_excerpt( $post->post_content, 320 ));
        $excerpt .= '…';
      }
     
		}
		return strip_tags($excerpt);
	}

  public function metaTitle() { 
		$term = get_queried_object();
    $seoTitle = "";
		if(is_home()) { // blog page
			$page_for_posts = get_option( 'page_for_posts' );
			$seoTitle = get_field('ds_seo_title', $page_for_posts);
			if($seoTitle) { return $seoTitle;	}
		}
    elseif(is_front_page()) {
      $seoTitle = get_field('ds_seo_title');
			if($seoTitle) { return $seoTitle;	}
      else {  return get_bloginfo( 'name' ); };
    }
		elseif( isset($term) ){ // if any tax or shop
			if(function_exists('is_shop')) {
				if( is_shop() ) {
					$shop = get_option( 'woocommerce_shop_page_id' );
					$seoTitle = get_field('ds_seo_title', $shop);
					if($seoTitle) { return $seoTitle;	}
				}
			}
			$seoTitle = get_field('ds_seo_title', $term);
			if($seoTitle) { return $seoTitle;	}
		}
		elseif (is_archive()) {
			return $this->getDefaultTitle();
		}
		else {
			$seoTitle = get_field('ds_seo_title');
			if($seoTitle) { return $seoTitle;	}
		}
		return $this->getDefaultTitle();
  }

  function getDefaultTitle() {
    $default = "";
    $separator = get_theme_mod('ds_seo_separator') ?? '-';
    $title = wp_title('', false, 'right'); 
    $site = get_bloginfo( 'name' ); 
    return "{$title} {$separator} {$site}";
  }

  public function nagYoast() {
    $link = menu_page_url("dirigibleSEO", false);
    $warning = "<p>It looks like you have Yoast active. In order minimize duplicate <head> entries, Dirigible SEO will not print any meta data until Yoast has been deactivated. In the meantime, you can still set up your Dirigible SEO data.</p><p>If you already have Yoast metadata set up, you can <a href='$link'>use our migration tool</a> to move your data from Yoast to Dirigible SEO.</p>";
    printf("<div class='notice notice-error is-dismissable'><h2>Dirigible SEO and Yoast are both active.</h2>{$warning}</div>");
  }

  public function nagACF() {
    $warning = "Dirigible SEO requires <a href='https://www.advancedcustomfields.com/'>Advanced Custom Fields</a> for SEO functionality. Please install ACF. (Let's be real, you should be using ACF anyway!)";
    printf("<div class='notice notice-error'><h2>SEO Warning</h2><p>{$warning}</p></div>");
  }

  public function registerStyle() {
    wp_register_style( 'dirigible-seo', plugins_url('dirigible-seo/dist/ds-seo.css') );
    wp_enqueue_style( 'dirigible-seo' );
  }

  public function registerScripts() {
    wp_register_script( 'dirigible-seo-js', plugins_url('dirigible-seo/dist/ds-seo-min.js'), ['jquery'], NULL, true);
    wp_enqueue_script('dirigible-seo-js');
  }

  public function registerFields() {
    $separator = get_theme_mod('ds_seo_separator') ?? '-';
    $site = get_bloginfo( 'name' ); 
    $default_title = "Page Title {$separator} {$site}";
    $SEO_fields = [
      'key' => 'group_5e7523693299c',
      'title' => 'SEO',
      'fields' => [
        [
          'key' => 'field_800f652cfae908aa',
          'label' => 'Preview',
          'name' => 'ds_seo_preview',
          'type' => 'message',
          'wrapper' => [ 'id' => 'ds-editor-seo-preview'],
          'message' => 'Search Engine Preview',
          'new_lines' => '',
          'esc_html' => 0,
        ],
        [
          'key' => 'field_5e752dca506b7',
          'label' => 'SEO Title',
          'name' => 'ds_seo_title',
          'type' => 'text',
          'wrapper' => [ 'id' => 'ds-editor-seo-title'],
          'placeholder' => '{title} {|} {site}',
          'default' => '{title} {|} {site}',
          'maxlength' => 70,
        ],
        [
          'key' => 'field_c2d0e73dd0f44771',
          'label' => 'SEO Description',
          'name' => 'ds_seo_description',
          'wrapper' => [ 'id' => 'ds-editor-seo-description'],
          'type' => 'textarea',
          'rows' => 6,
          'maxlength' => 320,
        ],
      ],
      'location' => [
        [
          [
            'param' => 'post_type',
            'operator' => '==',
            'value' => 'post',
          ],
        ],
        [
          [
            'param' => 'post_type',
            'operator' => '!=',
            'value' => 'post',
          ],
        ],
        [
          [
            'param' => 'page_type',
            'operator' => '==',
            'value' => 'front_page',
          ],
        ],
        [
          [
            'param' => 'page_type',
            'operator' => '!=',
            'value' => 'front_page',
          ],
        ],
        [
          [
            'param' => 'taxonomy',
            'operator' => '==',
            'value' => 'all',
          ],
        ],
      ],
      'menu_order' => 1,
      'position' => 'side',
      'active' => true,
    ];
    acf_add_local_field_group($SEO_fields);
  }

  public function registerToolsPages() {
    if ( empty ( $GLOBALS['admin_page_hooks']['dirigibleAdminPage'] ) ) {
  		$icon = "PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHZpZXdCb3g9IjAgMCA0NDYuNDQgMTgzLjg1Ij4KICA8cGF0aCBzdHJva2U9Im5vbmUiIGZpbGw9IndoaXRlIiBkPSJNMTMuMzQsMTYyLjYzbDQwLjM4LTUyLjM4TDAsNzAuNDNjNi4xMi0xLDEwLjczLTEuNzYsMTUuMzctMi40MUM0MC41Niw2NC41NCw2NS43OCw2MS4xNyw5MSw1Ny41NGExMCwxMCwwLDAsMSw4LjU5LDIuNTFjMy4zMiwyLjc1LDYuODgsNi45NCwxMC41LDcuMTFTMTE3LjM2LDYzLjI3LDEyMSw2MUMxNDUuMjIsNDUuNDQsMTcxLjc5LDM0Ljg4LDE5OSwyNS44NWMzMS43OC0xMC41NCw2NC4xOS0xOC42NCw5Ny40NC0yMi44QzMyOS0xLDM2MS42LTIuNCwzOTMuMzYsNy44MmExMDMuNDksMTAzLjQ5LDAsMCwxLDI4LDEzLjcxYzI2LjU1LDE4Ljg4LDM3LDU3LDYuMTksODguODgtMTYuNTgsMTcuMTgtMzYuOTQsMjguMDktNTguODMsMzYuNzNhMTMuNCwxMy40LDAsMCwwLTUuOTEsNS4yNGMtMi42Miw0LjIzLTQuMjYsOS4wNi02LjkzLDEzLjI1YTEwLjA2LDEwLjA2LDAsMCwxLTUuOTUsNC4yNWMtMjYuNjcsNC43NC01My40LDkuMTEtODAuMDgsMTMuODEtMy4zMS41OC00LjgzLS4zNi02LjI1LTMuMTEtMi42OC01LjE3LTUuNDktMTAuMy04LjYzLTE1LjE5YTcuMzIsNy4zMiwwLDAsMC00Ljg4LTMuMWMtNDMuNTMtMi05NC45NC0xMS45MS0xMjguODktMjguMTMtMy40Niw0LjI4LTYuODUsOC44LTEwLjYyLDEzYTEwLjU3LDEwLjU3LDAsMCwxLTUuNjgsMy40MkM3NS4zMiwxNTQuODMsNDUuNjgsMTU4LjksMTYsMTYzQTE3LjE5LDE3LjE5LDAsMCwxLDEzLjM0LDE2Mi42M1oiLz4KPC9zdmc+Cg==";
  		add_menu_page(
  			'Dirigible Options', // Page Title
  			'Dirigible', // Menu Title
  			'manage_options', // Capability
  			'dirigible/tools', // menu_slug
        [$this, 'dirigibleAdminPageRender'], // output function
  			'data:image/svg+xml;base64,' . $icon, // icon
  			99 // position
  		);
  	}
    add_submenu_page(
  		'dirigible/tools', // parent slug
  		'Dirigible SEO', // page_title
  		'SEO', // menu title
  		'manage_options', // capability
  		'dirigibleSEO', // slug
  		[$this, 'adminPage'] // output function
  	);
  }

  public function dirigibleAdminPageRender() {
    ?>
<div class='wrap dirigible-admin-page'>
  <h1>Dirigible Studio</h1>
  <div class="dirigible-seo-tools">
    <p>For more information, please visit <a href="http://dirigiblestudio.com">Dirigible Studio</a>.</p>
  </div>
</div>
<?php
  }

  public function adminPage() {
    ?>
<div class='wrap dirigible-seo-page'>
  <h1>Dirigible SEO</h1>
  <div class="dirigible-seo-tools">
    <div class="tool">
      <h3>Migrate Yoast Data</h3>
      <p>Transfer data from Yoast to Dirigible SEO. This will overwrite any conflicting data, so use with caution!</p>
      <a class='button' id='ds-migrate-yoast'>Migrate</a>
    </div>
  </div>
</div>
<?php
  }

}

?>