<?php

class DirigibleSEO
{
  public $path = "";
  public $dir = "";
  public $yoast = false;
  public $imageSearch = null;

  function __construct($args)
  {
    if (
      is_plugin_active('advanced-custom-fields-pro/acf.php') ||
      is_plugin_active('advanced-custom-fields/acf.php') ||
      !(class_exists('acf_pro') || class_exists('acf'))
    ) {
      $this->path = $args;
      $this->dir = plugin_dir_path($args);
      if (is_plugin_active('wordpress-seo/wp-seo.php')) {
        $this->yoast = true;
      }
      add_action('admin_enqueue_scripts', [$this, 'registerStyle']);
      add_action('admin_enqueue_scripts', [$this, 'registerScripts']);
      add_filter('document_title_parts', [$this, 'dirigiblePageTitle']);
      add_action('customize_register', [$this, 'registerCustomizer'], 999, 1);
      add_action('acf/init', [$this, 'registerFields']);
      add_action('ds-tools-page', [$this, 'addMigrateTool'], 12, 0);

      add_action('ds_seo_head_title_tag', [$this, 'printMetaTitleTag'], 10);
      add_action('ds_seo_head_description_tag', [$this, 'printMetaDescriptionTag'], 10);
      add_action('ds_seo_head_image_tag', [$this, 'printMetaImageTag'], 10);


      if ($this->yoast) {
        add_action('admin_notices', [$this, 'nagYoast']);
      }
      add_action('wp_head', [$this, 'readerHeaderHook'], 1);
      add_action('admin_menu', [$this, 'registerToolsPages'], 11);
    } else {
      add_action('admin_notices', [$this, 'nagACF']);
    }
  }

  function registerCustomizer($wp_customize)
  {
    if (class_exists('DirigibleSettings')) {
      $Settings = new DirigibleSettings($this->dir . 'src/seo.json');
      $Settings->registerCustomizer($wp_customize);
    }
  }

  public function printMetaTitleTag()
  {
    $title = $this->stringFilters($this->metaTitle());
    echo '<meta property="og:title" content="' . $title . '">';
  }


  public function printMetaDescriptionTag()
  {
    $description = $this->stringFilters($this->metaDescription());
    echo '<meta property="og:description" name="description" content="' . $description . '">';
  }

  public function printMetaImageTag()
  {
    $id = get_the_id();
    if ($id) {
      if (has_post_thumbnail()) {
        $thumbnail = wp_get_attachment_image_src(get_post_thumbnail_id($id), 'large');
        echo "<meta property='og:image' content='{$thumbnail[0]}' />";
      } else {
        global $post;
        $blocks = parse_blocks($post->post_content);
        $firstBlockImage = $blocks[0]['attrs']['bgImageID'] ?? null;
        if ($firstBlockImage) {
          $url = wp_get_attachment_image_src($firstBlockImage, 'large');
          echo "<meta property='og:image' content='{$url[0]}' />";
        } else {
          foreach ($blocks as $block) {
            $this->searchForImageBlock($block);
            if ($this->imageSearch) {
              echo "<meta property='og:image' content='{$this->imageSearch}' />";
            }
          }
        }
      }
    }
  }

  public function searchForImageBlock($block)
  {
    if ($this->imageSearch === null) {
      $blockName = $block['blockName'] ?? null;
      if ($blockName === 'core/image') {
        $imageID = $block['attrs']['id'] ?? null;
        if ($imageID) {
          $url = wp_get_attachment_image_src($imageID, 'large');
          $this->imageSearch = $url[0];
        }
      } else {
        foreach ($block['innerBlocks'] as $newBlock) {
          $this->searchForImageBlock($newBlock);
        }
      }
    }
  }


