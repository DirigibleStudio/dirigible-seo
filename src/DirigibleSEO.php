<?php

class DirigibleSEO
{
  public $path = "";
  public $dir = "";
  public $version = "";
  public $yoast = false;
  public $imageSearch = null;

  function __construct($args, $version)
  {
    $this->path = $args;
    $this->dir = plugin_dir_path($args);
    $this->version = $version;



    if (defined('WPSEO_VERSION')) {
      $this->yoast = true;
    }

    add_action('admin_enqueue_scripts', [$this, 'registerStyle']);
    add_action('admin_enqueue_scripts', [$this, 'registerScripts']);
    add_filter('document_title_parts', [$this, 'dirigiblePageTitle']);
    add_action('customize_register', [$this, 'registerCustomizer'], 999, 1);

    // new meta boxes
    add_action('add_meta_boxes', [$this, 'registerMetaBoxes']);
    add_action('save_post', [$this, 'saveMetaBoxData']);

    // Register term meta fields
    add_action('init', [$this, 'registerTermMetaFields']);

    // User profile no-index field
    add_action('show_user_profile', [$this, 'addUserNoIndexField']);
    add_action('edit_user_profile', [$this, 'addUserNoIndexField']);
    add_action('personal_options_update', [$this, 'saveUserNoIndexField']);
    add_action('edit_user_profile_update', [$this, 'saveUserNoIndexField']);

    // add_action('acf/init', [$this, 'registerFields']);

    add_action('ds-tools-page', [$this, 'addMigrateTool'], 12, 0);
    add_action('ds-tools-page', [$this, 'addLLMsTxtTool'], 13, 0);

    add_action('ds_seo_head_title_tag', [$this, 'printMetaTitleTag']);
    add_action('ds_seo_head_description_tag', [$this, 'printMetaDescriptionTag']);
    add_action('ds_seo_head_image_tag', [$this, 'printMetaImageTag']);
    add_action('ds_seo_head_no_index_tag', [$this, 'printNoIndexTag']);
    add_action('ds_seo_head_canonical_tag', [$this, 'printCanonicalTag']);
    remove_action('wp_head', 'rel_canonical');

    if ($this->yoast) {
      add_action('admin_notices', [$this, 'nagYoast']);
    }

    add_action('admin_menu', [$this, 'registerToolsPages'], 11);

    add_filter('ds_jsonld_output', [$this, 'overrideJsonldOutput']);

    add_action('save_post', [$this, 'cacheOgImage'], 20);
  }

  /**
   * Override JSON-LD output with custom JSON-LD if enabled
   */
  public function overrideJsonldOutput($jsonld)
  {
    if ($this->hasCustomJsonld()) {
      $customJsonld = get_post_meta(get_the_ID(), 'ds_seo_custom_jsonld', true);
      if ($customJsonld) {
        return '<script type="application/ld+json" id="ds-custom-jsonld">' . $customJsonld . '</script>';
      }
    }

    return $jsonld;
  }

  public function hasCustomJsonld()
  {
    // check if custom JSON-LD is enabled AND has content
    $enabled = get_post_meta(get_the_ID(), 'ds_seo_enable_custom_jsonld', true);
    $content = get_post_meta(get_the_ID(), 'ds_seo_custom_jsonld', true);

    return $enabled && !empty($content);
  }

  public function registerMetaBoxes()
  {
    $post_types = get_post_types(['public' => true]);
    // Loop through each post type and add the meta box
    foreach ($post_types as $post_type) {
      add_meta_box(
        'ds_seo_meta', // Meta box ID
        'SEO', // Title
        [$this, 'renderMetaBox'], // Callback function to display the meta box content
        $post_type, // Post type
        'side', // Context (side column)
        'default' // Priority
      );
    }
  }

