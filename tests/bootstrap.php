<?php
/**
 * PHPUnit bootstrap: the Composer autoloader plus the WordPress constants and
 * classes the plugin touches. WordPress functions are mocked per test with
 * Brain Monkey, so WordPress itself is not loaded.
 *
 * @package PratikLondhe\PratiksBlockGrammarEditor
 */

require dirname( __DIR__ ) . '/vendor/autoload.php';
require __DIR__ . '/Stubs/wordpress.php';

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/' );
defined( 'PratikLondhe\PratiksBlockGrammarEditor\PLUGIN_FILE' ) || define( 'PratikLondhe\PratiksBlockGrammarEditor\PLUGIN_FILE', dirname( __DIR__ ) . '/pratiks-block-grammar-editor.php' );
defined( 'PratikLondhe\PratiksBlockGrammarEditor\VERSION' ) || define( 'PratikLondhe\PratiksBlockGrammarEditor\VERSION', '0.1.0' );
