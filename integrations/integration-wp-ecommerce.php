<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/**
 * Adds WooCommerce support to this plugin
 */
class POC_FB_Pixel_WP_eCommerce {
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
		if ( function_exists( 'wpsc_is_checkout' ) && wpsc_is_checkout() ) { $integration[] = 'wp-ec-checkout'; }
		if ( function_exists( 'is_wpsc_profile_page' ) && is_wpsc_profile_page() ) { $integration[] = 'wp-ec-account'; }

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
		$headings['wp-ecommerce'] = __( 'WP eCommerce', 'poc-fb-pixel' );

		return $headings;
	}

	/**
	 * Register the integration's conditions reference for the meta box.
	 *
	 * @param  array $headings The existing array of conditions.
	 * @return array           The modified array of conditions.
	 */
	public function register_conditions_reference( $conditions ) {
		$conditions['wp-ecommerce'] = array();


		$conditions['wp-ecommerce']['wp-ec-checkout'] = array(
			'label'       => __( 'Checkout Page', 'poc-fb-pixel' ),
			'description' => __( 'The WP eCommerce "Checkout" page', 'poc-fb-pixel' )
		);

		$conditions['wp-ecommerce']['wp-ec-account'] = array(
			'label'       => __( 'Account Pages', 'poc-fb-pixel' ),
			'description' => __( 'The WP eCommerce "Account" pages', 'poc-fb-pixel' )
		);

		return $conditions;
	}
}

// Initialise the integration.
new POC_FB_Pixel_WP_eCommerce();