  public function renderMetaBox($post)
  {
    // Nonce field for security
    wp_nonce_field('ds_seo_nonce', 'ds_seo_nonce_field');
    $ds_seo_title = get_post_meta($post->ID, 'ds_seo_title', true);
    $ds_seo_description = get_post_meta($post->ID, 'ds_seo_description', true);
    $ds_seo_no_index = get_post_meta($post->ID, 'ds_seo_no_index', true);
    $ds_seo_canonical_url = get_post_meta($post->ID, 'ds_seo_canonical_url', true);
    $ds_seo_enable_custom_jsonld = get_post_meta($post->ID, 'ds_seo_enable_custom_jsonld', true);

    $placeholderExample = "{
  \"@context\": \"https://schema.org\",
  \"@type\": \"AboutPage\",
  \"name\": \"Leadership | Your Site\",
  \"url\": \"https://yoursite.com/about-us/\",
}";
?>
    <div id="ds-seo-meta-box">
      <div id="ds-editor-seo-preview">
        <div class="ds-seo-preview">
          <p>Search Engine Preview</p>
        </div>
      </div>
      <div id="ds-editor-seo-fields">
        <div id="ds-editor-seo-title" class="seo-field">
          <label for="ds_seo_title">SEO Title</label>
          <div class="seo-token-insert" aria-label="Insert token">
            <button type="button" class="seo-pill seo-pill--insert seo-pill--title" data-token="{title}">Title</button>
            <button type="button" class="seo-pill seo-pill--insert seo-pill--sep" data-token="{|}">-</button>
            <button type="button" class="seo-pill seo-pill--insert seo-pill--site" data-token="{site}">Site</button>
          </div>
          <div class="seo-title-input-wrap<?php echo empty($ds_seo_title) ? ' is-empty' : ''; ?>">
            <span class="seo-title-placeholder" aria-hidden="true">Enter a title</span>
            <div id="ds_seo_title_editor"
              contenteditable="true"
              role="textbox"
              aria-multiline="false"
              aria-label="SEO Title"
              spellcheck="false"
              autocorrect="off"
              autocomplete="off"></div>
            <input type="hidden"
              name="ds_seo_title"
              id="ds_seo_title"
              value="<?php echo esc_attr($ds_seo_title); ?>" />
          </div>
        </div>
        <div id="ds-editor-seo-description" class="seo-field">
          <label for=" ds_seo_description">SEO Description</label>
          <textarea name="ds_seo_description" id="ds_seo_description" placeholder="Enter a description for this page..." rows="6"><?php echo esc_textarea($ds_seo_description); ?></textarea>
        </div>
        <div id="ds-editor-seo-no-index" class="seo-field seo-field-checkbox">

          <label class="seo-field-checkbox-input" style="--checked:var(--danger)">
            <input type="checkbox" name="ds_seo_no_index" id="ds_seo_no_index" value="1" <?php checked($ds_seo_no_index, 1); ?> />
            <span>Private Page (Do Not Index)</span>
          </label>

