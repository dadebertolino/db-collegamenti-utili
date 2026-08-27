/* global jQuery, wp, dbcuI18n */
( function ( $ ) {
	'use strict';

	var i18n = window.dbcuI18n || {};

	// Contatore per gli indici delle nuove righe allegato. Parte da Date.now()
	// per non collidere con le righe già rese dal PHP, ma poi incrementa: usare
	// Date.now() a ogni click farebbe collidere due righe aggiunte nello stesso
	// millisecondo. Gli indici servono solo a tenere distinti i campi nel POST,
	// perché il PHP li reindicizza al salvataggio.
	var rowIndex = Date.now();

	/**
	 * Media picker per immagini (logo carosello).
	 */
	$( document ).on( 'click', '.dbcu-media-select', function ( e ) {
		e.preventDefault();
		var $btn    = $( this );
		var target  = $btn.data( 'target' );
		var preview = $btn.data( 'preview' );

		var frame = wp.media( {
			title: i18n.selectImage || 'Seleziona immagine',
			button: { text: i18n.useImage || 'Usa questa immagine' },
			library: { type: 'image' },
			multiple: false
		} );

		frame.on( 'select', function () {
			var att = frame.state().get( 'selection' ).first().toJSON();
			$( '#' + target ).val( att.id );
			var url = att.sizes && att.sizes.medium ? att.sizes.medium.url : att.url;
			$( '#' + preview ).html(
				$( '<img>', { src: url, alt: att.alt || '', css: { maxWidth: '180px', height: 'auto' } } )
			);
		} );

		frame.open();
	} );

	/**
	 * Rimozione immagine.
	 */
	$( document ).on( 'click', '.dbcu-media-remove', function ( e ) {
		e.preventDefault();
		var $btn = $( this );
		$( '#' + $btn.data( 'target' ) ).val( '' );
		$( '#' + $btn.data( 'preview' ) ).empty();
	} );

	/**
	 * Media picker per allegati (qualsiasi tipo di file).
	 */
	$( document ).on( 'click', '.dbcu-file-select', function ( e ) {
		e.preventDefault();
		var $row = $( this ).closest( '.dbcu-attachment-row' );

		var frame = wp.media( {
			title: i18n.selectFile || 'Seleziona allegato',
			button: { text: i18n.useFile || 'Usa questo file' },
			multiple: false
		} );

		frame.on( 'select', function () {
			var att = frame.state().get( 'selection' ).first().toJSON();
			$row.find( '.dbcu-file-id' ).val( att.id );
			$row.find( '.dbcu-file-name' ).text( att.filename || att.title );
			// Se il titolo visualizzato è vuoto, precompila col titolo del file.
			var $title = $row.find( 'input[name*="[title]"]' );
			if ( $title.length && ! $title.val() ) {
				$title.val( att.title );
			}
		} );

		frame.open();
	} );

	/**
	 * Aggiunta riga allegato.
	 */
	$( document ).on( 'click', '#dbcu-add-attachment', function ( e ) {
		e.preventDefault();
		var tpl = $( '#dbcu-attachment-template' ).html();
		tpl = tpl.replace( /__INDEX__/g, rowIndex++ );
		$( '#dbcu-attachments-list' ).append( tpl );
	} );

	/**
	 * Rimozione riga allegato.
	 */
	$( document ).on( 'click', '.dbcu-attachment-remove', function ( e ) {
		e.preventDefault();
		if ( window.confirm( i18n.confirmDel || 'Rimuovere questo allegato?' ) ) {
			$( this ).closest( '.dbcu-attachment-row' ).remove();
		}
	} );

} )( jQuery );
