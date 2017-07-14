<?php
/**
* Plugin Name: Simple Mega Menu
* Description: Create mega menu use the content of a custom post type.
* Plugin URI: https://wordpress.org
* Author: Truong Giang
* Author URI: https://truongwp.com
* Version: 0.1.0
* License: GPL2
* Text Domain: simple-mega-menu
* Domain Path: /languages
*/

/*
Copyright (C) 2017  Truong Giang  truongwp@gmail.com

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License, version 2, as
published by the Free Software Foundation.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

if ( defined( 'SMM_PATH' ) ) {
	return;
}

define( 'SMM_PATH', plugin_dir_path( __FILE__ ) );

require_once SMM_PATH . 'class-smm-walker-nav-menu-edit.php';

/**
 * Loads plugin text domain.
 */
function smm_load_plugin_textdomain() {
	load_plugin_textdomain( 'simple-mega-menu', FALSE, SMM_PATH . 'languages/' );
}
add_action( 'plugins_loaded', 'smm_load_plugin_textdomain' );

/**
 * Filters the edit nav menu walker class name.
 *
 * @return string
 */
function smm_wp_edit_nav_menu_walker() {
	return 'SMM_Walker_Nav_Menu_Edit';
}
add_filter( 'wp_edit_nav_menu_walker', 'smm_wp_edit_nav_menu_walker' );

/**
 * Saves new field to postmeta for navigation.
 */
function smm_nav_menu_update( $menu_id, $menu_item_db_id, $args ) {
	$value = ! empty( $_POST['menu-item-attr-enable-mega-menu'][ $menu_item_db_id ] ) ? 1 : 0; // WPCS: sanitization, csrf ok.
	update_post_meta( $menu_item_db_id, '_menu_item_enable_mega_menu', $value );

	if ( isset( $_POST['menu-item-attr-mega-menu-post'][ $menu_item_db_id ] ) ) { // WPCS: sanitization, csrf ok.
		$value = absint( $_POST['menu-item-attr-mega-menu-post'][ $menu_item_db_id ] ); // WPCS: sanitization, csrf ok.
		update_post_meta( $menu_item_db_id, '_menu_item_mega_menu_post', $value );
	}
}
add_action( 'wp_update_nav_menu_item', 'smm_nav_menu_update', 10, 3 );

/**
 * Adds value of new field to $item object that will be passed to Walker_Nav_Menu_Edit.
 */
function smm_custom_nav_item( $menu_item ) {
	$menu_item->enable_mega_menu = get_post_meta( $menu_item->ID, '_menu_item_enable_mega_menu', true );
	$menu_item->mega_menu_post = get_post_meta( $menu_item->ID, '_menu_item_mega_menu_post', true );

	return $menu_item;
}
add_filter( 'wp_setup_nav_menu_item','smm_custom_nav_item' );

/**
 * Registers mega menu post type.
 *
 * @uses $wp_post_types Inserts new post type object into the list
 */
function smm_mega_menu_post_type_register() {

	$labels = array(
		'name'                => __( 'Mega menus', 'simple-mega-menu' ),
		'singular_name'       => __( 'Mega menu', 'simple-mega-menu' ),
		'add_new'             => _x( 'Add New Mega menu', 'simple-mega-menu', 'simple-mega-menu' ),
		'add_new_item'        => __( 'Add New Mega menu', 'simple-mega-menu' ),
		'edit_item'           => __( 'Edit Mega menu', 'simple-mega-menu' ),
		'new_item'            => __( 'New Mega menu', 'simple-mega-menu' ),
		'view_item'           => __( 'View Mega menu', 'simple-mega-menu' ),
		'search_items'        => __( 'Search Mega menus', 'simple-mega-menu' ),
		'not_found'           => __( 'No Mega menus found', 'simple-mega-menu' ),
		'not_found_in_trash'  => __( 'No Mega menus found in Trash', 'simple-mega-menu' ),
		'parent_item_colon'   => __( 'Parent Mega menu:', 'simple-mega-menu' ),
		'menu_name'           => __( 'Mega menus', 'simple-mega-menu' ),
	);

	$args = array(
		'labels'              => $labels,
		'hierarchical'        => false,
		'taxonomies'          => array(),
		'public'              => false,
		'show_ui'             => true,
		'show_in_menu'        => true,
		'show_in_admin_bar'   => false,
		'menu_position'       => null,
		'menu_icon'           => 'dashicons-menu',
		'show_in_nav_menus'   => false,
		'publicly_queryable'  => false,
		'exclude_from_search' => true,
		'has_archive'         => false,
		'query_var'           => false,
		'can_export'          => true,
		'rewrite'             => false,
		'capability_type'     => 'post',
		'supports'            => array( 'title', 'editor' ),
	);

	register_post_type( 'smm_mega_menu', $args );
}
add_action( 'init', 'smm_mega_menu_post_type_register' );

/**
 * Get mega menu posts.
 *
 * @return array
 */
function smm_get_mega_menu_posts() {
	return get_posts( array(
		'post_type' => 'smm_mega_menu',
		'nopaging'  => true,
		'orderby'   => 'title',
		'order'     => 'asc',
	) );
}

/**
 * Filters the output of menu item.
 *
 * @param  string $item_output Item output.
 * @param  object $item        Menu item object.
 * @return string
 */
function smm_mega_menu_output( $item_output, $item ) {
	if ( intval( $item->enable_mega_menu ) && $item->mega_menu_post ) {
		$mega_menu = get_post( $item->mega_menu_post );

		$item_output .= '<ul class="dropdown-menu megamenu"><li><div class="megamenu-wrap">';
		$item_output .= get_post_field( 'post_content', $mega_menu );
		$item_output .= '</div></li></ul>';
	}

	return $item_output;
}
add_filter( 'walker_nav_menu_start_el', 'smm_mega_menu_output', 99, 2 );