          <p>When checked, search engines will not index this page and a noindex meta tag will be printed.</p>
        </div>
        <div class="seo-divider"></div>
        <div id="ds-editor-seo-canonical" class="seo-field">
          <label class="seo-field-checkbox-input">
            <input type="checkbox" name="ds_seo_custom_canonical_enabled"
              id="ds_seo_custom_canonical_enabled" value="1"
              <?php checked(!empty($ds_seo_canonical_url)); ?> />
            <span>Override canonical URL</span>
          </label>
          <p>By default this page's own URL is used as the canonical. Override only if this page is a duplicate of another.</p>
          <div id="ds-editor-seo-canonical-url" class="seo-field"
            style="<?php echo empty($ds_seo_canonical_url) ? 'display:none' : ''; ?>">
            <label for="ds_seo_canonical_url">Canonical URL</label>
            <input type="url" name="ds_seo_canonical_url" id="ds_seo_canonical_url"
              placeholder="https://example.com/original-page/"
              value="<?php echo esc_attr($ds_seo_canonical_url); ?>" />
          </div>
        </div>
        <div class="seo-divider"></div>
        <h3>JSON-LD</h3>
        <div id="ds-editor-seo-enable-custom-jsonld" class="seo-field seo-field-checkbox">
          <label class="seo-field-checkbox-input" style="--checked:var(--success)">
            <input type="checkbox" name="ds_seo_enable_custom_jsonld" id="ds_seo_enable_custom_jsonld" value="1" <?php checked($ds_seo_enable_custom_jsonld, 1); ?> />
            <span>Enable Custom JSON-LD</span>
          </label>
          <p>Enabling custom JSON-LD will replace the default JSON-LD for this page.</p>
        </div>
        <div id="ds-editor-seo-custom-jsonld" class="seo-field">
          <label for="ds_seo_custom_jsonld">Custom JSON-LD</label>
          <p>Paste your custom JSON-LD, without the &lt;script&gt; tags.</p>
          <textarea name="ds_seo_custom_jsonld" id="ds_seo_custom_jsonld" placeholder="<?php echo esc_attr($placeholderExample); ?>" rows="6"><?php echo (get_post_meta($post->ID, 'ds_seo_custom_jsonld', true)); ?></textarea>
        </div>
      </div>
    </div>
  <?php
  }

  public function saveMetaBoxData($post_id)
  {
    if (!isset($_POST['ds_seo_nonce_field']) || !wp_verify_nonce($_POST['ds_seo_nonce_field'], 'ds_seo_nonce')) {
      return;
    }

    // Check if the user has permission to edit the post
    if (!current_user_can('edit_post', $post_id)) {
      return;
    }

    // Avoid saving on autosave
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
      return;
    }

    // Save the SEO Title
    if (isset($_POST['ds_seo_title'])) {
      update_post_meta($post_id, 'ds_seo_title', sanitize_text_field($_POST['ds_seo_title']));
    }

    // Save the SEO Description
    if (isset($_POST['ds_seo_description'])) {
      update_post_meta($post_id, 'ds_seo_description', sanitize_textarea_field($_POST['ds_seo_description']));
    }

    // Save the No Index checkbox (store as 1 or 0)
    if (isset($_POST['ds_seo_no_index'])) {
      update_post_meta($post_id, 'ds_seo_no_index', 1);
    } else {
      update_post_meta($post_id, 'ds_seo_no_index', 0);
    }

    if (!empty($_POST['ds_seo_canonical_url'])) {
      update_post_meta(
        $post_id,
        'ds_seo_canonical_url',
        esc_url_raw(sanitize_text_field($_POST['ds_seo_canonical_url']))
      );
    } else {
      delete_post_meta($post_id, 'ds_seo_canonical_url');
    }

    // Save the Enable Custom JSON-LD checkbox (store as 1 or 0)
    if (isset($_POST['ds_seo_enable_custom_jsonld'])) {
      update_post_meta($post_id, 'ds_seo_enable_custom_jsonld', 1);
    } else {
      update_post_meta($post_id, 'ds_seo_enable_custom_jsonld', 0);
    }

    // Save the Custom JSON-LD
    if (isset($_POST['ds_seo_custom_jsonld'])) {
      update_post_meta($post_id, 'ds_seo_custom_jsonld', sanitize_textarea_field($_POST['ds_seo_custom_jsonld']));
    } else {
      update_post_meta($post_id, 'ds_seo_custom_jsonld', '');
    }
  }

  public function registerCustomizer($wp_customize)
  {
    if (class_exists('DirigibleSettings')) {
      $Settings = new DirigibleSettings($this->dir . 'src/seo.json');
      $Settings->registerCustomizer($wp_customize);
    }
  }

  public function printMetaTitleTag()
  {
    $title = $this->stringFilters($this->metaTitle());
    echo '<meta property="og:title" content="' . esc_attr($title) . '">';
  }

  public function printNoIndexTag()
  {
    $term = get_queried_object();

    // Check if we're on a post/page
    if (is_singular()) {
      if (get_post_meta(get_the_ID(), 'ds_seo_no_index', true)) {
        echo '<meta name="robots" content="noindex">';
      }
    }
    // Check if we're on a term/taxonomy page
    elseif (is_tax() || is_category() || is_tag()) {
      if ($term instanceof WP_Term && get_term_meta($term->term_id, 'ds_seo_no_index', true)) {
        echo '<meta name="robots" content="noindex">';
      }
    }
    // Check if we're on an author archive page
    elseif (is_author()) {
      $author_id = get_query_var('author');
      if ($author_id && get_user_meta($author_id, 'ds_seo_no_index', true)) {
        echo '<meta name="robots" content="noindex">';
      }
    }
  }

  public function printMetaDescriptionTag()
  {
    $description = $this->stringFilters($this->metaDescription());
    echo '<meta name="description" content="' . esc_attr($description) . '">';
    echo '<meta property="og:description" content="' . esc_attr($description) . '">';
  }

  public function printCanonicalTag()
  {
    $url = $this->canonicalUrl();
    if ($url) {
      echo '<link rel="canonical" href="' . esc_url($url) . '" />' . "\n";
    }
  }

  public function canonicalUrl()
  {
    $term = get_queried_object();

    if (is_singular()) {
      $override = get_post_meta(get_the_ID(), 'ds_seo_canonical_url', true);
      if (!empty($override)) {
        $override = esc_url_raw($override);
        if (!empty($override)) {
          return $override;
        }
      }
    } elseif ((is_tax() || is_category() || is_tag()) && $term instanceof WP_Term) {
      $override = get_term_meta($term->term_id, 'ds_seo_canonical_url', true);
      if (!empty($override)) {
        $override = esc_url_raw($override);
        if (!empty($override)) {
          return $override;
        }
      }
    }

    if (is_singular()) {
      return wp_get_canonical_url(get_the_ID()) ?: get_permalink();
    } elseif (function_exists('is_shop') && is_shop()) {
      return get_permalink(get_option('woocommerce_shop_page_id'));
    } elseif (is_front_page()) {
      return home_url('/');
    } elseif (is_home()) {
      return get_permalink(get_option('page_for_posts'));
    } elseif ((is_tax() || is_category() || is_tag()) && $term instanceof WP_Term) {
      $link = get_term_link($term);
      return is_wp_error($link) ? '' : $link;
    } elseif (is_author()) {
      return get_author_posts_url(get_query_var('author'));
    } elseif (is_archive()) {
      return get_pagenum_link(get_query_var('paged') ?: 1);
    }
    return '';
  }

  public function printMetaImageTag()
  {
    $id = get_the_id();
    if (!$id) {
      return;
    }

    $cachedUrl = get_post_meta($id, 'ds_seo_og_image', true);

    if ($cachedUrl === '' && !metadata_exists('post', $id, 'ds_seo_og_image')) {
      $this->cacheOgImage($id);
      $cachedUrl = get_post_meta($id, 'ds_seo_og_image', true);
    }

    if ($cachedUrl) {
      echo "<meta property='og:image' content='" . esc_url($cachedUrl) . "' />";
    }
  }

  public function cacheOgImage($post_id)
  {
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
      return;
    }
    if (wp_is_post_revision($post_id)) {
      return;
    }

    $post = get_post($post_id);
    if (!$post || $post->post_status === 'auto-draft') {
      return;
    }

    $imageUrl = '';

    if (has_post_thumbnail($post_id)) {
      $thumbnail = wp_get_attachment_image_src(get_post_thumbnail_id($post_id), 'large');
      $imageUrl = $thumbnail[0] ?? '';
    } elseif ($post->post_content) {
      $imageUrl = $this->findFirstImageInBlocks(parse_blocks($post->post_content));
    }

    update_post_meta($post_id, 'ds_seo_og_image', $imageUrl);
  }

  private function findFirstImageInBlocks(array $blocks): string
  {
    $firstBlockImage = $blocks[0]['attrs']['bgImageID'] ?? null;
    if ($firstBlockImage) {
      $url = wp_get_attachment_image_src($firstBlockImage, 'large');
      if ($url && is_array($url)) {
        return $url[0];
      }
    }

    foreach ($blocks as $block) {
      $found = $this->searchBlockForImage($block);
      if ($found) {
        return $found;
      }
    }

    return '';
  }

  private function searchBlockForImage(array $block): string
  {
    $blockName = $block['blockName'] ?? null;
    if ($blockName === 'core/image') {
      $imageID = $block['attrs']['id'] ?? null;
      if ($imageID) {
        $url = wp_get_attachment_image_src($imageID, 'large');
        if ($url && is_array($url)) {
          return $url[0];
        }
      }
    }

    foreach ($block['innerBlocks'] ?? [] as $innerBlock) {
      $found = $this->searchBlockForImage($innerBlock);
      if ($found) {
        return $found;
      }
    }

    return '';
  }

  public function seoHeaderHook()
  {
    echo '<!-- Dirigible SEO -->';
    // the_post();
    if ($this->yoast) {
      echo "<!--\n";
    } // comment it out if yoast is active
    if ($this->yoast) {
      echo "Please deactivate Yoast SEO in order to use Dirigible SEO.\n";
    } // comment it out if yoast is active

    global $wp;
    $link = home_url($wp->request);
    $name = get_bloginfo('name');
    echo "<meta property='og:type' content='website' />";
    echo '<meta property="og:url" content="' . esc_url($link) . '">';
    echo '<meta property="og:site_name" content="' . esc_attr($name) . '">';
    do_action('ds_seo_head_title_tag');
    do_action('ds_seo_head_description_tag');
    do_action('ds_seo_head_image_tag');
    do_action('ds_seo_head_no_index_tag');
    do_action('ds_seo_head_canonical_tag');
    if ($this->yoast) {
      echo "-->\n";
    } // comment it out if yoast is active
    echo '<!-- End Dirigible SEO -->';
  }

  function dirigiblePageTitle($title_parts)
  {
    // Break out if both yoast and dseo are active. Prevents infinite loop on events page.
    if ($this->yoast) {
      return $title_parts;
    }

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
      $separator = (function_exists('ds_get_setting') ? ds_get_setting('ds_seo_separator', '-') : get_theme_mod('ds_seo_separator', '-'));
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

    if (is_home()) {
      // Blog page
      $page_for_posts = get_option('page_for_posts');
      $seoDescription = get_post_meta($page_for_posts, 'ds_seo_description', true) ?: $this->getDefaultDescription();
    } elseif (isset($term)) {
      // WooCommerce shop or product pages
      if (function_exists('is_shop')) {
        if (is_shop()) {
          $shop = get_option('woocommerce_shop_page_id');
          $seoDescription = get_post_meta($shop, 'ds_seo_description', true) ?: $this->getDefaultDescription();
        } elseif (is_product() && $term instanceof WP_Post) {
          // For WooCommerce product pages
          $seoDescription = get_post_meta($term->ID, 'ds_seo_description', true) ?: $this->getDefaultDescription();
        }
      }

      // Taxonomy term description
      if ($term instanceof WP_Term) {
        $seoDescription = get_term_meta($term->term_id, 'ds_seo_description', true);
        if (empty($seoDescription)) {
          // Try WordPress category description before falling back to default
          $category_description = term_description($term->term_id);
          $seoDescription = !empty($category_description) ? strip_tags($category_description) : $this->getDefaultDescription();
        }
      }
      // Post description
      elseif ($term instanceof WP_Post) {
        $seoDescription = get_post_meta($term->ID, 'ds_seo_description', true) ?: $this->getDefaultDescription();
      }
    } elseif (is_archive()) {
      // Archive pages (fallback to blog page description)
      $page_for_posts = get_option('page_for_posts');
      $seoDescription = get_post_meta($page_for_posts, 'ds_seo_description', true) ?: $this->getDefaultDescription();
    } else {
      // Default for single posts or pages
      $seoDescription = get_post_meta(get_the_ID(), 'ds_seo_description', true) ?: $this->getDefaultDescription();
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

  function addLLMsTxtTool()
  {
    $content = $this->getLLMsTxtContent();
  ?>
    <div class="tool">
      <h3>LLMs.txt Manager</h3>
      <p>Create and manage an <a href="https://llmstxt.org/" target="_blank">llms.txt</a> file in your WordPress root directory to help LLMs understand your website. This feature is not compatible with WordPress multisite.</p>
      <div id="llms-txt-editor">
        <textarea id="llms-txt-content" rows="20" style="width: 100%; font-family: monospace;" placeholder="# Your Site Name

> Brief description of your site

Detailed information about your site and content.

## Key Pages

- [Home](https://yoursite.com): Your homepage
- [About](https://yoursite.com/about): About your company"><?php echo esc_textarea($content); ?></textarea>
        <br><br>
        <button class='button button-primary' id='ds-save-llms-txt'>Save llms.txt</button>
        <button class='button' id='ds-load-llms-txt'>Reload from File</button>
        <button class='button button-link-delete' id='ds-delete-llms-txt' style='color: #d63638;'>Delete llms.txt</button>
      </div>
      <div id="llms-txt-status"></div>
    </div>
  <?php
  }

  function getLLMsTxtContent()
  {
    $file_path = ABSPATH . 'llms.txt';
    if (file_exists($file_path)) {
      return file_get_contents($file_path);
    }
    return '';
  }

  function saveLLMsTxtContent($content)
  {
    $file_path = ABSPATH . 'llms.txt';
    return file_put_contents($file_path, $content) !== false;
  }

  function deleteLLMsTxtFile()
  {
    $file_path = ABSPATH . 'llms.txt';
    if (file_exists($file_path)) {
      return unlink($file_path);
    }
    return true; // File doesn't exist, so it's already "deleted"
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
      // Blog page
      $page_for_posts = get_option('page_for_posts');
      $returnTitle = get_post_meta($page_for_posts, 'ds_seo_title', true) ?: $this->getDefaultTitle();
    } elseif (is_front_page()) {
      // Home page
      $returnTitle = get_post_meta(get_the_ID(), 'ds_seo_title', true) ?: get_bloginfo('name');
    } elseif (isset($term)) {
      // Check if it's the WooCommerce shop page
      if (function_exists('is_shop') && is_shop()) {
        $shop = get_option('woocommerce_shop_page_id');
        $returnTitle = get_post_meta($shop, 'ds_seo_title', true) ?: $this->getDefaultTitle();
      }
      // If term is a taxonomy or archive
      elseif ($term instanceof WP_Term) {
        $returnTitle = get_term_meta($term->term_id, 'ds_seo_title', true) ?: $this->getDefaultTitle();
      }
      // If term is a post (e.g., custom post type)
      elseif ($term instanceof WP_Post) {
        $returnTitle = get_post_meta($term->ID, 'ds_seo_title', true) ?: $this->getDefaultTitle();
      }
    } elseif (is_archive()) {
      // Archive pages
      $returnTitle = $this->getDefaultTitle();
    } else {
      // Fallback for single posts, pages, or other post types
      $returnTitle = get_post_meta(get_the_ID(), 'ds_seo_title', true) ?: $this->getDefaultTitle();
    }

    return $returnTitle === '' ? $this->getDefaultTitle() : $returnTitle;
  }

  function getDefaultTitle()
  {
    $separator = (function_exists('ds_get_setting') ? ds_get_setting('ds_seo_separator', '-') : get_theme_mod('ds_seo_separator', '-'));
    $title = trim(wp_title('', false, 'right'));
    $site = get_bloginfo('name');
    return "{$title} {$separator} {$site}";
  }

  public function nagYoast()
  {
    $link = menu_page_url("dirigibleSEO", false);
    $warning = "<p>It looks like you have Yoast active. In order minimize duplicate <head> entries, Dirigible SEO will not print any meta data until Yoast has been deactivated. In the meantime, you can still set up your Dirigible SEO data.</p><p>If you already have Yoast metadata set up, you can <a href='" . esc_url($link) . "'>use our migration tool</a> to move your data from Yoast to Dirigible SEO.</p>";
    printf("<div class='notice notice-error is-dismissable'><h2>Dirigible SEO and Yoast are both active.</h2>{$warning}</div>");
  }

  public function nagACF()
  {
    $warning = "Dirigible SEO requires <a href='https://www.advancedcustomfields.com/'>Advanced Custom Fields</a> for SEO functionality. Please install ACF. (Let's be real, you should be using ACF anyway!)";
    printf("<div class='notice notice-error'><h2>SEO Warning</h2><p>{$warning}</p></div>");
  }

  public function registerStyle()
  {
    wp_register_style(
      'dirigible-seo',
      plugins_url('dirigible-seo/dist/ds-seo.css'),
      [],
      DS_SEO_VERSION,
      'all'
    );
    wp_enqueue_style('dirigible-seo');
  }

  public function registerScripts()
  {
    wp_register_script(
      'dirigible-seo-js',
      plugins_url('src/ds-seo.js', $this->path),
      [],
      DS_SEO_VERSION,
      true
    );

    $post_id = isset($_GET['post']) ? (int) $_GET['post'] : 0;
    $tag_id = isset($_GET['tag_ID']) ? (int) $_GET['tag_ID'] : 0;
    $taxonomy = isset($_GET['taxonomy']) ? sanitize_key($_GET['taxonomy']) : '';

    $page_title = '';
    $permalink = 'https://example.com/';
    $default_description = '';

    if ($tag_id > 0 && $taxonomy) {
      $term = get_term($tag_id, $taxonomy);
      if ($term && !is_wp_error($term)) {
        $page_title = $term->name;
        $link = get_term_link($term);
        $permalink = is_wp_error($link) ? '' : $link;
      }
    } elseif ($post_id > 0) {
      $page_title = get_the_title($post_id);
      $permalink = get_permalink($post_id) ?: 'https://example.com/';

      $excerpt = apply_filters(
        'the_excerpt',
        get_post_field('post_excerpt', $post_id, 'display')
      );
      if (empty($excerpt)) {
        $post = get_post($post_id);
        if ($post) {
          $excerpt = wp_html_excerpt($post->post_content, 320);
          if ($excerpt) {
            $excerpt .= '…';
          }
        }
      }
      $default_description = $excerpt ?: '';
    }

    $separator = (function_exists('ds_get_setting') ? ds_get_setting('ds_seo_separator', '-') : get_theme_mod('ds_seo_separator', '-'));
    if (empty($separator)) {
      $separator = '-';
    }

    wp_localize_script('dirigible-seo-js', 'ds_seo_ajax', [
      'nonce' => wp_create_nonce('ds_llms_txt_nonce'),
      'separator' => $separator,
      'site' => get_bloginfo('name'),
      'page_title' => $page_title,
      'permalink' => $permalink,
      'default_description' => $default_description,
    ]);
    wp_enqueue_script('dirigible-seo-js');
  }

  public function registerTermMetaFields()
  {
    // Get all public taxonomies
    $taxonomies = get_taxonomies(['public' => true], 'names');

    // Add a callback for each taxonomy's edit form
    foreach ($taxonomies as $taxonomy) {
      add_action("{$taxonomy}_edit_form_fields", [$this, 'addTermNoIndexField'], 10, 2);
      add_action("edited_{$taxonomy}", [$this, 'saveTermNoIndexField'], 10, 2);
    }
  }

  public function addTermNoIndexField($term, $taxonomy)
  {
    // Get the current value
    $ds_seo_no_index = get_term_meta($term->term_id, 'ds_seo_no_index', true);
  ?>
    <tr class="form-field">
      <th scope="row" valign="top"><label for="ds_seo_no_index">SEO Visibility</label></th>
      <td>
        <div id="ds-editor-seo-no-index" class="seo-field">
          <input type="checkbox" name="ds_seo_no_index" id="ds_seo_no_index" value="1" <?php checked($ds_seo_no_index, 1); ?> />
          <label for="ds_seo_no_index">Stop search engines from indexing this category?</label>
        </div>
        <p class="description">When checked, this adds a noindex meta tag to prevent search engines from indexing this category page.</p>
      </td>
    </tr>
    <tr class="form-field">
      <th scope="row" valign="top"><label for="ds_seo_canonical_url">Canonical URL</label></th>
      <td>
        <input type="url" name="ds_seo_canonical_url" id="ds_seo_canonical_url"
          class="regular-text"
          placeholder="https://example.com/original-page/"
          value="<?php echo esc_attr(get_term_meta($term->term_id, 'ds_seo_canonical_url', true)); ?>" />
        <p class="description">Leave empty to use this term's own URL as the canonical. Override only if this archive is a duplicate of another.</p>
      </td>
    </tr>
  <?php
  }

  public function saveTermNoIndexField($term_id, $tt_id)
  {
    if (isset($_POST['ds_seo_no_index'])) {
      update_term_meta($term_id, 'ds_seo_no_index', 1);
    } else {
      update_term_meta($term_id, 'ds_seo_no_index', 0);
    }

    if (!empty($_POST['ds_seo_canonical_url'])) {
      update_term_meta(
        $term_id,
        'ds_seo_canonical_url',
        esc_url_raw(sanitize_text_field($_POST['ds_seo_canonical_url']))
      );
    } else {
      delete_term_meta($term_id, 'ds_seo_canonical_url');
    }
  }

  public function addUserNoIndexField($user)
  {
    $ds_seo_no_index = get_user_meta($user->ID, 'ds_seo_no_index', true);
  ?>
    <h2>SEO Visibility</h2>
    <table class="form-table">
      <tr>
        <th scope="row">Author Archive No-Index</th>
        <td>
          <label for="ds_seo_no_index">
            <input type="checkbox" name="ds_seo_no_index" id="ds_seo_no_index" value="1" <?php checked($ds_seo_no_index, 1); ?> />
            Stop search engines from indexing this author's archive page?
          </label>
          <p class="description">When checked, a noindex meta tag will be added to this author's archive page, preventing search engines from indexing it.</p>
        </td>
      </tr>
    </table>
  <?php
  }

  public function saveUserNoIndexField($user_id)
  {
    if (!current_user_can('edit_user', $user_id)) {
      return;
    }

    if (isset($_POST['ds_seo_no_index'])) {
      update_user_meta($user_id, 'ds_seo_no_index', 1);
    } else {
      update_user_meta($user_id, 'ds_seo_no_index', 0);
    }
  }

  public function registerFields()
  {
    $separator = (function_exists('ds_get_setting') ? ds_get_setting('ds_seo_separator') : get_theme_mod('ds_seo_separator')) ?? '-';
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
        [
          'key' => 'field_630cf96e4afd5',
          'name' => 'ds_seo_no_index',
          'type' => 'true_false',
          'default' => 0,
          'message' => "Stop search engines from indexing this page?"
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
