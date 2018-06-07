<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
function ink_get_theme_mod( $setting, $default ) {
	if ( class_exists( 'Storefront_Customizer' ) ) {
		return ink_get_storefront_theme_mod( $setting, $default );
	} else { //TODO: hook settings for other themes
		return $default;
	}
}

function ink_get_storefront_theme_mod( $setting, $default ) {
	$storefront_customizer	 = new Storefront_Customizer();
	$storefront_theme_mods	 = $storefront_customizer->get_storefront_theme_mods();
	$result					 = $storefront_theme_mods[ $setting ];
	return ($result) ? $result : $default;
}
