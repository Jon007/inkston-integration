<?php
/* 
 * general purpose arrays functions
 */


/**
 * shuffle input array of post objects
 * 
 * @param   array   $array array of post objects
 * @return  array   array of post objects
 */
function shuffle_assoc($array)
{
    // Initialize
    $shuffled_array = array();

    // Get array's keys and shuffle them.
    $shuffled_keys = array_keys($array);
    shuffle($shuffled_keys);


    // Create same array, but in shuffled order.
    foreach ($shuffled_keys AS $shuffled_key) {
        $shuffled_array[$shuffled_key] = $array[$shuffled_key];
    } // foreach
    // Return
    return $shuffled_array;
}

/**
 * Recursively implodes an array with optional key inclusion
 * 
 * Example of $include_keys output: key, value, key, value, key, value
 * 
 * @access  public
 * @param   array   $array         multi-dimensional array to recursively implode
 * @param   string  $glue          value that glues elements together	
 * @param   bool    $include_keys  include keys before their values
 * @param   bool    $trim_all      trim ALL whitespace from string
 * @return  string  imploded array
 */
function recursive_filter_implode($glue, $array, $include_keys = false, $trim_all = true)
{
    if (!is_array($array)) {
        return $array;
    }
    $glued_string = '';
    $array = array_filter($array);
    // Recursively iterates array and adds key/value to glued string
    array_walk_recursive($array, function($value, $key) use ($glue, $include_keys, &$glued_string) {
        if ($value) {
            $include_keys and $glued_string .= $key . $glue;
            $glued_string .= $value . $glue;
        }
    });
    // Removes last $glue from string
    strlen($glue) > 0 and $glued_string = substr($glued_string, 0, -strlen($glue));
    // Trim ALL whitespace
    $trim_all and $glued_string = preg_replace("/(\s)/ixsm", '', $glued_string);
    return (string) $glued_string;
}
