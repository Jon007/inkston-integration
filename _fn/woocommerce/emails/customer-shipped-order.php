<?php
/**
 * Customer shipped order email
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/emails/customer-shipped-order.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://docs.woocommerce.com/document/template-structure/
 * @package WooCommerce/Templates/Emails
 * @version 3.5.0
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/*
 * @hooked WC_Emails::email_header() Output the email header
 */
do_action( 'woocommerce_email_header', $email_heading, $email );
?>

<?php /* translators: %s: Customer first name */ ?>
<p><?php printf( esc_html__( 'Hi %s,', 'woocommerce' ), esc_html( $order->get_billing_first_name() ) ); ?></p>
<p><?php
	echo (esc_html__( 'Good day! Thank you for shopping with us! We would like to tell you that your order has been dispatched.', 'inkston-integration' ) );

	$hastracking = $order->get_meta( '_wc_shipment_tracking_items', true );
	if ( $hastracking ) {
		echo ('<br />');
		echo (esc_html__( 'Please see tracking codes below to check the progress of your shipment. (Note: for some shipping providers and destinations the shipment may not appear in the tracker until 1 or 2 days after shipment.)', 'inkston-integration' ) );
	}
	?></p><?php
/*
 * @hooked email_address_shipping instead of WC_Emails::email_address() Shows customer shipping detail only
 */
do_action( 'woocommerce_email_customer_shipping_details', $order, $sent_to_admin, $plain_text, $email );
//do_action( 'woocommerce_email_customer_details', $order, $sent_to_admin, $plain_text, $email );

/*
 * @hooked WC_Emails::order_details() Shows the order details table.
 * @hooked WC_Structured_Data::generate_order_data() Generates structured data.
 * @hooked WC_Structured_Data::output_structured_data() Outputs structured data.
 * @since 2.5.0
 */
//do_action( 'woocommerce_email_order_details', $order, $sent_to_admin, $plain_text, $email );
do_action( 'woocommerce_email_order_packing_detail', $order, $sent_to_admin, $plain_text, $email );

/*
 * @hooked WC_Emails::order_meta() Shows order meta data.
 */
//do_action( 'woocommerce_email_order_meta', $order, $sent_to_admin, $plain_text, $email );
?>
<p>
<?php esc_html_e( 'Thanks for shopping with us.', 'woocommerce' ); ?>
</p>
<?php
/*
 * @hooked WC_Emails::email_footer() Output the email footer
 */
do_action( 'woocommerce_email_footer', $email );
