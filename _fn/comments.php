<?php

/*
 * comment form customizations
 */
/*
 * adjusts comment form login links to improve behaviour on return to page
 * adding #commment to return link to return to and auto-expand comment form
 */
function ink_comment_form_defaults( $defaults ) {
	$must_login = '<p class="must-log-in">' . sprintf(
	__( 'You must be <a href="%s">logged in</a> to post a comment.' ), wp_login_url( apply_filters( 'the_permalink', get_permalink() . '#comment' ) )
	) . ' ' .
//login is now simplified so no need for this complex process...
//        sprintf(__( 'If you do not have an account <a target="_blank" href="%s">please register</a> (will open in new window)', 'inkston-integration'), wp_registration_url()) .
//        sprintf(__( ' and then <a href="#comment" onclick="%s" ">click here</a>.', 'inkston-integration'), "javascript:window.location.hash = '#comment';window.location.reload(true);") .
	'</p>';

	$defaults[ 'must_log_in' ] = $must_login;
	return $defaults;
}

add_filter( 'comment_form_defaults', 'ink_comment_form_defaults' );



if ( class_exists( 'Polylang' ) ) {

	/*
	 * stop Polylang filtering comments
	 */
	function polylang_remove_comments_filter() {
		global $polylang;
		if ( $polylang ) {
			remove_filter( 'comments_clauses', array( &$polylang->filters, 'comments_clauses' ) );
		} else {
			ink_debug( 'ERROR: POLYLANG NOT DETECTED AND FILTER NOT REMOVED' );
		}
	}

	add_action( 'wp', 'polylang_remove_comments_filter' );

	/*
	 * if polylang is enabled, merge comments across languages
	 */
	function merge_comments( $comments, $post_ID ) {
		$merge_commments = true;

		/* temporarily remove comment merging on wooCommerce product reviews, since this isn't compatible with
		 * how wooCommerce ratings and totals are calculated as yet */
		if ( class_exists( 'woocommerce' ) ) {
			if ( is_woocommerce() && is_product() ) {
				$merge_commments = false;
			}
		}
		if ( $merge_commments ) {


			$post	 = get_post( $post_ID );
			$type	 = $post->post_type;
			/* TODO: use Polylang API directly rather than WPML compatibility layer */
			if ( function_exists( 'icl_get_languages' ) ) {
				// get all the languages for which this post exists
				$languages = icl_get_languages( 'skip_missing=1' );
				foreach ( $languages as $code => $l ) {
					// in $comments are already the comments from the current language
					if ( ! $l[ 'active' ] ) {
						$otherID		 = icl_object_id( $post_ID, $type, false, $l[ 'language_code' ] );
						$othercomments	 = get_comments( array( 'post_id' => $otherID, 'status' => 'approve', 'type' => 'comment', 'order' => 'ASC' ) );
						$comments		 = array_merge( $comments, $othercomments );
					}
				}
				if ( $languages ) {
					// if we merged some comments in we need to reestablish an order
					usort( $comments, 'sort_merged_comments' );
				}
			}
		}
		return $comments;
	}

	/*
	 * helper function for sort-merging two arrays of comments
	 */
	function sort_merged_comments( $a, $b ) {
		return $a->comment_ID - $b->comment_ID;
	}

	//note: this isn't called at all for wooCommerce products..
	function merge_comment_count( $count, $post_ID ) {
		//ignore fake page for Subscribe to Comments Reloaded
		if ( 9999999 == $post_ID ) {
			return $count;
		}
		/* temporarily remove comment merging on wooCommerce product reviews, since this isn't compatible with
		 * how wooCommerce ratings and totals are calculated as yet */
		if ( class_exists( 'woocommerce' ) ) {
			if ( is_woocommerce() && is_product() ) {
				return $count;
			}
		}

		$post	 = get_post( $post_ID );
		$type	 = $post->post_type;

		if ( function_exists( 'icl_get_languages' ) ) {
			// get all the languages for which this post exists
			$languages = icl_get_languages( 'skip_missing=1' );
			foreach ( $languages as $l ) {
				// in $count is already the count from the current language
				if ( ! $l[ 'active' ] ) {

					$otherID = icl_object_id( $post_ID, $type, false, $l[ 'language_code' ] );

					$othercomments	 = get_comments( array( 'post_id' => $otherID, 'status' => 'approve', 'type' => 'comment' ) );
					$count			 = $count + count( $othercomments );
				}
			}
		}
		return $count;
	}

	add_filter( 'comments_array', 'merge_comments', 100, 2 );
	add_filter( 'get_comments_number', 'merge_comment_count', 100, 2 );
}