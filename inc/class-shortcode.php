<?php
/**
 * Shortcode [db_collegamenti_utili] — carosello dei collegamenti in home.
 * Riusa il componente carousel di Bootstrap Italia già presente nel tema
 * Design Scuola Italia, così non duplica librerie e resta accessibile.
 *
 * @package DB_Collegamenti_Utili
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Gestore shortcode.
 */
class DBCU_Shortcode {

	/**
	 * Aggancia gli hook.
	 */
	public static function init() {
		add_shortcode( 'db_collegamenti_utili', array( __CLASS__, 'render' ) );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'register_assets' ) );
	}

	/**
	 * Registra (non carica) il CSS frontend; verrà caricato solo se lo shortcode è presente.
	 */
	public static function register_assets() {
		wp_register_style( 'dbcu-frontend', DBCU_URL . 'assets/css/frontend.css', array(), DBCU_VERSION );

		// Nessuna dipendenza dichiarata: Splide è caricato dal tema Design Scuola
		// Italia con un handle che non conosciamo. Lo script gira in footer e
		// verifica window.Splide a runtime, degradando a lista statica se manca.
		wp_register_script( 'dbcu-frontend', DBCU_URL . 'assets/js/frontend.js', array(), DBCU_VERSION, true );
	}

	/**
	 * Interpreta un attributo booleano dello shortcode.
	 *
	 * Serve a distinguere una disattivazione esplicita da tutto il resto, ora che
	 * l'autoplay è attivo di default. Non si usa wp_validate_boolean() perché
	 * considera vero qualsiasi stringa diversa da 'false', quindi `autoplay="no"`
	 * accenderebbe lo scorrimento invece di spegnerlo: chi scrive lo shortcode a
	 * mano nell'editor non ha motivo di sapere quali parole contano.
	 *
	 * @param mixed $valore Valore grezzo dell'attributo.
	 * @return bool
	 */
	protected static function attributo_attivo( $valore ) {
		if ( is_bool( $valore ) ) {
			return $valore;
		}
		$valore = strtolower( trim( (string) $valore ) );
		$spento = array( 'false', '0', 'no', 'off', 'falso', 'spento', 'disattivo', '' );

		return ! in_array( $valore, $spento, true );
	}

	/**
	 * Render dello shortcode.
	 *
	 * @param array $atts Attributi.
	 * @return string
	 */
	public static function render( $atts ) {
		$atts = shortcode_atts(
			array(
				'titolo'     => __( 'Collegamenti utili', 'db-collegamenti-utili' ),
				'per_view'   => 3,      // Loghi visibili per slide su desktop.
				'autoplay'   => 'true', // Attivo salvo disattivazione esplicita: il criterio WCAG 2.2.2 chiede un comando di pausa, non l'assenza di scorrimento, e il pulsante c'è.
				'intervallo' => 5,      // Secondi fra uno scorrimento e l'altro, se autoplay è attivo.
			),
			$atts,
			'db_collegamenti_utili'
		);

		$query = new WP_Query(
			array(
				'post_type'              => DBCU_CPT,
				'post_status'            => 'publish',
				'posts_per_page'         => 50,
				'orderby'                => 'date',
				'order'                  => 'DESC',
				'no_found_rows'          => true,
				'ignore_sticky_posts'    => true,
				'update_post_term_cache' => false,
				'meta_query'             => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
					array(
						'key'   => '_dbcu_show_in_carousel',
						'value' => '1',
					),
				),
			)
		);

		if ( ! $query->have_posts() ) {
			return '';
		}

		/*
		 * L'ordinamento avviene qui e non in SQL di proposito: passare 'meta_key' +
		 * 'meta_value_num' a WP_Query aggiunge un INNER JOIN sulla postmeta, che fa
		 * sparire dal carosello le voci prive di '_dbcu_carousel_order' anche col
		 * flag attivo (voci create da import, WP-CLI o salvataggi via REST). Qui le
		 * voci senza ordine valgono 0 e restano visibili. Il confronto sulla data
		 * come spareggio rende l'esito deterministico anche su PHP 7.4, dove usort
		 * non è ancora stabile.
		 */
		$slides = $query->posts;
		usort(
			$slides,
			static function ( $a, $b ) {
				$order_a = (int) get_post_meta( $a->ID, '_dbcu_carousel_order', true );
				$order_b = (int) get_post_meta( $b->ID, '_dbcu_carousel_order', true );
				if ( $order_a !== $order_b ) {
					return $order_a - $order_b;
				}
				return strcmp( $b->post_date, $a->post_date );
			}
		);

		wp_enqueue_style( 'dbcu-frontend' );

		wp_enqueue_script( 'dbcu-frontend' );

		$per_view = max( 1, min( 4, (int) $atts['per_view'] ) );
		$uid      = 'dbcu-' . wp_unique_id();
		$total    = count( $slides );

		/*
		 * Se c'è o no qualcosa da far scorrere NON si decide qui: dipende dalla
		 * larghezza dello schermo, perché i breakpoint abbassano `perPage` (a 2 sotto
		 * i 992px, a 1 sotto i 768px). Con 2 voci e per_view 3 il carosello è fermo su
		 * desktop ma ha due pagine su telefono. Frecce e trascinamento restano quindi
		 * sempre attivi e se ne occupa Splide, che conosce il breakpoint corrente;
		 * frontend.js nasconde i comandi solo quando servono davvero a nulla.
		 */
		// Con una voce sola non c'è nulla da far scorrere, qualunque cosa dica l'attributo.
		$autoplay = $total > 1 && self::attributo_attivo( $atts['autoplay'] );

		$interval = max( 3, (int) $atts['intervallo'] );

		/*
		 * Le opzioni viaggiano in un data attribute e vengono applicate dal nostro
		 * frontend.js. Non ci si può appoggiare all'inizializzatore del tema: quello
		 * di Bootstrap Italia legge le classi `it-carousel-*` sull'elemento Splide
		 * stesso (non sul wrapper), monta con opzioni tutte `undefined` se non le
		 * trova, e comunque forza `arrows: false` e non prevede l'autoplay.
		 */
		$options = array(
			// 'loop' solo se le voci superano quelle visibili anche su desktop:
			// in ciclo Splide clona le slide per riempire la pagina, e con poche
			// voci si vedrebbero gli stessi loghi ripetuti. 'rewind' dà comunque
			// la continuità che serve allo scorrimento automatico.
			'type'         => $total > $per_view ? 'loop' : 'slide',
			'rewind'       => true,
			'perPage'      => $per_view,
			'perMove'      => 1,
			'gap'          => '1.5rem',
			'arrows'       => true,
			'drag'         => true,
			'pagination'   => false,
			'slideFocus'   => false,
			'autoplay'     => $autoplay,
			'interval'     => $interval * 1000,
			'pauseOnHover' => true,
			'pauseOnFocus' => true,
			'i18n'         => array(
				'prev' => __( 'Vai al collegamento precedente', 'db-collegamenti-utili' ),
				'next' => __( 'Vai al collegamento successivo', 'db-collegamenti-utili' ),
			),
			'breakpoints'  => array(
				992 => array( 'perPage' => min( 2, $per_view ) ),
				768 => array( 'perPage' => 1 ),
			),
		);

		ob_start();
		?>
		<section class="dbcu-carousel-section" <?php echo $atts['titolo'] ? 'aria-labelledby="' . esc_attr( $uid ) . '-title"' : 'aria-label="' . esc_attr__( 'Collegamenti utili', 'db-collegamenti-utili' ) . '"'; ?>>
			<?php if ( $atts['titolo'] ) : ?>
				<h2 class="dbcu-carousel-title text-center" id="<?php echo esc_attr( $uid ); ?>-title"><?php echo esc_html( $atts['titolo'] ); ?></h2>
			<?php endif; ?>

			<div class="it-carousel-wrapper it-carousel-landscape-abstract-three-cols dbcu-carousel"
				id="<?php echo esc_attr( $uid ); ?>"
				data-dbcu-options="<?php echo esc_attr( wp_json_encode( $options ) ); ?>">
				<?php
				/*
				 * Volutamente `dbcu-splide` e non `splide`: bootstrap-italia.js cicla su
				 * getElementsByClassName('splide') e monterebbe una seconda istanza sopra
				 * la nostra. Splide non richiede quella classe sulla root — i fogli di
				 * stile agganciano `splide__track` / `splide__list` / `splide__slide` e i
				 * modificatori che Splide aggiunge da sé (`splide--ltr` e simili).
				 */
				?>
				<div class="dbcu-splide" role="group" aria-label="<?php esc_attr_e( 'Carosello collegamenti utili', 'db-collegamenti-utili' ); ?>">
					<div class="splide__track">
						<ul class="splide__list">
							<?php
							foreach ( $slides as $slide ) {
								echo self::render_slide( $slide->ID ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
							}
							?>
						</ul>
					</div>

					<?php // I comandi sono nascosti via CSS finché frontend.js non ha montato Splide, e di nuovo quando alla larghezza corrente non c'è nulla da scorrere. ?>
						<div class="splide__arrows dbcu-arrows">
							<button class="splide__arrow splide__arrow--prev dbcu-arrow dbcu-arrow--prev" type="button" aria-label="<?php esc_attr_e( 'Vai al collegamento precedente', 'db-collegamenti-utili' ); ?>">
								<svg class="icon" aria-hidden="true" focusable="false"><use xlink:href="#it-chevron-left" href="#it-chevron-left"></use></svg>
							</button>

							<?php if ( $autoplay ) : ?>
								<?php // WCAG 2.2.2 (livello A): lo scorrimento automatico deve avere un comando di pausa raggiungibile da tastiera. ?>
								<button class="dbcu-arrow dbcu-autoplay-toggle" type="button"
									data-label-pausa="<?php esc_attr_e( 'Metti in pausa lo scorrimento automatico', 'db-collegamenti-utili' ); ?>"
									data-label-riprendi="<?php esc_attr_e( 'Riprendi lo scorrimento automatico', 'db-collegamenti-utili' ); ?>"
									aria-label="<?php esc_attr_e( 'Metti in pausa lo scorrimento automatico', 'db-collegamenti-utili' ); ?>">
									<svg class="icon dbcu-icon-pausa" aria-hidden="true" focusable="false"><use xlink:href="#it-pause" href="#it-pause"></use></svg>
									<svg class="icon dbcu-icon-play" aria-hidden="true" focusable="false"><use xlink:href="#it-play" href="#it-play"></use></svg>
								</button>
							<?php endif; ?>

							<button class="splide__arrow splide__arrow--next dbcu-arrow dbcu-arrow--next" type="button" aria-label="<?php esc_attr_e( 'Vai al collegamento successivo', 'db-collegamenti-utili' ); ?>">
								<svg class="icon" aria-hidden="true" focusable="false"><use xlink:href="#it-chevron-right" href="#it-chevron-right"></use></svg>
							</button>
						</div>
				</div>
			</div>
		</section>
		<?php
		return ob_get_clean();
	}

	/**
	 * Singola slide (un logo).
	 *
	 * @param int $post_id ID post.
	 * @return string
	 */
	protected static function render_slide( $post_id ) {
		$logo_id = (int) get_post_meta( $post_id, '_dbcu_carousel_logo_id', true );
		$alt     = get_post_meta( $post_id, '_dbcu_carousel_logo_alt', true );
		$link    = get_post_meta( $post_id, '_dbcu_external_link', true );
		$title   = get_the_title( $post_id );

		if ( ! $alt ) {
			$alt = $title;
		}

		// Fallback all'immagine in evidenza se manca il logo carosello.
		if ( ! $logo_id ) {
			$logo_id = get_post_thumbnail_id( $post_id );
		}

		$img_html = $logo_id
			? wp_get_attachment_image(
				$logo_id,
				'medium',
				false,
				array(
					'alt'     => $alt,
					'class'   => 'dbcu-slide-logo',
					'loading' => 'lazy',
				)
			)
			: '<span class="dbcu-slide-noimg">' . esc_html( $title ) . '</span>';

		$is_external = ! empty( $link );
		$url         = $is_external ? $link : get_permalink( $post_id );

		$link_attrs = 'href="' . esc_url( $url ) . '" class="dbcu-slide-link"';
		$sr_label   = $title;
		if ( $is_external ) {
			$link_attrs .= ' target="_blank" rel="noopener noreferrer"';
			/* translators: %s: nome del collegamento */
			$sr_label = sprintf( __( '%s (si apre in una nuova scheda)', 'db-collegamenti-utili' ), $title );
		}

		ob_start();
		?>
		<li class="splide__slide dbcu-slide">
			<a <?php echo $link_attrs; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> aria-label="<?php echo esc_attr( $sr_label ); ?>">
				<span class="dbcu-slide-figure"><?php echo $img_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
				<span class="dbcu-slide-caption"><?php echo esc_html( $title ); ?>
					<?php if ( $is_external ) : ?>
						<svg class="icon icon-xs dbcu-ext-icon" aria-hidden="true" focusable="false"><use xlink:href="#it-external-link" href="#it-external-link"></use></svg>
					<?php endif; ?>
				</span>
			</a>
		</li>
		<?php
		return ob_get_clean();
	}
}
