<?php
/**
 * Abilities API integration for WordPress 6.9+ and MCP.
 *
 * @package PratikLondhe\PratiksBlockGrammarEditor
 */

namespace PratikLondhe\PratiksBlockGrammarEditor;

use WP_Error;

defined( 'ABSPATH' ) || exit;

/**
 * Registers Abilities API tools for AI agents and REST clients.
 */
class Abilities implements Service {

	public const CATEGORY = 'pratiks-block-grammar-editor';

	/**
	 * Registers service hooks.
	 */
	public function register(): void {
		add_action( 'wp_abilities_api_categories_init', array( $this, 'register_category' ) );
		add_action( 'wp_abilities_api_init', array( $this, 'register_abilities' ) );
	}

	/**
	 * Registers the ability category.
	 */
	public function register_category(): void {
		if ( ! function_exists( 'wp_register_ability_category' ) ) {
			return;
		}

		wp_register_ability_category(
			self::CATEGORY,
			array(
				'label'       => __( 'Block Grammar Editor', 'pratiks-block-grammar-editor' ),
				'description' => __( 'Tools to inspect, parse, and validate isolated Gutenberg block grammar.', 'pratiks-block-grammar-editor' ),
			)
		);
	}

	/**
	 * Registers plugin abilities.
	 */
	public function register_abilities(): void {
		if ( ! function_exists( 'wp_register_ability' ) ) {
			return;
		}

		wp_register_ability(
			self::CATEGORY . '/parse-grammar',
			array(
				'category'            => self::CATEGORY,
				'label'               => __( 'Parse Block Grammar', 'pratiks-block-grammar-editor' ),
				'description'         => __( 'Parses raw Gutenberg block comment grammar into structured block representations, attributes, and inner blocks.', 'pratiks-block-grammar-editor' ),
				'input_schema'        => array(
					'type'                 => 'object',
					'required'             => array( 'grammar' ),
					'properties'           => array(
						'grammar' => array(
							'type'        => 'string',
							'description' => __( 'Raw Gutenberg block comment grammar string.', 'pratiks-block-grammar-editor' ),
						),
					),
					'additionalProperties' => false,
				),
				'output_schema'       => array(
					'type'       => 'object',
					'required'   => array( 'blocks', 'count' ),
					'properties' => array(
						'blocks' => array(
							'type'  => 'array',
							'items' => array( 'type' => 'object' ),
						),
						'count'  => array( 'type' => 'integer' ),
					),
				),
				'permission_callback' => array( $this, 'check_permission' ),
				'execute_callback'    => array( $this, 'execute_parse_grammar' ),
				'meta'                => array(
					'annotations'  => array( 'readonly', 'idempotent' ),
					'show_in_rest' => true,
					'public'       => true,
					'mcp'          => array( 'public' => true ),
				),
			)
		);

		wp_register_ability(
			self::CATEGORY . '/validate-grammar',
			array(
				'category'            => self::CATEGORY,
				'label'               => __( 'Validate Block Grammar', 'pratiks-block-grammar-editor' ),
				'description'         => __( 'Validates that a Gutenberg block grammar string conforms to block comment delimiters and JSON attribute syntax.', 'pratiks-block-grammar-editor' ),
				'input_schema'        => array(
					'type'                 => 'object',
					'required'             => array( 'grammar' ),
					'properties'           => array(
						'grammar' => array(
							'type'        => 'string',
							'description' => __( 'The block grammar string to validate.', 'pratiks-block-grammar-editor' ),
						),
					),
					'additionalProperties' => false,
				),
				'output_schema'       => array(
					'type'       => 'object',
					'required'   => array( 'is_valid', 'message', 'block_count' ),
					'properties' => array(
						'is_valid'    => array( 'type' => 'boolean' ),
						'message'     => array( 'type' => 'string' ),
						'block_count' => array( 'type' => 'integer' ),
					),
				),
				'permission_callback' => array( $this, 'check_permission' ),
				'execute_callback'    => array( $this, 'execute_validate_grammar' ),
				'meta'                => array(
					'annotations'  => array( 'readonly', 'idempotent' ),
					'show_in_rest' => true,
					'public'       => true,
					'mcp'          => array( 'public' => true ),
				),
			)
		);
	}

	/**
	 * Checks user permission.
	 *
	 * @return bool True if permitted.
	 */
	public function check_permission(): bool {
		$settings   = (array) get_option( 'pratiks_block_grammar_editor_settings', array() );
		$cap        = ! empty( $settings['required_capability'] ) ? sanitize_key( $settings['required_capability'] ) : 'edit_posts';
		$capability = apply_filters( 'pratiks_block_grammar_editor_capability', $cap );

		return current_user_can( $capability );
	}

	/**
	 * Executes parse-grammar ability.
	 *
	 * @param array<string, mixed> $input Input arguments.
	 * @return array<string, mixed> Output data.
	 */
	public function execute_parse_grammar( array $input ): array {
		$grammar = (string) ( $input['grammar'] ?? '' );
		$blocks  = parse_blocks( $grammar );

		$filtered = array();
		foreach ( $blocks as $block ) {
			if ( ! empty( $block['blockName'] ) || '' !== trim( $block['innerHTML'] ) ) {
				$filtered[] = $block;
			}
		}

		return array(
			'blocks' => $filtered,
			'count'  => count( $filtered ),
		);
	}

	/**
	 * Executes validate-grammar ability.
	 *
	 * @param array<string, mixed> $input Input arguments.
	 * @return array<string, mixed> Validation result.
	 */
	public function execute_validate_grammar( array $input ): array {
		$grammar = trim( (string) ( $input['grammar'] ?? '' ) );

		if ( '' === $grammar ) {
			return array(
				'is_valid'    => false,
				'message'     => __( 'Grammar content cannot be empty.', 'pratiks-block-grammar-editor' ),
				'block_count' => 0,
			);
		}

		if ( 0 !== strpos( $grammar, '<!-- wp:' ) ) {
			return array(
				'is_valid'    => false,
				'message'     => __( 'Grammar must start with a Gutenberg <!-- wp:... --> comment marker.', 'pratiks-block-grammar-editor' ),
				'block_count' => 0,
			);
		}

		$blocks = parse_blocks( $grammar );
		$valid  = 0;
		foreach ( $blocks as $block ) {
			if ( ! empty( $block['blockName'] ) ) {
				++$valid;
			}
		}

		if ( 0 === $valid ) {
			return array(
				'is_valid'    => false,
				'message'     => __( 'No valid blocks detected in grammar.', 'pratiks-block-grammar-editor' ),
				'block_count' => 0,
			);
		}

		return array(
			'is_valid'    => true,
			'message'     => sprintf(
				/* translators: %d: number of blocks */
				__( 'Valid block grammar with %d block(s).', 'pratiks-block-grammar-editor' ),
				$valid
			),
			'block_count' => $valid,
		);
	}
}
