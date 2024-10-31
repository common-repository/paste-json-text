<?php
/*
Plugin Name: Paste JSON text
Plugin URI: https://elearn.jp/wpman/column/paste-json-text.html
Description: JSON text widget add to the admin post/page editor. A post content is changed into a JSON text, and it paste to the other editor.
Author: tmatsuur
Version: 2.0.0
Author URI: https://12net.jp/
*/

/*
    Copyright (C) 2012-2022 tmatsuur (Email: takenori dot matsuura at 12net dot jp)
           This program is licensed under the GNU GPL Version 2.
*/

define( 'PASTE_JSON_TEXT_DOMAIN', 'paste-json-text' );
define( 'PASTE_JSON_TEXT_DB_VERSION_NAME', 'paste-json-text-db-version' );
define( 'PASTE_JSON_TEXT_DB_VERSION', '2.0.0' );

$plugin_paste_json_text = new paste_json_text();
class paste_json_text {
	var $ajax_nonce = 'paste_json_text@12net.jp';
	var $ajax_get_postmeta = 'get_postmeta_classic';
	function __construct() {
		global $pagenow;
		load_plugin_textdomain( PASTE_JSON_TEXT_DOMAIN, false, plugin_basename( dirname( __FILE__ ) ).'/languages' );
		register_activation_hook( __FILE__ , array( &$this , 'activation' ) );
		add_action( 'wp_ajax_' . $this->ajax_get_postmeta, array( $this, 'get_postmeta' ) );
		if ( in_array( $pagenow, array( 'post.php', 'post-new.php', 'page.php', 'page-new.php' ) ) ) {
			add_action( 'admin_head', array( $this, 'style' ) );
			add_action( 'admin_footer', array( $this, 'action' ) );
			add_action( 'admin_init', array( $this, 'setup' ) );
			add_action( 'enqueue_block_editor_assets', array( $this, 'enqueue_block_editor_assets' ) );
		}
	}
	function activation() {
		if ( get_option( PASTE_JSON_TEXT_DB_VERSION_NAME ) != PASTE_JSON_TEXT_DB_VERSION ) {
			update_option( PASTE_JSON_TEXT_DB_VERSION_NAME, PASTE_JSON_TEXT_DB_VERSION );
		}
	}
	function style() {
?>
<style type="text/css">
<!--
#paste_json_text_meta_box #thisjson {
	display: none;
}
#paste_json_text_meta_box #jsontext {
	width: 99%;
}
#paste_json_text_meta_box .error {
	display: block;
	padding: 6px;
	border: 1px solid #FF0000;
	background-color: #FFF0F0;
	border-radius: 3px;
	-webkit-border-radius: 3px;
	-moz-border-radius: 3px;
}
#paste_json_text_meta_box #pastoptions {
	padding-left: 0.5em;
}
-->
</style>
<?php
	}
	function action() {
		global $post_type;
		if ( ! use_block_editor_for_post_type( $post_type ) && 'attachment' !== $post_type ) {
			$this->action_classic_editor();
		}
	}
	function action_classic_editor() {
?>
<script type="text/javascript">
//<![CDATA[
jQuery.noConflict();
( function($) {
	function esc_content( content ) {
		if ( $( '<textarea />' ).html( '<div>&lt; gt;</div>' ).text().match(/^<div></) ) {
			content = content.replace( /&lt;/g, '&amp;lt;' ).replace( /&gt;/g, '&amp;gt;' );
		}
		return $( '<textarea />' ).html( content ).text();
	}
	$( '#pastejson' ).click( function () {
		var jsontext = $( '#jsontext' ).val();
		if ( jsontext.length == 0 ) return false;
		try {
			var jsonobj = $.parseJSON( jsontext );
		} catch (e) {
			$('#jsontext').after( '<span class="error"><?php _e( 'The inputted text is an invalid JSON format.', PASTE_JSON_TEXT_DOMAIN ); ?></span>' );
			return false;
		}
		if ( jsonobj.post_title == null || jsonobj.post_content == null ) {
			$('#jsontext').after( '<span class="error"><?php _e( '"post_title" and "post_content" is required.', PASTE_JSON_TEXT_DOMAIN ); ?></span>' );
			return false;
		}
		// tmce active?
		var tmce = $( '#wp-content-wrap' ).hasClass( 'tmce-active' );
		if ( tmce ) {
			if ( 'undefined' === typeof switchEditors.switchto ) {
				document.getElementById( 'content-html' ).click();
			} else {
				switchEditors.switchto( document.getElementById( 'content-html' ) );
			}
		}
		// paste
		$( '#title' ).val( jsonobj.post_title );
		$( '#title-prompt-text' ).hide();
		if ( $( '#paste-post-name' ).prop( 'checked' ) ) {
			$( '#post_name' ).val( decodeURIComponent( jsonobj.post_name ) );
		}
		/* $( '#content' ).val( $( '<textarea />' ).html( jsonobj.post_content ).text() ); */
		$( '#content' ).val( esc_content( jsonobj.post_content ) );
		$( '#excerpt' ).val( jsonobj.post_excerpt.replace( /<.*?>/g, '' ) );	// [1.1.2] Strip tags.
		$( '[name="post_category[]"]' ).prop( 'checked', false );
		for ( var i = 0; i<jsonobj.post_categories.length; i++ ) {
			var checked = false;
			$( '#categorychecklist label.selectit' ).each( function () {
				if ( $( this ).html().match( new RegExp( '^<.+> '+jsonobj.post_categories[i]+'$' ) ) ) {
					$( this ).find( 'input' ).prop( 'checked', true );
					checked = true;
				}
			} );
			if ( !checked ) { // Not exists.
				$( '#newcategory' ).val( jsonobj.post_categories[i] );
				$( '#category-add-submit' ).click();
			}
		}
		$( '#post_tag .tagchecklist' ).each( function () {
			$( this ).html( '' );
			for ( i = 0; i<jsonobj.post_tags.length; i++ )
				$( this ).append( '<span><a id="post_tag-check-num-'+i+'" class="ntdelbutton">X</a>&nbsp;'+jsonobj.post_tags[i]+'</span>' );
			if ( typeof( tagBox ) == 'object' ) $( this ).find( 'a.ntdelbutton' ).click( function () { tagBox.parseTags(this); } );
		} );
		$( '#tax-input-post_tag' ).val( jsonobj.post_tags.join( ',' ) );
		$( '[name="post_format"]' ).each( function () {
			$(this).prop( 'checked', ( jsonobj.post_format == $(this).val() ) );
		} );
		if ( $( '#paste-post-meta' ).prop( 'checked' ) ) {
			$( '#list-table input.deletemeta' ).click();	// Remove all custom fields.

			var post_meta = [];
			for ( var meta_key in jsonobj.post_meta ) {
				if ( jsonobj.post_meta[meta_key] instanceof Array ) {
					for ( var i=0; i<jsonobj.post_meta[meta_key].length; i++ ) {
						post_meta.push( { key:meta_key, val:jsonobj.post_meta[meta_key][i] } );
					}
				} else {
					post_meta.push( { key:meta_key, val:jsonobj.post_meta[meta_key] } );
				}
			}
			if ( post_meta.length > 0 ) {	// [1.1.2] Add them while delaying one by one.
				var prev_meta = null;
				var timer_id = setInterval( function () {
					next_meta = true;
					if ( prev_meta != null ) {
						$( 'input[value="' + prev_meta.key + '"]' ).each( function () {
							next_meta = $( '#' + $(this).attr( 'id' ).replace( '-key', '-value' ) ).val() === prev_meta.val;
						} );
					}
					if ( next_meta ) {
						_meta = post_meta.pop();
						$( '#metakeyselect' ).hide();
						$( '#metakeyinput' ).show().val( _meta.key );
						$( '#metavalue' ).val( esc_content( _meta.val ) );
						if ( $( '#addmetasub' ).length == 1 ) {
							$( '#addmetasub' ).attr( 'id', 'meta-add-submit' ).click();
						} else if ( $( '#newmeta-submit' ).length == 1 ) {
							$( '#newmeta-submit' ).click();
						} else {
							$( '#meta-add-submit' ).click();
						}
						if ( post_meta.length < 1 ) {
							clearInterval( timer_id );
						}
						prev_meta = _meta;
					}
				} , 250 );
			}
		}
		// when tmce active
		if ( tmce ) {
			if ( 'undefined' === typeof switchEditors.switchto ) {
				document.getElementById( 'content-tmce' ).click();
			} else {
				switchEditors.switchto( document.getElementById( 'content-tmce' ) );
			}
		}

		if ( jsonobj.menu_order !== null ) {
			$( '#menu_order' ).val( jsonobj.menu_order );
		}

		if ( typeof jsonobj.extra == 'object' ) {
			for ( var form_id in jsonobj.extra ) {
				if ( jsonobj.extra[form_id] !== null ) {
					$( '#' + form_id ).val( jsonobj.extra[form_id] );
				}
			}
		}

		// scroll up
		$( 'html,body' ).animate( {'scrollTop':'0px'},'slow' );
		return false;
	} );
	$(document).ready( function () {
		if ( typeof clipboard_enabled !== 'undefined' && clipboard_enabled ) {
			const clipboard = new ClipboardJS( '#copyjson' );
			clipboard.on( 'success', function ( e ) {
				$( '#paste-message' ).text( '<?php _e( 'Post data was copied to clipboard.', PASTE_JSON_TEXT_DOMAIN ); ?>' );
				$( '#jsontext' ).val( e.text );
			} );
		} else {
			$( '#copyjson' ).click( function () {
				var temp = document.createElement( 'div' );
				temp.appendChild( document.createElement( 'pre' ) ).textContent = $(this).data( 'clipboard-text' );
				temp.style.position = 'fixed';
				temp.style.left = '-100%';
				document.body.appendChild( temp );
				document.getSelection().selectAllChildren( temp );
				var result = document.execCommand( 'copy' );
				document.body.removeChild( temp );
				if ( result ) {
					$( '#paste-message' ).text( '<?php _e( 'Post data was copied to clipboard.', PASTE_JSON_TEXT_DOMAIN ); ?>' );
				}
				$( '#jsontext' ).val( $(this).data( 'clipboard-text' ) );
				return false;
			} );
		}
	} );
} )( jQuery );
//]]>
</script>
<?php
	}

	/**
	 * @since 2.0.0
	 */
	function enqueue_block_editor_assets() {
		global $post_type;
		$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
		wp_enqueue_script( 'paste-json-text',
			plugins_url( 'assets/js/paste-json' . $suffix . '.js', __FILE__ ),
			array( 'wp-plugins', 'wp-edit-post', 'wp-element', 'wp-components', 'wp-data', 'wp-i18n' )
		);
		$post_terms = 'const post_terms = {';
		$taxonomies = get_object_taxonomies( $post_type );
		foreach ( $taxonomies as $taxonomy ) {
			$post_terms .= $taxonomy . ':[';
			$terms = get_terms( [ 'taxonomy' => $taxonomy, 'hide_empty'=> false ] );
			foreach ( $terms as $term ) {
				$post_terms .= '{ id:' . $term->term_id . ', name:"' . $term->name . '", slug:"' . $term->slug . '"},';
			}
			$post_terms .= '],';
		}
		$post_terms .= '};';
		$post_terms .= 'var paste_json_text_nonce = "' . esc_attr( wp_create_nonce( $this->ajax_nonce . $this->ajax_get_postmeta ) ) . '"';
		wp_add_inline_script( 'paste-json-text', $post_terms );
		wp_set_script_translations( 'paste-json-text', 'paste-json-text-js', plugin_dir_path( __FILE__ ) . 'languages' );
	}

	function setup() {
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );
		$title = __( 'JSON text pad', PASTE_JSON_TEXT_DOMAIN );

		$types = get_post_types( array( 'public' => true ) );
		foreach ( $types as $post_type ) {
			if ( ! use_block_editor_for_post_type( $post_type ) && 'attachment' !== $post_type ) {
				add_meta_box( 'paste_json_text_meta_box', $title, array( $this, 'meta_box' ), $post_type );
			}
		}
	}

	function admin_enqueue_scripts() {
		if ( wp_script_is( 'clipboard', 'registered' ) ) {
			wp_enqueue_script( 'clipboard' );
			wp_add_inline_script( 'clipboard', "const clipboard_enabled = true;\n" );
		}
	}

	function meta_box() {
		global $post;

		$thisjson = '';
		$json_error = '';
		if ( function_exists( 'json_encode' ) ) {
			$thiscats = array();
			$cats = get_the_category( $post->ID );
			if ( is_array( $cats ) ) foreach ( $cats as $cat ) $thiscats[] = $cat->name;
			$thistags = array();
			$tags = get_the_tags( $post->ID );
			if ( is_array( $tags ) ) foreach ( $tags as $tag ) $thistags[] = $tag->name;
			$thismeta = array();
			if ( is_array( $thismeta ) ) foreach ( get_post_custom( $post->ID ) as $key=>$meta ) if ( !preg_match( '/^_/', $key ) ) $thismeta[$key] = $meta;
			$thispost = (object)array(
				'post_title'		=> $post->post_title,
				'post_name'			=> $post->post_name,
//				'post_content'=>str_replace( '&', '&amp;amp;', $post->post_content ),	// 1.0.7
				'post_content'		=>str_replace( '&', '&amp;', $post->post_content ),	// 1.0.8
				'post_excerpt'		=> $post->post_excerpt,
				'post_date'			=> $post->post_date,
				'post_modified'		=> $post->post_modified,
				'post_type'			=> $post->post_type,
				'post_format'		=> get_post_format( $post->ID ),
				'post_categories'	=> $thiscats,
				'post_tags'			=> $thistags,
				'post_meta'			=> $thismeta,
				'menu_order'		=> $post->menu_order,
				'extra'				=> null,
				'@version'			=> PASTE_JSON_TEXT_DB_VERSION
			);
			$thispost->extra = apply_filters( 'paste_json_extra', $thispost->extra );	// 1.1.0
			$thisjson = json_encode( $thispost );
			if ( empty( $thisjson ) ) {
				$json_error = __( 'Failed to generate JSON.', PASTE_JSON_TEXT_DOMAIN );
			}
		} else {
			$json_error = __( 'Please check the version of PHP.', PASTE_JSON_TEXT_DOMAIN );
		}
?>
<p><input type="button" name="copyjson" id="copyjson" class="button" data-clipboard-text="<?php echo esc_attr( $thisjson ); ?>" value="<?php _e( 'Change JSON', PASTE_JSON_TEXT_DOMAIN ); ?>" />
<span id="paste-message"><?php _e( 'This post contents are copied to the textarea.', PASTE_JSON_TEXT_DOMAIN ); ?></span></p>
<p id="json-error"><?php echo esc_html( $json_error ); ?></p>
<textarea id="jsontext" name="jsontext" rows="10" cols="80" tabindex="18"></textarea>
<p><input type="button" name="pastejson" id="pastejson" class="button" value="<?php _e( 'JSON paste', PASTE_JSON_TEXT_DOMAIN ); ?>" tabindex="19">
<?php _e( 'The inputted JSON text is parsed and it pasted.', PASTE_JSON_TEXT_DOMAIN ); ?>
</p>
<p id="pastoptions"><input type="checkbox" name="paste-post-name" id="paste-post-name" value="1" /> <?php _e( 'Paste post name.', PASTE_JSON_TEXT_DOMAIN ); ?><br />
<input type="checkbox" name="paste-post-meta" id="paste-post-meta" value="1" /> <?php _e( 'Paste meta values.', PASTE_JSON_TEXT_DOMAIN ); ?><br />
</p>
<?php
	}

	/**
	 * @since 2.0.0
	 */
	function get_postmeta() {
		if ( ! ( defined( 'DOING_AJAX' ) && DOING_AJAX && isset( $_POST['post_id'] ) &&
			( current_user_can( 'edit_post' ) || current_user_can( 'edit_published_posts' ) ) ) ) {
			wp_die( -1 );
		}

		check_ajax_referer( $this->ajax_nonce . $this->ajax_get_postmeta );

		$_POST['post_id'] = intval( $_POST['post_id'] );
		$data = [
			'post_id' => $_POST['post_id']
		];
		$metas = get_post_custom( $_POST['post_id'] );
		if ( is_array( $metas ) ) foreach ( array_keys( $metas ) as $key ) {
			if ( preg_match( '/^_/u', $key ) ) {
				unset( $metas[$key] );
			}
		}
		$data['meta'] = $metas;
		wp_send_json_success( $data );
		wp_die( 0 );
	}
}