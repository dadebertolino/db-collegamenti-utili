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
				'titolo'   => __( 'Collegamenti utili', 'db-collegamenti-utili' ),
				'per_view' => 3,       // Loghi visibili per slide su desktop.
				'autoplay' => 'false', // Off di default: WCAG scoraggia l'auto-scroll non controllato.
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

		$per_view = max( 1, min( 4, (int) $atts['per_view'] ) );
		$autoplay = ( 'true' === $atts['autoplay'] || '1' === (string) $atts['autoplay'] ) ? 'true' : 'false';
		$uid      = 'dbcu-' . wp_unique_id();

		ob_start();
		?>
		<section class="dbcu-carousel-section" aria-label="<?php echo esc_attr( $atts['titolo'] ); ?>">
			<?php if ( $atts['titolo'] ) : ?>
				<h2 class="dbcu-carousel-title text-center"><?php echo esc_html( $atts['titolo'] ); ?></h2>
			<?php endif; ?>

			<div class="it-carousel-wrapper it-carousel-landscape-abstract-three-cols dbcu-carousel"
				id="<?php echo esc_attr( $uid ); ?>"
				data-bs-ride="<?php echo esc_attr( ( 'true' === $autoplay ) ? 'carousel' : 'false' ); ?>"
				data-dbcu-per-view="<?php echo esc_attr( $per_view ); ?>">
				<div class="splide" role="group" aria-label="<?php esc_attr_e( 'Carosello collegamenti utili', 'db-collegamenti-utili' ); ?>">
					<div class="splide__track">
						<ul class="splide__list">
							<?php
							foreach ( $slides as $slide ) {
								echo self::render_slide( $slide->ID ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
							}
							?>
						</ul>
					</div>

					<div class="splide__arrows dbcu-arrows">
						<button class="splide__arrow splide__arrow--prev dbcu-arrow dbcu-arrow--prev" type="button" aria-label="<?php esc_attr_e( 'Vai al collegamento precedente', 'db-collegamenti-utili' ); ?>">
							<svg class="icon" aria-hidden="true" focusable="false"><use xlink:href="#it-chevron-left" href="#it-chevron-left"></use></svg>
						</button>
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
