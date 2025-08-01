<?php


/* Get Preview Text Ajax
*******************************************************/
if (!function_exists('dsGetPreviewSEO')) {
  add_action('wp_ajax_dsGetPreviewSEO', 'dsGetPreviewSEO');
  function dsGetPreviewSEO()
  {

    $seo_title = sanitize_text_field($_REQUEST['seo_title']);
    $seo_description = sanitize_textarea_field($_REQUEST['seo_description']);



    if (isset($_REQUEST['page_id'])) {
      $page_id = (int) filter_var($_REQUEST['page_id'], FILTER_SANITIZE_NUMBER_INT);
    } else {
      $page_id = -1;
    }

    $editingURL = esc_url($_REQUEST['editing_URL']);
    $site = get_bloginfo('name');
    $separator = get_theme_mod('ds_seo_separator') ?? '-';

    $permalink = '';
    $title = 'Enter a Title';
    $titleSave = get_the_title($page_id) ?: 'Enter a Title';
    $description = '';

    // Permalink
    if ($page_id > 0) {
      $permalink = get_the_permalink($page_id);
    } else {
      $permalink = "https://example.com/";
    }

    // see if we are on a term ;
    $url_parts = parse_url($editingURL);
    if (isset($url_parts['query'])) {
      parse_str($url_parts['query'], $query);
      if (isset($query['tag_ID'])) {
        $term_id = (int)$query['tag_ID'];
        // $term_id = (int) filter_var($_REQUEST['page_id'],FILTER_SANITIZE_NUMBER_INT);
      } else {
        $term_id = -1;
      }
    } else {
      $term_id = -1;
    }


    if ($term_id > 0) {
      $tax = $query['taxonomy'];
      $term = get_term($term_id, $tax);
      $titleSave = $term->name;
      $permalink = get_term_link($term_id, $tax);
    }

    if (isset($seo_title) && $seo_title) {
      $title = $seo_title;
    } else {
      // Title
      if ($page_id > 0) {
        $title = get_the_title($page_id) . " {$separator} {$site}";
      } else {
        $title = "Enter a Title {$separator} {$site}";
      }
    }

    // SEO Description
    if ($seo_description) {
      $description = $seo_description;
    } else {
      $excerpt = "";
      $excerpt = apply_filters('the_excerpt', get_post_field('post_excerpt', $page_id, 'display'));
      if (empty($excerpt)) {
        $post = get_post($page_id);
        if ($post) {
          $excerpt = wp_html_excerpt($post->post_content, 320);
          if (empty($excerpt)) {
            $excerpt = "Enter a description";
          }
          $excerpt .= '…';
        } else {
          $excerpt = "Enter a description.";
        }
      }
      $description = $excerpt;
    }



    if (strpos($title, '{') !== false) {
      $title = str_replace(['{Title}', '{title}', '{page}', '{Page}'], $titleSave, $title);
      $title = str_replace(['{Site}', '{site}'], $site, $title);
      $title = str_replace(['{Sep}', '{sep}', '{separator}', '{Separator}', '{-}', '{|}'], $separator, $title);
    }
    if (strpos($description, '{') !== false) {
      $description = str_replace(['{Title}', '{title}', '{page}', '{Page}'], $titleSave, $description);
      $description = str_replace(['{Site}', '{site}'], $site, $description);
      $description = str_replace(['{Sep}', '{sep}', '{separator}', '{Separator}', '{-}', '{|}'], $separator, $description);
    }

    $values = [
      'permalink' => $permalink,
      'title' => $title,
      'excerpt' => $description
    ];
    echo json_encode($values);
    wp_die();
  }
}


