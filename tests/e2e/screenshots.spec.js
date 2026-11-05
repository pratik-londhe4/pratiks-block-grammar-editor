// Regenerates .wordpress-org/screenshot-N.png (readme.txt "== Screenshots ==" and README.md).
// Run on demand: npm run screenshots
const { test, expect } = require( '@playwright/test' );
const { wp } = require( './utils/wp-cli' );
const { openEditor } = require( './utils' );
const { prepareSite, shot } = require( './utils/media' );

test.describe( 'Screenshots', () => {
	test.use( { viewport: { width: 1280, height: 800 }, deviceScaleFactor: 2 } );

	let postId;

	test.beforeAll( async () => {
		prepareSite( 'Block Studio' );
		postId = Number(
			wp(
				'post',
				'create',
				'--post_type=post',
				'--post_title=Welcome to Block Grammar Editor',
				'--post_content=<!-- wp:paragraph {"fontSize":"medium","style":{"typography":{"lineHeight":"1.8"}}} --><p class="has-medium-font-size" style="line-height:1.8">Inspect, copy, format, validate, and edit isolated Gutenberg block grammar directly inside the editor canvas.</p><!-- /wp:paragraph -->',
				'--post_status=publish',
				'--porcelain'
			).trim()
		);
	} );

	test.afterAll( async () => {
		if ( postId ) {
			wp( 'post', 'delete', String( postId ), '--force' );
		}
	} );

	test( '1: block grammar editor modal with syntax validation and controls', async ( { page } ) => {
		await openEditor( page, postId );

		// Select block programmatically.
		await page.evaluate( () => {
			const blocks = window.wp.data.select( 'core/block-editor' ).getBlocks();
			if ( blocks.length > 0 ) {
				window.wp.data.dispatch( 'core/block-editor' ).selectBlock( blocks[ 0 ].clientId );
			}
		} );
		await page.waitForTimeout( 800 );

		// Open block options menu.
		const blockToolbarOptionsBtn = page.locator( '.block-editor-block-toolbar [aria-label="Options"], .block-editor-block-toolbar [aria-label="More options"], [aria-label="Block tools"] [aria-label="Options"], [aria-label="Block tools"] [aria-label="More options"]' ).first();
		await blockToolbarOptionsBtn.waitFor( { state: 'visible' } );
		await blockToolbarOptionsBtn.click();

		// Open grammar modal.
		const menuOption = page.getByRole( 'menuitem', { name: /Edit Block Grammar/i } );
		await menuOption.waitFor( { state: 'visible' } );
		await menuOption.click();

		const modal = page.locator( '.pbge-modal' );
		await expect( modal ).toBeVisible();
		await page.waitForTimeout( 600 );

		await shot( page, 1, { keepHover: true } );
	} );

	test( '2: settings page under Settings > Block Grammar Editor', async ( { page } ) => {
		await page.goto( '/wp-admin/options-general.php?page=pratiks-block-grammar-editor' );
		await expect( page.locator( '.wrap h1' ) ).toHaveText( 'Block Grammar Editor' );
		await shot( page, 2 );
	} );
} );
