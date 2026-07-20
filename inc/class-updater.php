<?php
/**
 * DB GitHub Updater — aggiornamenti automatici dalle Release di GitHub.
 * Componente condiviso tra i plugin di Davide Bertolino.
 *
 * @package DB_Collegamenti_Utili
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'DB_GitHub_Updater' ) ) {

	/**
	 * Gestisce il controllo e l'installazione degli aggiornamenti da GitHub Releases.
	 */
	class DB_GitHub_Updater {

		/**
		 * Percorso del file principale del plugin.
		 *
		 * @var string
		 */
		private $file;

		/**
		 * Basename del plugin (cartella/file.php).
		 *
		 * @var string
		 */
		private $basename;

		/**
		 * Slug del plugin.
		 *
		 * @var string
		 */
		private $slug;

		/**
		 * Owner GitHub.
		 *
		 * @var string
		 */
		private $owner;

		/**
		 * Repository GitHub.
		 *
		 * @var string
		 */
		private $repo;

		/**
		 * Dati del plugin.
		 *
		 * @var array
		 */
		private $plugin_data;

		/**
		 * Cache release GitHub.
		 *
		 * @var object|null
		 */
		private $github_release;

		/**
		 * Costruttore.
		 *
		 * @param string $file  Percorso file principale.
		 * @param string $owner Owner GitHub.
		 * @param string $repo  Nome repo.
		 */
		public function __construct( $file, $owner, $repo ) {
			$this->file  = $file;
			$this->owner = $owner;
			$this->repo  = $repo;

			add_action( 'admin_init', array( $this, 'set_plugin_properties' ) );
			add_filter( 'pre_set_site_transient_update_plugins', array( $this, 'modify_transient' ) );
			add_filter( 'plugins_api', array( $this, 'plugin_popup' ), 10, 3 );
			add_filter( 'upgrader_post_install', array( $this, 'after_install' ), 10, 3 );
		}

		/**
		 * Imposta le proprietà del plugin.
		 */
		public function set_plugin_properties() {
			if ( ! function_exists( 'get_plugin_data' ) ) {
				require_once ABSPATH . 'wp-admin/includes/plugin.php';
			}
			$this->plugin_data = get_plugin_data( $this->file );
			$this->basename    = plugin_basename( $this->file );
			$this->slug        = dirname( $this->basename );
		}

		/**
		 * Recupera l'ultima release da GitHub.
		 *
		 * @return object|false
		 */
		private function get_repository_info() {
			if ( ! is_null( $this->github_release ) ) {
				return $this->github_release;
			}

			$cache_key = 'dbcu_gh_release_' . md5( $this->owner . $this->repo );
			$cached    = get_transient( $cache_key );
			if ( false !== $cached ) {
				$this->github_release = $cached;
				return $cached;
			}

			$url      = sprintf( 'https://api.github.com/repos/%s/%s/releases/latest', $this->owner, $this->repo );
			$response = wp_remote_get(
				$url,
				array(
					'timeout' => 10,
					'headers' => array( 'Accept' => 'application/vnd.github+json' ),
				)
			);

			if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
				return false;
			}

			$data = json_decode( wp_remote_retrieve_body( $response ) );
			if ( empty( $data ) || empty( $data->tag_name ) ) {
				return false;
			}

			set_transient( $cache_key, $data, HOUR_IN_SECONDS * 6 );
			$this->github_release = $data;
			return $data;
		}

		/**
		 * Inietta l'update nel transient.
		 *
		 * @param object $transient Transient degli update.
		 * @return object
		 */
		public function modify_transient( $transient ) {
			if ( ! isset( $transient->checked ) ) {
				return $transient;
			}
			if ( empty( $this->basename ) ) {
				$this->set_plugin_properties();
			}

			$release = $this->get_repository_info();
			if ( ! $release ) {
				return $transient;
			}

			$new_version = ltrim( $release->tag_name, 'v' );
			$current     = isset( $transient->checked[ $this->basename ] ) ? $transient->checked[ $this->basename ] : $this->plugin_data['Version'];

			if ( version_compare( $new_version, $current, '>' ) ) {
				$package = ! empty( $release->assets[0]->browser_download_url )
					? $release->assets[0]->browser_download_url
					: $release->zipball_url;

				$transient->response[ $this->basename ] = (object) array(
					'slug'        => $this->slug,
					'plugin'      => $this->basename,
					'new_version' => $new_version,
					'url'         => $this->plugin_data['PluginURI'],
					'package'     => $package,
				);
			}
			return $transient;
		}

		/**
		 * Popup dettagli plugin.
		 *
		 * @param mixed  $result Risultato.
		 * @param string $action Azione.
		 * @param object $args   Argomenti.
		 * @return mixed
		 */
		public function plugin_popup( $result, $action, $args ) {
			if ( 'plugin_information' !== $action || empty( $args->slug ) || $args->slug !== $this->slug ) {
				return $result;
			}
			$release = $this->get_repository_info();
			if ( ! $release ) {
				return $result;
			}
			return (object) array(
				'name'          => $this->plugin_data['Name'],
				'slug'          => $this->slug,
				'version'       => ltrim( $release->tag_name, 'v' ),
				'author'        => $this->plugin_data['AuthorName'],
				'homepage'      => $this->plugin_data['PluginURI'],
				'download_link' => ! empty( $release->assets[0]->browser_download_url ) ? $release->assets[0]->browser_download_url : $release->zipball_url,
				'sections'      => array( 'changelog' => nl2br( esc_html( $release->body ) ) ),
			);
		}

		/**
		 * Sistema la cartella dopo l'installazione.
		 *
		 * @param bool  $response   Esito.
		 * @param array $hook_extra Extra.
		 * @param array $result     Risultato.
		 * @return array
		 */
		public function after_install( $response, $hook_extra, $result ) {
			global $wp_filesystem;
			if ( empty( $this->basename ) ) {
				$this->set_plugin_properties();
			}
			$install_dir = plugin_dir_path( $this->file );
			$wp_filesystem->move( $result['destination'], $install_dir );
			$result['destination'] = $install_dir;
			return $result;
		}
	}
}
