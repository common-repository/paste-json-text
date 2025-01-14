( function ( wp ) {
	var __ = wp.i18n.__;
	var useSelect = wp.data.useSelect;
	var useDispatch = wp.data.useDispatch;
	var el = wp.element.createElement;
	var Fragment = wp.element.Fragment;
	var useState = wp.element.useState;
	var Textarea = wp.components.TextareaControl;
	var Toggle = wp.components.ToggleControl;
	var Button = wp.components.Button;
	var Notice = wp.components.Notice;

	var PasteJSONForm = function () {
		const [ text, setText ] = useState( '' );
		const [ pastePostName, setPastePostName ] = useState( true );
		const [ pasteMetaValues, setPasteMetaValues ] = useState( true );
		const [ noticeText, setNoticeText ] = useState( '' );
		const [ noticeStatus, setNoticeStatus ] = useState( '' );
		const [ parsedPostData, setParsedPostData ] = useState( null );

		const escapeContent = function ( content ) {
			let div = document.createElement( 'div' );
			div.innerHTML = content.replace( /</g, "&lt;" ).replace( />/g, "&gt;" );
			return div.textContent || div.innerText;
		};

		const getEditorSettings = useSelect( ( select ) => () =>
			select( wp.editor.store ).getEditorSettings(), []
		);
		const getPostID = useSelect( ( select ) => () =>
			select( wp.editor.store ).getCurrentPostId(), []
		);
		const getPostType = useSelect( ( select ) => () =>
			select( wp.editor.store ).getCurrentPostType(), []
		);
		const getPostAttribute = useSelect( ( select ) => ( attribute ) =>
			select( wp.editor.store ).getEditedPostAttribute( attribute ), []
		);

		const jsonPostData = function () {
			let categories = null;
			if ( 0 < post_terms.category.length ) {
				categories    = [];
				const cat_ids = getPostAttribute( 'categories' );
				post_terms.category.forEach( function ( category ) {
					if ( cat_ids.includes( category.id ) ) {
						categories.push( {
							id:   category.id,
							name: category.name,
							slug: category.slug,
						} );
					}
				} );
			}

			let tags = null;
			if ( 0 < post_terms.post_tag.length ) {
				tags = [];
				const tag_ids  = getPostAttribute( 'tags' );
				post_terms.post_tag.forEach( function ( tag ) {
					if ( tag_ids.includes( tag.id ) ) {
						tags.push( {
							id:   tag.id,
							name: tag.name,
							slug: tag.slug,
						} );
					}
				} );
			}

			const meta = getPostAttribute( 'meta' );
			const settings = getEditorSettings();
			if ( undefined != settings.enableCustomFields && settings.enableCustomFields ) {
				for ( let key in classic_meta ) {
					if ( 'object' === typeof meta[key] ) {
						meta[key] = classic_meta[key];
					} else if ( 'undefined' !== typeof meta[key] ) {
						meta[key] = classic_meta[key][0];
					}
				}
			}

			const postData = {
				post_type:     getPostType(),
				post_title:    getPostAttribute( 'title' ),
				post_name:     getPostAttribute( 'slug' ),
				menu_order:    getPostAttribute( 'menu_order' ),
				post_template: getPostAttribute( 'template' ),
				post_content:  getPostAttribute( 'content' ),
				post_excerpt:  getPostAttribute( 'excerpt' ),
				post_category: categories,
				post_tag:      tags,
				post_meta:     meta,
				classic_meta:  classic_meta,
			};

			return JSON.stringify( postData );
		}

		const onCopySuccess = function () {
			setNoticeStatus( 'success' );
			setNoticeText( __( 'The content has been copied to the clipboard.', 'paste-json-text-js' ) );
		}

		const confirmPostData = function ( newContent ) {
			if ( newContent ) {
				try {
					const postData = JSON.parse( newContent );
					setParsedPostData( postData );
					if ( isValidPostData( postData ) ) {
						setNoticeStatus( 'info' );
						setNoticeText( __( 'JSON text is valid post data.', 'paste-json-text-js' ) );
					} else {
						setNoticeStatus( 'warning' );
						setNoticeText( __( 'JSON text is not post data.', 'paste-json-text-js' ) );
					}
				} catch ( error ) {
					setNoticeStatus( 'error' );
					setNoticeText( __( 'Not a valid JSON text.', 'paste-json-text-js' ) );
				}
			} else {
				setNoticeStatus( '' );
				setNoticeText( '' );
			}
		}

		const isValidPostData = function ( postData ) {
			return ( null !== postData &&
				'string' === typeof postData.post_type &&
				'string' === typeof postData.post_title &&
				'string' === typeof postData.post_name &&
				'string' === typeof postData.post_content
			);
		}

		const pastePostData = async function () {
			wp.data.dispatch( 'core/editor' ).editPost( {
				title:      parsedPostData.post_title,
				template:   parsedPostData.post_template,
				menu_order: parsedPostData.menu_order,
				content:    parsedPostData.post_content,
				excerpt:    parsedPostData.post_excerpt,
			} );

			if ( 0 < post_terms.category.length ) {
				let categories = [];
				parsedPostData.post_category.forEach( function ( category ) {
					for ( let i = 0; i < post_terms.category.length; i++ ) {
						if ( category.name === post_terms.category[i].name ) {
							categories.push( post_terms.category[i].id );
							break;
						}
					}
				} );
				wp.data.dispatch( 'core/editor' ).editPost( {
					categories: categories,
				} );
			}

			if ( 0 < post_terms.post_tag.length ) {
				let tags = [];
				parsedPostData.post_tag.forEach( function ( tag ) {
					for ( let i = 0; i < post_terms.post_tag.length; i++ ) {
						if ( tag.name === post_terms.post_tag[i].name ) {
							tags.push( post_terms.post_tag[i].id );
							break;
						}
					}
				} );
				wp.data.dispatch( 'core/editor' ).editPost( {
					tags: tags,
				} );
			}

			if ( pastePostName ) {
				wp.data.dispatch( 'core/editor' ).editPost( {
					name: parsedPostData.post_name,
				} );
			}

			if ( pasteMetaValues ) {
				wp.data.dispatch( 'core/editor' ).editPost( {
					meta: parsedPostData.post_meta,
				} );

				const settings = getEditorSettings();
				if ( undefined != settings.enableCustomFields && settings.enableCustomFields ) {
					const metaKeySelect = document.getElementById( 'metakeyselect' );
					metaKeySelect.classList.add( 'hide-if-js' );
					const metaKeyInput = document.getElementById( 'metakeyinput' );
					metaKeyInput.classList.remove( 'hide-if-js' );
					const metaValue = document.getElementById( 'metavalue' );
					const newMetaSubmit = document.getElementById( 'newmeta-submit' );
					for ( let key in parsedPostData.classic_meta ) {
						for ( let i = 0; i < parsedPostData.classic_meta[key].length; i++ ) {
							metaKeyInput.value = key;
							metaValue.innerHTML = escapeContent( parsedPostData.classic_meta[key][i] );
							newMetaSubmit.click();
						}
					}
					metaKeyInput.classList.add( 'hide-if-js' );
					metaKeySelect.classList.remove( 'hide-if-js' );
				}
			}

			setNoticeStatus( 'success' );
			setNoticeText( __( 'Pasted in the post.', 'paste-json-text-js' ) );
		}

		// Retrieve post data to get the latest postmeta.
		let classic_meta = null;
		const formData = new FormData();
		formData.append( 'post_id', getPostID() );
		formData.append( '_ajax_nonce', paste_json_text_nonce );
		fetch( ajaxurl + '?action=get_postmeta_classic', {
			method: 'POST',
			cache: 'no-cache',
			credentials: 'same-origin',
			referrerPolicy: 'origin',
			body: formData
		} )
		.then( ( response ) => response.json() )
		.then( ( data ) => {
			classic_meta = data.data.meta;
		} );
		wp.apiFetch( { path: '/wp/v2/categories' } ).then( ( result ) => {
			post_terms.post_category = [];
			result.forEach( function ( category ) {
				post_terms.post_category.push( {
					id:   category.id,
					name: category.name,
					slug: category.slug,
				} );
			} );
		} );
		wp.apiFetch( { path: '/wp/v2/tags' } ).then( ( result ) => {
			post_terms.post_tag = [];
			result.forEach( function ( tag ) {
				post_terms.post_tag.push( {
					id:   tag.id,
					name: tag.name,
					slug: tag.slug,
				} );
			} );
		} );

		const copyRef = wp.compose.useCopyToClipboard( jsonPostData, onCopySuccess );

		return el( Fragment, {},
			noticeText? el( Notice,
				{
					status: noticeStatus,
					onDismiss: function () {
						setNoticeStatus( '' );
						setNoticeText( '' );
					}
				},
				noticeText
			): '',
			el( 'div',
				{ className: 'components-panel__body is-opened' },
				el( 'div',
					{ className: 'components-panel__row' },
					el( Button,
						{
							label: 'copy',
							variant: 'secondary',
							ref: copyRef,
						},
						__( 'Copy content', 'paste-json-text-js' )
					)
				),
				el( 'hr' ),
				el( 'div',
					{ className: 'components-base-control' },
					el( Textarea,
						{
							label: __( 'JSON text', 'paste-json-text-js' ),
							rows: 10,
							value: text,
							onChange: function ( newContent ) {
								setText( newContent );
								confirmPostData( newContent );
							},
						}
					)
				),
				isValidPostData( parsedPostData )? el( 'div',
					{ className: 'components-base-control' },
					el( 'p', {},
						__( 'Select content to paste:', 'paste-json-text-js' )
					),
					el( Toggle,
						{
							label: __( 'Post name(Slug)', 'paste-json-text-js' ),
							help: pastePostName?
								__( 'Paste post name.', 'paste-json-text-js' ):
								__( 'Do not paste post name.', 'paste-json-text-js' ),
							checked: pastePostName,
							onChange: function () {
								setPastePostName( ! pastePostName );
							},
						}
					),
					el( Toggle,
						{
							label: __( 'Custom fields(Meta values)', 'paste-json-text-js' ),
							help: pasteMetaValues?
								__( 'Paste custom fields.', 'paste-json-text-js' ):
								__( 'Do not paste custom fields.', 'paste-json-text-js' ),
							checked: pasteMetaValues,
							onChange: function () {
								setPasteMetaValues( ! pasteMetaValues );
							},
						}
					),
					el( Button,
						{
							label: 'paste it',
							variant: 'secondary',
							onClick: pastePostData
						},
						__( 'Paste to post', 'paste-json-text-js' )
					)
				): ''
			)
		);
	};

	wp.plugins.registerPlugin(
		'plugin-paste-json-text',
		{
			render: function () {
				return el(
					wp.editPost.PluginSidebar,
					{
						name:  'plugin-paste-json-text',
						icon:  'clipboard',
						title: __( 'Paste JSON text', 'paste-json-text-js' ),
					},
					el( PasteJSONForm )
				);
			},
		}
	);
} )( window.wp );