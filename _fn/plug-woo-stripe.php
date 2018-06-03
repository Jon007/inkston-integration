<?php

/*
 * Stripe payment gateway customization
 */

/* stripe on product pages causes error which breaks page for users from unsuppported countries */
add_filter( 'wc_stripe_hide_payment_request_on_product_page', '__return_true' );
