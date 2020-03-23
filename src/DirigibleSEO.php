<?php

class DirigibleSEO {
  public $path = "";

  function __construct($args) {
    if ( !is_plugin_active('advanced-custom-fields-pro/acf.php') ) {
      // abort if ACF isnt active
      printf("<div class='notice notice-error'><h1>SEO Warning</h1><p>Dirigible SEO requires <a href='https://www.advancedcustomfields.com/'>Advanced Custom Fields Pro</a> for SEO functionality. Please install ACF.</p></div>");
    }
    else {
      $this->path = $args;
      add_action( 'admin_enqueue_scripts', [ $this, 'registerStyle' ] );
      add_action( 'acf/init',[ $this, 'registerFields' ] );
    }

  //  register_activation_hook($this->path, [$this, 'activate'] );
  //  add_action( 'init', [ $this, 'registerPostType' ] );
  //  add_action( 'init', [ $this, 'registerTaxonomies' ] );
  //  add_action( 'wp_enqueue_scripts', [ $this, 'registerStyle' ]  );

//    add_action('wp_enqueue_scripts', [ $this, 'registerScripts' ]);
//    add_action('admin_enqueue_scripts', [ $this, 'registerScripts' ]);
  }

  public function registerStyle() {
    wp_register_style( 'dirigible-seo', plugins_url('dirigible-seo/css/dirigible-seo.css') );
    wp_enqueue_style( 'dirigible-seo' );
  }

  public function registerFields() {
    $default_title =  'Page Title - ' . get_bloginfo( 'name' );
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
          'placeholder' => $default_title,
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

}

?>
