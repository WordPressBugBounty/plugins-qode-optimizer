<?php
/**
 * Implementation of general procedures
 *
 * @package Qode
 */

if ( ! defined( 'ABSPATH' ) ) {
	// Exit if accessed directly.
	exit;
}

class Qode_Optimizer_General {

	/**
	 * Initialization process
	 */
	public function init() {
		// Adds the bulk action to the admin menu.
		add_action( 'admin_menu', array( $this, 'admin_menu' ), 60 );

		// Handle the bulk actions from the media library.
		if ( Qode_Optimizer_User::is_admin() ) {
			add_filter( 'handle_bulk_actions-upload', array( $this, 'bulk_action_handler' ), 10, 3 );
		}
	}

	/**
	 * Adds various items to the admin menu.
	 */
	public function admin_menu() {
		// Adds bulk optimize to the media library menu.
		$permissions = apply_filters( 'qode_optimizer_bulk_permissions', '' );

		// Adds Bulk Optimize to the media library bulk actions.
		add_filter( 'bulk_actions-upload', array( $this, 'add_bulk_media_actions' ) );
	}

	/**
	 * Handles the bulk actions POST.
	 *
	 * @param string $sendback The redirect URL.
	 * @param string $doaction The action being taken.
	 * @param array  $post_ids The items to take the action on. Accepts an array of IDs
	 * @return string The URL to go back to when we are done handling the action.
	 */
	public function bulk_action_handler( $sendback, $doaction, $post_ids ) {
		if (
			! Qode_Optimizer_User::is_admin() ||
			empty( $doaction ) ||
			! in_array( $doaction, array( 'bulk_optimize', 'bulk_thumbnails_regenerate', 'bulk_restore' ), true )
		) {
			return $sendback;
		}
		// If there is no media to optimize, do nothing.
		if ( ! Qode_Optimizer_Utility::is_iterable( $post_ids ) ) {
			return $sendback;
		}
		check_admin_referer( 'bulk-media' );
		// Prep the attachment IDs for optimization.
		$ids = implode( ',', array_map( 'intval', $post_ids ) );

		if ( 'bulk_optimize' === $doaction ) {
			$admin_redirect_page = 'optimization';
		} elseif ( 'bulk_thumbnails_regenerate' === $doaction ) {
			$admin_redirect_page = 'regeneration';
		} elseif ( 'bulk_restore' === $doaction ) {
			$admin_redirect_page = 'restoration';
		} else {
			$admin_redirect_page = '';
		}

		return add_query_arg(
			array(
				'page'       => $admin_redirect_page,
				'ids'        => $ids,
				'qo_referer' => 'media_library',
				'qo_nonce'   => wp_create_nonce( 'qo-nonce' ),
			),
			admin_url( 'admin.php' )
		);
	}

	/**
	 * Add our bulk optimize action to the bulk actions drop-down menu.
	 *
	 * @param array $bulk_actions A list of actions available already.
	 * @return array The list of actions, with our bulk action included.
	 */
	public function add_bulk_media_actions( $bulk_actions ) {
		if (
			Qode_Optimizer_User::is_admin() &&
			is_array( $bulk_actions )
		) {
			$bulk_actions['bulk_optimize'] = __( 'QO Bulk Optimize', 'qode-optimizer' );

			// QO admin media options modifications.
			$bulk_actions = apply_filters( 'qode_optimizer_filter_modify_admin_media_actions', $bulk_actions );
		}
		return $bulk_actions;
	}
}
