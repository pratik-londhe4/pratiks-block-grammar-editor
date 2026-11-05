<?php
/**
 * Plugin Name:       Pratiks Block Grammar Editor
 * Plugin URI:        https://wordpress.org/plugins/pratiks-block-grammar-editor/
 * Description:       Inspect, copy, format, validate, and edit the Gutenberg block grammar of individual blocks directly in the block editor.
 * Version:           0.1.0
 * Requires at least: 6.6
 * Requires PHP:      7.4
 * Author:            Pratik Londhe
 * Author URI:        https://profiles.wordpress.org/pratiklondhe/
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       pratiks-block-grammar-editor
 *
 * @package PratikLondhe\PratiksBlockGrammarEditor
 */

namespace PratikLondhe\PratiksBlockGrammarEditor;

defined( 'ABSPATH' ) || exit;

const VERSION     = '0.1.0';
const PLUGIN_FILE = __FILE__;

// PSR-4: PratikLondhe\PratiksBlockGrammarEditor\Foo\Bar lives in src/Foo/Bar.php.
spl_autoload_register(
	static function ( $class_name ) {
		$prefix = __NAMESPACE__ . '\\';
		if ( 0 !== strpos( $class_name, $prefix ) ) {
			return;
		}

		$file = __DIR__ . '/src/' . str_replace( '\\', '/', substr( $class_name, strlen( $prefix ) ) ) . '.php';
		if ( is_readable( $file ) ) {
			require $file;
		}
	}
);

add_action( 'plugins_loaded', array( Plugin::class, 'boot' ) );
