=== Pratiks Block Grammar Editor ===
Contributors: pratiklondhe
Tags: gutenberg, blocks, block-editor, grammar, code-editor
Requires at least: 6.6
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 0.1.0
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Inspect, copy, format, validate, and edit Gutenberg block comment grammar for individual blocks in the editor.

== Description ==

Pratiks Block Grammar Editor allows power users, developers, pattern creators, and site builders to inspect and edit the raw Gutenberg comment grammar of a single block without switching the whole page into Code Editor mode.

WordPress Gutenberg stores block layouts, styles, and data as structured HTML comments (`<!-- wp:blockName {...} -->...<!-- /wp:blockName -->`). Until now, you only had two choices:
1. Open the global Code Editor (`Ctrl+Shift+Alt+M`), which dumps the entire post into a massive raw text editor, making it easy to accidentally corrupt neighboring blocks.
2. Choose "Edit as HTML" on a block, which completely strips away Gutenberg block comments, destroying block attributes, styles, and inner block hierarchies.

Block Grammar Editor bridges this gap by providing an isolated editor directly in the block's "More Options" menu in the block toolbar.

Features:
* Isolated block grammar editing directly from the Gutenberg block options toolbar.
* Clean monospace code editor with word wrap and keyboard shortcuts (Cmd/Ctrl+Enter to apply, Escape to close).
* Live syntax validation: detects malformed comment delimiters or broken JSON attributes before applying changes.
* One-click clipboard copying with visual confirmation.
* Pretty-print JSON formatter: reformats block attribute objects into clean, indented JSON.
* Safe updates: applies changes cleanly using core block parser and editor replacement routines.
* Role-based permissions: configure access via Settings > Block Grammar Editor (authors/editors or admins).
* Full WP-CLI command suite (`wp pratiks-block-grammar-editor inspect`, `validate`).
* WordPress 6.9+ Abilities API integration for AI agents and MCP workflows.
* 100% accessible (tested with axe-core and keyboard navigation).
* Zero third-party dependencies; lightweight and native.

== Installation ==

1. Upload the plugin files to `/wp-content/plugins/pratiks-block-grammar-editor`, or install via Plugins > Add New.
2. Activate the plugin through the 'Plugins' screen in WordPress.
3. Open any post or page in the block editor, click the three dots in any block's toolbar, and select "Edit Block Grammar".

== Frequently Asked Questions ==

= Does this modify my theme or other blocks? =
No. It only affects the block (or nested block group) you have currently selected when you click Apply Changes.

= How does validation prevent broken blocks? =
The editor validates your block grammar in real time on every keystroke. If comment tags are unclosed or JSON attributes are malformed, the "Apply Changes" button is automatically disabled and an error notice explains what needs fixing.

= Can I restrict access to administrators only? =
Yes. Navigate to Settings > Block Grammar Editor and set the Minimum Required Role to "Administrators Only (manage_options)". You can also use the `pratiks_block_grammar_editor_capability` PHP filter.

== Screenshots ==

1. Block Grammar Editor modal with syntax validation and controls.
2. Settings page under Settings > Block Grammar Editor.

== Changelog ==

= 0.1.0 =
* First release.
