<?php
/**
 * Plugin Name: Create Pages on Multisite WordPress
 * Plugin URI: http://www.wpriders.com
 * Description: This plugin creates a new page an all multisite sites. If the Page exists, it will be updated with the new info (title/content). Optionally, you can set the new page as Blog/Homepage
 * Version: 1.0.2-beta.1
 * Author: Mihai Irodiu from WPRiders
 * Author URI: http://www.wpriders.com
 * License: GPL2
 */

if ( ! defined( 'ABSPATH' ) ) {
	header( 'Status: 403 Forbidden' );
	header( 'HTTP/1.1 403 Forbidden' );
	exit;
}

class WP_RidersMultisitePageAll {
	private $wpdb = null;

	function __construct( $wpdb ) {
		$this->wpdb = $wpdb;
		add_action( 'admin_menu', array( &$this, 'wpriders_add_menu' ) ); // Creating the menu in tools
		add_action( 'wp_ajax_buildpageblog', array(
			&$this,
			'process_the_actions'
		) ); // Ajax will send info to this function and the magic starts
	}

	function wpriders_add_menu() {
		add_management_page( __( "Create Pages on Multisite WordPress", 'wpriders-cpb' ), __( "Create Pages on Multisite WordPress", 'wpriders-cpb' ), 'manage_options', 'wprdiers-cpb-index', array(
			$this,
			'menu_goes_to'
		) );
	}

	function menu_goes_to() {
		require_once( plugin_dir_path( __FILE__ ) . 'inc/content.php' );
	}


	function process_the_actions() {
		@error_reporting( 0 ); // Don't break the JSON result;
		$send_success = false;
		//get info
		$blog_id      = (int) $_POST['blog_id'];
		$page_slug    = trim( $_POST['new_slug'] );
		$page_title   = trim( $_POST['new_title'] );
		$get_template = trim( $_POST['get_template'] );


		$page_content = stripslashes( urldecode( $_POST['new_content'] ) );
		$page_options = $_POST['cpb_option'];
		// decode
		$page_content = htmlspecialchars_decode( $page_content );
		$page_title   = htmlspecialchars_decode( $page_title );


		switch_to_blog( $blog_id );
		// it is a much more safe (from security perspective)
		$sql         = $this->wpdb->prepare( "SELECT ID FROM {$this->wpdb->posts} WHERE post_name='%s' AND ( post_type='page' OR post_type='post' )", $page_slug );
		$get_results = $this->wpdb->get_var( $sql );
		$get_results = (int) $get_results;
		if ( trim( $page_title ) == "" || trim( $page_slug ) == "" || trim( $page_content ) == "" ) {
			wp_send_json_error( 'The fields are mandatory, please fill Page title, Slug and Content' );
		}
		if ( $get_results == 0 ) {
			$args    = array(
				'post_content' => $page_content,
				'post_name'    => $page_slug,
				'post_title'   => $page_title,
				'post_status'  => 'publish',
				'post_type'    => 'page'
			);
			$post_id = wp_insert_post( $args, true );
			if ( ! is_wp_error( $post_id ) ) {
				$send_success = true;
				if ( $page_options == "set_home" ) {
					update_option( 'show_on_front', 'page', true );
					update_option( 'page_on_front', $post_id, true );
				} elseif ( $page_options == "set_blog" ) {
					update_option( 'show_on_front', 'page', true );
					update_option( 'page_for_posts', $post_id, true );
				}
				update_post_meta( $post_id, '_wp_page_template', $get_template );
				update_post_meta( $post_id, '_multisite_created_plugin', '1' );
				wp_send_json_success( 'For Blog ID ' . $blog_id . ' The page has been created with sucess' );
			} else {

				wp_send_json_error( 'Something went wrong, the page couldn\'t be created: ' . $args->get_error_message() );
			}
		} else {
			$args    = array(
				'ID'           => $get_results,
				'post_content' => $page_content,
				'post_name'    => $page_slug,
				'post_title'   => $page_title,
				'post_status'  => 'publish',
				'post_type'    => 'page'
			);
			$post_id = wp_insert_post( $args, true );
			if ( ! is_wp_error( $post_id ) ) {
				$send_success = true;
				if ( $page_options == "set_home" ) {
					update_option( 'show_on_front', 'page', true );
					update_option( 'page_on_front', $post_id, true );
				} elseif ( $page_options == "set_blog" ) {
					update_option( 'show_on_front', 'page', true );
					update_option( 'page_for_posts', $post_id, true );
				}
				update_post_meta( $post_id, '_wp_page_template', $get_template );
				update_post_meta( $post_id, '_multisite_created_plugin', '1' );
				wp_send_json_success( 'For Blog ID ' . $blog_id . ' The page has been updated with sucess' );
			} else {
				wp_send_json_error( 'Something went wrong, the page couldn\'t be created: ' . $args->get_error_message() );
			}
		}
		restore_current_blog();

		// These are fail safe messages ( if everything is alright, these messages should never be sent )
		if ( $send_success ) {
			wp_send_json_success( 'For Blog ID ' . $blog_id . ' The page has been created with sucess' );
		}
		wp_send_json_error( 'Something went wrong, try again' );
	}

	/*
	 * Get the list with all blogs
	 */
	function wpriders_gethomepageslist() {
		$sql         = "
		SELECT
			dom.blog_id,
			dom.path,
			dom.domain
		FROM
			`{$this->wpdb->base_prefix}blogs` AS dom
		ORDER BY domain ASC
		";
		$return_info = $this->wpdb->get_results( $sql );

		return $return_info;
	}

}

$wpriders_mgt = new WP_RidersMultisitePageAll( $wpdb );
