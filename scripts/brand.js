const path = require( 'path' );
const fs = require( 'fs' );
const ROOT = path.join( __dirname, '..' );
const { chromium } = require( path.join( ROOT, 'node_modules/@playwright/test' ) );

const shot = 'data:image/png;base64,' + fs.readFileSync( path.join( ROOT, '.wordpress-org/screenshot-1.png' ) ).toString( 'base64' );
const codeIcon = ( size, color ) => `<svg width="${ size }" height="${ size }" viewBox="0 0 24 24"><path fill="${ color }" d="M9.4 16.6L4.8 12l4.6-4.6L8 6l-6 6 6 6 1.4-1.4zm5.2 0l4.6-4.6-4.6-4.6L16 6l6 6-6 6-1.4-1.4z"/></svg>`;
const bg = 'background:linear-gradient(135deg,#0f172a 0%,#1e293b 55%,#2563eb 100%);';
const font = "font-family:-apple-system,'Segoe UI',Inter,Helvetica,Arial,sans-serif;";
const previewShot = ( w, h ) => `<div style="width:${ w }px;height:${ h }px;border-radius:${ Math.round( h / 14 ) }px;overflow:hidden;box-shadow:0 ${ h / 25 }px ${ h / 8 }px rgba(0,0,0,.45);background:url(${ shot }) 50% 50% / 100% auto no-repeat #f8fafc"></div>`;

const banner = ( w, h ) => `<div style="${ bg }${ font }width:${ w }px;height:${ h }px;display:flex;align-items:center;justify-content:space-between;padding:0 ${ w * 0.06 }px;box-sizing:border-box;color:#fff">
	<div>
		<div style="display:flex;align-items:center;gap:${ h * 0.05 }px">
			${ codeIcon( h * 0.22, '#38bdf8' ) }
			<div style="font-size:${ h * 0.13 }px;font-weight:700;letter-spacing:-.5px;white-space:nowrap">Block Grammar Editor</div>
		</div>
		<div style="font-size:${ h * 0.06 }px;opacity:.92;margin-top:${ h * 0.04 }px;color:#e2e8f0">Inspect, copy, format & edit isolated Gutenberg block grammar</div>
	</div>
	${ previewShot( w * 0.38, h * 0.76 ) }
</div>`;

const items = [
	[ 'icon-256x256.png', 256, 256, `<div style="${ bg }width:256px;height:256px;display:flex;align-items:center;justify-content:center">${ codeIcon( 160, '#38bdf8' ) }</div>` ],
	[ 'icon-128x128.png', 128, 128, `<div style="${ bg }width:128px;height:128px;display:flex;align-items:center;justify-content:center">${ codeIcon( 80, '#38bdf8' ) }</div>` ],
	[ 'banner-772x250.png', 772, 250, banner( 772, 250 ) ],
	[ 'banner-1544x500.png', 1544, 500, banner( 1544, 500 ) ],
	[ '../.github/media/social-preview.png', 1280, 640, banner( 1280, 640 ) ],
];

( async () => {
	const browser = await chromium.launch( { channel: 'chrome' } );
	for ( const [ name, w, h, html ] of items ) {
		const page = await browser.newPage( { viewport: { width: w, height: h } } );
		await page.setContent( `<body style="margin:0">${ html }</body>` );
		await page.screenshot( { path: path.join( ROOT, '.wordpress-org', name ) } );
		await page.close();
	}
	await browser.close();
	console.log( 'Generated icons, banners and social-preview.' );
} )();
