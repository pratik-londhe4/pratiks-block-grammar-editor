<?php
/**
 * Abilities API unit tests.
 *
 * @package PratikLondhe\PratiksBlockGrammarEditor\Tests\Unit
 */

namespace PratikLondhe\PratiksBlockGrammarEditor\Tests\Unit;

use Brain\Monkey\Actions;
use Brain\Monkey\Functions;
use PratikLondhe\PratiksBlockGrammarEditor\Abilities;

/**
 * Tests Abilities API category and ability execution.
 */
class AbilitiesTest extends TestCase {

	public function test_register_hooks(): void {
		$abilities = new Abilities();

		Actions\expectAdded( 'wp_abilities_api_categories_init' )->once();
		Actions\expectAdded( 'wp_abilities_api_init' )->once();

		$abilities->register();
	}

	public function test_execute_parse_grammar(): void {
		$abilities = new Abilities();

		Functions\when( 'parse_blocks' )->alias(
			function ( $grammar ) {
				return array(
					array(
						'blockName'    => 'core/paragraph',
						'attrs'        => array( 'fontSize' => 'small' ),
						'innerBlocks'  => array(),
						'innerHTML'    => '<p>Hello world</p>',
						'innerContent' => array( '<p>Hello world</p>' ),
					),
				);
			}
		);

		$result = $abilities->execute_parse_grammar(
			array(
				'grammar' => '<!-- wp:paragraph {"fontSize":"small"} --><p>Hello world</p><!-- /wp:paragraph -->',
			)
		);

		$this->assertSame( 1, $result['count'] );
		$this->assertSame( 'core/paragraph', $result['blocks'][0]['blockName'] );
	}

	public function test_execute_validate_grammar_valid(): void {
		$abilities = new Abilities();

		Functions\when( 'parse_blocks' )->alias(
			function ( $grammar ) {
				return array(
					array(
						'blockName' => 'core/heading',
					),
				);
			}
		);

		$result = $abilities->execute_validate_grammar(
			array(
				'grammar' => '<!-- wp:heading --><h2>Title</h2><!-- /wp:heading -->',
			)
		);

		$this->assertTrue( $result['is_valid'] );
		$this->assertSame( 1, $result['block_count'] );
	}

	public function test_execute_validate_grammar_empty(): void {
		$abilities = new Abilities();

		$result = $abilities->execute_validate_grammar(
			array(
				'grammar' => '   ',
			)
		);

		$this->assertFalse( $result['is_valid'] );
		$this->assertSame( 0, $result['block_count'] );
	}

	public function test_execute_validate_grammar_invalid_marker(): void {
		$abilities = new Abilities();

		$result = $abilities->execute_validate_grammar(
			array(
				'grammar' => '<p>Plain HTML without block comment</p>',
			)
		);

		$this->assertFalse( $result['is_valid'] );
		$this->assertSame( 0, $result['block_count'] );
	}
}
