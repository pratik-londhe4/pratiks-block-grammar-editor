<?php
/**
 * Block editor asset loader.
 *
 * @package PratikLondhe\PratiksBlockGrammarEditor\Editor
 */

namespace PratikLondhe\PratiksBlockGrammarEditor\Editor;

use PratikLondhe\PratiksBlockGrammarEditor\Service;
use const PratikLondhe\PratiksBlockGrammarEditor\VERSION;
use const PratikLondhe\PratiksBlockGrammarEditor\PLUGIN_FILE;

defined( 'ABSPATH' ) || exit;

/**
 * Enqueues JavaScript and CSS for the block editor.
 */
class Assets implements Service {

	/**
	 * Registers WordPress hooks.
	 */
	public function register(): void {
		add_action( 'enqueue_block_editor_assets', array( $this, 'enqueue_assets' ) );
	}

	/**
	 * Enqueues scripts and styles for authorized users.
	 */
	public function enqueue_assets(): void {
		$capability = $this->get_required_capability();
		/**
		 * Filters the required capability to use the Block Grammar Editor.
		 *
		 * @param string $capability The capability required.
		 */
		$capability = apply_filters( 'pratiks_block_grammar_editor_capability', $capability );

		if ( ! current_user_can( $capability ) ) {
			return;
		}

		$plugin_dir_url = plugin_dir_url( PLUGIN_FILE );

		wp_register_script(
			'pratiks-block-grammar-editor',
			$plugin_dir_url . 'assets/js/editor.js',
			array(
				'wp-blocks',
				'wp-element',
				'wp-components',
				'wp-block-editor',
				'wp-data',
				'wp-i18n',
				'wp-hooks',
				'wp-plugins',
			),
			VERSION,
			true
		);

		$settings = (array) get_option( 'pratiks_block_grammar_editor_settings', array() );

		$script_data = array(
			'canEdit'           => true,
			'autoFormat'        => ! empty( $settings['auto_format_attributes'] ),
			'enableCopy'        => ! isset( $settings['enable_copy_button'] ) || ! empty( $settings['enable_copy_button'] ),
			'defaultCapability' => $capability,
		);

		/**
		 * Filters the data passed to the editor script.
		 *
		 * @param array $script_data Localized script data.
		 */
		$script_data = apply_filters( 'pratiks_block_grammar_editor_script_data', $script_data );

		wp_add_inline_script(
			'pratiks-block-grammar-editor',
			'window.pratiksBlockGrammarEditorData = ' . wp_json_encode( $script_data ) . ';',
			'before'
		);

		wp_set_script_translations( 'pratiks-block-grammar-editor', 'pratiks-block-grammar-editor' );

		wp_enqueue_script( 'pratiks-block-grammar-editor' );

		wp_enqueue_style(
			'pratiks-block-grammar-editor',
			$plugin_dir_url . 'assets/css/editor.css',
			array( 'wp-components' ),
			VERSION
		);
	}

	/**
	 * Gets the required capability configured in settings.
	 *
	 * @return string Required capability.
	 */
	public function get_required_capability(): string {
		$settings = (array) get_option( 'pratiks_block_grammar_editor_settings', array() );
		$cap      = ! empty( $settings['required_capability'] ) ? sanitize_key( $settings['required_capability'] ) : 'edit_posts';

		if ( ! in_array( $cap, array( 'edit_posts', 'manage_options' ), true ) ) {
			$cap = 'edit_posts';
		}

		return $cap;
	}
}