  public function readerHeaderHook()
  {
    echo '<!-- Dirigible SEO -->';
    // the_post();
    if ($this->yoast) {
      echo "<!--\n";
    } // comment it out if yoast is active
    if ($this->yoast) {
      echo "Please deactivate Yoast SEO in order to use Dirigible SEO.\n";
    } // comment it out if yoast is active

    $link = get_the_permalink();
    $name = get_bloginfo('name');
    echo "<meta property='og:type' content='website' />";
    echo '<meta property="og:url" content="' . $link . '">';
    echo '<meta property="og:site_name" content="' . $name . '">';
    do_action('ds_seo_head_title_tag');
    do_action('ds_seo_head_description_tag');
    do_action('ds_seo_head_image_tag');
    if ($this->yoast) {
      echo "-->\n";
    } // comment it out if yoast is active
    echo '<!-- End Dirigible SEO -->';
  }


  function dirigiblePageTitle($title_parts)
  {
    $newTitle = $this->stringFilters($this->metaTitle());
    if ($newTitle) {
      $title_parts['title'] = $newTitle;
      $title_parts['tagline'] = '';
      $title_parts['site'] = '';
    }
    return $title_parts;
  }




  public function stringFilters($str)
  {
    if (strpos($str, '{') !== false) {
      $term = get_queried_object();
      $title = get_the_title();
      $separator = get_theme_mod('ds_seo_separator', '-');
      if (isset($term)) {
        $title = $term->name ?: get_the_title();
      }
      $site = get_bloginfo('name');
      $str = str_replace(['{Title}', '{title}', '{page}', '{Page}'], $title, $str);
      $str = str_replace(['{Site}', '{site}'], $site, $str);
      $str = str_replace(['{Sep}', '{sep}', '{separator}', '{Separator}', '{-}', '{|}'], $separator, $str);
    }
    return $str;
  }

  public function metaDescription()
  {
    $seoDescription = "";
    $term = get_queried_object();
    if (is_home()) { // blog page
      $page_for_posts = get_option('page_for_posts');
      $seoDescription = get_field('ds_seo_description', $page_for_posts) ?: $this->getDefaultDescription();
    } elseif (isset($term)) {
      if (function_exists('is_shop')) {

        if (is_shop()) {
          // shop
          $shop = get_option('woocommerce_shop_page_id');
          $seoDescription = get_field('ds_seo_description', $shop) ?: $this->getDefaultDescription();
        }
        if (is_product()) {
          // product
          $seoDescription = get_field('ds_seo_description', $term) ?: $this->getDefaultDescription();
        }
      }
      if ($term instanceof WP_Post) {
        $seoDescription = get_field('ds_seo_description') ?: $this->getDefaultDescription();
      } else {
        $seoDescription = get_field('ds_seo_description', $term) ?: $this->getDefaultDescription();
      }
    } elseif (is_archive()) {
      $page_for_posts = get_option('page_for_posts');
      $seoDescription = get_field('ds_seo_description', $page_for_posts) ?: $this->getDefaultDescription();
    } else {
      $seoDescription = get_field('ds_seo_description') ?: $this->getDefaultDescription();
    }
    return $seoDescription === '' ? $this->getDefaultDescription() : $seoDescription;
  }


  function addMigrateTool()
  { ?>
    <div class="tool">
      <h3>Migrate Yoast Data</h3>
      <p>Transfer data from Yoast to Dirigible SEO. This will overwrite any conflicting data, so use with caution!</p>
      <a class='button' id='ds-migrate-yoast'>Migrate</a>
    </div>
  <?php
  }

  function getDefaultDescription()
  {
    $excerpt = "";
    $page_id = get_the_id();
    $excerpt = apply_filters('the_excerpt', get_post_field('post_excerpt', $page_id, 'display'));
    if (empty($excerpt)) {
      if ($page_id) {
        $post = get_post($page_id);
        $excerpt =  apply_filters('the_excerpt',  wp_html_excerpt($post->post_content, 320));
        $excerpt .= '…';
      }
    }
    return strip_tags($excerpt);
  }

