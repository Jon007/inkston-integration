<?php
add_action( 'admin_init', 'ii_options_init' );
add_action( 'admin_menu', 'ii_add_admin_menu' );
add_filter( 'plugin_action_links_inkston-integration/inkston-integration.php', 'ii_settings_link' );
/**
 * Add settings link to plugins page
 * @param array $links
 * @return array
 */
function ii_settings_link( $links ) {
	$links[] = '<a href="' .
	get_admin_url( null, 'options-general.php?page=inkston-integration' ) . '">' .
	esc_html__( 'Settings', 'inkston-integration' ) . '</a>';
	return $links;
}

/**
 * Add settings link to Admin Settings menu
 */
function ii_add_admin_menu() {
	add_options_page( 'Inkston Integration', 'Inkston Integration', 'manage_options', 'inkston-integration', 'ii_options_page' );
}

/*
 * Define options
 */
function ii_options_init() {

	$section_group	 = 'ii_options';
	$section_name	 = 'ii_options';
	register_setting( $section_group, $section_name );

	$settings_section	 = 'ii_options';
	$page				 = $section_group;
	add_settings_section(
	$settings_section, __( 'General Options', 'inkston-integration' ), 'ii_options_section_callback', $page
	);

	add_settings_field(
	'debug', __( 'Debug', 'inkston-integration' ), 'debug_render', $section_group, $settings_section, array(
		__( 'log additional debug messages from this plugin.', 'inkston-integration' )
	)
	);
	add_settings_field(
	'excerpt_length', __( 'Excerpt Length', 'inkston-integration' ), 'excerpt_length_render', $section_group, $settings_section, array(
		__( 'Set excerpt length or leave blank to turn off functionality', 'inkston-integration' )
	)
	);
	add_settings_field(
	'embed', __( 'Embed', 'inkston-integration' ), 'embed_render', $section_group, $settings_section, array(
		__( 'Fix embeds compatibility issues.', 'inkston-integration' )
	)
	);
	add_settings_field(
	'merge_comments', __( 'Merge Comments', 'inkston-integration' ), 'merge_comments_render', $section_group, $settings_section, array(
		__( 'merge comments/reviews across languages, affects comments and plug-woo-poly-reviews.', 'inkston-integration' )
	)
	);
	add_settings_field(
	'disable_emoji', __( 'Disable Emoji', 'inkston-integration' ), 'disable_emoji_render', $section_group, $settings_section, array(
		__( 'Stop loading scripts for emoji [disable-emoji.php]', 'inkston-integration' )
	)
	);

	add_settings_field(
	'featured_posts', __( 'Featured Posts', 'inkston-integration' ), 'featured_posts_render', $section_group, $settings_section, array(
		__( 'Enables Featured posts/products array [featured-posts.php].', 'inkston-integration' )
	)
	);

	add_settings_field(
	'hashtags', __( 'Hashtags and keywords', 'inkston-integration' ), 'hashtags_render', $section_group, $settings_section, array(
		__( 'adds terms as hashtags to feeds etc [keywords.php].', 'inkston-integration' )
	)
	);

	$settings_section	 = 'ii_rss_options';
	//this would register a separate option, but was unable to get the settings to save into this option
	//register_setting( $section_group, $settings_section );
	$page				 = $section_group;
	add_settings_section(
	$settings_section, __( 'RSS Integration Options', 'inkston-integration' ), 'ii_rss_section_callback', $page
	);

	add_settings_field(
	'rss_enclosure', __( 'enclosure tag', 'inkston-integration' ), 'rss_enclosure_render', $section_group, $settings_section, array(
		__( 'include enclosure tag for featured image (appears to be required by IFTT)', 'inkston-integration' )
	)
	);
	add_settings_field(
	'rss_media', __( 'media tag', 'inkston-integration' ), 'rss_media_render', $section_group, $settings_section, array(
		__( 'include media tag for featured image (supported by Zapier and others)', 'inkston-integration' )
	)
	);


	$settings_section	 = 'ii_plugin_options';
	//this would register a separate option, but was unable to get the settings to save into this option
	//register_setting( $section_group, $settings_section );
	$page				 = $section_group;
	add_settings_section(
	$settings_section, __( 'Plugin Integration Options', 'inkston-integration' ), 'ii_plugins_section_callback', $page
	);


	add_settings_field(
	'relevanssi', __( 'Relevanssi exclude shortcodes', 'inkston-integration' ), 'relevanssi_render', $section_group, $settings_section, array(
		__( 'exclude these shortcodes from search parsing [plug-relevanssi.php]', 'inkston-integration' )
	)
	);

	add_settings_field(
	'badgeos_levels', __( 'BadgeOS Levels', 'inkston-integration' ), 'badgeos_levels_render', $section_group, $settings_section, array(
		__( 'user levels: enables avatar, shortcodes [inkpoints] and [inklevel]  [plug-badgeos.php].', 'inkston-integration' )
	)
	);
	add_settings_field(
	'bbpress', __( 'bbPress', 'inkston-integration' ), 'bbpress_render', $section_group, $settings_section, array(
		__( 'enables topic reply login, forum titles in emails, rich text editor, shortcode handling, hashtags, avatar sizing [plug-bbpress.php', 'inkston-integration' )
	)
	);

	add_settings_field(
	'bus_directory', __( 'Business Directory', 'inkston-integration' ), 'bus_directory_render', $section_group, $settings_section, array(
		__( 'enables fields translations, t&c links, custom author archive  [plug-business-directory.php]', 'inkston-integration' )
	)
	);

	add_settings_field(
	'mailpoet', __( 'MailPoet', 'inkston-integration' ), 'mailpoet_render', $section_group, $settings_section, array(
		__( 'provides [ink_get_newsletter_subscribe_link] to help manage subscriptions [plug-mailpoet.php]' )
	)
	);

	add_settings_field(
	'polylang', __( 'Polylang', 'inkston-integration' ), 'polylang_render', $section_group, $settings_section, array(
		__( 'provides default copy content [plug-polylang.php]' )
	)
	);

	add_settings_field(
	'socializer', __( 'Social login', 'inkston-integration' ), 'socializer_render', $section_group, $settings_section, array(
		__( 'adds social login buttons to top of standard login form [plug-socializer.php]' )
	)
	);


	$settings_section	 = 'ii_woo_options';
	//this would register a separate option, but was unable to get the settings to save into this option
	//register_setting( $section_group, $settings_section );
	$page				 = $section_group;
	add_settings_section(
	$settings_section, __( 'WooCommerce Integration Options', 'inkston-integration' ), 'ii_woo_section_callback', $page
	);

	add_settings_field(
	'amazonusa', __( 'Amazon USA', 'inkston-integration' ), 'amazonusa_render', $section_group, $settings_section, array(
		__( 'adds Amazon USA review links to products with ASINS' )
	)
	);

	add_settings_field(
	'amazoneu', __( 'Amazon Europe', 'inkston-integration' ), 'amazoneu_render', $section_group, $settings_section, array(
		__( 'adds Amazon EU review links to products with ASINS' )
	)
	);

	add_settings_field(
	'asinupc', __( 'Custom Fields', 'inkston-integration' ), 'asinupc_render', $section_group, $settings_section, array(
		__( 'adds custom fields for ASIN, UPC and net weight and size' )
	)
	);

	add_settings_field(
	'buttons', __( 'Checkout Buttons', 'inkston-integration' ), 'buttons_render', $section_group, $settings_section, array(
		__( 'fixes button titles and adds extra checkout button after adding to cart' )
	)
	);

	add_settings_field(
	'cart', __( 'Cart', 'inkston-integration' ), 'cart_render', $section_group, $settings_section, array(
		__( 'adds inkston shopping cart button with items and amount indicators' )
	)
	);

	//TODO: make list of currencies extensible here???
	add_settings_field(
	'ccys', __( 'Currencies', 'inkston-integration' ), 'ccys_render', $section_group, $settings_section, array(
		__( 'adds support for multiple currencies' )
	)
	);

	add_settings_field(
	'files', __( 'Customization files', 'inkston-integration' ), 'files_render', $section_group, $settings_section, array(
		__( 'adds support for uploading design files during checkout (for products with customization option)' )
	)
	);

	add_settings_field(
	'loginredir', __( 'Login Redirection', 'inkston-integration' ), 'loginredir_render', $section_group, $settings_section, array(
		__( 'Supports login via woocommerce account page and correctly return to previous page' )
	)
	);

	add_settings_field(
	'paystatus', __( 'Payment Status', 'inkston-integration' ), 'paystatus_render', $section_group, $settings_section, array(
		__( 'Allows orders which are On Hold to be paid or cancelled online.' )
	)
	);

	add_settings_field(
	'bundle', __( 'Bundle Enhancements', 'inkston-integration' ), 'bundle_render', $section_group, $settings_section, array(
		__( 'Enhances bundle products by showing discount% sale flash and automatically adding upsells.' )
	)
	);

	add_settings_field(
	'bundletrans', __( 'Bundle Translations', 'inkston-integration' ), 'bundletrans_render', $section_group, $settings_section, array(
		__( 'Allows bundled products to be translated (automatically finds related products in bundle).' )
	)
	);

	add_settings_field(
	'vouchers', __( 'Gift Coupons', 'inkston-integration' ), 'vouchers_render', $section_group, $settings_section, array(
		__( 'Enhances gift coupons by allowing rich text messages.' )
	)
	);

	add_settings_field(
	'group', __( 'Group Products', 'inkston-integration' ), 'group_render', $section_group, $settings_section, array(
		__( 'Enhances group products, eg improved excerpts.' )
	)
	);

	add_settings_field(
	'wooseo', __( 'WooCommerce SEO', 'inkston-integration' ), 'wooseo_render', $section_group, $settings_section, array(
		__( 'SEO enhancments for product pages.' )
	)
	);
	add_settings_field(
	'stripe', __( 'Stripe disable', 'inkston-integration' ), 'stripe_render', $section_group, $settings_section, array(
		__( 'Disable stripe checkout on product pages (checkout only on checkout page).' )
	)
	);
	add_settings_field(
	'hovercat', __( 'Catalogue popups', 'inkston-integration' ), 'hovercat_render', $section_group, $settings_section, array(
		__( 'Information popups in the shop catalogue, on categories and products.' )
	)
	);

	add_settings_field(
	'woopdf', __( 'WooCommerce Invoice enhancements', 'inkston-integration' ), 'woopdf_render', $section_group, $settings_section, array(
		__( 'Allow Chinese characters in invoices, add correct VAT total lines if tax enabled.' )
	)
	);


	add_settings_field(
	'woocoupons', __( 'WooCommerce Coupons', 'inkston-integration' ), 'woocoupons_render', $section_group, $settings_section, array(
		__( 'Provides description instead of 0.00 for free product coupons (if auto-added coupons is active).' )
	)
	);
	add_settings_field(
	'wootemplates', __( 'WooCommerce Templates', 'inkston-integration' ), 'wootemplates_render', $section_group, $settings_section, array(
		__( 'Enable Inkston WooCommerce template overrides.' )
	)
	);
	add_settings_field(
	'sku', __( 'Auto-generate sku', 'inkston-integration' ), 'sku_render', $section_group, $settings_section, array(
		__( 'Generate sku automatically' )
	)
	);
	add_settings_field(
	'skuformat', __( 'Sku format', 'inkston-integration' ), 'skuformat_render', $section_group, $settings_section, array(
		__( 'Use the following format for SKU, use literal text and supported tokens {initials} or {slug} and {id} or {variationid}: by default variations will use the id of the parent (which is what you normally use to look up the product) and extra code in the {initials} or {slug} for the child details' )
	)
	);


	$settings_section	 = 'ii_woo_freeshipping';
	//this would register a separate option, but was unable to get the settings to save into this option
	//register_setting( $section_group, $settings_section );
	$page				 = $section_group;
	add_settings_section(
	$settings_section, __( 'WooCommerce Free Shipping Options', 'inkston-integration' ), 'ii_woo_freeshipping_callback', $page
	);

	add_settings_field(
	'woofreeshippingoffer', __( 'Enable free shipping offer messages', 'inkston-integration' ), 'woofreeshippingoffer_render', $section_group, $settings_section, array(
		__( 'Show free shipping qualification messages in the shopping cart.' )
	)
	);
	add_settings_field(
	'woofreeshippinglevel', __( 'Free shipping minimum order', 'inkston-integration' ), 'woofreeshippinglevel_render', $section_group, $settings_section, array(
		__( 'Minimum order level to qualify for free shipping: if set this will update related woocommerce shipping methods but does not update the shipping policy pages: please update shipping policy separately.' )
	)
	);
	add_settings_field(
	'woofreeshippingencourage', __( 'Free shipping encouragement level', 'inkston-integration' ), 'woofreeshippingencourage_render', $section_group, $settings_section, array(
		__( 'If set, when order reaches this value, message will be shown encouraging user to add more for free shipping.' )
	)
	);
	add_settings_field(
	'woofreeshippingexcept', __( 'Free shipping exceptions', 'inkston-integration' ), 'woofreeshippingexcept_render', $section_group, $settings_section, array(
		__( 'If set, exception message will be added to free shipping message, for example countries where this does not apply. This is a purely information message which is NOT checked against the WooCommerce shipping configuration or shipping policy pages - please check these separately.' )
	)
	);


	$settings_section	 = 'ii_woo_cloning_options';
	//this would register a separate option, but was unable to get the settings to save into this option
	//register_setting( $section_group, $settings_section );
	$page				 = $section_group;
	add_settings_section(
	$settings_section, __( 'WooCommerce Product Cloning Options', 'inkston-integration' ), 'ii_woo_clone_section_callback', $page
	);


	add_settings_field(
	'sitepricefactor', __( 'Site price multiplier', 'inkston-integration' ), 'sitepricefactor_render', $section_group, $settings_section, array(
		__( 'Factor to use when converting prices from main inkston site to this site: should take into account currency conversion plus local site taxation and pricing policies.' )
	)
	);
	add_settings_field(
	'sitepricesync', __( 'Synchronise prices', 'inkston-integration' ), 'sitepricesync_render', $section_group, $settings_section, array(
		__( 'For linked posts, synchronise prices when updated in main site.' )
	)
	);
	add_settings_field(
	'sitesalesync', __( 'Synchronise sale prices and dates', 'inkston-integration' ), 'sitesalesync_render', $section_group, $settings_section, array(
		__( 'For linked posts, synchronise prices when updated in main site.' )
	)
	);

	$settings_section	 = 'ii_backorder_options';
	//this would register a separate option, but was unable to get the settings to save into this option
	//register_setting( $section_group, $settings_section );
	$page				 = $section_group;
	add_settings_section(
	$settings_section, __( 'WooCommerce Backorder options', 'inkston-integration' ), 'ii_woo_backorder_section_callback', $page
	);


	add_settings_field(
	'allowbackorders', __( 'Allow back orders', 'inkston-integration' ), 'allowbackorders_render', $section_group, $settings_section, array(
		__( 'Allow back orders by default, applies when copying items to this site' )
	)
	);

	add_settings_field(
	'backordered', __( 'Back-ordered message', 'inkston-integration' ), 'backordered_render', $section_group, $settings_section, array(
		__( 'Message shown against backordered items on customer orders' )
	)
	);

	add_settings_field(
	'willbackorder', __( 'On back-order message', 'inkston-integration' ), 'willbackorder_render', $section_group, $settings_section, array(
		__( 'Message shown on products not in stock locally (eg will be backordered from remote location or made fresh)' )
	)
	);
}

