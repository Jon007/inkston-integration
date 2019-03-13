<?php

/*
 * when switching language for order email, unload these additional text domains
 */
function ii_unload_email_textdomains() {
	unload_textdomain( 'inkston-integration' );
	unload_textdomain( 'woo-advanced-shipment-tracking' );
}

add_action( 'woo-poly.Emails.switchLanguage', 'ii_unload_email_textdomains' );
/*
 * when switching language for order email, reload these additional text domains
 */
function ii_load_email_textdomains() {
	inkston_integration::load_textdomain();
	global $WC_advanced_Shipment_Tracking;
	if ( $WC_advanced_Shipment_Tracking ) {
		$WC_advanced_Shipment_Tracking->wst_load_textdomain();
	}
}

add_action( 'woo-poly.Emails.afterSwitchLanguage', 'ii_load_email_textdomains' );
/*
 * add shipped mail to translatable emails
 */
function ii_translatable_emails_available( $emails ) {
	$emails[]	 = 'customer_shipped_order';
	return $emails;
}

add_filter( 'woo-poly.Emails.translatableEmails', 'ii_translatable_emails_available' );

/*
 * add default strings
 */
function ii_translatable_emails_default_settings( $translatableStrings ) {
	$translatableStrings[ 'customer_shipped_order_subject' ] = __( 'Your {site_title} order {order_number} is Shipped', 'inkston-integration' );
	$translatableStrings[ 'customer_shipped_order_heading' ] = __( 'Your {site_title} order {order_number} is Shipped', 'inkston-integration' );
	return $translatableStrings;
}

add_filter( 'woo-poly.Emails.defaultSettings', 'ii_translatable_emails_default_settings' );

// shipped order
add_filter( 'woocommerce_email_subject_customer_shipped_order', 'translateEmailSubjectCustomerShippedOrder', 10, 2 );
add_filter( 'woocommerce_email_heading_customer_shipped_order', 'translateEmailHeadingCustomerShippedOrder', 10, 2 );
/**
 * Translate to the order language, the email subject of shipped order email notifications to the customer.
 *
 * @param string   $subject Email subject in default language
 * @param WC_Order $order   Order object
 *
 * @return string Translated subject
 */
function translateEmailSubjectCustomerShippedOrder( $subject, $order ) {
	return translateEmailStringToOrderLanguage( $subject, $order, 'subject', 'customer_shipped_order' );
}

/**
 * Translate to the order language, the email heading of shipped order email notifications to the customer.
 *
 * @param string   $heading Email heading in default language
 * @param WC_Order $order   Order object
 *
 * @return string Translated heading
 */
function translateEmailHeadingCustomerShippedOrder( $heading, $order ) {
	return translateEmailStringToOrderLanguage( $heading, $order, 'heading', 'customer_shipped_order' );
}

/**
 * Translates Woocommerce email subjects and headings content to the order language.
 *
 * @param string   $string      Subject or heading not translated
 * @param WC_Order $order       Order object
 * @param string   $string_type Type of string to translate <subject | heading>
 * @param string   $email_type  Email template
 *
 * @return string Translated string, returns the original $string on error
 */
function translateEmailStringToOrderLanguage( $string, $order, $string_type, $email_type ) {
	//allow function to be called with no order to try to pick up pll locale for footer, from address and name
	$order_language = ($order) ? pll_get_post_language( $order->get_id(), 'locale' ) : '';
	if ( $order_language == '' ) {
		$order_language = pll_current_language( 'locale' );
	}
	$locale		 = get_locale();
	$baseLocale	 = get_option( 'WPLANG' );

	// Get setting used to register string in the Polylang strings translation table
	$_string = $string; // Store original string to return in case of error
	$test	 = getEmailSetting( $string_type, $email_type );
	if ( $test ) {
		$string = $test;
	}

	// Switch language - this should already be done by previous calls...
	if ( $order_language != $locale ) {
		switchLanguage( $order_language );
	}

	// Retrieve translation from Polylang Strings Translations table
	if ( $order_language )
		$test = pll_translate_string( $string, $order_language );
	if ( $test != $string ) {
		$string = $test;
	} elseif ( $order_language != $baseLocale ) {
		// If no user translation found in Polylang Strings Translations table, use default translation
		switch ( $email_type ) {
			case 'customer_shipped_order':
				$string = __( 'Your {site_title} order {order_number} is Shipped', 'inkston-integration' );
		}
	}

	if ( $order ) {
		$find	 = array();
		$replace = array();

		$find[ 'order-date' ]	 = '{order_date}';
		$find[ 'order-number' ]	 = '{order_number}';
		$find[ 'site_title' ]	 = '{site_title}';

		$replace[ 'order-date' ]	 = date_i18n( wc_date_format(), strtotime( $order->get_date_created() ) );
		$replace[ 'order-number' ]	 = $order->get_order_number();
		$replace[ 'site_title' ]	 = get_bloginfo( 'name' );

		$string = str_replace( apply_filters( 'woo-poly.Emails.orderFindReplaceFind', $find, $order ), apply_filters( 'woo-poly.Emails.orderFindReplaceReplace', $replace, $order ), $string );
	}
	return $string;
}

