<?php
/**
 * Contract for classes that hook into WordPress.
 *
 * @package PratikLondhe\PratiksBlockGrammarEditor
 */

namespace PratikLondhe\PratiksBlockGrammarEditor;

defined( 'ABSPATH' ) || exit;

/**
 * A part of the plugin that adds its own actions and filters.
 */
interface Service {

	/**
	 * Adds the service's hooks.
	 */
	public function register(): void;
}