/* Load or default the options, once */
function ii_get_options() {
	static $ii_options;
	if ( ! $ii_options ) {
		//Pull from WP options database table
		$ii_options = get_option( 'ii_options' );
		//set defaults if not set
		if ( ! is_array( $ii_options ) ) {

			$ii_options[ 'badgeos_levels' ]	 = true;
			$ii_options[ 'relevanssi' ]		 = implode( ',', array( 'inkpoints', 'inklevel', 'badgeos_achievements_list', 'robo-gallery', 'maxmegamenu' ) );
			$ii_options[ 'bbpress' ]		 = true;
			$ii_options[ 'debug' ]	 = true;
			$ii_options[ 'embed' ]	 = true;
			$ii_options[ 'excerpt_length' ]	 = 35;
			$ii_options[ 'merge_comments' ]	 = true;
			$ii_options[ 'disable_emoji' ]	 = true;
			$ii_options[ 'featured_posts' ]	 = true;
			$ii_options[ 'hashtags' ]		 = true;
			$ii_options[ 'bus_directory' ]	 = true;
			$ii_options[ 'mailpoet' ]		 = true;
			$ii_options[ 'polylang' ]		 = true;
			$ii_options[ 'socializer' ]		 = true;
			$ii_options[ 'amazonusa' ]		 = true;
			$ii_options[ 'amazoneu' ]		 = true;
			$ii_options[ 'asinupc' ]		 = true;
			$ii_options[ 'buttons' ]		 = true;
			$ii_options[ 'cart' ]			 = true;
			$ii_options[ 'ccys' ]			 = true;
			$ii_options[ 'files' ]			 = true;
			$ii_options[ 'loginredir' ]		 = true;
			$ii_options[ 'paystatus' ]		 = true;
			$ii_options[ 'bundle' ]			 = true;
			$ii_options[ 'bundletrans' ]	 = true;
			$ii_options[ 'vouchers' ]		 = true;
			$ii_options[ 'group' ]			 = true;
			$ii_options[ 'wooseo' ]			 = true;
			$ii_options[ 'stripe' ]			 = true;
			$ii_options[ 'hovercat' ]		 = true;
			$ii_options[ 'sku' ]			 = true;
			$ii_options[ 'skuformat' ]		 = 'ink-{initials}-{id}';
			$ii_options[ 'woocoupons' ]		 = true;
			$ii_options[ 'wootemplates' ]	 = true;
			$ii_options[ 'woopdf' ]			 = true;

			$ii_options[ 'woofreeshippingoffer' ]		 = true;
			$ii_options[ 'woofreeshippinglevel' ]		 = 150;
			$ii_options[ 'woofreeshippingencourage' ]	 = 100;
			$ii_options[ 'woofreeshippingexcept' ]		 = '';

			$ii_options[ 'sitepricefactor' ] = 1;
			$ii_options[ 'sitepricesync' ]	 = true;
			$ii_options[ 'sitesalesync' ]	 = true;
			$ii_options[ 'allowbackorders' ] = true;
			$ii_options[ 'backordered' ]	 = '';
			$ii_options[ 'willbackorder' ]	 = '';

			//update_option('ii_options', $options);
		}
	}
	return $ii_options;
}

