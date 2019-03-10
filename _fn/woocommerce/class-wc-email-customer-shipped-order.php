<?php

/**
 * Class WC_Email_Customer_Shipped_Order file.
 *
 * @package WooCommerce\Emails
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'WC_Email_Customer_Shipped_Order', false ) ) :

	/**
	 * Customer Shipped Order Email.
	 *
	 * Order shipped emails are sent to the customer when the order is marked shipped and usual indicates that the order has been shipped.
	 *
	 * @class       WC_Email_Customer_Shipped_Order
	 * @version     2.0.0
	 * @package     WooCommerce/Classes/Emails
	 * @extends     WC_Email
	 */
	class WC_Email_Customer_Shipped_Order extends WC_Email {
		/**
		 * Constructor.
		 */
		public function __construct() {
			$this->id				 = 'customer_shipped_order';
			$this->customer_email	 = true;
			$this->title			 = __( 'Shipped order', 'inkston-integration' );
			$this->description		 = __( 'Order shipped emails are sent to customers when their orders are marked shipped and usually indicate that their orders have been shipped.', 'inkston-integration' );
			$this->template_html	 = 'emails/customer-shipped-order.php';
			$this->template_plain	 = 'emails/plain/customer-shipped-order.php';
			$this->placeholders		 = array(
				'{site_title}'	 => $this->get_blogname(),
				'{order_date}'	 => '',
				'{order_number}' => '',
			);

			// Triggers for this email.
			add_action( 'woocommerce_order_status_shipped_notification', array( $this, 'trigger' ), 10, 2 );

			add_action( 'woocommerce_email_customer_shipping_details', 'email_address_shipping', 10, 3 );
			add_action( 'woocommerce_email_order_packing_detail', 'order_packing_detail', 10, 4 );

			// Call parent constructor.
			parent::__construct();
		}

		/**
		 * Trigger the sending of this email.
		 *
		 * @param int            $order_id The order ID.
		 * @param WC_Order|false $order Order object.
		 */
		public function trigger( $order_id, $order = false ) {
			$this->setup_locale();

			if ( $order_id && ! is_a( $order, 'WC_Order' ) ) {
				$order = wc_get_order( $order_id );
			}

			if ( is_a( $order, 'WC_Order' ) ) {
				$this->object							 = $order;
				$this->recipient						 = $this->object->get_billing_email();
				$this->placeholders[ '{order_date}' ]	 = wc_format_datetime( $this->object->get_date_created() );
				$this->placeholders[ '{order_number}' ]	 = $this->object->get_order_number();
				/*
				  if ( function_exists( 'pll_get_post_language' ) ) {
				  $orderlang = pll_get_post_language( $order_id, '' );
				  switch_to_locale( $orderlang );
				  }
				 *
				 */
			}

			if ( $this->is_enabled() && $this->get_recipient() ) {
				$this->send( $this->get_recipient(), $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments() );
			}

			$this->restore_locale();
		}

		/**
		 * Set the locale to the store locale for customer emails to make sure emails are in the store language.
		 */
		public function setup_locale() {
			if ( $this->is_customer_email() && apply_filters( 'woocommerce_email_setup_locale', true ) ) {
				wc_switch_to_site_locale();
			}
		}

		/**
		 * Restore the locale to the default locale. Use after finished with setup_locale.
		 */
		public function restore_locale() {
			if ( $this->is_customer_email() && apply_filters( 'woocommerce_email_restore_locale', true ) ) {
				wc_restore_locale();
			}
		}

		/**
		 * Get email subject.
		 *
		 * @since  3.1.0
		 * @return string
		 */
		public function get_default_subject() {
			return __( 'Your {site_title} order {order_number} is Shipped', 'inkston-integration' );
		}

		/**
		 * Get email heading.
		 *
		 * @since  3.1.0
		 * @return string
		 */
		public function get_default_heading() {
			return __( 'Your {site_title} order {order_number} is Shipped', 'inkston-integration' );
		}

		/**
		 * Get email subject.
		 * cannot readily translate here as
		 * @return string
		  public function get_subject() {
		  $subject = $this->get_option( 'subject', $this->get_default_subject() );
		  if (function_exists( 'pll__' )) ? pll__( 'customer_shipped_order_subject' ) : $this->get_option( 'subject', $this->get_default_subject() );
		  return apply_filters( 'woocommerce_email_subject_' . $this->id, $this->format_string( $subject ), $this->object );
		  }
		 */
		/**
		 * Get email heading.
		 *
		 * @return string
		public function get_heading() {
			$heading = (function_exists( 'pll__' )) ? pll__( 'customer_shipped_order_subject' ) : $this->get_option( 'subject', $this->get_default_subject() );
			return apply_filters( 'woocommerce_email_heading_' . $this->id, $this->format_string( $heading ), $this->object );
		}
		 */
		/**
		 * Get content html.
		 *
		 * @return string
		 */
		public function get_content_html() {
			return wc_get_template_html(
			$this->template_html, array(
				'order'			 => $this->object,
				'email_heading'	 => $this->get_heading(),
				'sent_to_admin'	 => false,
				'plain_text'	 => false,
				'email'			 => $this,
			)
			);
		}

		/**
		 * Get content plain.
		 *
		 * @return string
		 */
		public function get_content_plain() {
			return wc_get_template_html(
			$this->template_plain, array(
				'order'			 => $this->object,
				'email_heading'	 => $this->get_heading(),
				'sent_to_admin'	 => false,
				'plain_text'	 => true,
				'email'			 => $this,
			)
			);
		}

		/**
		 * Initialise settings form fields.
		 */
		public function init_form_fields() {
			$this->form_fields = array(
				'enabled'	 => array(
					'title'		 => __( 'Enable/Disable', 'woocommerce' ),
					'type'		 => 'checkbox',
					'label'		 => __( 'Enable this email notification', 'woocommerce' ),
					'default'	 => 'yes',
				),
				'subject'	 => array(
					'title'			 => __( 'Subject', 'woocommerce' ),
					'type'			 => 'text',
					'desc_tip'		 => true,
					/* translators: %s: list of placeholders */
					'description'	 => sprintf( __( 'Available placeholders: %s', 'woocommerce' ), '<code>{site_title}, {order_date}, {order_number}</code>' ),
					'placeholder'	 => $this->get_default_subject(),
					'default'		 => '',
				),
				'heading'	 => array(
					'title'			 => __( 'Email heading', 'woocommerce' ),
					'type'			 => 'text',
					'desc_tip'		 => true,
					/* translators: %s: list of placeholders */
					'description'	 => sprintf( __( 'Available placeholders: %s', 'woocommerce' ), '<code>{site_title}, {order_date}, {order_number}</code>' ),
					'placeholder'	 => $this->get_default_heading(),
					'default'		 => '',
				),
				'email_type' => array(
					'title'			 => __( 'Email type', 'woocommerce' ),
					'type'			 => 'select',
					'description'	 => __( 'Choose which format of email to send.', 'woocommerce' ),
					'default'		 => 'html',
					'class'			 => 'email_type wc-enhanced-select',
					'options'		 => $this->get_email_type_options(),
					'desc_tip'		 => true,
				),
			);
		}

	}

	endif;

return new WC_Email_Customer_Shipped_Order();