  public function metaTitle()
  {
    $term = get_queried_object();
    $returnTitle = "";

    if (is_home()) {
      // blog page
      $page_for_posts = get_option('page_for_posts');
      $returnTitle = get_field('ds_seo_title', $page_for_posts) ?: $this->getDefaultTitle();
    } elseif (is_front_page()) {
      // home page
      $returnTitle = get_field('ds_seo_title') ?: get_bloginfo('name');
    } elseif (isset($term)) {
      // if shop
      if (function_exists('is_shop')) {
        if (is_shop()) {
          $shop = get_option('woocommerce_shop_page_id');
          $returnTitle = get_field('ds_seo_title', $shop);
        }
      }
      // is taxonomy
      $returnTitle = get_field('ds_seo_title', $term) ?: $this->getDefaultTitle();
    } elseif (is_archive()) {
      // is archive

      $returnTitle = $this->getDefaultTitle();
    } else {
      $returnTitle = get_field('ds_seo_title') ?: $this->getDefaultTitle();
    }
    return $returnTitle === '' ? $this->getDefaultTitle() : $returnTitle;
  }

  function getDefaultTitle()
  {
    $separator = get_theme_mod('ds_seo_separator', '-');
    $title = wp_title('', false, 'right');
    if (is_tax()) {
      // only show one parent category
      $term = get_term_by('slug', get_query_var('term'), get_query_var('taxonomy'));
      $term_title = $term->name;
      $title = $term_title;
    }
    $site = get_bloginfo('name');
    return "{$title} {$separator} {$site}";
  }

  public function nagYoast()
  {
    $link = menu_page_url("dirigibleSEO", false);
    $warning = "<p>It looks like you have Yoast active. In order minimize duplicate <head> entries, Dirigible SEO will not print any meta data until Yoast has been deactivated. In the meantime, you can still set up your Dirigible SEO data.</p><p>If you already have Yoast metadata set up, you can <a href='$link'>use our migration tool</a> to move your data from Yoast to Dirigible SEO.</p>";
    printf("<div class='notice notice-error is-dismissable'><h2>Dirigible SEO and Yoast are both active.</h2>{$warning}</div>");
  }

  public function nagACF()
  {
    $warning = "Dirigible SEO requires <a href='https://www.advancedcustomfields.com/'>Advanced Custom Fields</a> for SEO functionality. Please install ACF. (Let's be real, you should be using ACF anyway!)";
    printf("<div class='notice notice-error'><h2>SEO Warning</h2><p>{$warning}</p></div>");
  }

  public function registerStyle()
  {
    wp_register_style('dirigible-seo', plugins_url('dirigible-seo/dist/ds-seo.css'));
    wp_enqueue_style('dirigible-seo');
  }

  public function registerScripts()
  {
    wp_register_script('dirigible-seo-js', plugins_url('dirigible-seo/dist/ds-seo-min.js'), ['jquery'], NULL, true);
    wp_enqueue_script('dirigible-seo-js');
  }

  public function registerFields()
  {
    $separator = get_theme_mod('ds_seo_separator') ?? '-';
    $site = get_bloginfo('name');
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
          'wrapper' => ['id' => 'ds-editor-seo-preview'],
          'message' => 'Search Engine Preview',
          'new_lines' => '',
          'esc_html' => 0,
        ],
        [
          'key' => 'field_5e752dca506b7',
          'label' => 'SEO Title',
          'name' => 'ds_seo_title',
          'type' => 'text',
          'wrapper' => ['id' => 'ds-editor-seo-title'],
          'placeholder' => '{title} {|} {site}',
          'default' => '{title} {|} {site}',
          'maxlength' => 70,
        ],
        [
          'key' => 'field_c2d0e73dd0f44771',
          'label' => 'SEO Description',
          'name' => 'ds_seo_description',
          'wrapper' => ['id' => 'ds-editor-seo-description'],
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

  public function registerToolsPages()
  {
    global $admin_page_hooks;
    if (empty($GLOBALS['admin_page_hooks']['dirigible/tools'])) {
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
      add_submenu_page(
        'dirigible/tools', // parent slug
        'Dirigible Tools', // page_title
        'Tools', // menu title
        'manage_options', // capability
        'dirigible/tools', // slug
        'ds_blocks_tools_page' // id
      );
    }
  }

  public function dirigibleAdminPageRender()
  {
  ?>
    <div class='wrap dirigible-tools-page'>
      <h1>Dirigible Tools</h1>
      <div class="dirigible-tools">
        <?php do_action('ds-tools-page'); ?>
      </div>
    </div>
<?php
  }
}

?>