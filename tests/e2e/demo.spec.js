// Records .github/media/demo.gif for the README. Run on demand: npm run demo
const { test, expect } = require( '@playwright/test' );
const { wp } = require( './utils/wp-cli' );
const { openEditor } = require( './utils' );
const { prepareSite, recordDemo } = require( './utils/media' );

test( 'demo: inspect, format, copy, and edit block grammar in Gutenberg', async ( { browser } ) => {
	test.setTimeout( 180_000 );
	prepareSite( 'Block Studio' );

	const postId = Number(
		wp(
			'post',
			'create',
			'--post_type=post',
			'--post_title=Mastering Block Grammar in Gutenberg',
			'--post_content=<!-- wp:paragraph {"fontSize":"medium","dropCap":true} --><p class="has-medium-font-size has-drop-cap">Gutenberg stores block content and attributes as HTML comments. With Block Grammar Editor, power users can inspect and edit single block comments in isolation.</p><!-- /wp:paragraph -->',
			'--post_status=publish',
			'--porcelain'
		).trim()
	);

	try {
		await recordDemo(
			browser,
			async ( page, demo ) => {
				await openEditor( page, postId );
				await demo.start();
				await demo.pause( 800 );

				// Select block programmatically.
				await page.evaluate( () => {
					const blocks = window.wp.data.select( 'core/block-editor' ).getBlocks();
					if ( blocks.length > 0 ) {
						window.wp.data.dispatch( 'core/block-editor' ).selectBlock( blocks[ 0 ].clientId );
					}
				} );
				await demo.pause( 600 );

				// Click block options dropdown in toolbar.
				const blockToolbarOptionsBtn = page.locator( '.block-editor-block-toolbar [aria-label="Options"], .block-editor-block-toolbar [aria-label="More options"], [aria-label="Block tools"] [aria-label="Options"], [aria-label="Block tools"] [aria-label="More options"]' ).first();
				await demo.click( blockToolbarOptionsBtn, 600 );

				// Click "Edit Block Grammar".
				const menuOption = page.getByRole( 'menuitem', { name: /Edit Block Grammar/i } );
				await demo.click( menuOption, 800 );

				// Modal is open.
				const modal = page.locator( '.pbge-modal' );
				await expect( modal ).toBeVisible();
				await demo.pause( 1200 );

				// Format JSON button.
				const formatBtn = modal.getByRole( 'button', { name: /Format JSON/i } );
				await demo.click( formatBtn, 800 );
				await demo.pause( 1000 );

				// Copy Grammar button.
				const copyBtn = modal.getByRole( 'button', { name: /Copy Grammar/i } );
				await demo.click( copyBtn, 600 );
				await demo.pause( 1200 );

				// Edit grammar textarea.
				const textarea = modal.locator( 'textarea.pbge-textarea' );
				await demo.click( textarea, 400 );
				const currentVal = await textarea.inputValue();
				const updated = currentVal.replace( 'With Block Grammar Editor', 'With isolated Block Grammar editing' );
				await textarea.fill( updated );
				await demo.pause( 1200 );

				// Click Apply Changes.
				const applyBtn = modal.getByRole( 'button', { name: /Apply Changes/i } );
				await demo.click( applyBtn, 800 );

				// Modal closes.
				await expect( modal ).toBeHidden();
				await demo.pause( 800 );

				// Click Update button.
				const updateBtn = page.getByRole( 'button', { name: /Update|Save/i } ).first();
				await demo.click( updateBtn, 1500 );
				await demo.pause( 2000 );
			},
			{ viewport: { width: 1280, height: 760 }, width: 960, fps: 10 }
		);
	} finally {
		wp( 'post', 'delete', String( postId ), '--force' );
	}
} );