/* Option display callbacks */
function excerpt_length_render( $s ) {
	ii_render_input( 'excerpt_length', $s );
}
function debug_render( $s ) {
	ii_render_checkbox( 'debug', $s );
}
function embed_render( $s ) {
	ii_render_checkbox( 'embed', $s );
}
function merge_comments_render( $s ) {
	ii_render_checkbox( 'merge_comments', $s );
}

function disable_emoji_render( $s ) {
	ii_render_checkbox( 'disable_emoji', $s );
}

function featured_posts_render( $s ) {
	ii_render_checkbox( 'featured_posts', $s );
}

function hashtags_render( $s ) {
	ii_render_checkbox( 'hashtags', $s );
}

function rss_enclosure_render( $s ) {
	ii_render_checkbox( 'rss_enclosure', $s );
}

function rss_media_render( $s ) {
	ii_render_checkbox( 'rss_media', $s );
}

function badgeos_levels_render( $s ) {
	ii_render_checkbox( 'badgeos_levels', $s );
}

function relevanssi_render( $s ) {
	ii_render_multiline( 'relevanssi', $s );
}

function bbpress_render( $s ) {
	ii_render_checkbox( 'bbpress', $s );
}

function bus_directory_render( $s ) {
	ii_render_checkbox( 'bus_directory', $s );
}

