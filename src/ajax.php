<?php


/* Get Preview Text Ajax
*******************************************************/
if(!function_exists('dsGetPreviewSEO')) {
  add_action( 'wp_ajax_dsGetPreviewSEO', 'dsGetPreviewSEO' );
  function dsGetPreviewSEO() {
  	$seo_title = $_REQUEST['seo_title'];
  	$seo_description = $_REQUEST['seo_description'];
  	$page_id = $_REQUEST['page_id'];
    $editingURL = $_REQUEST['editing_URL'];
    $site = get_bloginfo( 'name' );

  	$permalink = '';
  	$title = 'Enter a Title';
    $titleSave = get_the_title($page_id) ?: 'Enter a Title';
    $description = '';

    // Permalink
  	if($page_id > 0) { 	$permalink = get_the_permalink($page_id); }
  	else { $permalink = "https://example.com/"; }

    // see if we are on a term ;
    $url_parts = parse_url($editingURL);
    parse_str($url_parts['query'], $query);
    $term_id = (int)$query['tag_ID'];
    if($term_id > 0) {
      $tax = $query['taxonomy'];
      $term = get_term($term_id, $tax);
      $titleSave = $term->name;
      $permalink = get_term_link($term_id, $tax);
    }




  	// Title
  	if(isset($seo_title) && $seo_title) {
  		$title = $seo_title;
  	}
  	else {
  		if($page_id > 0) {
  			$title = get_the_title($page_id) . ' - ' . get_bloginfo( 'name' );
  		}
  		else {
  			$title = 'Enter a Title - ' . $site;

  		}
  	}

  	// SEO Description
  	if($seo_description) {
  		$description = $seo_description;
  	}
  	else {
  		$excerpt = "";
  		$excerpt = apply_filters( 'the_excerpt', get_post_field( 'post_excerpt', $page_id, 'display' ) );
  		if( empty($excerpt) ) {
  			$post = get_post($page_id);
  			$excerpt = wp_html_excerpt( $post->post_content, 320 );
        if( empty($excerpt)) {
          $excerpt = "Enter a description";
        }
  			$excerpt .= '…';
  		}
  		$description = $excerpt;
  	}

    if (strpos($title, '{') !== false) {
      $title = str_replace(['{Title}', '{title}'], $titleSave, $title);
      $title = str_replace(['{Site}', '{site}'], $site, $title);
    }
    if (strpos($description, '{') !== false) {
      $description = str_replace(['{Title}', '{title}'], $titleSave, $description);
      $description = str_replace(['{Site}', '{site}'], $site, $description);
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


if(!function_exists('ds_replace_yoast_string')) {
  function ds_replace_yoast_string($new_title, $id){
    $new_title = str_replace('%%sep%%', ' - ', $new_title);
    $new_title = str_replace('%%sitename%%', '{site}', $new_title);
    $new_title = str_replace('%%title%%', '{title}', $new_title);
    $new_title = str_replace('%%page%%', "", $new_title);
    if (strpos($new_title, '%%primary_category%%') !== false) {
      $currentID = get_the_ID();
      $category = get_the_category();
      if($category) {
        $category_display = '';
        $category_slug = '';
        if ( class_exists('WPSEO_Primary_Term') ) {
          $wpseo_primary_term = new WPSEO_Primary_Term( 'category', get_the_id() );
          $wpseo_primary_term = $wpseo_primary_term->get_primary_term();
          $term = get_term( $wpseo_primary_term );
             if ( is_wp_error( $term ) ) {
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
        $new_title = str_replace('%%primary_category%%',$category_display, $new_title);
      }
      else {
        $new_title = str_replace('%%primary_category%%','', $new_title);
      }
    }
    return $new_title;
  }
}


if(!function_exists('ds_replace_yoast_string_term')) {
  function ds_replace_yoast_string_term($new_title, $term_id){
    $term_name = get_term( $term_id )->name;
    $new_title = str_replace('%%sep%%', ' - ', $new_title);
    $new_title = str_replace('%%sitename%%', '{site}', $new_title);
    $new_title = str_replace('%%title%%', '{title}', $new_title);
    $new_title = str_replace('%%term_title%%', '{title}', $new_title);
    $new_title = str_replace('%%page%%', "", $new_title);
    return $new_title;
  }
}




/* Get Preview Text Ajax
*******************************************************/
if(!function_exists('ds_migrate_yoast')) {
  add_action( 'wp_ajax_ds_migrate_yoast', 'ds_migrate_yoast' );
  function get_post_primary_category($post_id, $term='category', $return_all_categories=false){
    $return = array();
    if (class_exists('WPSEO_Primary_Term')){
        $wpseo_primary_term = new WPSEO_Primary_Term( $term, $post_id );
        $primary_term = get_term($wpseo_primary_term->get_primary_term());
        if (!is_wp_error($primary_term)){
            $return['primary_category'] = $primary_term;
        }
    }
    if (empty($return['primary_category']) || $return_all_categories){
        $categories_list = get_the_terms($post_id, $term);
        if (empty($return['primary_category']) && !empty($categories_list)){
            $return['primary_category'] = $categories_list[0];  //get the first
        }
        if ($return_all_categories){
            $return['all_categories'] = array();
            if (!empty($categories_list)){
                foreach($categories_list as &$category){
                    $return['all_categories'][] = $category->term_id;
                }
            }
        }
    }
    return $return;
  }
  function ds_migrate_yoast() {
    $return = [];
    if ( is_plugin_active('wordpress-seo/wp-seo.php') ) {
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
      $loop = new WP_Query( $args );
      while ( $loop->have_posts() ) : $loop->the_post();
        $totalCount++;
        $id = get_the_id();
        // title
        $yoast_title = get_post_meta($id, '_yoast_wpseo_title', true);
        if($yoast_title) {
          $new_title = ds_replace_yoast_string($yoast_title, $id);
          update_field('ds_seo_title', $new_title, $id);
          $titleUpdates++;
        }
        // meta
        $yoast_meta = get_post_meta($id, '_yoast_wpseo_metadesc', true);
        if($yoast_meta) {
          $new_meta = ds_replace_yoast_string($yoast_meta, $id);
          update_field('ds_seo_description', $new_meta, $id);
          $descUpdates++;
        }
        if($yoast_title || $yoast_meta) { $totalUpdates++; }
      endwhile;
      wp_reset_postdata();


      // Handle Terms
      $terms = get_option( 'wpseo_taxonomy_meta' );
      $termsUpdated = 0;
      $termTitlesUpdates = 0;
      $termDescUpdates = 0;
      foreach ($terms as $key => $meta) {
        foreach ($meta as $tax_id => $single_cat) {
          //    $return[] = "<div class='notification'>$tax_id</div>";
        //  $return[] = "<pre>".print_r($single_cat, true)."</pre>";
          $seoTitle = $single_cat['wpseo_title'];
          if($seoTitle) {
            $new_title = ds_replace_yoast_string_term($seoTitle, $tax_id);
            update_field('ds_seo_title', $new_title, get_term( $tax_id ));
            $termTitlesUpdates++;
          }
          $seoDesc = $single_cat['wpseo_desc'];
          if($seoDesc) {
            $new_meta = ds_replace_yoast_string_term($seoDesc, $tax_id);
            update_field('ds_seo_description', $new_meta, get_term( $tax_id ));
            $termDescUpdates++;
          }
          if($seoTitle || $seoDesc) {
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
    }
    else {
      $return[] = "<div class='notification error'>Uh oh! It looks like Yoast is not activated. Activate to continue.</div>";
    }

    echo json_encode($return);
    wp_die();
  }
}
