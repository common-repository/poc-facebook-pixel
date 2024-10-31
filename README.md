# POC Facebook Pixel

[![Built with Grunt](https://cdn.gruntjs.com/builtwith.png)](http://gruntjs.com/) [![Support us](http://img.shields.io/gittip/SiR-DanieL.svg)](https://www.gittip.com/SiR-DanieL/)

With POC Facebook Pixel, it's possible to add Facebook pixel codes according to a context (for example, a specific page, a specific blog post, certain categories or the search results screen).

Looking to contribute code to this plugin? [Fork the repository over at GitHub](https://github.com/PinchOfCode/poc-facebook-pixel). Please also read the CONTRIBUTING.md file, bundled within this plugin.

**This plugin is compatible with**
* [WooCommerce](http://www.woothemes.com/woocommerce/)
* [JigoShop](https://www.jigoshop.com/)
* [WP eCommerce](http://getshopped.org/)
* [bbPress](http://bbpress.org/)
* [BuddyPress](http://buddypress.org/)

### Thanks

Thanks to [WooThemes](http://woothemes.com) and [WooSidebars](https://github.com/woothemes/woosidebars) which i used as start base.

## Installation

### Automatic installation

Automatic installation is the easiest option as WordPress handles the file transfers itself and you don’t even need to leave your web browser. To do an automatic install of POC Facebook Pixel, log in to your WordPress admin panel, navigate to the Plugins menu and click Add New.

In the search field type "POC Facebook Pixel" and click Search Plugins. Once you’ve found our widget areas plugin you can view details about it such as the the point release, rating and description. Most importantly of course, you can install it by simply clicking Install Now. After clicking that link you will be asked if you’re sure you want to install the plugin. Click yes and WordPress will automatically complete the installation.

### Manual installation

The manual installation method involves downloading POC Facebook Pixel and uploading it to your webserver via your favourite FTP application.

1. Download the plugin file to your computer and unzip it
2. Using an FTP program, or your hosting control panel, upload the unzipped plugin folder to your WordPress installation’s wp-content/plugins/ directory.
3. Activate the plugin from the Plugins menu within the WordPress admin.

### Where to go after installation

Once POC Facebook Pixel has been installed and activated, please visit the "Appearance > Pixel Codes" screen to begin adding pixel codes.

### Upgrading

Automatic updates should work a charm; as always though, ensure you backup your site just in case.

## Frequently Asked Questions

### Will POC Facebook Pixel work with my theme?

Yes; POC Facebook Pixel will work with any theme that supports wp_head hook.

### How can I contribute to POC Facebook Pixel development?

Looking to contribute code to this plugin? [Fork the repository over at GitHub](https://github.com/PinchOfCode/poc-facebook-pixel) and submit a pull request with your improvements.

## Changelog

### 1.0.1
* Add: bbPress compatibility.
* Add: BuddyPress compatibility.
* Add: JigoShop compatibility.
* Add: WP eCommerce compatibility.
* Fix: Check if WooCommerce is active before to include the integration class.

### 1.0.0
* First release