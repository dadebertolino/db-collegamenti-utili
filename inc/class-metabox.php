<?php
/**
 * Meta box e salvataggio dei campi personalizzati (campi nativi, nessuna dipendenza).
 *
 * @package DB_Collegamenti_Utili
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Gestore meta box.
 */
class DBCU_Metabox {

	/**
	 * Aggancia gli hook.
	 */
	public static function init() {
		add_action( 'add_meta_boxes', array( __CLASS__, 'add_boxes' ) );
		add_action( 'save_post_' . DBCU_CPT, array( __CLASS__, 'save' ), 10, 2 );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue' ) );
		add_action( 'admin_notices', array( __CLASS__, 'alt_missing_notice' ) );
	}

	/**
	 * Avvisa se è stato salvato un logo carosello senza testo alternativo.
	 *
	 * La validazione è lato server e non con l'attributo HTML5 `required`: le
	 * meta box classiche vengono inviate da Gutenberg dentro un iframe nascosto,
	 * dove una validazione fallita bloccherebbe il salvataggio senza mostrare
	 * alcun messaggio all'utente.
	 */
	public static function alt_missing_notice() {
		$screen = get_current_screen();
		if ( ! $screen || DBCU_CPT !== $screen->post_type || 'post' !== $screen->base ) {
			return;
		}

		$post_id = isset( $_GET['post'] ) ? absint( $_GET['post'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( ! $post_id || ! get_transient( 'dbcu_alt_missing_' . $post_id ) ) {
			return;
		}
		delete_transient( 'dbcu_alt_missing_' . $post_id );

		printf(
			'<div class="notice notice-warning is-dismissible"><p>%s</p></div>',
			esc_html__( 'Hai impostato un logo per il carosello senza testo alternativo. Il logo resta visibile, ma per gli screen reader sarà descritto dal titolo della voce: compila il campo "Testo alternativo del logo" per rispettare le WCAG 2.1 AA.', 'db-collegamenti-utili' )
		);
	}

	/**
	 * Carica media picker e asset admin solo sulla schermata del CPT.
	 *
	 * @param string $hook Hook corrente.
	 */
	public static function enqueue( $hook ) {
		$screen = get_current_screen();
		if ( ! $screen || DBCU_CPT !== $screen->post_type ) {
			return;
		}
		if ( ! in_array( $hook, array( 'post.php', 'post-new.php' ), true ) ) {
			return;
		}

		wp_enqueue_media();

		if ( file_exists( DBCU_DIR . 'assets/css/db-admin-ui.css' ) ) {
			wp_enqueue_style( 'dbcu-admin-ui', DBCU_URL . 'assets/css/db-admin-ui.css', array(), DBCU_VERSION );
		}
		wp_enqueue_style( 'dbcu-admin', DBCU_URL . 'assets/css/admin.css', array( 'dbcu-admin-ui' ), DBCU_VERSION );
		wp_enqueue_script( 'dbcu-admin', DBCU_URL . 'assets/js/admin.js', array( 'jquery' ), DBCU_VERSION, true );
		wp_localize_script(
			'dbcu-admin',
			'dbcuI18n',
			array(
				'selectImage'  => __( 'Seleziona immagine', 'db-collegamenti-utili' ),
				'useImage'     => __( 'Usa questa immagine', 'db-collegamenti-utili' ),
				'selectFile'   => __( 'Seleziona allegato', 'db-collegamenti-utili' ),
				'useFile'      => __( 'Usa questo file', 'db-collegamenti-utili' ),
				'remove'       => __( 'Rimuovi', 'db-collegamenti-utili' ),
				'confirmDel'   => __( 'Rimuovere questo allegato?', 'db-collegamenti-utili' ),
			)
		);
	}

	/**
	 * Registra le meta box.
	 */
	public static function add_boxes() {
		add_meta_box(
			'dbcu_details',
			__( 'Dettagli collegamento / bando', 'db-collegamenti-utili' ),
			array( __CLASS__, 'render_details' ),
			DBCU_CPT,
			'normal',
			'high'
		);
		add_meta_box(
			'dbcu_attachments',
			__( 'Documenti allegati', 'db-collegamenti-utili' ),
			array( __CLASS__, 'render_attachments' ),
			DBCU_CPT,
			'normal',
			'default'
		);
	}

	/**
	 * Meta box dei dettagli principali.
	 *
	 * @param WP_Post $post Post corrente.
	 */
	public static function render_details( $post ) {
		wp_nonce_field( 'dbcu_save', 'dbcu_nonce' );

		$logo_id   = (int) get_post_meta( $post->ID, '_dbcu_carousel_logo_id', true );
		$logo_alt  = get_post_meta( $post->ID, '_dbcu_carousel_logo_alt', true );
		$link      = get_post_meta( $post->ID, '_dbcu_external_link', true );
		$show      = get_post_meta( $post->ID, '_dbcu_show_in_carousel', true );
		$order     = get_post_meta( $post->ID, '_dbcu_carousel_order', true );

		$img = $logo_id ? wp_get_attachment_image( $logo_id, 'medium', false, array( 'style' => 'max-width:180px;height:auto;' ) ) : '';
		?>
		<div class="dbcu-field">
			<label class="dbcu-label"><?php esc_html_e( 'Logo per il carosello (secondo logo)', 'db-collegamenti-utili' ); ?></label>
			<p class="description"><?php esc_html_e( 'Il logo principale della pagina di dettaglio è l\'immagine in evidenza. Qui carichi il logo mostrato nel carosello in home.', 'db-collegamenti-utili' ); ?></p>
			<div class="dbcu-media-preview" id="dbcu-logo-preview"><?php echo $img; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
			<input type="hidden" id="dbcu_carousel_logo_id" name="dbcu_carousel_logo_id" value="<?php echo esc_attr( $logo_id ); ?>" />
			<button type="button" class="button dbcu-media-select" data-target="dbcu_carousel_logo_id" data-preview="dbcu-logo-preview"><?php esc_html_e( 'Scegli logo', 'db-collegamenti-utili' ); ?></button>
			<button type="button" class="button dbcu-media-remove" data-target="dbcu_carousel_logo_id" data-preview="dbcu-logo-preview"><?php esc_html_e( 'Rimuovi', 'db-collegamenti-utili' ); ?></button>
		</div>

		<div class="dbcu-field">
			<label class="dbcu-label" for="dbcu_carousel_logo_alt"><?php esc_html_e( 'Testo alternativo del logo (alt) — obbligatorio se c\'è un logo', 'db-collegamenti-utili' ); ?></label>
			<input type="text" class="widefat" id="dbcu_carousel_logo_alt" name="dbcu_carousel_logo_alt" value="<?php echo esc_attr( $logo_alt ); ?>" aria-required="true" placeholder="<?php esc_attr_e( 'Es. Logo PON 2014-2020 Fondi Strutturali Europei', 'db-collegamenti-utili' ); ?>" />
			<p class="description"><?php esc_html_e( 'Descrizione dell\'immagine per screen reader (accessibilità WCAG 2.1 AA).', 'db-collegamenti-utili' ); ?></p>
		</div>

		<div class="dbcu-field">
			<label class="dbcu-label" for="dbcu_external_link"><?php esc_html_e( 'Link esterno (opzionale)', 'db-collegamenti-utili' ); ?></label>
			<input type="url" class="widefat" id="dbcu_external_link" name="dbcu_external_link" value="<?php echo esc_attr( $link ); ?>" placeholder="https://..." />
			<p class="description"><?php esc_html_e( 'Se compilato, dal carosello si aprirà questo link (in nuova scheda) invece della pagina di dettaglio.', 'db-collegamenti-utili' ); ?></p>
		</div>

		<div class="dbcu-field">
			<label class="dbcu-label">
				<input type="checkbox" name="dbcu_show_in_carousel" value="1" <?php checked( $show, '1' ); ?> />
				<?php esc_html_e( 'Mostra questa voce nel carosello della home', 'db-collegamenti-utili' ); ?>
			</label>
		</div>

		<div class="dbcu-field">
			<label class="dbcu-label" for="dbcu_carousel_order"><?php esc_html_e( 'Ordine nel carosello', 'db-collegamenti-utili' ); ?></label>
			<input type="number" id="dbcu_carousel_order" name="dbcu_carousel_order" value="<?php echo esc_attr( '' !== $order ? $order : 0 ); ?>" step="1" style="width:90px" />
			<p class="description"><?php esc_html_e( 'Numero più basso = mostrato prima.', 'db-collegamenti-utili' ); ?></p>
		</div>
		<?php
	}

	/**
	 * Meta box degli allegati ripetibili.
	 *
	 * @param WP_Post $post Post corrente.
	 */
	public static function render_attachments( $post ) {
		$items = get_post_meta( $post->ID, '_dbcu_attachments', true );
		if ( ! is_array( $items ) ) {
			$items = array();
		}
		?>
		<p class="description"><?php esc_html_e( 'Documenti scaricabili (lettera di candidatura, avviso, autorizzazione…). Ogni voce ha titolo, descrizione e data per la tracciabilità amministrativa.', 'db-collegamenti-utili' ); ?></p>
		<div id="dbcu-attachments-list">
			<?php
			if ( empty( $items ) ) {
				self::render_attachment_row( 0, array() );
			} else {
				foreach ( $items as $i => $item ) {
					self::render_attachment_row( $i, $item );
				}
			}
			?>
		</div>
		<button type="button" class="button button-secondary" id="dbcu-add-attachment"><?php esc_html_e( '+ Aggiungi allegato', 'db-collegamenti-utili' ); ?></button>

		<script type="text/html" id="dbcu-attachment-template">
			<?php self::render_attachment_row( '__INDEX__', array() ); ?>
		</script>
		<?php
	}

	/**
	 * Riga singola allegato.
	 *
	 * @param int|string $index Indice riga.
	 * @param array      $item  Dati riga.
	 */
	protected static function render_attachment_row( $index, $item ) {
		$file_id = isset( $item['file_id'] ) ? (int) $item['file_id'] : 0;
		$title   = isset( $item['title'] ) ? $item['title'] : '';
		$desc    = isset( $item['desc'] ) ? $item['desc'] : '';
		$date    = isset( $item['date'] ) ? $item['date'] : '';
		$fname   = $file_id ? get_the_title( $file_id ) : '';
		?>
		<div class="dbcu-attachment-row">
			<div class="dbcu-attachment-grid">
				<label>
					<span><?php esc_html_e( 'Titolo visualizzato', 'db-collegamenti-utili' ); ?></span>
					<input type="text" name="dbcu_attachments[<?php echo esc_attr( $index ); ?>][title]" value="<?php echo esc_attr( $title ); ?>" class="widefat" />
				</label>
				<label>
					<span><?php esc_html_e( 'Data', 'db-collegamenti-utili' ); ?></span>
					<input type="date" name="dbcu_attachments[<?php echo esc_attr( $index ); ?>][date]" value="<?php echo esc_attr( $date ); ?>" />
				</label>
			</div>
			<label>
				<span><?php esc_html_e( 'Descrizione breve', 'db-collegamenti-utili' ); ?></span>
				<textarea name="dbcu_attachments[<?php echo esc_attr( $index ); ?>][desc]" rows="2" class="widefat"><?php echo esc_textarea( $desc ); ?></textarea>
			</label>
			<div class="dbcu-attachment-file">
				<input type="hidden" class="dbcu-file-id" name="dbcu_attachments[<?php echo esc_attr( $index ); ?>][file_id]" value="<?php echo esc_attr( $file_id ); ?>" />
				<span class="dbcu-file-name"><?php echo esc_html( $fname ? $fname : __( 'Nessun file selezionato', 'db-collegamenti-utili' ) ); ?></span>
				<button type="button" class="button dbcu-file-select"><?php esc_html_e( 'Scegli file', 'db-collegamenti-utili' ); ?></button>
				<button type="button" class="button-link dbcu-attachment-remove"><?php esc_html_e( 'Elimina riga', 'db-collegamenti-utili' ); ?></button>
			</div>
		</div>
		<?php
	}

	/**
	 * Salvataggio meta.
	 *
	 * @param int     $post_id ID post.
	 * @param WP_Post $post    Post.
	 */
	public static function save( $post_id, $post ) {
		if ( ! isset( $_POST['dbcu_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['dbcu_nonce'] ) ), 'dbcu_save' ) ) {
			return;
		}
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		// Logo carosello.
		$logo_id = isset( $_POST['dbcu_carousel_logo_id'] ) ? absint( $_POST['dbcu_carousel_logo_id'] ) : 0;
		update_post_meta( $post_id, '_dbcu_carousel_logo_id', $logo_id );

		$logo_alt = isset( $_POST['dbcu_carousel_logo_alt'] ) ? sanitize_text_field( wp_unslash( $_POST['dbcu_carousel_logo_alt'] ) ) : '';
		update_post_meta( $post_id, '_dbcu_carousel_logo_alt', $logo_alt );

		// Segnala l'alt mancante al redirect successivo (vedi alt_missing_notice()).
		if ( $logo_id && '' === $logo_alt ) {
			set_transient( 'dbcu_alt_missing_' . $post_id, 1, MINUTE_IN_SECONDS );
		}

		$link = isset( $_POST['dbcu_external_link'] ) ? esc_url_raw( wp_unslash( $_POST['dbcu_external_link'] ) ) : '';
		update_post_meta( $post_id, '_dbcu_external_link', $link );

		$show = isset( $_POST['dbcu_show_in_carousel'] ) ? '1' : '';
		update_post_meta( $post_id, '_dbcu_show_in_carousel', $show );

		$order = isset( $_POST['dbcu_carousel_order'] ) ? intval( $_POST['dbcu_carousel_order'] ) : 0;
		update_post_meta( $post_id, '_dbcu_carousel_order', $order );

		// Allegati.
		$clean = array();
		if ( isset( $_POST['dbcu_attachments'] ) && is_array( $_POST['dbcu_attachments'] ) ) {
			$raw = wp_unslash( $_POST['dbcu_attachments'] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			foreach ( $raw as $row ) {
				$file_id = isset( $row['file_id'] ) ? absint( $row['file_id'] ) : 0;
				$title   = isset( $row['title'] ) ? sanitize_text_field( $row['title'] ) : '';
				$desc    = isset( $row['desc'] ) ? sanitize_textarea_field( $row['desc'] ) : '';
				$date    = isset( $row['date'] ) ? sanitize_text_field( $row['date'] ) : '';

				// Scarta righe vuote.
				if ( ! $file_id && '' === $title ) {
					continue;
				}
				$clean[] = array(
					'file_id' => $file_id,
					'title'   => $title,
					'desc'    => $desc,
					'date'    => $date,
				);
			}
		}
		update_post_meta( $post_id, '_dbcu_attachments', $clean );
	}
}
