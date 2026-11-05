<?php
/**
 * Editor assets tests.
 *
 * @package PratikLondhe\PratiksBlockGrammarEditor\Tests\Unit
 */

namespace PratikLondhe\PratiksBlockGrammarEditor\Tests\Unit;

use Brain\Monkey\Actions;
use Brain\Monkey\Filters;
use Brain\Monkey\Functions;
use PratikLondhe\PratiksBlockGrammarEditor\Editor\Assets;

/**
 * Tests block editor asset registration and enqueueing.
 */
class AssetsTest extends TestCase {

	public function test_register_hooks(): void {
		$assets = new Assets();
		Actions\expectAdded( 'enqueue_block_editor_assets' )->once();

		$assets->register();
	}

	public function test_enqueue_assets_for_authorized_user(): void {
		$assets = new Assets();

		Functions\when( 'current_user_can' )->justReturn( true );
		Functions\when( 'plugin_dir_url' )->justReturn( 'https://example.com/wp-content/plugins/pratiks-block-grammar-editor/' );
		Functions\when( 'wp_register_script' )->justReturn( true );
		Functions\when( 'wp_add_inline_script' )->justReturn( true );
		Functions\when( 'wp_set_script_translations' )->justReturn( true );
		Functions\when( 'wp_enqueue_script' )->justReturn( true );
		Functions\when( 'wp_enqueue_style' )->justReturn( true );

		$assets->enqueue_assets();

		$this->assertSame( 'edit_posts', $assets->get_required_capability() );
	}

	public function test_enqueue_assets_aborts_for_unauthorized_user(): void {
		$assets = new Assets();

		Functions\when( 'current_user_can' )->justReturn( false );
		Functions\expect( 'wp_enqueue_script' )->never();

		$assets->enqueue_assets();
	}

	public function test_capability_filter_applies(): void {
		$assets = new Assets();

		Filters\expectApplied( 'pratiks_block_grammar_editor_capability' )
			->once()
			->with( 'edit_posts' )
			->andReturn( 'custom_capability' );

		Functions\when( 'current_user_can' )
			->alias(
				function ( $cap ) {
					return 'custom_capability' === $cap;
				}
			);

		Functions\when( 'plugin_dir_url' )->justReturn( 'https://example.com/wp-content/plugins/pratiks-block-grammar-editor/' );
		Functions\when( 'wp_register_script' )->justReturn( true );
		Functions\when( 'wp_add_inline_script' )->justReturn( true );
		Functions\when( 'wp_set_script_translations' )->justReturn( true );
		Functions\when( 'wp_enqueue_script' )->justReturn( true );
		Functions\when( 'wp_enqueue_style' )->justReturn( true );

		$assets->enqueue_assets();
	}
}
