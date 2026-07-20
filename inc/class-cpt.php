<?php
/**
 * Registrazione del Custom Post Type dei collegamenti utili / bandi.
 *
 * @package DB_Collegamenti_Utili
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Gestore CPT.
 */
class DBCU_CPT_Manager {

	/**
	 * Aggancia gli hook.
	 */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'register_cpt' ) );
		add_filter( 'manage_' . DBCU_CPT . '_posts_columns', array( __CLASS__, 'columns' ) );
		add_action( 'manage_' . DBCU_CPT . '_posts_custom_column', array( __CLASS__, 'column_content' ), 10, 2 );
	}

	/**
	 * Registra il post type.
	 */
	public static function register_cpt() {
		$labels = array(
			'name'               => __( 'Collegamenti utili', 'db-collegamenti-utili' ),
			'singular_name'      => __( 'Collegamento utile', 'db-collegamenti-utili' ),
			'menu_name'          => __( 'Collegamenti utili', 'db-collegamenti-utili' ),
			'add_new'            => __( 'Aggiungi nuovo', 'db-collegamenti-utili' ),
			'add_new_item'       => __( 'Aggiungi nuovo collegamento', 'db-collegamenti-utili' ),
			'edit_item'          => __( 'Modifica collegamento', 'db-collegamenti-utili' ),
			'new_item'           => __( 'Nuovo collegamento', 'db-collegamenti-utili' ),
			'view_item'          => __( 'Visualizza collegamento', 'db-collegamenti-utili' ),
			'search_items'       => __( 'Cerca collegamenti', 'db-collegamenti-utili' ),
			'not_found'          => __( 'Nessun collegamento trovato', 'db-collegamenti-utili' ),
			'not_found_in_trash' => __( 'Nessun collegamento nel cestino', 'db-collegamenti-utili' ),
			'all_items'          => __( 'Tutti i collegamenti', 'db-collegamenti-utili' ),
		);

		$args = array(
			'labels'             => $labels,
			'public'             => true,
			'publicly_queryable' => true,
			'show_ui'            => true,
			'show_in_menu'       => true,
			'show_in_rest'       => true,
			'query_var'          => true,
			'rewrite'            => array( 'slug' => 'bandi', 'with_front' => false ),
			'capability_type'    => 'post',
			'has_archive'        => true,
			'hierarchical'       => false,
			'menu_position'      => 25,
			'menu_icon'          => 'dashicons-megaphone',
			'supports'           => array( 'title', 'editor', 'thumbnail', 'page-attributes', 'excerpt' ),
		);

		register_post_type( DBCU_CPT, $args );
	}

	/**
	 * Colonne personalizzate nella lista admin.
	 *
	 * @param array $columns Colonne esistenti.
	 * @return array
	 */
	public static function columns( $columns ) {
		$new = array();
		foreach ( $columns as $key => $label ) {
			if ( 'title' === $key ) {
				$new['dbcu_logo'] = __( 'Logo carosello', 'db-collegamenti-utili' );
			}
			$new[ $key ] = $label;
			if ( 'title' === $key ) {
				$new['dbcu_carousel'] = __( 'In carosello', 'db-collegamenti-utili' );
			}
		}
		return $new;
	}

	/**
	 * Contenuto delle colonne personalizzate.
	 *
	 * @param string $column  Nome colonna.
	 * @param int    $post_id ID post.
	 */
	public static function column_content( $column, $post_id ) {
		if ( 'dbcu_logo' === $column ) {
			$logo_id = (int) get_post_meta( $post_id, '_dbcu_carousel_logo_id', true );
			if ( $logo_id ) {
				echo wp_get_attachment_image( $logo_id, array( 60, 60 ) );
			} else {
				echo '&mdash;';
			}
		}
		if ( 'dbcu_carousel' === $column ) {
			$show  = get_post_meta( $post_id, '_dbcu_show_in_carousel', true );
			$class = $show ? 'dashicons-yes' : 'dashicons-minus';
			$color = $show ? '#2e7d32' : '#999';
			printf(
				'<span class="dashicons %1$s" style="color:%2$s"></span>',
				esc_attr( $class ),
				esc_attr( $color )
			);
		}
	}
}
