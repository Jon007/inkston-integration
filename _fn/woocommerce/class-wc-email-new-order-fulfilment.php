<?php

/**
 * New Order Email.
 *
 * An email sent to the warehouse when a new order is received/paid for.
 *
 * @class       WC_Email_New_Order
 * @version     2.0.0
 * @package     WooCommerce/Classes/Emails
 * @extends     WC_Email
 */
class WC_Email_New_Order_Fulfilment extends WC_Email {
	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id				 = 'new_order_fulfilment';
		$this->title			 = __( 'New order fulfilment', 'inkston-integration' );
		$this->description		 = __( 'New order fulfilment emails are sent to chosen recipient(s) when a new order is received.', 'inkston-integration' );
		$this->template_html	 = 'emails/admin-new-order-fulfilment.php';
		$this->template_plain	 = 'emails/plain/admin-new-order-fulfilment.php';
		$this->placeholders		 = array(
			'{site_title}'	 => $this->get_blogname(),
			'{order_date}'	 => '',
			'{order_number}' => '',
		);

		// Triggers for this email.
		add_action( 'woocommerce_order_status_pending_to_processing_notification', array( $this, 'trigger' ), 10, 2 );
		add_action( 'woocommerce_order_status_pending_to_completed_notification', array( $this, 'trigger' ), 10, 2 );
		add_action( 'woocommerce_order_status_pending_to_on-hold_notification', array( $this, 'trigger' ), 10, 2 );
		add_action( 'woocommerce_order_status_failed_to_processing_notification', array( $this, 'trigger' ), 10, 2 );
		add_action( 'woocommerce_order_status_failed_to_completed_notification', array( $this, 'trigger' ), 10, 2 );
		add_action( 'woocommerce_order_status_failed_to_on-hold_notification', array( $this, 'trigger' ), 10, 2 );
		add_action( 'woocommerce_order_status_cancelled_to_processing_notification', array( $this, 'trigger' ), 10, 2 );
		add_action( 'woocommerce_order_status_cancelled_to_completed_notification', array( $this, 'trigger' ), 10, 2 );
		add_action( 'woocommerce_order_status_cancelled_to_on-hold_notification', array( $this, 'trigger' ), 10, 2 );

		add_action( 'woocommerce_email_customer_shipping_details', 'email_address_shipping', 10, 3 );
		add_action( 'woocommerce_email_order_packing_detail', 'order_packing_detail', 10, 4 );

		// Call parent constructor.
		parent::__construct();

		// Other settings.
		$this->recipient = $this->get_option( 'recipient', get_option( 'admin_email' ) );
	}

	/**
	 * Get email subject.
	 *
	 * @since  3.1.0
	 * @return string
	 */
	public function get_default_subject() {
		return __( '[{site_title}]: New order #{order_number} for {dest}', 'woocommerce' );
	}

	/**
	 * Get email heading.
	 *
	 * @since  3.1.0
	 * @return string
	 */
	public function get_default_heading() {
		return __( 'New Order: #{order_number} for {dest}', 'woocommerce' );
	}

	/**
	 * Trigger the sending of this email.
	 *
	 * @param int            $order_id The order ID.
	 * @param WC_Order|false $order Order object.
	 */
	public function trigger( $order_id, $order = false ) {
		//$this->setup_locale();
		//switchLanguage( 'zh' );
		switch_to_zh();

		if ( $order_id && ! is_a( $order, 'WC_Order' ) ) {
			$order = wc_get_order( $order_id );
		}

		if ( is_a( $order, 'WC_Order' ) ) {
			$this->object							 = $order;
			$this->placeholders[ '{order_date}' ]	 = wc_format_datetime( $this->object->get_date_created() );
			$this->placeholders[ '{order_number}' ]	 = $this->object->get_order_number();
			$this->placeholders[ '{dest}' ]			 = ii_order_destination( $order );
		}

		if ( $this->is_enabled() && $this->get_recipient() ) {
			$this->send( $this->get_recipient(), $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments() );
		}

		$this->restore_locale();
	}

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
			'sent_to_admin'	 => true,
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
			'sent_to_admin'	 => true,
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
			'recipient'	 => array(
				'title'			 => __( 'Recipient(s)', 'woocommerce' ),
				'type'			 => 'text',
				/* translators: %s: WP admin email */
				'description'	 => sprintf( __( 'Enter recipients (comma separated) for this email. Defaults to %s.', 'woocommerce' ), '<code>' . esc_attr( get_option( 'admin_email' ) ) . '</code>' ),
				'placeholder'	 => '',
				'default'		 => '',
				'desc_tip'		 => true,
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

return new WC_Email_New_Order_Fulfilment();