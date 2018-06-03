<?php

/*
 * Shortcode [termtag name="myterm"] to show popup term description
 * TODO: implement
 *
 */


/*
  function shortcode_Term($params = array(), $content) {

  // default parameters
  extract(shortcode_atts(array(
  'id' => 0,
  'slug' => '',
  'name' => '',
  ), $params));

  // parse parameters and generate html
  return '';
  }
  add_shortcode( 'termtag', 'shortcode_Term');
 *
 */



/* stop filtering tag/category to allow html description in tags */
foreach ( array( 'pre_term_description' ) as $filter ) {
    remove_filter( $filter, 'wp_filter_kses' );
}
foreach ( array( 'term_description' ) as $filter ) {
    remove_filter( $filter, 'wp_kses_data' );
}

