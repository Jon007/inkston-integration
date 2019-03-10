<?php

/*
 * register additional emails used by inkston
 */

/*
 * add the shipped email class to the list of email classes that WooCommerce loads
 */
function add_ii_woocommerce_emails( $email_classes ) {
	$email_classes[ 'WC_Email_Customer_Shipped_Order' ]	 = include 'woocommerce/class-wc-email-customer-shipped-order.php';
	$email_classes[ 'WC_Email_New_Order_Fulfilment' ]	 = include 'woocommerce/class-wc-email-new-order-fulfilment.php';
	return $email_classes;
}

add_filter( 'woocommerce_email_classes', 'add_ii_woocommerce_emails' );

/*
 * hook the shipped email whenever status is changed to shipped
 */
function ii_filter_woocommerce_email_actions( $actions ) {
	$actions[] = 'woocommerce_order_status_shipped';
	return $actions;
}

add_filter( 'woocommerce_email_actions', 'ii_filter_woocommerce_email_actions' );

/*
 * add shipped email to the meta box provided by pdf invoice plugin for resending emails
 */
function ii_woocommerce_resend_order_emails_available( $emails ) {
	$emails[]	 = 'customer_shipped_order';
	$emails[]	 = 'new_order_fulfilment';
	$emails[]	 = 'customer_on_hold_order';
	$emails[]	 = 'customer_refunded_order';
	return $emails;
}

add_filter( 'woocommerce_resend_order_emails_available', 'ii_woocommerce_resend_order_emails_available' );
/**
 * Get the email addresses.
 *
 * @param WC_Order $order         Order instance.
 * @param bool     $sent_to_admin If should sent to admin.
 * @param bool     $plain_text    If is plain text email.
 */
function email_address_shipping( $order, $sent_to_admin = false, $plain_text = false ) {
	if ( ! is_a( $order, 'WC_Order' ) ) {
		return;
	}
	if ( $plain_text ) {
		wc_get_template(
		'emails/plain/email-address-shipping.php', array(
			'order'			 => $order,
			'sent_to_admin'	 => $sent_to_admin,
		)
		);
	} else {
		wc_get_template(
		'emails/email-address-shipping.php', array(
			'order'			 => $order,
			'sent_to_admin'	 => $sent_to_admin,
		)
		);
	}
}

/**
 * Show the order packing details table
 *
 * @param WC_Order $order         Order instance.
 * @param bool     $sent_to_admin If should sent to admin.
 * @param bool     $plain_text    If is plain text email.
 * @param string   $email         Email address.
 */
function order_packing_detail( $order, $sent_to_admin = false, $plain_text = false, $email = '' ) {
	if ( $plain_text ) {
		wc_get_template(
		'emails/plain/email-order-packing-details.php', array(
			'order'			 => $order,
			'sent_to_admin'	 => $sent_to_admin,
			'plain_text'	 => $plain_text,
			'email'			 => $email,
		)
		);
	} else {
		wc_get_template(
		'emails/email-order-packing-details.php', array(
			'order'			 => $order,
			'sent_to_admin'	 => $sent_to_admin,
			'plain_text'	 => $plain_text,
			'email'			 => $email,
		)
		);
	}
}

function ii_get_email_order_items_packing( $order, $args = array() ) {
	ob_start();

	$defaults = array(
		'show_sku'		 => false,
		'show_image'	 => false,
		'image_size'	 => array( 32, 32 ),
		'plain_text'	 => false,
		'sent_to_admin'	 => false,
	);

	$args		 = wp_parse_args( $args, $defaults );
	$template	 = $args[ 'plain_text' ] ? 'emails/plain/email-order-items-packing.php' : 'emails/email-order-items-packing.php';

	wc_get_template( $template, apply_filters( 'woocommerce_email_order_items_args', array(
		'order'					 => $order,
		'items'					 => $order->get_items(),
		'show_download_links'	 => $order->is_download_permitted() && ! $args[ 'sent_to_admin' ],
		'show_sku'				 => $args[ 'show_sku' ],
		'show_purchase_note'	 => $order->is_paid() && ! $args[ 'sent_to_admin' ],
		'show_image'			 => $args[ 'show_image' ],
		'image_size'			 => $args[ 'image_size' ],
		'plain_text'			 => $args[ 'plain_text' ],
		'sent_to_admin'			 => $args[ 'sent_to_admin' ],
	) ) );

	return apply_filters( 'woocommerce_email_order_items_table', ob_get_clean(), $order );
}
