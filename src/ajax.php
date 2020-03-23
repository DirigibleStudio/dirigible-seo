<?php


/* Get Preview Text Ajax
*******************************************************/
if(!function_exists('dsGetPreviewSEO')) {
  add_action( 'wp_ajax_dsGetPreviewSEO', 'dsGetPreviewSEO' );
  function dsGetPreviewSEO() {
  	$seo_title = $_REQUEST['seo_title'];
  	$seo_description = $_REQUEST['seo_description'];
  	$page_id = $_REQUEST['page_id'];

  	$permalink = '';
  	$title = 'test';
  	$description = '';

  	// Permalink
  	if($page_id > 0) {
  		$permalink = get_the_permalink($page_id);
  	}
  	else {
  		$permalink = "https://example.com/";
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
  			$title = 'No Title - ' + get_bloginfo( 'name' );
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
  			$excerpt .= '…';
  		}
  		$description = $excerpt;
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
    $new_title = str_replace('%%sitename%%', get_bloginfo('name'), $new_title);
    $new_title = str_replace('%%title%%', get_the_title($id), $new_title);
    $new_title = str_replace('%%page%%', "", $new_title);
    if (strpos($new_title, '%%primary_category%%') !== false) {
      $currentID = get_the_ID();
      $category = get_the_category();
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
      $$descUpdates = 0;

      $args = [
        'post_type' => 'any',
        'post_status' => ['publish', 'pending', 'draft', 'auto-draft', 'future', 'private', 'inherit', 'trash'],
        'posts_per_page' => -1,
      ];

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
      $return[] = "<div class='notification'><p>We found <strong>{$totalCount}</strong> total posts (and pages) on this site.</p><p><strong>{$totalUpdates}</strong> had Yoast data that was migrated.</p><p>Updated {$titleUpdates} SEO titles.</p><p>Updated {$descUpdates} SEO meta descriptions.</p></div>";
    }
    else {
      $return[] = "<div class='notification error'>Uh oh! It looks like Yoast is not activated. Activate to continue.</div>";
    }

    echo json_encode($return);
    wp_die();
  }
}
