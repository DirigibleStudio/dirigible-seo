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
