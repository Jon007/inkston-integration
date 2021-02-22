<?php
/**
 * Checkout terms and conditions area.
 *
 * @author  WooThemes mod J.Moore: checkbox removed in favour of info message
 * @package WooCommerce\Templates
 * @version 3.4.0
 */

defined( 'ABSPATH' ) || exit;

if ( apply_filters( 'woocommerce_checkout_show_terms', true ) && function_exists( 'wc_terms_and_conditions_checkbox_enabled' ) ) {
	do_action( 'woocommerce_checkout_before_terms_and_conditions' );

	?>
	<div class="woocommerce-terms-and-conditions-wrapper">
		<?php
		/**
		 * Terms and conditions hook used to inject content.
		 *
		 * @since 3.4.0.
		 * @hooked wc_checkout_privacy_policy_text() Shows custom privacy policy text. Priority 20.
		 * @hooked wc_terms_and_conditions_page_content() Shows t&c page content. Priority 30.
		 */
		do_action( 'woocommerce_checkout_terms_and_conditions' );
		?>

		<?php if ( wc_terms_and_conditions_checkbox_enabled() ) : 
		//INKSTON: remove checkbox ?>
	<p class="form-row terms wc-terms-and-conditions">
		<label class="woocommerce-form__label">
			<span><?php printf( __( 'By placing your order, you agree to the inkston <a href="%s" target="_blank" class="woocommerce-terms-and-conditions-link">terms &amp; conditions</a>', 'inkston-integration' ), esc_url( wc_get_page_permalink( 'terms' ) ) ); ?></span>  
		</label>
		<input type="hidden" name="terms" value="1" />
		<input type="hidden" name="terms-field" value="1" />
	</p>
		<?php //END: INKSTON
		endif; ?>
	</div>
	<?php

	do_action( 'woocommerce_checkout_after_terms_and_conditions' );
}
