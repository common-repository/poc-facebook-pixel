<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/**
 * Adds WooCommerce support to this plugin
 */
class POC_FB_Pixel_BuddyPress {
	/**
	 * An array of WC taxonomies
	 *
	 * @access private
	 * @var array
	 */
	private $taxonomies = array();

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_filter( 'poc_fb_pixel_conditions', array( $this, 'register_conditions' ) );
		add_filter( 'poc_fb_pixel_conditions_headings', array( $this, 'register_conditions_headings' ) );
		add_filter( 'poc_fb_pixel_conditions_reference', array( $this, 'register_conditions_reference' ) );
	}

	/**
	 * Register the integration conditions with POC FB Pixel.
	 *
	 * @param  array $conditions The existing array of conditions.
	 * @return array             The modified array of conditions.
	 */
	public function register_conditions( $conditions ) {
		global $post;

		$integration = array();

		if ( function_exists( 'bp_is_user' ) && bp_is_user() ) { $integration[] = 'bp-user'; }
		if ( function_exists( 'bp_is_group' ) && bp_is_group() ) { $integration[] = 'bp-group'; }
		if ( function_exists( 'bp_is_user_messages' ) && bp_is_user_messages() ) { $integration[] = 'bp-user_messages'; }
		if ( function_exists( 'bp_is_register_page' ) && bp_is_register_page() ) { $integration[] = 'bp-register'; }
		if ( function_exists( 'bp_is_single_item' ) && bp_is_single_item() ) { $integration[] = 'bp-single_item'; }

		$integration[] = $conditions[ count( $conditions ) - 1 ];

		array_splice( $conditions, count( $conditions ), 0, $integration );

		return $conditions;
	}

	/**
	 * Register the integration's headings for the meta box.
	 *
	 * @param  array $headings The existing array of headings.
	 * @return array           The modified array of headings.
	 */
	public function register_conditions_headings( $headings ) {
		$headings['buddypress'] = __( 'BuddyPress', 'poc-fb-pixel' );

		return $headings;
	}

	/**
	 * Register the integration's conditions reference for the meta box.
	 *
	 * @param  array $headings The existing array of conditions.
	 * @return array           The modified array of conditions.
	 */
	public function register_conditions_reference( $conditions ) {
		$conditions['buddypress'] = array();

		$conditions['buddypress']['bp-user'] = array(
			'label'       => __( 'User', 'poc-fb-pixel' ),
			'description' => __( 'All user pages.', 'poc-fb-pixel' )
		);

		$conditions['buddypress']['bp-group'] = array(
			'label'       => __( 'Group', 'poc-fb-pixel' ),
			'description' => __( 'All group pages.', 'poc-fb-pixel' )
		);

		$conditions['buddypress']['bp-user_messages'] = array(
			'label'       => __( 'User messages', 'poc-fb-pixel' ),
			'description' => __( 'All user messages pages.', 'poc-fb-pixel' )
		);

		$conditions['buddypress']['bp-register'] = array(
			'label'       => __( 'Register', 'poc-fb-pixel' ),
			'description' => __( 'The register page.', 'poc-fb-pixel' )
		);

		$conditions['buddypress']['bp-single_item'] = array(
			'label'       => __( 'Single item', 'poc-fb-pixel' ),
			'description' => __( 'A single item page (user, group, etc.).', 'poc-fb-pixel' )
		);

		return $conditions;
	}
}

// Initialise the integration.
new POC_FB_Pixel_BuddyPress();