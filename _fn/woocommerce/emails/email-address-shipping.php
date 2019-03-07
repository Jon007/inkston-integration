<?php
/**
 * Email Addresses
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/emails/email-addresses.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://docs.woocommerce.com/document/template-structure/
 * @package WooCommerce/Templates/Emails
 * @version 3.5.4
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$text_align	 = is_rtl() ? 'right' : 'left';
$shipping	 = $order->get_formatted_shipping_address();
?><table id="addresses" cellspacing="0" cellpadding="0" style="width: 100%; vertical-align: top; margin-bottom: 40px; padding:0;" border="0">
	<tr>
		<td style="text-align:<?php echo esc_attr( $text_align ); ?>; font-family: 'Helvetica Neue', Helvetica, Roboto, Arial, sans-serif; border:0; padding:0;" valign="top" width="50%">
			<h2><?php esc_html_e( 'Shipping From:', 'inkston-integration' ); ?></h2>

			<address class="address" style="border:0">
				<?php echo wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES ); ?>
				<br/>
				<?php
				$store_city	 = get_option( 'woocommerce_store_city' );
				if ( $store_city ) {
					echo $store_city . '<br />';
				}
				$location	 = wc_get_base_location();
				$states		 = WC()->countries->get_states( $location[ 'country' ] );
				$state		 = ! empty( $states[ $location[ 'state' ] ] ) ? $states[ $location[ 'state' ] ] : '';
				if ( $state ) {
					echo __( $state ) . '<br />';
				}
				echo __( WC()->countries->countries[ $location[ 'country' ] ] );
				?>
			</address>
		</td>
		<td style="text-align:<?php echo esc_attr( $text_align ); ?>; font-family: 'Helvetica Neue', Helvetica, Roboto, Arial, sans-serif; padding:0;" valign="top" width="50%">
			<h2><?php esc_html_e( 'Shipping To:', 'inkston-integration' ); ?></h2>

			<address class="address" style="border:0"><?php echo wp_kses_post( $shipping ); ?>
				<?php if ( $order->get_billing_phone() ) : ?>
					<br/><?php echo esc_html( $order->get_billing_phone() ); ?>
				<?php endif; ?>
			</address>
		</td>
	</tr>
</table>