if (!function_exists('ds_replace_yoast_string')) {
  function ds_replace_yoast_string($new_title, $id)
  {
    $new_title = str_replace('%%sep%%', ' - ', $new_title);
    $new_title = str_replace('%%sitename%%', '{site}', $new_title);
    $new_title = str_replace('%%title%%', '{title}', $new_title);
    $new_title = str_replace('%%page%%', "", $new_title);
    if (strpos($new_title, '%%primary_category%%') !== false) {
      $currentID = get_the_ID();
      $category = get_the_category();
      if ($category) {
        $category_display = '';
        $category_slug = '';
        if (class_exists('WPSEO_Primary_Term')) {
          $wpseo_primary_term = new WPSEO_Primary_Term('category', get_the_id());
          $wpseo_primary_term = $wpseo_primary_term->get_primary_term();
          $term = get_term($wpseo_primary_term);
          if (is_wp_error($term)) {
            $category_display = $category[0]->name;
            $category_slug = $category[0]->slug;
          } else {
            $category_id = $term->term_id;
            $category_term = get_category($category_id);
            $category_display = $term->name;
            $category_slug = $term->slug;
          }
        } else {
          $category_display = $category[0]->name;
          $category_slug = $category[0]->slug;
        }
        $new_title = str_replace('%%primary_category%%', $category_display, $new_title);
      } else {
        $new_title = str_replace('%%primary_category%%', '', $new_title);
      }
    }
    return $new_title;
  }
}


if (!function_exists('ds_replace_yoast_string_term')) {
  function ds_replace_yoast_string_term($new_title, $term_id)
  {
    $term_name = get_term($term_id)->name;
    $new_title = str_replace('%%sep%%', ' - ', $new_title);
    $new_title = str_replace('%%sitename%%', '{site}', $new_title);
    $new_title = str_replace('%%title%%', '{title}', $new_title);
    $new_title = str_replace('%%term_title%%', '{title}', $new_title);
    $new_title = str_replace('%%page%%', "", $new_title);
    return $new_title;
  }
}




/* Migrate Yoast Data
*******************************************************/
if (!function_exists('ds_migrate_yoast')) {
  add_action('wp_ajax_ds_migrate_yoast', 'ds_migrate_yoast');

  function get_post_primary_category($post_id, $term = 'category', $return_all_categories = false)
  {
    $return = array();
    if (class_exists('WPSEO_Primary_Term')) {
      $wpseo_primary_term = new WPSEO_Primary_Term($term, $post_id);
      $primary_term = get_term($wpseo_primary_term->get_primary_term());
      if (!is_wp_error($primary_term)) {
        $return['primary_category'] = $primary_term;
      }
    }
    if (empty($return['primary_category']) || $return_all_categories) {
      $categories_list = get_the_terms($post_id, $term);
      if (empty($return['primary_category']) && !empty($categories_list)) {
        $return['primary_category'] = $categories_list[0];  //get the first
      }
      if ($return_all_categories) {
        $return['all_categories'] = array();
        if (!empty($categories_list)) {
          foreach ($categories_list as &$category) {
            $return['all_categories'][] = $category->term_id;
          }
        }
      }
    }
    return $return;
  }

  function ds_migrate_yoast()
  {
    $return = [];
    if (is_plugin_active('wordpress-seo/wp-seo.php')) {
      $response = "";
      $totalCount = 0;
      $titleUpdates = 0;
      $totalUpdates = 0;
      $descUpdates = 0;
      $args = [
        'post_type' => 'any',
        'post_status' => ['publish', 'pending', 'draft', 'auto-draft', 'future', 'private', 'inherit', 'trash'],
        'posts_per_page' => -1,
      ];

      // Handle Posts and Pages
      $loop = new WP_Query($args);
      while ($loop->have_posts()) : $loop->the_post();
        $totalCount++;
        $id = get_the_id();

        // title
        $yoast_title = get_post_meta($id, '_yoast_wpseo_title', true);
        if ($yoast_title) {
          $new_title = ds_replace_yoast_string($yoast_title, $id);
          update_post_meta($id, 'ds_seo_title', $new_title); // Changed to update_post_meta
          $titleUpdates++;
        }

        // meta
        $yoast_meta = get_post_meta($id, '_yoast_wpseo_metadesc', true);
        if ($yoast_meta) {
          $new_meta = ds_replace_yoast_string($yoast_meta, $id);
          update_post_meta($id, 'ds_seo_description', $new_meta); // Changed to update_post_meta
          $descUpdates++;
        }

        if ($yoast_title || $yoast_meta) {
          $totalUpdates++;
        }
      endwhile;
      wp_reset_postdata();



      // Handle Terms
      $terms = get_option('wpseo_taxonomy_meta');
      $termsUpdated = 0;
      $termTitlesUpdates = 0;
      $termDescUpdates = 0;

      foreach ($terms as $key => $meta) {
        foreach ($meta as $tax_id => $single_cat) {
          $seoTitle = $single_cat['wpseo_title'];
          if ($seoTitle) {
            $new_title = ds_replace_yoast_string_term($seoTitle, $tax_id);
            update_term_meta($tax_id, 'ds_seo_title', $new_title); // Changed to update_term_meta
            $termTitlesUpdates++;
          }

          $seoDesc = $single_cat['wpseo_desc'];
          if ($seoDesc) {
            $new_meta = ds_replace_yoast_string_term($seoDesc, $tax_id);
            update_term_meta($tax_id, 'ds_seo_description', $new_meta); // Changed to update_term_meta
            $termDescUpdates++;
          }

          if ($seoTitle || $seoDesc) {
            $termsUpdated++;
          }
        }
      }


      $return[] = "<div class='notification'>
        <h3>We found <strong>{$totalCount}</strong> posts/pages on your site.</h3>
        <p><strong>{$totalUpdates}</strong> had Yoast data that was migrated.</p>
        <p>Updated {$titleUpdates} SEO titles.</p>
        <p>Updated {$descUpdates} SEO meta descriptions.</p>
      </div>";
      $return[] = "<div class='notification'>
        <h3>We found <strong>{$termsUpdated}</strong> categories/tags that had Yoast data.</h3>
        <p>Updated {$termTitlesUpdates} SEO titles.</p>
        <p>Updated {$termDescUpdates} SEO meta descriptions.</p>
      </div>";
    } else {
      $return[] = "<div class='notification error'>Uh oh! It looks like Yoast is not activated. Activate to continue.</div>";
    }

    echo json_encode($return);
    wp_die();
  }
}

