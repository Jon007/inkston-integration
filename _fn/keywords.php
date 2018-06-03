<?php
/* 
 * keywords and hastags functionality
 * Note: parallel and separate function exists in plug-bbpress.php
 */


/*
 * get hashtags for current post, eg used by feed
 * @param int|WP_Post $post 
 */
function ink_wp_hashtags($post)
{
    $post = get_post($post);
    if (empty($post)) {
        return '';
    }

    //depending on the post type, get the taxonomies to use for hashtags
    $tax_ids = [];
    switch ($post->post_type) {
        case 'post':
            //$tax_ids[]= 'category';
            $tax_ids[] = 'post_tag';
            break;
        case 'product':
            $tax_ids[] = 'product_cat';
            $tax_ids[] = 'product_tag';
            $tax_ids[] = 'pa_suitable-for';
            //$tax_ids[]= 'pa_brand';
            break;
        case 'topic':
        case 'reply':
            if (function_exists( 'bbp_get_topic_tag_tax_id')) {
                $tax_ids[] = bbp_get_topic_tag_tax_id();
            } else {
                $tax_ids[] = 'topic-tag';
            }
            break;
        case 'wpbdp_listing':
            $tax_ids[] = 'wpbdp_tag';
            break;
        default;
    }

    $hashtags = '';
    $hashtaglist = [];
    foreach ($tax_ids as $tax_id) {
        $terms = get_the_terms($post, $tax_id);

        //in our systems display name is quite descriptive, for short hashtag we prefer the slug
        //$tagfield = ($tax_id=='product_category') ? 'slug' : 'name';
        $tagfield = 'slug';
        if (is_array($terms)) {
            $hashtaglist = array_merge($hashtaglist, wp_list_pluck($terms, $tagfield, $tagfield));
        }
    }
    $hashtaglist = array_unique($hashtaglist);
    //implode all the tags from all terms 
    return ink_hashtag_implode( ' #', $hashtaglist);
}

/*
 * return correctly formatted hashtags, matching function signature of php implode
 * 
 * @param array $hashtaglist simple array of strings eg tag names
 * @param string $glue  Defaults to # hash.
 * @param array $hashtaglist The array of hashtags to implode.
 * 
 * @return string formatted hashtag list
 */
function ink_hashtag_implode($separator = ' #', $hashtaglist = [])
{
    $output = '';
    $finaltags = [];
    foreach ($hashtaglist as $hashtag) {
        $hashtag = ink_valid_tag($hashtag);

        //ignore tags which are 2 characters or less after removing spaces
        if (strlen($hashtag) > 2) {
            $finaltags[$hashtag] = $hashtag;
        }
    }
    //add prefixed with hash and space
    $output = implode($separator, $finaltags);
    if ($output == '') {
        return $output;
    } else {
        return $separator . $output;
    }
}

/*
 * validate tags by turning multiword tags, hyphenated and underscore separated words 
 * into single word CamelCase tags
 */
function ink_valid_tag($tag)
{
    //turn hyphenated-tags to spaced words
    $hashtag = str_replace( '-', ' ', $tag);

    //turn hyphenated-tags to spaced words
    $hashtag = str_replace( '_', ' ', $hashtag);

    //Capitalise Every Word
    $hashtag = ucwords($hashtag);

    //Remove Spaces to leave CamelCase string
    $hashtag = str_replace( ' ', '', $hashtag);

    return $hashtag;
}
add_filter( 'wpseo_og_article_tag', 'ink_valid_tag', 10, 1);

/*
 * adds hastags to input strings, used in filters to add hashtags to excerpt texts
 */
function ink_addhashtags($input)
{
    global $post;
    return $input . ink_wp_hashtags($post);
}
add_filter( 'wpseo_og_og_description', 'ink_addhashtags', 10, 1);
add_filter( 'wpseo_twitter_description', 'ink_addhashtags', 10, 1);

/*
 * get standard hashtag list and convert to keywords by removing hash
 */
function ink_hashtag_keywords($keywords)
{
    global $post;
    $ink_keywords = ink_wp_hashtags($post);
    if ($ink_keywords) {
        return str_replace( '#', '', str_replace( ' ', ',', trim($ink_keywords)));
    } else {
        return $keywords;
    }
}
//filter and all meta keywords functionality removed in wpseo 6.3
//add_filter( 'wpseo_metakeywords', 'ink_hashtag_keywords');
