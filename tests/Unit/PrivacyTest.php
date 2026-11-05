<?php
/**
 * Privacy tests.
 *
 * @package PratikLondhe\PratiksBlockGrammarEditor\Tests\Unit
 */

namespace PratikLondhe\PratiksBlockGrammarEditor\Tests\Unit;

use Brain\Monkey\Actions;
use Brain\Monkey\Filters;
use Brain\Monkey\Functions;
use PratikLondhe\PratiksBlockGrammarEditor\Privacy;

/**
 * Tests privacy policy content and data exporter/eraser registrations.
 */
class PrivacyTest extends TestCase {

	public function test_register_hooks(): void {
		$privacy = new Privacy();

		Actions\expectAdded( 'admin_init' )->once();
		Filters\expectAdded( 'wp_privacy_personal_data_exporters' )->once();
		Filters\expectAdded( 'wp_privacy_personal_data_erasers' )->once();

		$privacy->register();
	}

	public function test_add_privacy_policy_content(): void {
		$privacy = new Privacy();

		Functions\when( 'wp_kses_post' )->returnArg();
		Functions\when( 'wpautop' )->returnArg();

		$privacy->add_privacy_policy_content();
		$this->assertTrue( true );
	}

	public function test_export_and_erase_callbacks(): void {
		$privacy = new Privacy();

		$exporters = $privacy->register_exporter( array() );
		$this->assertArrayHasKey( 'pratiks-block-grammar-editor', $exporters );

		$exported = $privacy->export_personal_data( 'user@example.com' );
		$this->assertTrue( $exported['done'] );
		$this->assertEmpty( $exported['data'] );

		$erasers = $privacy->register_eraser( array() );
		$this->assertArrayHasKey( 'pratiks-block-grammar-editor', $erasers );

		$erased = $privacy->erase_personal_data( 'user@example.com' );
		$this->assertTrue( $erased['done'] );
		$this->assertFalse( $erased['items_removed'] );
	}
}