/* Save LLMs.txt Content Ajax
*******************************************************/
if (!function_exists('ds_save_llms_txt')) {
  add_action('wp_ajax_ds_save_llms_txt', 'ds_save_llms_txt');

  function ds_save_llms_txt()
  {
    // Check user permissions
    if (!current_user_can('manage_options')) {
      wp_die('Unauthorized');
    }

    // Verify nonce
    if (!wp_verify_nonce($_POST['nonce'], 'ds_llms_txt_nonce')) {
      wp_die('Security check failed');
    }

    $content = stripslashes($_POST['content']);
    $file_path = ABSPATH . 'llms.txt';

    $success = file_put_contents($file_path, $content) !== false;

    if ($success) {
      echo json_encode(['success' => true, 'message' => 'llms.txt saved successfully!']);
    } else {
      echo json_encode(['success' => false, 'message' => 'Failed to save llms.txt. Check file permissions.']);
    }

    wp_die();
  }
}

/* Load LLMs.txt Content Ajax
*******************************************************/
if (!function_exists('ds_load_llms_txt')) {
  add_action('wp_ajax_ds_load_llms_txt', 'ds_load_llms_txt');

  function ds_load_llms_txt()
  {
    // Check user permissions
    if (!current_user_can('manage_options')) {
      wp_die('Unauthorized');
    }

    // Verify nonce
    if (!wp_verify_nonce($_POST['nonce'], 'ds_llms_txt_nonce')) {
      wp_die('Security check failed');
    }

    $file_path = ABSPATH . 'llms.txt';
    $content = '';

    if (file_exists($file_path)) {
      $content = file_get_contents($file_path);
    }

    echo json_encode(['success' => true, 'content' => $content]);
    wp_die();
  }
}

/* Delete LLMs.txt File Ajax
*******************************************************/
if (!function_exists('ds_delete_llms_txt')) {
  add_action('wp_ajax_ds_delete_llms_txt', 'ds_delete_llms_txt');

  function ds_delete_llms_txt()
  {
    // Check user permissions
    if (!current_user_can('manage_options')) {
      wp_die('Unauthorized');
    }

    // Verify nonce
    if (!wp_verify_nonce($_POST['nonce'], 'ds_llms_txt_nonce')) {
      wp_die('Security check failed');
    }

    $file_path = ABSPATH . 'llms.txt';

    if (file_exists($file_path)) {
      $success = unlink($file_path);
      if ($success) {
        echo json_encode(['success' => true, 'message' => 'llms.txt deleted successfully!']);
      } else {
        echo json_encode(['success' => false, 'message' => 'Failed to delete llms.txt. Check file permissions.']);
      }
    } else {
      echo json_encode(['success' => true, 'message' => 'llms.txt file does not exist.']);
    }

    wp_die();
  }
}
