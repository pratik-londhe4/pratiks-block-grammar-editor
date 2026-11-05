<?php
/**
 * Privacy and personal data integration.
 *
 * @package PratikLondhe\PratiksBlockGrammarEditor
 */

namespace PratikLondhe\PratiksBlockGrammarEditor;

defined( 'ABSPATH' ) || exit;

/**
 * Handles privacy policy content and core data exporter/eraser hooks.
 */
class Privacy implements Service {

	/**
	 * Registers service hooks.
	 */
	public function register(): void {
		add_action( 'admin_init', array( $this, 'add_privacy_policy_content' ) );
		add_filter( 'wp_privacy_personal_data_exporters', array( $this, 'register_exporter' ) );
		add_filter( 'wp_privacy_personal_data_erasers', array( $this, 'register_eraser' ) );
	}

	/**
	 * Adds privacy policy text suggestions.
	 */
	public function add_privacy_policy_content(): void {
		if ( ! function_exists( 'wp_add_privacy_policy_content' ) ) {
			return;
		}

		$content = __(
			'Block Grammar Editor operates entirely within the WordPress block editor and server. It does not collect, transmit, or store any personal data.',
			'pratiks-block-grammar-editor'
		);

		wp_add_privacy_policy_content(
			__( 'Block Grammar Editor', 'pratiks-block-grammar-editor' ),
			wp_kses_post( wpautop( $content, false ) )
		);
	}

	/**
	 * Registers personal data exporter.
	 *
	 * @param array<string, mixed> $exporters Registered exporters.
	 * @return array<string, mixed> Updated exporters.
	 */
	public function register_exporter( array $exporters ): array {
		$exporters['pratiks-block-grammar-editor'] = array(
			'exporter_friendly_name' => __( 'Block Grammar Editor Data', 'pratiks-block-grammar-editor' ),
			'callback'               => array( $this, 'export_personal_data' ),
		);
		return $exporters;
	}

	/**
	 * Exports personal data (none stored).
	 *
	 * @param string $email_address User email address.
	 * @param int    $page          Pagination page.
	 * @return array<string, mixed> Export results.
	 */
	public function export_personal_data( string $email_address, int $page = 1 ): array { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
		return array(
			'data' => array(),
			'done' => true,
		);
	}

	/**
	 * Registers personal data eraser.
	 *
	 * @param array<string, mixed> $erasers Registered erasers.
	 * @return array<string, mixed> Updated erasers.
	 */
	public function register_eraser( array $erasers ): array {
		$erasers['pratiks-block-grammar-editor'] = array(
			'eraser_friendly_name' => __( 'Block Grammar Editor Data', 'pratiks-block-grammar-editor' ),
			'callback'             => array( $this, 'erase_personal_data' ),
		);
		return $erasers;
	}

	/**
	 * Erases personal data (none stored).
	 *
	 * @param string $email_address User email address.
	 * @param int    $page          Pagination page.
	 * @return array<string, mixed> Eraser results.
	 */
	public function erase_personal_data( string $email_address, int $page = 1 ): array { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
		return array(
			'items_removed'  => false,
			'items_retained' => false,
			'messages'       => array(),
			'done'           => true,
		);
	}
}
