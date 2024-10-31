<?php
/**
 * Plugin Name: POC Facebook Pixel
 * Plugin URI: http://woothemes.com/woosidebars/
 * Description: Adds Facebook pixel code in all your post types conditionally.
 * Author: Pinch Of Code <info@pinchofcode.com>
 * Author URI: http://pinchofcode.com
 * Version: 1.0.1
 *
 * License:  GPL-2
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * GitHub Plugin URI: https://github.com/PinchOfCode/poc-facebook-pixel/
 *
 * Text Domain: poc-fb-pixel
 * Domain Path: /i18n/
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/**
 * Adds Donate links in Plugins > Installed Plugins.
 *
 * @param array $links
 * @param string $file
 * @return array
 */
add_filter( 'plugin_action_links', 'poc_facebook_pixel_add_donate_link', 10, 4 );
function poc_facebook_pixel_add_donate_link( $links, $file ) {
    if( $file == plugin_basename( __FILE__ ) ) {
        $donate_link = '<a href="https://www.paypal.com/cgi-bin/webscr?cmd=_xclick&business=paypal@pinchofcode.com&item_name=Donation+for+Pinch+Of+Code" title="' . __( 'Donate', 'poc-fb-pixel' ) . '" target="_blank">' . __( 'Donate', 'poc-fb-pixel' ) . '</a>';
        array_unshift( $links, $donate_link );
    }

    return $links;
}

require_once( 'classes/class-poc-fb-pixel-conditions.php' );
require_once( 'classes/class-poc-fb-pixel.php' );

global $poc_fb_pixel;
$poc_fb_pixel = new POC_FB_Pixel();