function mailpoet_render( $s ) {
	ii_render_checkbox( 'mailpoet', $s );
}

function polylang_render( $s ) {
	ii_render_checkbox( 'polylang', $s );
}

function socializer_render( $s ) {
	ii_render_checkbox( 'socializer', $s );
}

function amazonusa_render( $s ) {
	ii_render_checkbox( 'amazonusa', $s );
}

function amazoneu_render( $s ) {
	ii_render_checkbox( 'amazoneu', $s );
}

function asinupc_render( $s ) {
	ii_render_checkbox( 'asinupc', $s );
}

function buttons_render( $s ) {
	ii_render_checkbox( 'buttons', $s );
}

function cart_render( $s ) {
	ii_render_checkbox( 'cart', $s );
}

function ccys_render( $s ) {
	ii_render_checkbox( 'ccys', $s );
}

function files_render( $s ) {
	ii_render_checkbox( 'files', $s );
}

function loginredir_render( $s ) {
	ii_render_checkbox( 'loginredir', $s );
}

function paystatus_render( $s ) {
	ii_render_checkbox( 'paystatus', $s );
}

function bundle_render( $s ) {
	ii_render_checkbox( 'bundle', $s );
}

function bundletrans_render( $s ) {
	ii_render_checkbox( 'bundletrans', $s );
}

