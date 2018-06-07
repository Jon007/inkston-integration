<?php
/*
 * add sharing links
 */

/*
 * Add sharing links for current page
 * - currently hooked on woocommerce sharing hook and theme before footer
 */
function ink_sharing() {
	//ignore on admin and feed pages
	if ( is_admin() || is_feed() ) {
		return;
	}
	//do not apply to user profile pages
	if ( function_exists( 'bbp_is_single_user' ) && bbp_is_single_user() ) {
		return;
	}
	//do not share woocommerce special pages
	if ( class_exists( 'woocommerce' ) ) {
		if ( is_cart() || is_checkout() || is_account_page() || is_ajax() ) {
			return;
		}
	}
	?><div class="entry-content saleflash ink-share-container"><?php
	_e( 'If you like this, please share: ', 'inkston-integration' );
	$current_url = "https://" . $_SERVER[ 'HTTP_HOST' ] . $_SERVER[ 'REQUEST_URI' ];
	$encoded_url = urlencode( $current_url );
	?> <ul id="menu-share" class="menu-social">
			<li><a target="_blank" href="https://www.facebook.com/sharer/sharer.php?u=<?php echo($encoded_url); ?>"></a></li>
			<li><a target="_blank" href="https://twitter.com/home?status=<?php echo($encoded_url); ?>"></a></li>
			<li><a target="_blank" href="https://plus.google.com/share?url=<?php echo($encoded_url); ?>"></a></li>
			<li><a target="_blank" href="https://www.linkedin.com/shareArticle?mini=true&url=<?php echo($encoded_url); //&title=great%20stuff%20from%20inkston&summary=Summary%20excerpt&source=inkston.com         ?>"></a></li>
			<li><a href="mailto:?&subject=<?php echo($encoded_url); ?>&body=<?php echo($encoded_url); ?>"></a></li>
		</ul>
		<?php
		//function now only exists when woocommerce is activated
		if ( function_exists( 'ink_output_please_leave_reviews' ) ) {
			ink_output_please_leave_reviews();
		}
		?></div><!-- .entry-content -->
	<?php
}

add_action( 'woocommerce_share', 'ink_sharing' );
add_action( 'storefront_before_footer', 'ink_sharing' );
add_action( 'twentysixteen_credits', 'ink_sharing' );
//can't share individual forum topic url easily
//add_action( 'bbp_template_after_single_topic', 'ink_sharing', 50);
//for social menu itself, twenty-sixteen has if ( has_nav_menu( 'social' ) ) in the footer
//storefront does not..
//add_action( 'storefront_footer', 'ink_footer_social' );


/*
 * enqueue css if this is enabled
 */
//font-awesome 5 breaks existing -o styles making it impossible to switch cleanly between themes
//.. and storefront only partially includes v5
wp_enqueue_style( 'font-awesome', 'https://stackpath.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css' );
//wp_enqueue_style( 'font-awesome', 'https://maxcdn.bootstrapcdn.com/font-awesome/latest/css/font-awesome.min.css' );
inkston_integration::get_instance()->ii_enqueue_script( 'ii-socialmenu' );
