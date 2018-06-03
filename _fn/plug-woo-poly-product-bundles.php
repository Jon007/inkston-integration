<?php

/**
 * Filter the custom field woosb_ids for WooCommerce Product Bundle 
 * to get the translated products in the bundle
 *  '10330/1,10328/1,6382/1'
 *
 * @param array  $keys list of custom fields names
 * @param bool   $sync true if it is synchronization, false if it is a copy
 * @param int    $from id of the post from which we copy informations
 * @param int    $to   id of the post to which we paste informations
 * @param string $lang language slug
 */
//$keys = array_unique( apply_filters( 'pll_copy_post_metas', $keys, $sync, $from, $to, $lang ) );

/**
 * Polylang meta filter, return true to exclude meta item from synchronization.
 * (we translated it later in the pll_save_post action)
 *
 * @param string      $meta_key Meta key
 * @param string|null $meta_type
 * 
 * @return bool True if the key is protected, false otherwise.
 */
function nosync_woosb_ids($protected, $meta_key, $meta_type)
{
    if ($meta_key == 'woosb_ids') {
        return true;
    } else {
        return $protected;
    }
}
add_filter( 'is_protected_meta', 'nosync_woosb_ids', 10, 3);

/**
 * translate the custom field woosb_ids for WooCommerce Product Bundle 
 * to get the translated products in the bundle saved in postmeta in the format 
 *  {id}/{quantity},{id}/{quantity}
 * eg:
 *  '10330/1,10328/1,6382/1'
 * [Polylang only supports sync or no sync so we exclude from sync and save here]
 *
 * Hooks pll_save_post Fires after the post language and translations are saved
 *
 * @param int    $post_id      Post id
 * @param object $post         Post object
 * @param array  $translations The list of translations post ids
 */
function translate_woosb_ids($post_id, $post, $translations)
{

    //if creating a new translation, we need to reverse the logic and copy from the original
    //the original is not included in the translations array as not linked yet
    //and the new post has no woosb_ids to check
    if (isset($_GET['new_lang']) && isset($_GET['from_post'])) {
        $post_id = $_GET['from_post'];
    }

    //get woosb_ids and exit if none
    $woosb_ids = get_post_meta($post_id, 'woosb_ids', true);
    //if (!($woosb_ids)) {
        //consider adaptation to support Grouped product in the same way as smart bundle
        //$woosb_ids = get_post_meta( $post_id, '_children', true );
    //}
    if (!($woosb_ids)) {
        return false;
    }

    //parse $woosb_ids {id}/{quantity},{id}/{quantity} format
    $woosb_items = explode( ',', $woosb_ids);
    if (is_array($woosb_items) && count($woosb_items) > 0) {
        $lang = pll_get_post_language($post_id);
        $translations[$lang] = $post_id;
        //loop through translations
        foreach ($translations as $translation) {
            //ignore source item, which should already be in correct lang?
            //or process anyway just to check and add missing upsells?
            if (!$translation) { // || ($post_id == $translation) ){
                continue;
            }
            $targetlang = pll_get_post_language($translation);
            $translateditems = array();

            foreach ($woosb_items as $woosb_item) {
                $woosb_item_arr = explode( '/', $woosb_item);
                $woosb_product = get_translated_variation($woosb_item_arr[0], $targetlang);
                if ($woosb_product) {
                    //item found, make sure it is an upsell on the translation
                    $translateditems[] = $woosb_product . '/' . $woosb_item_arr[1];
                    add_upsell($woosb_product, $translation);
                } else {
                    //if item not found there was a problem in get_translated_variation()
                    //and item cannot be added
                    //$translateditems[] = $woosb_item_arr[0] . '/' . $woosb_item_arr[1];                    
                }
            }
            if ($lang != $targetlang) {
                update_metadata( 'post', $translation, 'woosb_ids', implode( ',', $translateditems));
            }
        }
    }
}
add_action( 'pll_save_post', 'translate_woosb_ids', 99, 3);

/*
 * Automatically add bundles as an upsell to the component items
 * 
 * @param int $addto        Product to add upsell to
 * @param int $upselltoadd  the Product to add as the upsell
 * 
 */
function add_upsell($addto, $upselltoadd)
{
    //get the parent product if it is a variation (upsells only valid on parent)
    $product = get_product_or_parent($addto);
    $upsells = $product->get_upsell_ids();
    if (!in_array($upselltoadd, $upsells)) {
        $upsells[] = $upselltoadd;
        $upsells = array_unique($upsells);
        //set_upsell_ids doesn't save product.. 
        $product->set_upsell_ids($upsells);
        //we don't want to get in event loop saving whole product again to update meta
        update_post_meta($product->get_id(), '_upsell_ids', $upsells);
    }
}

/**
 * When getting Upsells, also include Group Children if it is a Grouped product
 *
 * @param array      $related_ids array of product ids
 * @param WC_Product $product current product
 *
 * @return array filtered result
 */
function addChildrenToUpsells($relatedIds, $product)
{
    if ($product->get_type() == 'grouped') {
        $children = $product->get_children();
        if ($children) {
            $relatedIds = array_merge($relatedIds, $children);
        }
    }
    return $relatedIds;
}
add_filter( 'woocommerce_product_get_upsell_ids', 'addChildrenToUpsells', 5, 2);

