<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/**
 * Adds WooCommerce support to this plugin
 */
class POC_FB_Pixel_JigoShop {
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

		if ( function_exists( 'is_jigoshop' ) && ! is_jigoshop() ) return $conditions;

		$integration = array();
		if ( function_exists( 'is_shop' ) && is_shop() ) { $integration[] = 'js-shop_page'; }
		if ( function_exists( 'is_product_category' ) && is_product_category() ) { $integration[] = 'js-product_category'; }
		if ( function_exists( 'is_product_tag' ) && is_product_tag() ) { $integration[] = 'js-product_tag'; }
		if ( function_exists( 'is_cart' ) && is_cart() ) { $integration[] = 'js-cart'; }
		if ( function_exists( 'is_checkout' ) && is_checkout() ) { $integration[] = 'js-checkout'; }
		if ( function_exists( 'is_account' ) && is_account() ) { $integration[] = 'js-account'; }
		if ( function_exists( 'is_order_tracker' ) && is_order_tracker() ) { $integration[] = 'js-order_tracker'; }

		if ( function_exists( 'is_product' ) && is_product() ) {
			$integration[] = 'js-product';

			$categories = get_the_terms( $post->ID, 'product_cat' );

			if ( ! is_wp_error( $categories ) && is_array( $categories ) && ( count( $categories ) > 0 ) ) {
				foreach ( $categories as $k => $v ) {
					$integration[] = 'in-term-' . esc_attr( $v->term_id );
				}
			}

			$tags = get_the_terms( $post->ID, 'product_tag' );

			if ( ! is_wp_error( $tags ) && is_array( $tags ) && ( count( $tags ) > 0 ) ) {
				foreach ( $tags as $k => $v ) {
					$integration[] = 'in-term-' . esc_attr( $v->term_id );
				}
			}

		}

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
		$headings['jigoshop'] = __( 'JigoShop', 'poc-fb-pixel' );

		return $headings;
	}

	/**
	 * Register the integration's conditions reference for the meta box.
	 *
	 * @param  array $headings The existing array of conditions.
	 * @return array           The modified array of conditions.
	 */
	public function register_conditions_reference( $conditions ) {
		$conditions['jigoshop'] = array();

		$conditions['jigoshop']['js-shop_page'] = array(
			'label'       => __( 'Shop Page', 'poc-fb-pixel' ),
			'description' => __( 'The JigoShop "Shop" landing page', 'poc-fb-pixel' )
		);

		$conditions['jigoshop']['js-product_category'] = array(
			'label'       => __( 'Product Categories', 'poc-fb-pixel' ),
			'description' => __( 'All product categories', 'poc-fb-pixel' )
		);

		$conditions['jigoshop']['js-product_tag'] = array(
			'label'       => __( 'Product Tags', 'poc-fb-pixel' ),
			'description' => __( 'All product tags', 'poc-fb-pixel' )
		);

		$conditions['jigoshop']['js-product'] = array(
			'label'       => __( 'Products', 'poc-fb-pixel' ),
			'description' => __( 'All products', 'poc-fb-pixel' )
		);

		$conditions['jigoshop']['js-cart'] = array(
			'label'       => __( 'Cart Page', 'poc-fb-pixel' ),
			'description' => __( 'The JigoShop "Cart" page', 'poc-fb-pixel' )
		);

		$conditions['jigoshop']['js-checkout'] = array(
			'label'       => __( 'Checkout Page', 'poc-fb-pixel' ),
			'description' => __( 'The JigoShop "Checkout" page', 'poc-fb-pixel' )
		);

		$conditions['jigoshop']['js-account'] = array(
			'label'       => __( 'Account Pages', 'poc-fb-pixel' ),
			'description' => __( 'The JigoShop "Account" pages', 'poc-fb-pixel' )
		);

		$conditions['jigoshop']['js-order_tracker'] = array(
			'label'       => __( 'Order tracker Pages', 'poc-fb-pixel' ),
			'description' => __( 'The JigoShop "Order Tracker" pages', 'poc-fb-pixel' )
		);

		// Setup terminologies for the "in category" and "tagged with" conditions.
		$terminologies = array(
			'taxonomy-product_cat' => __( 'Products in the "%s" category', 'poc-fb-pixel' ),
			'taxonomy-product_tag' => __( 'Products tagged "%s"', 'poc-fb-pixel' )
		);

		foreach ( $terminologies as $k => $v ) {
			if( ! isset( $conditions[ $k ] ) ) continue;
			foreach ( $conditions[ $k ] as $i => $j ) {
				$conditions[ $k ][ 'in-' . $i ] = array( 'label' => sprintf( $terminologies[ $k ], $j['label'] ), 'description' => sprintf( $terminologies[ $k ], $j['label'] ) );
			}
		}

		return $conditions;
	}
}

// Initialise the integration.
new POC_FB_Pixel_JigoShop();