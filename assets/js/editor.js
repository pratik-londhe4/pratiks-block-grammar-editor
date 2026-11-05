/**
 * Block Grammar Editor - Gutenberg Editor Integration
 *
 * @package PratikLondhe\PratiksBlockGrammarEditor
 */

( function ( wp ) {
	'use strict';

	if ( ! wp || ! wp.plugins || ! wp.blocks || ! wp.element || ! wp.components || ! wp.data ) {
		return;
	}

	const { registerPlugin } = wp.plugins;
	const { createElement, useState, useEffect, useRef, Fragment } = wp.element;
	const { Modal, Button, MenuItem, SVG, Path } = wp.components;
	const { useSelect, useDispatch } = wp.data;
	const { serialize, parse } = wp.blocks;
	const { __, sprintf } = wp.i18n;
	const { doAction, applyFilters } = wp.hooks;

	// Fallback for BlockSettingsMenuControls across Gutenberg versions.
	const BlockSettingsMenuControls =
		( wp.blockEditor && wp.blockEditor.BlockSettingsMenuControls ) ||
		( wp.editor && wp.editor.BlockSettingsMenuControls );

	if ( ! BlockSettingsMenuControls ) {
		return;
	}

	// Code bracket icon for menu item and modal header.
	const CodeIcon = createElement(
		SVG,
		{
			className: 'pbge-menu-icon',
			width: 20,
			height: 20,
			viewBox: '0 0 24 24',
			xmlns: 'http://www.w3.org/2000/svg',
		},
		createElement( Path, {
			d: 'M9.4 16.6L4.8 12l4.6-4.6L8 6l-6 6 6 6 1.4-1.4zm5.2 0l4.6-4.6-4.6-4.6L16 6l6 6-6 6-1.4-1.4z',
		} )
	);

	/**
	 * Prettifies JSON attribute objects inside block comment grammar delimiters.
	 *
	 * @param {string} grammar Raw block comment grammar.
	 * @return {string} Formatted grammar.
	 */
	function formatGrammarJson( grammar ) {
		if ( ! grammar || typeof grammar !== 'string' ) {
			return grammar;
		}

		// Matches <!-- wp:name {JSON} --> or <!-- wp:name {JSON} /-->
		return grammar.replace(
			/<!--\s+wp:([a-z0-9\/-]+)\s+(\{[\s\S]*?\})\s*(\/?)-->/g,
			function ( match, blockName, jsonStr, isSelfClosing ) {
				try {
					const parsed = JSON.parse( jsonStr );
					const formattedJson = JSON.stringify( parsed, null, 2 );
					const closer = isSelfClosing ? ' /-->' : ' -->';
					return '<!-- wp:' + blockName + ' ' + formattedJson + closer;
				} catch ( err ) {
					// Return unchanged if JSON was not parseable.
					return match;
				}
			}
		);
	}

	/**
	 * Validates block grammar string.
	 *
	 * @param {string} code Block grammar text.
	 * @return {{ isValid: boolean, message: string, parsedBlocks: Array }} Validation result.
	 */
	function validateGrammar( code ) {
		const trimmed = ( code || '' ).trim();
		if ( ! trimmed ) {
			return {
				isValid: false,
				message: __( 'Grammar content cannot be empty.', 'pratiks-block-grammar-editor' ),
				parsedBlocks: [],
			};
		}

		// Check basic block comment markers.
		if ( ! trimmed.startsWith( '<!-- wp:' ) ) {
			return {
				isValid: false,
				message: __( 'Invalid block grammar: Must begin with a <!-- wp:... --> comment marker.', 'pratiks-block-grammar-editor' ),
				parsedBlocks: [],
			};
		}

		// Check attribute JSON validity.
		const headerRegex = /<!--\s+wp:([a-z0-9\/-]+)([\s\S]*?)\s*(\/?)-->/g;
		let headerMatch;
		while ( ( headerMatch = headerRegex.exec( trimmed ) ) !== null ) {
			const attrsPart = ( headerMatch[ 2 ] || '' ).trim();
			if ( attrsPart.startsWith( '{' ) ) {
				try {
					JSON.parse( attrsPart );
				} catch ( e ) {
					return {
						isValid: false,
						message: sprintf(
							/* translators: %s: error message */
							__( 'Malformed JSON in block attributes: %s', 'pratiks-block-grammar-editor' ),
							e.message
						),
						parsedBlocks: [],
					};
				}
			}
		}

		// Parse using core block parser.
		try {
			const parsed = parse( trimmed );
			if ( ! parsed || parsed.length === 0 ) {
				return {
					isValid: false,
					message: __( 'Unable to parse block from given grammar.', 'pratiks-block-grammar-editor' ),
					parsedBlocks: [],
				};
			}

			// If input started with a block comment like <!-- wp:paragraph, but parsed result fell back to core/freeform or core/html,
			// the block grammar or attributes failed to parse.
			const firstBlock = parsed[ 0 ];
			if (
				firstBlock &&
				( firstBlock.name === 'core/freeform' || firstBlock.name === 'core/html' ) &&
				! trimmed.startsWith( '<!-- wp:freeform' ) &&
				! trimmed.startsWith( '<!-- wp:html' )
			) {
				return {
					isValid: false,
					message: __( 'Invalid block grammar: Gutenberg failed to parse block delimiters or attributes.', 'pratiks-block-grammar-editor' ),
					parsedBlocks: [],
				};
			}

			if ( parsed.some( function ( b ) { return b.isValid === false; } ) ) {
				return {
					isValid: false,
					message: __( 'One or more blocks contain validation errors.', 'pratiks-block-grammar-editor' ),
					parsedBlocks: [],
				};
			}

			return {
				isValid: true,
				message: sprintf(
					/* translators: %d: number of parsed blocks */
					__( 'Valid block grammar (%d block(s))', 'pratiks-block-grammar-editor' ),
					parsed.length
				),
				parsedBlocks: parsed,
			};
		} catch ( err ) {
			return {
				isValid: false,
				message: sprintf(
					/* translators: %s: error message */
					__( 'Block parser error: %s', 'pratiks-block-grammar-editor' ),
					err.message
				),
				parsedBlocks: [],
			};
		}
	}

	/**
	 * Main Modal Component for Block Grammar Editor.
	 */
	function GrammarEditorModal( props ) {
		const { block, clientIds, onClose } = props;
		const { replaceBlocks } = useDispatch( 'core/block-editor' );

		const initialGrammar = serialize( block );
		const [ grammar, setGrammar ] = useState( function () {
			const config = window.pratiksBlockGrammarEditorData || {};
			return config.autoFormat ? formatGrammarJson( initialGrammar ) : initialGrammar;
		} );
		const [ copied, setCopied ] = useState( false );
		const [ validation, setValidation ] = useState( function () {
			return validateGrammar( initialGrammar );
		} );
		const textareaRef = useRef( null );

		// Run validation on grammar updates.
		useEffect( function () {
			setValidation( validateGrammar( grammar ) );
		}, [ grammar ] );

		// Fire open action hook.
		useEffect( function () {
			doAction( 'pratiksBlockGrammarEditor.onOpen', {
				block: block,
				rawGrammar: initialGrammar,
			} );
		}, [] );

		// Handle clipboard copy.
		const handleCopy = function () {
			if ( navigator.clipboard && navigator.clipboard.writeText ) {
				navigator.clipboard.writeText( grammar ).then( function () {
					setCopied( true );
					setTimeout( function () {
						setCopied( false );
					}, 2000 );
				} );
			}
		};

		// Format JSON attributes inside grammar.
		const handleFormatJson = function () {
			setGrammar( formatGrammarJson( grammar ) );
		};

		// Reset to original.
		const handleReset = function () {
			setGrammar( initialGrammar );
		};

		// Apply changes to the editor.
		const handleApply = function () {
			if ( ! validation.isValid || ! validation.parsedBlocks.length ) {
				return;
			}

			// Call replaceBlocks to update editor canvas and state.
			const targetIds = Array.isArray( clientIds ) ? clientIds : [ block.clientId ];
			replaceBlocks( targetIds, validation.parsedBlocks );

			doAction( 'pratiksBlockGrammarEditor.onApply', {
				oldBlock: block,
				newBlocks: validation.parsedBlocks,
			} );

			onClose();
		};

		// Handle keyboard shortcuts (Cmd/Ctrl + Enter to apply).
		const handleKeyDown = function ( event ) {
			if ( ( event.metaKey || event.ctrlKey ) && event.key === 'Enter' ) {
				event.preventDefault();
				if ( validation.isValid ) {
					handleApply();
				}
			}
		};

		const modalTitle = applyFilters(
			'pratiksBlockGrammarEditor.modalTitle',
			__( 'Block Grammar Editor', 'pratiks-block-grammar-editor' ),
			block
		);

		const blockName = block.name || __( 'Unknown block', 'pratiks-block-grammar-editor' );
		const shortId = block.clientId ? block.clientId.slice( 0, 8 ) : '';
		const innerCount = block.innerBlocks ? block.innerBlocks.length : 0;

		return createElement(
			Modal,
			{
				title: modalTitle,
				onRequestClose: onClose,
				className: 'pbge-modal',
				shouldCloseOnClickOutside: false,
			},
			createElement(
				'div',
				{ className: 'pbge-container' },
				// Meta info chips & live validation pill.
				createElement(
					'div',
					{ className: 'pbge-meta-bar' },
					createElement(
						'div',
						{ className: 'pbge-meta-chips' },
						createElement(
							'span',
							{ className: 'pbge-chip' },
							createElement( 'span', { className: 'pbge-chip-label' }, 'block:' ),
							blockName
						),
						shortId &&
							createElement(
								'span',
								{ className: 'pbge-chip' },
								createElement( 'span', { className: 'pbge-chip-label' }, 'id:' ),
								shortId
							),
						createElement(
							'span',
							{ className: 'pbge-chip' },
							createElement( 'span', { className: 'pbge-chip-label' }, 'inner:' ),
							innerCount
						)
					),
					createElement(
						'div',
						{
							className:
								'pbge-status-pill ' +
								( validation.isValid ? 'pbge-status-valid' : 'pbge-status-invalid' ),
							role: 'status',
							'aria-live': 'polite',
						},
						createElement( 'span', { className: 'pbge-status-dot' } ),
						createElement( 'span', null, validation.message )
					)
				),
				// Error detail box if invalid.
				! validation.isValid &&
					createElement(
						'div',
						{ className: 'pbge-error-alert', role: 'alert' },
						validation.message
					),
				// Textarea code editor.
				createElement(
					'div',
					{ className: 'pbge-editor-wrapper' },
					createElement( 'textarea', {
						ref: textareaRef,
						className: 'pbge-textarea',
						value: grammar,
						onChange: function ( e ) {
							setGrammar( e.target.value );
						},
						onKeyDown: handleKeyDown,
						spellCheck: false,
						'aria-label': __( 'Gutenberg block comment grammar editor', 'pratiks-block-grammar-editor' ),
					} )
				),
				// Action footer.
				createElement(
					'div',
					{ className: 'pbge-footer' },
					createElement(
						'div',
						{ className: 'pbge-footer-left' },
						createElement(
							Button,
							{
								variant: 'secondary',
								onClick: handleCopy,
								disabled: ! grammar,
							},
							copied
								? __( 'Copied!', 'pratiks-block-grammar-editor' )
								: __( 'Copy Grammar', 'pratiks-block-grammar-editor' )
						),
						createElement(
							Button,
							{
								variant: 'secondary',
								onClick: handleFormatJson,
								disabled: ! grammar,
							},
							__( 'Format JSON', 'pratiks-block-grammar-editor' )
						),
						createElement(
							Button,
							{
								variant: 'tertiary',
								onClick: handleReset,
								disabled: grammar === initialGrammar,
							},
							__( 'Reset', 'pratiks-block-grammar-editor' )
						)
					),
					createElement(
						'div',
						{ className: 'pbge-footer-right' },
						createElement(
							'span',
							{ className: 'pbge-hint' },
							__( 'Ctrl/Cmd+Enter to apply', 'pratiks-block-grammar-editor' )
						),
						createElement(
							Button,
							{
								variant: 'secondary',
								onClick: onClose,
							},
							__( 'Cancel', 'pratiks-block-grammar-editor' )
						),
						createElement(
							Button,
							{
								variant: 'primary',
								onClick: handleApply,
								disabled: ! validation.isValid || grammar === initialGrammar,
							},
							__( 'Apply Changes', 'pratiks-block-grammar-editor' )
						)
					)
				)
			)
		);
	}

	/**
	 * Block Settings Menu Item Extension.
	 */
	function BlockGrammarMenuItem() {
		const [ isModalOpen, setIsModalOpen ] = useState( false );
		const [ targetBlock, setTargetBlock ] = useState( null );
		const [ targetClientIds, setTargetClientIds ] = useState( [] );

		const selectedBlock = useSelect( function ( select ) {
			const editor = select( 'core/block-editor' );
			if ( ! editor ) {
				return null;
			}
			if ( typeof editor.getSelectedBlock === 'function' ) {
				const block = editor.getSelectedBlock();
				if ( block ) {
					return block;
				}
			}
			const clientIds = typeof editor.getSelectedBlockClientIds === 'function' ? editor.getSelectedBlockClientIds() : [];
			if ( clientIds && clientIds.length > 0 && typeof editor.getBlock === 'function' ) {
				return editor.getBlock( clientIds[ 0 ] );
			}
			return null;
		}, [] );

		return createElement(
			Fragment,
			null,
			createElement(
				BlockSettingsMenuControls,
				null,
				function ( menuProps ) {
					const clientIds = ( menuProps && menuProps.selectedClientIds ) || [];
					const onCloseMenu = menuProps && menuProps.onClose;

					if ( ! selectedBlock ) {
						return null;
					}

					const canEdit = applyFilters(
						'pratiksBlockGrammarEditor.canEditBlock',
						true,
						selectedBlock
					);

					if ( ! canEdit ) {
						return null;
					}

					return createElement(
						MenuItem,
						{
							icon: CodeIcon,
							onClick: function () {
								if ( typeof onCloseMenu === 'function' ) {
									onCloseMenu();
								}
								setTargetBlock( selectedBlock );
								setTargetClientIds( clientIds.length ? clientIds : [ selectedBlock.clientId ] );
								setIsModalOpen( true );
							},
						},
						__( 'Edit Block Grammar', 'pratiks-block-grammar-editor' )
					);
				}
			),
			isModalOpen &&
				targetBlock &&
				createElement( GrammarEditorModal, {
					block: targetBlock,
					clientIds: targetClientIds,
					onClose: function () {
						setIsModalOpen( false );
						setTargetBlock( null );
					},
				} )
		);
	}

	// Register block editor plugin.
	registerPlugin( 'pratiks-block-grammar-editor', {
		render: BlockGrammarMenuItem,
		icon: 'editor-code',
	} );
} )( window.wp );
