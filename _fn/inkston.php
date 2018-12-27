<?php
/*
 * functions which apply ONLY to the inkston site
 * but should be preserved when switching themes rather than part of the theme
 */
function ink_author_link( $link, $author_id, $author_nicename ) {
    if ( is_inkston() && strpos( $link, 'community' ) == 0 ) {
      if ( $author_id ) {
        $link = network_site_url() . 'community/forums/users/' . $author_nicename . '/';
      } else {
        //if wp generated an author link but no author is available, suppress it
        if ( strpos( $link, 'author' ) ) {
          return '';
        }
      }
    }
    return $link;
}

//add with higher filter than polylang (20)
add_filter( 'author_link', 'ink_author_link', 30, 3 );
/*
 * manipulate title for inkston
 */
function inkston_title() {
    if ( ! is_inkston() ) {
	return;
    }

    static $title;
    if ( ! isset( $title ) ) {

	/* get default title, overridden by Yoast SEO as appropriate */
	$title = wp_title( '&raquo;', false, '' );
	if ( is_search() ) {
	    if ( get_search_query() == '' ) {
		$title = __( 'Search Inkston.', 'inkston-integration' );
		;
	    } else {
		global $wp_query;
		$title .= ' ( ' . $wp_query->found_posts . ' ' . __( 'results', 'inkston-integration' ) . ')';
	    }
	}
	/**
	 * Template WooCommerce
	 */
	if ( class_exists( 'woocommerce' ) ) {
	    if ( is_woocommerce() && ! is_product() ) {
		$title = woocommerce_page_title( false );
	    }
	}
    }
    /* remove trailing Inkston if added by Yoast SEO  */
    $title = str_replace( '- Inkston', '', $title );

    return $title;
}

/*
 * theme css of login screen, very specific to inkston design,
 * needs to be set for each theme
 */
function my_login_logo() {
    if ( ! is_inkston() ) {
	return;
    }
    ?>
    <style type="text/css">#login h1 a, .login h1 a {
    	background-image: url(<?php echo(get_logo()); ?>);
    	height:122px;
    	width:322px;
    	background-size: 322px 122px;
    	background-repeat: no-repeat;
        }
        #wp-submit{background-color:#39aa39;}
        #wp-submit:hover, #wp-submit:active{background-color:#4d914d;}
        @media (min-width: 450px){
    	#site-main {
    	    width: 400px;
    	    margin: 0 auto;
    	    background-color: white;
    	    box-shadow: 0 4px 8px 0 rgba(0, 0, 0, 0.2), 0 6px 20px 0 rgba(0, 0, 0, 0.19);
    	    vertical-align: middle;
    	    padding: 15px;
    	    margin-top: 100px;
    	}
        }
    </style>
    <?php
}

add_action( 'login_enqueue_scripts', 'my_login_logo' );
