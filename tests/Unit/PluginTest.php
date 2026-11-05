<?php
/**
 * Plugin bootstrap tests.
 *
 * @package PratikLondhe\PratiksBlockGrammarEditor\Tests\Unit
 */

namespace PratikLondhe\PratiksBlockGrammarEditor\Tests\Unit;

use Brain\Monkey\Actions;
use PratikLondhe\PratiksBlockGrammarEditor\Plugin;

/**
 * Verifies plugin bootstrap and registration.
 */
class PluginTest extends TestCase {

	public function test_plugin_boots_and_fires_action(): void {
		Actions\expectDone( 'pratiks_block_grammar_editor_loaded' )->once();

		Plugin::boot();

		$this->assertInstanceOf( Plugin::class, Plugin::instance() );
	}
}
