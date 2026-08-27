/* global Splide */
/**
 * Montaggio del carosello dei collegamenti utili.
 *
 * Perché il plugin monta Splide da sé invece di lasciar fare al tema:
 * l'inizializzatore di bootstrap-italia.js cicla su getElementsByClassName('splide')
 * e legge le classi `it-carousel-*` sull'elemento Splide stesso, non sul wrapper.
 * Se non le trova monta comunque, con un oggetto di opzioni in cui ogni valore è
 * `undefined` (type, perPage, gap, arrows), e il carosello resta bloccato a una
 * slide a piena larghezza con i comandi inerti. Anche riconoscendolo, quel percorso
 * forza `arrows: false` e non prevede l'autoplay.
 *
 * La libreria non viene duplicata: si riusa `window.Splide` già caricato dal tema.
 * Se non c'è, il markup resta una lista statica di loghi perfettamente utilizzabile.
 */
( function () {
	'use strict';

	function montaCarosello( wrapper ) {
		var elemento = wrapper.querySelector( '.dbcu-splide' );
		if ( ! elemento || elemento.hasAttribute( 'data-dbcu-montato' ) ) {
			return;
		}

		var opzioni;
		try {
			opzioni = JSON.parse( wrapper.getAttribute( 'data-dbcu-options' ) || '{}' );
		} catch ( e ) {
			return;
		}

		// WCAG 2.3.3: chi ha chiesto meno animazioni non deve subire lo scorrimento
		// automatico, a prescindere da come è configurato lo shortcode.
		var motoRidotto = window.matchMedia &&
			window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches;
		if ( motoRidotto ) {
			opzioni.autoplay = false;
		}

		var carosello = new Splide( elemento, opzioni );

		/*
		 * Se alla larghezza corrente ci sia o no qualcosa da scorrere lo sa solo
		 * Splide: i breakpoint cambiano `perPage`, quindi lo stesso carosello può
		 * essere fermo su desktop e scorrevole su telefono. `Controller.getEnd()`
		 * vale 0 quando esiste una sola pagina. Va ricalcolato a ogni ridimensiona-
		 * mento, non una volta sola al montaggio.
		 */
		function aggiornaScorrimento() {
			var fine = carosello.Components.Controller.getEnd();
			wrapper.classList.toggle( 'dbcu-carousel--statico', fine <= 0 );
		}

		carosello.on( 'mounted resized updated', aggiornaScorrimento );
		carosello.mount();

		elemento.setAttribute( 'data-dbcu-montato', '1' );
		wrapper.classList.add( 'dbcu-carousel--montato' );
		aggiornaScorrimento();

		collegaPulsantePausa( wrapper, carosello, opzioni.autoplay, motoRidotto );
	}

	/**
	 * Collega il pulsante pausa/riprendi richiesto dal criterio WCAG 2.2.2.
	 */
	function collegaPulsantePausa( wrapper, carosello, autoplayAttivo, motoRidotto ) {
		var pulsante = wrapper.querySelector( '.dbcu-autoplay-toggle' );
		if ( ! pulsante ) {
			return;
		}

		// Senza scorrimento automatico il comando non ha nulla da comandare.
		if ( ! autoplayAttivo || ! carosello.Components.Autoplay ) {
			pulsante.hidden = true;
			if ( motoRidotto ) {
				pulsante.remove();
			}
			return;
		}

		var autoplay = carosello.Components.Autoplay;
		var inPausaPerScelta = false;

		function aggiornaEtichetta() {
			var etichetta = inPausaPerScelta
				? pulsante.getAttribute( 'data-label-riprendi' )
				: pulsante.getAttribute( 'data-label-pausa' );
			pulsante.setAttribute( 'aria-label', etichetta );
			pulsante.classList.toggle( 'is-paused', inPausaPerScelta );
		}

		pulsante.addEventListener( 'click', function () {
			inPausaPerScelta = ! inPausaPerScelta;
			if ( inPausaPerScelta ) {
				autoplay.pause();
			} else {
				autoplay.play();
			}
			aggiornaEtichetta();
		} );

		// pauseOnHover/pauseOnFocus usano le stesse pause() e play() del pulsante:
		// senza questo, allontanare il mouse farebbe ripartire un carosello che
		// l'utente aveva fermato di proposito, vanificando il comando.
		carosello.on( 'autoplay:play', function () {
			if ( inPausaPerScelta ) {
				autoplay.pause();
			}
		} );

		aggiornaEtichetta();
	}

	function avvia() {
		if ( typeof Splide === 'undefined' ) {
			return;
		}
		var caroselli = document.querySelectorAll( '.dbcu-carousel[data-dbcu-options]' );
		Array.prototype.forEach.call( caroselli, montaCarosello );
	}

	if ( 'loading' === document.readyState ) {
		document.addEventListener( 'DOMContentLoaded', avvia );
	} else {
		avvia();
	}
} )();
