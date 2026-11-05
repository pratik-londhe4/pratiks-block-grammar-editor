<?php
/**
 * Settings page tests.
 *
 * @package PratikLondhe\PratiksBlockGrammarEditor\Tests\Unit
 */

namespace PratikLondhe\PratiksBlockGrammarEditor\Tests\Unit;

use Brain\Monkey\Actions;
use Brain\Monkey\Filters;
use Brain\Monkey\Functions;
use PratikLondhe\PratiksBlockGrammarEditor\Settings\SettingsPage;

/**
 * Tests settings registration, options defaults, and sanitization.
 */
class SettingsPageTest extends TestCase {

	public function test_register_hooks(): void {
		$page = new SettingsPage();

		Actions\expectAdded( 'admin_menu' )->once();
		Actions\expectAdded( 'admin_init' )->once();
		Filters\expectAdded( 'plugin_action_links_' . plugin_basename( \PratikLondhe\PratiksBlockGrammarEditor\PLUGIN_FILE ) )->once();

		$page->register();
	}

	public function test_get_defaults(): void {
		$page     = new SettingsPage();
		$defaults = $page->get_defaults();

		$this->assertSame( 'edit_posts', $defaults['required_capability'] );
		$this->assertSame( 0, $defaults['auto_format_attributes'] );
		$this->assertSame( 1, $defaults['enable_copy_button'] );
	}

	public function test_sanitize_settings(): void {
		$page = new SettingsPage();

		$clean = $page->sanitize_settings(
			array(
				'required_capability'    => 'manage_options',
				'auto_format_attributes' => '1',
				'enable_copy_button'     => '1',
			)
		);

		$this->assertSame( 'manage_options', $clean['required_capability'] );
		$this->assertSame( 1, $clean['auto_format_attributes'] );
		$this->assertSame( 1, $clean['enable_copy_button'] );

		// Fallback for invalid capability.
		$fallback = $page->sanitize_settings(
			array(
				'required_capability' => 'invalid_capability',
			)
		);
		$this->assertSame( 'edit_posts', $fallback['required_capability'] );
	}

	public function test_add_action_links(): void {
		$page  = new SettingsPage();
		$links = $page->add_action_links( array() );

		$this->assertCount( 1, $links );
		$this->assertStringContainsString( 'Settings', $links[0] );
		$this->assertStringContainsString( 'options-general.php?page=pratiks-block-grammar-editor', $links[0] );
	}
}
