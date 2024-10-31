<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/**
 * Adds WooCommerce support to this plugin
 */
class POC_FB_Pixel_bbPress {
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

		if ( function_exists( 'is_bbpress' ) && ! is_bbpress() ) return $conditions;

		$integration = array();
		if ( function_exists( 'bbp_is_forum_archive' ) && bbp_is_forum_archive() ) { $integration[] = 'bbpress-forum_archive'; }
		if ( function_exists( 'bbp_is_topic_archive' ) && bbp_is_topic_archive() ) { $integration[] = 'bbpress-topic_archive'; }
		if ( function_exists( 'bbp_is_topic_tag' ) && bbp_is_topic_tag() ) { $integration[] = 'bbpress-topic_tag'; }
		if ( function_exists( 'bbp_is_single_forum' ) && bbp_is_single_forum() ) { $integration[] = 'bbpress-single_forum'; }

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
		$headings['bbpress'] = __( 'bbPress', 'poc-fb-pixel' );

		return $headings;
	}

	/**
	 * Register the integration's conditions reference for the meta box.
	 *
	 * @param  array $headings The existing array of conditions.
	 * @return array           The modified array of conditions.
	 */
	public function register_conditions_reference( $conditions ) {
		$conditions['bbpress'] = array();

		$conditions['bbpress']['bbpress-forum_archive'] = array(
			'label'       => __( 'Forum archive', 'poc-fb-pixel' ),
			'description' => __( 'All forum archive pages', 'poc-fb-pixel' )
		);

		$conditions['bbpress']['bbpress-topic_archive'] = array(
			'label'       => __( 'Topic archive', 'poc-fb-pixel' ),
			'description' => __( 'All topic archive pages', 'poc-fb-pixel' )
		);

		$conditions['bbpress']['bbpress-topic_tag'] = array(
			'label'       => __( 'Topic tags', 'poc-fb-pixel' ),
			'description' => __( 'All topic tags', 'poc-fb-pixel' )
		);

		$conditions['bbpress']['bbpress-single_forum'] = array(
			'label'       => __( 'Single forum', 'poc-fb-pixel' ),
			'description' => __( 'All forums', 'poc-fb-pixel' )
		);

		return $conditions;
	}
}

// Initialise the integration.
new POC_FB_Pixel_bbPress();