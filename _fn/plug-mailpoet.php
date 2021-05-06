<?php

/*
 * customizations to support Mailpoet plugin
 */

/**
 * Filters the dashboard URL for a user.
 *
 * @return string magic url for mailpoet, like:
 * ?mailpoet_page=subscriptions&mailpoet_router&endpoint=subscription&action=manage
 * &data=eyJ0b2tlbiI6IjE3ZmI2MCIsImVtYWlsIjoiaW5nbGVub0BpY2xvdWQuY29tIn0
 */
use MailPoet\Subscription\SubscriptionUrlFactory;
use MailPoet\Models\Subscriber;

/*
 * helper function to get management link for current user
 */
function ink_get_newsletter_subscribe_url() {
	$managelink	 = '';
	$thisuser	 = wp_get_current_user();
	if ( $thisuser ) {
		global $mailpoet_plugin;
		if ( $mailpoet_plugin ) {
			$managelink = SubscriptionUrlFactory::getInstance()->getManageUrl( Subscriber::getCurrentWPUser() );
		}
	}
	return $managelink;
}

/*
 * this is a workaround for the fact that mailpoet only activates the mailpoet_manage_subscription
 * shortcode when the subscription management parameters are added (as per generated links in emails)
 * this extension allows a manage newsletter capability for logged on users who have not arrived
 * via an email link
 */
function ink_get_newsletter_subscribe_link() {
	if ( shortcode_exists( 'mailpoet_manage_subscription' ) ) {
		echo(do_shortcode( '[mailpoet_manage_subscription]' ));
	} else {
		if ( get_current_user_id() ) {
			$manageurl = ink_get_newsletter_subscribe_url();
			if ( $manageurl ) {
				echo( '<a href="' . $manageurl . '" class="manageurl">');
				_e( 'click here to manage your subscription', 'inkston-integration' );
				echo( '</a>');
			}
		}
	}
}

add_shortcode( 'ink_get_newsletter_subscribe_link', 'ink_get_newsletter_subscribe_link' );

/*
 * enqueue css if this is enabled
 */
inkston_integration::get_instance()->ii_enqueue_css( 'ii-mailpoet' );
