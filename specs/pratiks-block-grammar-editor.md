# Pratiks Block Grammar Editor (`pratiks-block-grammar-editor`)

Namespace `PratikLondhe\PratiksBlockGrammarEditor`, prefix `pratiks_block_grammar_editor_`, CSS prefix `pbge-`. E2E port 8913.
Requires at least 6.6 and PHP 7.4.

## Problem

WordPress Gutenberg stores post and page content as structured HTML comments (block grammar, e.g. `<!-- wp:paragraph {"fontSize":"small"} -->...<!-- /wp:paragraph -->`).
When power users, pattern builders, developers, or support teams need to view or edit the exact block grammar, WordPress provides only the global "Code Editor" view (`Ctrl+Shift+Alt+M`). This switches the entire post into a massive raw code buffer, which is dangerous, confusing, and unwieldy for long posts with many nested blocks.
Conversely, the block-level "Edit as HTML" button strips the Gutenberg comment grammar entirely, converting the block to raw inner HTML and destroying block metadata, attributes, and inner block relations.
There is no isolated tool to view, copy, format, validate, and edit the block grammar for an individual selected block or block subtree.

## v1.0.0 scope

1. **Editor UI & Block Toolbar Integration**:
   - In the block options dropdown menu (`editor.BlockSettingsMenuControls`), registers an action item: "Edit Block Grammar" with a code icon.
   - Launches a focused modal dialog titled "Block Grammar Editor":
     - Displays the serialized block grammar (`wp.blocks.serialize(block)`).
     - Clean monospace code editor textarea with line wrap and keyboard shortcut support (Escape to close, Cmd/Ctrl+Enter to apply).
     - Inspector chips: block name (e.g. `core/paragraph`), client ID, and inner blocks count.
     - Live validation: on keystroke, tests grammar via `wp.blocks.parse(code)`. Shows a real-time status pill: "Valid block grammar" (with block count) or "Invalid block grammar" (with error details).
     - "Format JSON" button: cleanly reformats attribute JSON within the block comment delimiter while preserving markup.
     - "Copy Grammar" button: one-click copy to clipboard with instant confirmation checkmark.
     - "Apply Changes" button: safely replaces the active block in editor state via `wp.data.dispatch('core/block-editor').replaceBlocks(clientId, parsedBlocks)`. Disabled when grammar is invalid or empty.
2. **Settings Screen**:
   - Admin settings screen located at `Settings > Block Grammar Editor` (`options-general.php?page=pratiks-block-grammar-editor`).
   - Settings stored in `pratiks_block_grammar_editor_settings`:
     - `required_capability` (default `edit_posts`, configurable to `edit_posts` or `manage_options`).
     - `auto_format_attributes` (default `0`).
     - `enable_copy_button` (default `1`).
3. **Abilities API (WordPress 6.9+)**:
   - `pratiks-block-grammar-editor/parse-grammar` (readonly): parses raw block grammar into block attributes and structure.
   - `pratiks-block-grammar-editor/validate-grammar` (readonly): validates block grammar string.
4. **WP-CLI**:
   - `wp pratiks-block-grammar-editor inspect <post-id> [--block=<index>] [--format=json|table|raw]`
   - `wp pratiks-block-grammar-editor validate <grammar-or-file>`
5. **Privacy & Lifecycle**:
   - Privacy policy disclosure and exporter/eraser handlers (zero personal data collected or stored).
   - `uninstall.php` completely deletes plugin options.

## Hooks

Filters:
- `pratiks_block_grammar_editor_capability`: filter required capability to view and use the editor.
- `pratiks_block_grammar_editor_default_settings`: filter default option settings.
- `pratiks_block_grammar_editor_script_data`: filter data localized to the editor JS.

Actions:
- `pratiks_block_grammar_editor_loaded`: fired when all services are booted.

JS (`wp.hooks`, namespace `pratiksBlockGrammarEditor`):
- Action `pratiksBlockGrammarEditor.onOpen`: fired when modal opens with `{ block, rawGrammar }`.
- Action `pratiksBlockGrammarEditor.onApply`: fired when changes apply with `{ oldBlock, newBlocks }`.
- Filter `pratiksBlockGrammarEditor.modalTitle`: customizes modal title.
- Filter `pratiksBlockGrammarEditor.canEditBlock`: whether editing is allowed for specific block types.

## Abilities

- `pratiks-block-grammar-editor/parse-grammar` (readonly)
- `pratiks-block-grammar-editor/validate-grammar` (readonly)

## WP-CLI

- `wp pratiks-block-grammar-editor inspect <post-id> [--block=<index>] [--format=json|table|raw]`
- `wp pratiks-block-grammar-editor validate <grammar-or-file>`

## E2E ideas

- Open block editor, insert standard paragraph block.
- Open "Edit Block Grammar" from block settings dropdown.
- Verify modal renders with exact serialized comment grammar.
- Test live validation with malformed comment.
- Modify text in grammar modal, click "Apply Changes", verify document is updated.
- Verify settings page loads cleanly and saves options.
- Verify WCAG 2.1 AA accessibility in all screens.
