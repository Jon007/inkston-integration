<?php
/* 
 * inkston custom featured posts 
 * Shortcode: [featured]
 */

/**
 * Get selection of featured, recent and sale products and posts
 * 
 * @return  array   array of post objects
 */
function get_featured_posts() {
    $final_posts = [];
    $locale = (function_exists( 'pll_current_language')) ? pll_current_language( 'locale') : get_locale();
    $tKey = 'inkfeat' . $locale;
    $final_posts = get_transient($tKey);
    if (!$final_posts) {
        //RECENT POSTS
        $query_args = array(
            'ignore_sticky_posts' => 0, //sticky posts automatically added by WP
            'post_type' => array( 'post'),
            'orderby' => 'modified',
            'posts_per_page' => 50,
            'showposts' => 50,
            'order' => 'DESC',
            'fields' => 'ids',
            );
        $recent_list = get_posts($query_args);

        //FEATURED PRODUCTS 
        if (class_exists( 'woocommerce' )) {
            $query_args = array(
                'posts_per_page' => 100,
                'showposts' => 100,
                'post_status' => 'publish',
                'post_type' => 'product',
                'post__in' => array_merge(array(0), wc_get_featured_product_ids(), wc_get_product_ids_on_sale()),
                'orderby' => 'modified',
                'order' => 'DESC',
                'fields' => 'ids',
            );
            $product_list = get_posts($query_args);
            //SALE PRODUCTS 
            /*
              $query_args = array(
              'posts_per_page' => 25,
              'showposts'   =>  25,
              'post_status' => 'publish',
              'post_type'   => 'product',
              'post__in'    => array_merge( array( 0 ), wc_get_product_ids_on_sale() ),
              'orderby'     =>  'modified',
              'order'       =>  'DESC',
              );
              $sale_list = new WP_Query( $query_args );
             */

            //RECENT NON-FEATURED PRODUCTS 
            $query_args = array(
                'post_type' => 'product',
                'posts_per_page' => 25,
                'showposts' => 25,
                'post_status' => 'publish',
                'post__not_in' => array_merge(array(0), wc_get_product_ids_on_sale(), wc_get_featured_product_ids()),
                'orderby' => 'modified',
                'order' => 'DESC',
                'fields' => 'ids',
            );
            $recentproduct_list = get_posts($query_args);

            //TOP POPULAR NON-FEATURED PRODUCTS 
            $query_args = array(
                'post_type' => 'product',
                'posts_per_page' => 25,
                'showposts' => 25,
                'post_status' => 'publish',
                'post__not_in' => array_merge(array(8476, 8486, 13865), wc_get_product_ids_on_sale(), wc_get_featured_product_ids()),
                'meta_key' => 'total_sales',
                'orderby' => 'meta_value_num',
                'order' => 'DESC',
                'fields' => 'ids',
            );
            $popularproduct_list = get_posts($query_args);
            $final_posts = array_merge($recent_list, $product_list, $recentproduct_list, $popularproduct_list);
            set_transient($tKey, $final_posts, 3600);
        } else {
            $final_posts = $recent_list;
        }
    }


    //$final_posts = array_unique ( $final_posts);
    //$final_posts = shuffle_assoc($final_posts);
    shuffle($final_posts);
    return $final_posts;
}

/*
 * output the featured posts as tiles
 */
function featured_post_tiles() {
	//echo('<div class="fixbox"');
	$final_posts = array();
	if ( function_exists( 'get_featured_posts' ) ) {
		$final_posts = get_featured_posts();
		foreach ( $final_posts as $post_id ) {
			global $post;
			$post = get_post( $post_id );
			setup_postdata( $post );
			tile_thumb();
		}
	}

	if ( function_exists( 'AjaxLoadMore' ) ) {
		AjaxLoadMore()->alm_shortcode(
		array(
			preloaded			 => "true",
			preloaded_amount	 => "96",
			posts_per_page		 => "96",
			max_pages			 => "99",
			post__not_in		 => "' . implode( ',', $final_posts ) . '",
			post_type			 => "product",
			orderby				 => "comment_count",
			progress_bar		 => "true",
			progress_bar_color	 => "39aa39",
			button_label		 => "More..",
			button_loading_label => "More .. .. ..",
			transition			 => "fade"
		)
		);
	}
	//REMAINING NON-FEATURED PRODUCTS as load more, with option to exclude papers

	$loadmore = do_shortcode( '[ajax_load_more preloaded="true" preloaded_amount="96" posts_per_page="96" max_pages="99" '
	. ' post__not_in="' . implode( ',', $final_posts ) . '"'
	. ' post_type="product" orderby="comment_count" progress_bar="true" progress_bar_color="39aa39" button_label="More.." button_loading_label="More .. .. .." transition="fade"]' );
	echo ($loadmore);
	wp_reset_postdata();


	echo('</div><div style="clear:both"></div>');
}

add_shortcode( 'featured', 'featured_post_tiles' );
/*
 * worker function for tiles
 */
function tile_thumb() {
	$thumbnail = wp_get_attachment_image_src( get_post_thumbnail_id(), 'thumbnail' );
	if ( $thumbnail ) {
		$thumbnail = $thumbnail[ 0 ];
	} else {
		$thumbnail = inkston_catch_image();
	}
	/* $extract = inkston_get_excerpt( 25 );  just plain text, use inkston_excerpt( 40 ); for html.. */
	$title			 = get_the_title();
	$class			 = "tile h-entry";
	$beforelink		 = '';
	$excerpt_length	 = 20;
	global $post;
	if ( $post->post_type == 'product' ) {
		$product	 = wc_get_product( $post );
		$beforelink	 .= wc_get_rating_html( $product->get_average_rating(), $product->get_rating_count() );
		if ( $product->is_on_sale() ) {
			$beforelink .= apply_filters( 'woocommerce_sale_flash', '<span class="onsale">' . esc_html__( 'Sale!', 'woocommerce' ) . '</span>', $post, $product );
		}
		$excerpt_length = 13; //shorter excerpt for products to allow space for price etc
	}

	if ( strrpos( $thumbnail, "no-image.png" ) !== false ) {
		$class .= ' nopic';
	}
	?><div class="<?php echo($class); ?>" id="post-<?php the_ID(); ?>" style="background-image:url( '<?php echo $thumbnail; ?>');"><?php echo($beforelink); ?><a href="<?php the_permalink(); ?>" rel="bookmark"><h3><?php echo($title); ?></h3><p class="p-summary"><?php echo(inkston_get_excerpt( $excerpt_length )); ?></p></a></div>
	<?php
}