/**
 * Get setting used to register string in the Polylang strings translation table.
 *
 * @param string $string_type <subject | heading> of $email_type, e.g. subject, subject_paid
 * @param string $email_type  Email type, e.g. new_order, customer_invoice
 *
 * return $string|boolean Email setting from database if one is found, false otherwise
 */
function getEmailSetting( $string_type, $email_type ) {
	$settings = get_option( 'woocommerce_' . $email_type . '_settings' );

	if ( $settings && isset( $settings[ $string_type ] ) ) {
		return $settings[ $string_type ];
	} else {
		return false; // Setting not registered for translation (admin have changed woocommerce default)
	}
}

function ii_suppress_woo_cust_email_shop_base_lang() {
	return false;
}

add_filter( 'woocommerce_email_setup_locale', function() {
	return false;
} );
add_filter( 'woocommerce_email_restore_locale', function() {
	return false;
} );
/**
 * Reload text domains with order locale.
 *
 * @param string $language Language slug (e.g. en, de )
 */
function switchLanguage( $language ) {
	if ( class_exists( 'Polylang' ) ) {
		global $locale, $polylang, $woocommerce;
		static $cache; // Polylang string translations cache object to avoid loading the same translations object several times
		// Cache object not found. Create one...
		if ( empty( $cache ) ) {
			$cache = new \PLL_Cache();
		}

		//$current_language = pll_current_language( 'locale' );
		// unload plugin's textdomains
		unload_textdomain( 'default' );
		unload_textdomain( 'woocommerce' ); #

		do_action( 'woo-poly.Emails.switchLanguage' );

		// set locale to order locale
		$locale						 = apply_filters( 'locale', $language );
		$polylang->curlang->locale	 = $language;

		// Cache miss
		if ( false === $mo = $cache->get( $language ) ) {
			$mo									 = new \PLL_MO();
			$mo->import_from_db( $GLOBALS[ 'polylang' ]->model->get_language( $language ) );
			$GLOBALS[ 'l10n' ][ 'pll_string' ]	 = &$mo;

			// Add to cache
			$cache->set( $language, $mo );
		}

		// (re-)load plugin's textdomain with order locale
		load_default_textdomain( $language );

		$woocommerce->load_plugin_textdomain();
		do_action( 'woo-poly.Emails.afterSwitchLanguage' );

		$wp_locale = new \WP_Locale();
	}
}

function switch_to_zh() {
	unload_textdomain( 'default' );
	unload_textdomain( 'woocommerce' ); #

	do_action( 'woo-poly.Emails.switchLanguage' );

	add_filter( 'locale', function() {
		return 'zh_CN';
	}, 9999 );
	// Filter on plugin_locale so load_plugin_textdomain loads the correct locale.
	add_filter( 'plugin_locale', function() {
		return 'zh_CN';
	}, 9999 );

	if ( function_exists( 'switch_to_locale' ) ) {

		switch_to_locale( 'zh_CN' );
	}
	global $locale;
	$locale = 'zh_CN';
	// Init WC locale.
	WC()->load_plugin_textdomain();
	do_action( 'woo-poly.Emails.afterSwitchLanguage' );

	$wp_locale = new \WP_Locale();
}

/*
 * get locale formatted country/state destination for order
 */
function ii_order_destination( WC_Order $order ) {
	$countryCode = $order->get_shipping_country();
	$country	 = __( WC()->countries->countries[ $countryCode ], 'woocommerce' );
	$stateCode	 = $order->get_shipping_state();
	$state		 = '';
	if ( $stateCode ) {
		$states	 = WC()->countries->get_states( $countryCode );
		$state	 = ! empty( $states[ $stateCode ] ) ? $states[ $stateCode ] : '';
	}
	if ( $state ) {
		$state		 = __( $state, 'woocommerce' );
		/* translators: %1s: state, %2s country */
		$template	 = __( '%1$s, %2$s', 'inkston-integration' );
		$location	 = sprintf( $template, __( $state, 'woocommerce' ), $country );
		return $location;
	} else {
		return $country;
	}
}
