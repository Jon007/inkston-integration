<?php
/* 
 * inkston custom featured posts 
 */

/**
 * Get selection of featured, recent and sale products and posts
 * 
 * @return  array   array of post objects
 */
function get_featured_posts()
{
    $final_posts = [];
    $locale = (function_exists( 'pll_current_language')) ? pll_current_language( 'locale') : get_locale();
    $tKey = 'inkfeat' . $locale;
    $final_posts = get_transient($tKey);
    if (!$final_posts) {
        //RECENT POSTS
        $query_args = array(
            'ignore_sticky_posts' => 0, //sticky posts automatically added by WP
            'post_type' => array( 'post'),
            'orderby' => 'modified',
            'posts_per_page' => 50,
            'showposts' => 50,
            'order' => 'DESC',
            'fields' => 'ids',
            );
        $recent_list = get_posts($query_args);

        //FEATURED PRODUCTS 
        if (class_exists( 'woocommerce' )) {
            $query_args = array(
                'posts_per_page' => 100,
                'showposts' => 100,
                'post_status' => 'publish',
                'post_type' => 'product',
                'post__in' => array_merge(array(0), wc_get_featured_product_ids(), wc_get_product_ids_on_sale()),
                'orderby' => 'modified',
                'order' => 'DESC',
                'fields' => 'ids',
            );
            $product_list = get_posts($query_args);
            //SALE PRODUCTS 
            /*
              $query_args = array(
              'posts_per_page' => 25,
              'showposts'   =>  25,
              'post_status' => 'publish',
              'post_type'   => 'product',
              'post__in'    => array_merge( array( 0 ), wc_get_product_ids_on_sale() ),
              'orderby'     =>  'modified',
              'order'       =>  'DESC',
              );
              $sale_list = new WP_Query( $query_args );
             */

            //RECENT NON-FEATURED PRODUCTS 
            $query_args = array(
                'post_type' => 'product',
                'posts_per_page' => 25,
                'showposts' => 25,
                'post_status' => 'publish',
                'post__not_in' => array_merge(array(0), wc_get_product_ids_on_sale(), wc_get_featured_product_ids()),
                'orderby' => 'modified',
                'order' => 'DESC',
                'fields' => 'ids',
            );
            $recentproduct_list = get_posts($query_args);

            //TOP POPULAR NON-FEATURED PRODUCTS 
            $query_args = array(
                'post_type' => 'product',
                'posts_per_page' => 25,
                'showposts' => 25,
                'post_status' => 'publish',
                'post__not_in' => array_merge(array(8476, 8486, 13865), wc_get_product_ids_on_sale(), wc_get_featured_product_ids()),
                'meta_key' => 'total_sales',
                'orderby' => 'meta_value_num',
                'order' => 'DESC',
                'fields' => 'ids',
            );
            $popularproduct_list = get_posts($query_args);
            $final_posts = array_merge($recent_list, $product_list, $recentproduct_list, $popularproduct_list);
            set_transient($tKey, $final_posts, 3600);
        } else {
            $final_posts = $recent_list;
        }
    }


    //$final_posts = array_unique ( $final_posts);
    //$final_posts = shuffle_assoc($final_posts);
    shuffle($final_posts);
    return $final_posts;
}

