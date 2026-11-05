<?php
/**
 * WP-CLI integration.
 *
 * @package PratikLondhe\PratiksBlockGrammarEditor\Cli
 */

namespace PratikLondhe\PratiksBlockGrammarEditor\Cli;

use PratikLondhe\PratiksBlockGrammarEditor\Service;
use WP_CLI;

defined( 'ABSPATH' ) || exit;

/**
 * Commands for inspecting and validating Gutenberg block grammar.
 */
class Command implements Service {

	/**
	 * Registers WP-CLI commands if running in CLI.
	 */
	public function register(): void {
		if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
			return;
		}

		WP_CLI::add_command( 'pratiks-block-grammar-editor', $this );
	}

	/**
	 * Inspects block grammar of a WordPress post.
	 *
	 * ## OPTIONS
	 *
	 * <post-id>
	 * : The ID of the post to inspect.
	 *
	 * [--block=<index>]
	 * : Zero-based index of a specific block to inspect.
	 *
	 * [--format=<format>]
	 * : Output format: table, json, raw.
	 * ---
	 * default: table
	 * options:
	 *   - table
	 *   - json
	 *   - raw
	 * ---
	 *
	 * ## EXAMPLES
	 *
	 *     wp pratiks-block-grammar-editor inspect 123
	 *     wp pratiks-block-grammar-editor inspect 123 --block=0 --format=raw
	 *
	 * @param array<int, string>    $args Positional arguments.
	 * @param array<string, string> $assoc_args Associative arguments.
	 */
	public function inspect( array $args, array $assoc_args ): void {
		$post_id = (int) $args[0];
		$post    = get_post( $post_id );

		if ( ! $post ) {
			WP_CLI::error( sprintf( 'Post %d not found.', $post_id ) );
			return;
		}

		$blocks = parse_blocks( $post->post_content );
		// Filter out empty whitespace blocks.
		$filtered_blocks = array();
		foreach ( $blocks as $block ) {
			if ( ! empty( $block['blockName'] ) || '' !== trim( $block['innerHTML'] ) ) {
				$filtered_blocks[] = $block;
			}
		}

		$format = isset( $assoc_args['format'] ) ? $assoc_args['format'] : 'table';

		if ( isset( $assoc_args['block'] ) ) {
			$index = (int) $assoc_args['block'];
			if ( ! isset( $filtered_blocks[ $index ] ) ) {
				WP_CLI::error( sprintf( 'Block index %d does not exist. Total blocks: %d.', $index, count( $filtered_blocks ) ) );
				return;
			}

			$target = $filtered_blocks[ $index ];
			if ( 'raw' === $format ) {
				WP_CLI::line( serialize_block( $target ) );
				return;
			}

			if ( 'json' === $format ) {
				WP_CLI::line( (string) wp_json_encode( $target, JSON_PRETTY_PRINT ) );
				return;
			}

			$block_name = ! empty( $target['blockName'] ) ? $target['blockName'] : '(Classic HTML)';
			WP_CLI::line( sprintf( 'Block Name: %s', $block_name ) );
			WP_CLI::line( sprintf( 'Attributes: %s', wp_json_encode( $target['attrs'] ) ) );
			WP_CLI::line( sprintf( 'Inner Blocks: %d', count( $target['innerBlocks'] ) ) );
			WP_CLI::line( '--- Raw Grammar ---' );
			WP_CLI::line( serialize_block( $target ) );
			return;
		}

		if ( 'json' === $format ) {
			WP_CLI::line( (string) wp_json_encode( $filtered_blocks, JSON_PRETTY_PRINT ) );
			return;
		}

		if ( 'raw' === $format ) {
			foreach ( $filtered_blocks as $idx => $block ) {
				$name = ! empty( $block['blockName'] ) ? $block['blockName'] : 'core/freeform';
				WP_CLI::line( sprintf( '/* Block %d: %s */', $idx, $name ) );
				WP_CLI::line( serialize_block( $block ) );
			}
			return;
		}

		$items = array();
		foreach ( $filtered_blocks as $idx => $block ) {
			$items[] = array(
				'index'        => $idx,
				'name'         => ! empty( $block['blockName'] ) ? $block['blockName'] : '(Classic HTML)',
				'attrs_count'  => count( $block['attrs'] ),
				'inner_blocks' => count( $block['innerBlocks'] ),
			);
		}

		WP_CLI\Utils\format_items( 'table', $items, array( 'index', 'name', 'attrs_count', 'inner_blocks' ) );
	}

	/**
	 * Validates a block grammar string or file.
	 *
	 * ## OPTIONS
	 *
	 * <grammar>
	 * : Raw block grammar string or path to a file containing grammar.
	 *
	 * ## EXAMPLES
	 *
	 *     wp pratiks-block-grammar-editor validate "<!-- wp:paragraph --><p>Hi</p><!-- /wp:paragraph -->"
	 *
	 * @param array<int, string> $args Positional arguments.
	 */
	public function validate( array $args ): void {
		$input = $args[0];

		if ( file_exists( $input ) && is_readable( $input ) ) {
			// phpcs:ignore WordPressVIPMinimum.Performance.FetchingRemoteData.FileGetContentsUnknown
			$content = (string) file_get_contents( $input );
		} else {
			$content = $input;
		}

		$content = trim( $content );

		if ( '' === $content ) {
			WP_CLI::error( 'Grammar input is empty.' );
			return;
		}

		if ( 0 !== strpos( $content, '<!-- wp:' ) ) {
			WP_CLI::error( 'Invalid grammar: content does not begin with a Gutenberg <!-- wp:... --> marker.' );
			return;
		}

		$parsed = parse_blocks( $content );
		if ( empty( $parsed ) ) {
			WP_CLI::error( 'Failed to parse any valid blocks from the input grammar.' );
			return;
		}

		$valid_count = 0;
		foreach ( $parsed as $block ) {
			if ( ! empty( $block['blockName'] ) ) {
				++$valid_count;
			}
		}

		WP_CLI::success( sprintf( 'Valid Gutenberg block grammar (%d block(s) detected).', $valid_count ) );
	}
}