function vouchers_render( $s ) {
	ii_render_checkbox( 'vouchers', $s );
}

function group_render( $s ) {
	ii_render_checkbox( 'group', $s );
}

function wooseo_render( $s ) {
	ii_render_checkbox( 'wooseo', $s );
}

function stripe_render( $s ) {
	ii_render_checkbox( 'stripe', $s );
}

function hovercat_render( $s ) {
	ii_render_checkbox( 'hovercat', $s );
}

function sku_render( $s ) {
	ii_render_checkbox( 'sku', $s );
}

function skuformat_render( $s ) {
	ii_render_input( 'skuformat', $s );
}

function woocoupons_render( $s ) {
	ii_render_checkbox( 'woocoupons', $s );
}

function wootemplates_render( $s ) {
	ii_render_checkbox( 'wootemplates', $s );
}

function woopdf_render( $s ) {
	ii_render_checkbox( 'woopdf', $s );
}

function sitepricefactor_render( $s ) {
	ii_render_input( 'sitepricefactor', $s );
}

function sitepricesync_render( $s ) {
	ii_render_checkbox( 'sitepricesync', $s );
}

function sitesalesync_render( $s ) {
	ii_render_checkbox( 'sitesalesync', $s );
}

function allowbackorders_render( $s ) {
	ii_render_checkbox( 'allowbackorders', $s );
}

