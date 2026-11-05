// End-to-end tests for Pratiks Block Grammar Editor. Check every outcome in the database through WP-CLI as well as on screen.
const { test, expect } = require( './utils' );
const { openEditor } = require( './utils' );
const { wp } = require( './utils/wp-cli' );

test.describe( 'Block Grammar Editor E2E', () => {
	test.beforeEach( async () => {
		// Ensure default settings.
		wp( 'option', 'delete', 'pratiks_block_grammar_editor_settings' );
	} );

	test( 'the plugin is active in plugins list', async ( { page } ) => {
		await page.goto( '/wp-admin/plugins.php' );
		await expect( page.locator( 'tr[data-slug="pratiks-block-grammar-editor"].active' ) ).toBeVisible();
	} );

	test( 'settings screen renders without Pratiks prefix and saves options', async ( { page } ) => {
		await page.goto( '/wp-admin/options-general.php?page=pratiks-block-grammar-editor' );

		// Header must be "Block Grammar Editor" (no Pratiks prefix in UI).
		const header = page.locator( '.wrap h1' );
		await expect( header ).toHaveText( 'Block Grammar Editor' );

		// Check the checkbox for auto-formatting.
		const autoFormatCheckbox = page.locator( '#pbge_auto_format' );
		await expect( autoFormatCheckbox ).not.toBeChecked();
		await autoFormatCheckbox.check();

		// Submit form.
		await page.locator( '#submit' ).click();

		// Verify settings saved in database.
		const settingsJson = wp( 'option', 'get', 'pratiks_block_grammar_editor_settings', '--format=json' );
		const parsedSettings = JSON.parse( settingsJson );
		expect( parsedSettings.auto_format_attributes ).toBe( 1 );
	} );

	test( 'WP-CLI inspect and validate commands work', async () => {
		const postId = Number(
			wp(
				'post',
				'create',
				'--post_type=post',
				'--post_title=CLI Test Post',
				'--post_content=<!-- wp:paragraph {"fontSize":"small"} --><p class="has-small-font-size">CLI Grammar test</p><!-- /wp:paragraph -->',
				'--post_status=publish',
				'--porcelain'
			).trim()
		);

		// Test inspect command.
		const inspectOutput = wp( 'pratiks-block-grammar-editor', 'inspect', String( postId ), '--format=json' );
		const parsedBlocks = JSON.parse( inspectOutput );
		expect( parsedBlocks.length ).toBeGreaterThanOrEqual( 1 );
		expect( parsedBlocks[ 0 ].blockName ).toBe( 'core/paragraph' );

		// Test validate command with valid grammar.
		const validOutput = wp(
			'pratiks-block-grammar-editor',
			'validate',
			'<!-- wp:heading {"level":2} --><h2>Heading</h2><!-- /wp:heading -->'
		);
		expect( validOutput ).toContain( 'Valid Gutenberg block grammar' );

		wp( 'post', 'delete', String( postId ), '--force' );
	} );

	test( 'can open block grammar modal, edit grammar, and apply changes', async ( { page } ) => {
		const postId = Number(
			wp(
				'post',
				'create',
				'--post_type=post',
				'--post_title=Editor E2E Post',
				'--post_content=<!-- wp:paragraph {"fontSize":"small"} --><p class="has-small-font-size">Original text before edit</p><!-- /wp:paragraph -->',
				'--post_status=publish',
				'--porcelain'
			).trim()
		);

		try {
			await openEditor( page, postId );

			// Select the first block programmatically.
			await page.evaluate( () => {
				const blocks = window.wp.data.select( 'core/block-editor' ).getBlocks();
				if ( blocks.length > 0 ) {
					window.wp.data.dispatch( 'core/block-editor' ).selectBlock( blocks[ 0 ].clientId );
				}
			} );
			await page.waitForTimeout( 800 );

			// Find options button specifically inside the block toolbar.
			const blockToolbarOptionsBtn = page.locator( '.block-editor-block-toolbar [aria-label="Options"], .block-editor-block-toolbar [aria-label="More options"], [aria-label="Block tools"] [aria-label="Options"], [aria-label="Block tools"] [aria-label="More options"]' ).first();
			await blockToolbarOptionsBtn.waitFor( { state: 'visible', timeout: 10000 } );
			await blockToolbarOptionsBtn.click();

			// Click "Edit Block Grammar".
			const menuOption = page.getByRole( 'menuitem', { name: /Edit Block Grammar/i } );
			await menuOption.waitFor( { state: 'visible', timeout: 10000 } );
			await menuOption.click();

			// Verify the modal opened.
			const modal = page.locator( '.pbge-modal' );
			await expect( modal ).toBeVisible();
			await expect( modal.getByText( 'Block Grammar Editor' ) ).toBeVisible();

			// Check status pill.
			const statusPill = modal.locator( '.pbge-status-pill' );
			await expect( statusPill ).toContainText( 'Valid block grammar' );

			// Edit the grammar textarea.
			const textarea = modal.locator( 'textarea.pbge-textarea' );
			await expect( textarea ).toBeVisible();
			const initialVal = await textarea.inputValue();
			expect( initialVal ).toContain( '<!-- wp:paragraph' );

			// Update the textarea with modified text and attributes.
			const updatedGrammar = '<!-- wp:paragraph {"fontSize":"large"} -->\n<p class="has-large-font-size">Modified by E2E test</p>\n<!-- /wp:paragraph -->';
			await textarea.fill( updatedGrammar );

			// Status should still be valid.
			await expect( statusPill ).toContainText( 'Valid block grammar' );

			// Click Apply Changes.
			const applyBtn = modal.getByRole( 'button', { name: /Apply Changes/i } );
			await expect( applyBtn ).toBeEnabled();
			await applyBtn.click();

			// Modal should close.
			await expect( modal ).toBeHidden();

			// Save post in Gutenberg.
			const updateBtn = page.getByRole( 'button', { name: /Update|Save/i } ).first();
			await updateBtn.click();
			await page.waitForTimeout( 1500 );

			// Verify post content in database through WP-CLI.
			const updatedContent = wp( 'post', 'get', String( postId ), '--field=post_content' );
			expect( updatedContent ).toContain( 'Modified by E2E test' );
			expect( updatedContent ).toContain( 'has-large-font-size' );
		} finally {
			wp( 'post', 'delete', String( postId ), '--force' );
		}
	} );

	test( 'disables apply button and shows error alert on invalid grammar', async ( { page } ) => {
		const postId = Number(
			wp(
				'post',
				'create',
				'--post_type=post',
				'--post_title=Invalid Grammar Post',
				'--post_content=<!-- wp:paragraph --><p>Test invalid</p><!-- /wp:paragraph -->',
				'--post_status=publish',
				'--porcelain'
			).trim()
		);

		try {
			await openEditor( page, postId );

			// Select the first block programmatically.
			await page.evaluate( () => {
				const blocks = window.wp.data.select( 'core/block-editor' ).getBlocks();
				if ( blocks.length > 0 ) {
					window.wp.data.dispatch( 'core/block-editor' ).selectBlock( blocks[ 0 ].clientId );
				}
			} );
			await page.waitForTimeout( 800 );

			const blockToolbarOptionsBtn = page.locator( '.block-editor-block-toolbar [aria-label="Options"], .block-editor-block-toolbar [aria-label="More options"], [aria-label="Block tools"] [aria-label="Options"], [aria-label="Block tools"] [aria-label="More options"]' ).first();
			await blockToolbarOptionsBtn.waitFor( { state: 'visible', timeout: 10000 } );
			await blockToolbarOptionsBtn.click();

			const menuOption = page.getByRole( 'menuitem', { name: /Edit Block Grammar/i } );
			await menuOption.waitFor( { state: 'visible', timeout: 10000 } );
			await menuOption.click();

			const modal = page.locator( '.pbge-modal' );
			await expect( modal ).toBeVisible();

			const textarea = modal.locator( 'textarea.pbge-textarea' );
			await textarea.fill( '<!-- wp:paragraph {"invalid": malformed json --> broken' );

			const applyBtn = modal.getByRole( 'button', { name: /Apply Changes/i } );
			await expect( applyBtn ).toBeDisabled();

			const statusPill = modal.locator( '.pbge-status-pill' );
			await expect( statusPill ).toHaveClass( /pbge-status-invalid/ );

			// Close modal.
			await modal.getByRole( 'button', { name: /Cancel/i } ).click();
			await expect( modal ).toBeHidden();
		} finally {
			wp( 'post', 'delete', String( postId ), '--force' );
		}
	} );
} );