function backordered_render( $s ) {
	ii_render_multiline( 'backordered', $s );
}

function willbackorder_render( $s ) {
	ii_render_multiline( 'willbackorder', $s );
}

function woofreeshippingoffer_render( $s ) {
	ii_render_checkbox( 'woofreeshippingoffer', $s );
}

function woofreeshippinglevel_render( $s ) {
	ii_render_input( 'woofreeshippinglevel', $s );
}

function woofreeshippingencourage_render( $s ) {
	ii_render_input( 'woofreeshippingencourage', $s );
}

function woofreeshippingexcept_render( $s ) {
	ii_render_multiline( 'woofreeshippingexcept', $s );
}

/* Option render controls - standard input box */
function ii_render_input( $optionName, $s ) {
	$options = ii_get_options();
	?>
	<input type="text" name="ii_options[<?php echo($optionName) ?>]" id="<?php echo($optionName) ?>" value="<?php
	if ( isset( $options[ $optionName ] ) ) {
		echo $options[ $optionName ];
	}
	?>" /><?php
		   echo(implode( ' ', $s ));
	   }

	   /* Option render controls - standard textarea */
	   function ii_render_multiline( $optionName, $s ) {
		   $options = ii_get_options();
		   ?>
	<textarea style="width:100%" name="ii_options[<?php echo($optionName) ?>]" id="<?php echo($optionName) ?>"><?php
		if ( isset( $options[ $optionName ] ) ) {
			echo $options[ $optionName ];
		}
		?></textarea>
	<?php
	echo(implode( ' ', $s ));
}

/* Option render controls - standard checkbox */
function ii_render_checkbox( $optionName, $s ) {
	$options = ii_get_options();
	echo('<input type="checkbox" name="ii_options[');
	echo($optionName);
	echo (']" id="');
	echo($optionName);
	echo ('" ');
	if ( isset( $options[ $optionName ] ) ) {
		echo(' checked ');
	}
	echo('value="1">');
		   echo(implode( ' ', $s ));
	   }

	   /* Option section titles */
	   function ii_options_section_callback() {
		   _e( 'General Options:', 'inkston-integration' );
	   }

function ii_rss_section_callback() {
	_e( 'Options for rss images:', 'inkston-integration' );
}

	   function ii_plugins_section_callback() {
		   _e( 'Options for integrated plugins (automatically deactivated if related plugin is not active):', 'inkston-integration' );
	   }

	   function ii_woo_section_callback() {
		   _e( 'WooCommerce Integration Options:', 'inkston-integration' );
	   }

	   function ii_woo_clone_section_callback() {
		   _e( 'These options apply where this site is a destination site for cloning:', 'inkston-integration' );
	   }

function ii_woo_freeshipping_callback() {
	_e( 'Free shipping offer notice options' );
}

function ii_woo_backorder_section_callback() {
	_e( 'Options for allowing and displaying items which are not stocked locally (eg will be backordered from remote supplier.' );
}

 function ii_options_page() {
		   // check user capabilities
		   if ( ! current_user_can( 'manage_options' ) ) {
			   return;
		   }
		   $translations_link = admin_url() . '/admin.php?page=mlang_strings&s&group=Polylang+User+Alerts&paged=1';
		   echo '<h1>' . esc_html( get_admin_page_title() ) . '</h1>';
		   ?>
	<form action='options.php' method='post'>

		<h2>Inkston Integration</h2>
		<p><a target="_blank" href="https://github.com/Jon007/inkston-integration/">inkston-integration</a> <?php _e( 'is a plugin integration helper tool from', 'inkston-integration' ) ?> <a target="_blank" href="https://jonmoblog.wordpress.com/">Jonathan Moore</a>. It allows consistent behaviour and plugin integration across multiple sites and different themes.
			Crucially, almost every feature can be turned off individually to facilitate compatibility debugging (features also auto switch-off when dependency functionality is not enabled)</p>

		<?php
		settings_fields( 'ii_options' );
		do_settings_sections( 'ii_options' );
		submit_button();
		?>

	</form>
	<h2>Usage</h2>
	<p>Set the options and save them.</p>
	<p>Please see <a href="https://github.com/Jon007/inkston-integration/">inkston-integration on Github</a> for more details.</p>
	<h2>Notes</h2>
	<p>This tool is provided free as-is, use and modify as you like, and at your own risk.</p>
	<p>WooCommerce is recommended:
	<ul><li>if used without WooCommerce 3 then WooCommerce related settings will have no effect.
			Due to the huge number of api changes in version 3, earlier versions of WooCommerce will be ignored.</li>
	</ul>	<?php
}

/*
 * if the free shipping level is changed, update the woocommerce shipping methods
 *
 * @param $old_value array of old values for ii_options
 * @param $new_value array of new values for ii_options
 * @param $option string 'ii_options'
 *
 */
function ii_woo_set_shipping( $old_value, $value, $option ) {
	if ( ! class_exists( 'woocommerce' ) || ( ! function_exists( 'inkston_free_shipping_level' )) ) {
		return;
	}
	$existing_zones		 = WC_Shipping_Zones::get_zones();
	$free_shipping_level = (isset( $value[ 'woofreeshippinglevel' ] )) ? $value[ 'woofreeshippinglevel' ] : 0;
	$old_shipping_level	 = (isset( $old_value[ 'woofreeshippinglevel' ] )) ? $old_value[ 'woofreeshippinglevel' ] : 0;
	if ( $free_shipping_level && ( $free_shipping_level != $old_shipping_level) ) {
		foreach ( $existing_zones as $existing_zone ) {
			$shipping_methods = $existing_zone[ 'shipping_methods' ];
			foreach ( $shipping_methods as $shipping_method ) {
				if ( $shipping_method instanceof WC_Shipping_Free_Shipping ) {
					$shipping_method->min_amount = $free_shipping_level;
					$key						 = $shipping_method->get_instance_option_key();
					$settings					 = $shipping_method->instance_settings;
					$settings[ 'min_amount' ]		 = $free_shipping_level;
					update_option( $key, $settings, 'yes' );
				}
			}
		}
	}
}

add_action( 'update_option_ii_options', 'ii_woo_set_shipping', 10, 3 );
