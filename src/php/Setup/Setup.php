<?php
/**
 * Setup class.
 *
 * Инициализация плагина: установка/обновление/удаление опций (site + network),
 * а также регистрация базовой функциональности темы/плагина.
 *
 * @package Strt\Plugin\Setup
 * @since   1.0.0
 */

namespace Strt\Plugin\Setup;

defined( 'ABSPATH' ) || exit;

use Strt\Plugin\Utils\Helper;

if ( ! class_exists( __NAMESPACE__ . '\\Setup', false ) ) {

	/**
	 * Class Setup.
	 *
	 * Отвечает за первичную установку, миграции и деинсталляцию (per-site и network).
	 *
	 * @since 1.0.0
	 */
	class Setup {

		/**
		 * Экземпляр Helper.
		 *
		 * @var Helper
		 */
		protected Helper $helper;

		/**
		 * Конструктор класса.
		 *
		 * @param  Helper  $helper  Экземпляр помощника.
		 */
		public function __construct( Helper $helper ) {
			$this->helper = $helper;
		}

		/**
		 * Инициализирует хуки плагина.
		 *
		 * @return void
		 */
		public function init(): void {
			add_action( 'after_setup_theme', [ $this, 'setup_theme_supports' ] );

			add_action( 'strt_activation_hook', [ $this, 'install' ], 1 );
			add_action( 'strt_network_activation', [ $this, 'network_install' ], 1 );

			add_action( 'strt_deactivation_hook', [ $this, 'deactivate' ], 1 );

			add_action( 'strt_uninstall_hook', [ $this, 'uninstall' ], 1 );
			add_action( 'strt_network_uninstall', [ $this, 'network_uninstall' ], 1 );
		}

		/**
		 * Регистрирует базовые темы/ядра.
		 *
		 * @return void
		 */
		public function setup_theme_supports(): void {
			add_theme_support( 'menus' );
		}

		/**
		 * Выполняет установку/обновление на сайте (или для каждой таблицы блога в сети).
		 *
		 * Хранит версию схемы и дефолты в wp_options.
		 *
		 * @return void
		 */
		public function install(): void {
			set_transient( 'strt_plugin_install', true, MINUTE_IN_SECONDS * 10 );

			$current_version_db = get_option( 'strt_plugin_version_db', false );
			$new_version_db     = $this->helper::get_version_db();
			$site_defaults      = apply_filters( 'strt_plugin_options', [] );

			if ( false === $current_version_db ) {
				add_option( 'strt_plugin_version_db', $new_version_db, '', true );

				if ( is_array( $site_defaults ) ) {
					foreach ( $site_defaults as $opt => $defaults ) {
						if ( false === get_option( $opt, false ) ) {
							add_option( $opt, $defaults, '', true );
						}
					}
				}

				return;
			}

			if ( version_compare( (string) $new_version_db, (string) $current_version_db, '>' ) ) {
				update_option( 'strt_plugin_version_db', $new_version_db );

				if ( is_array( $site_defaults ) ) {
					foreach ( $site_defaults as $opt => $defaults ) {
						$existing = get_option( $opt, false );

						if ( false === $existing ) {
							add_option( $opt, $defaults, '', true );
							continue;
						}

						if ( is_array( $existing ) && is_array( $defaults ) ) {
							$merged = $this->merge_defaults( $existing, $defaults );
							if ( $merged !== $existing ) {
								update_option( $opt, $merged );
							}
						}
					}
				}
			}
		}

		/**
		 * Выполняет установку/обновление для всей сети (Multisite).
		 *
		 * Вызывается один раз при network-активации.
		 * Хранит версию схемы и дефолты в wp_siteoptions.
		 *
		 * @return void
		 */
		public function network_install(): void {
			$network_version_key = 'strt_plugin_version_db_network';

			$current_version_db = get_site_option( $network_version_key, false );
			$new_version_db     = $this->helper::get_version_db();
			$network_defaults   = apply_filters( 'strt_plugin_network_options', [] );

			if ( false === $current_version_db ) {
				add_site_option( $network_version_key, $new_version_db );

				if ( is_array( $network_defaults ) ) {
					foreach ( $network_defaults as $opt => $defaults ) {
						if ( false === get_site_option( $opt, false ) ) {
							add_site_option( $opt, $defaults );
						}
					}
				}

				return;
			}

			if ( version_compare( (string) $new_version_db, (string) $current_version_db, '>' ) ) {
				update_site_option( $network_version_key, $new_version_db );

				if ( is_array( $network_defaults ) ) {
					foreach ( $network_defaults as $opt => $defaults ) {
						$existing = get_site_option( $opt, false );

						if ( false === $existing ) {
							add_site_option( $opt, $defaults );
							continue;
						}

						if ( is_array( $existing ) && is_array( $defaults ) ) {
							$merged = $this->merge_defaults( $existing, $defaults );
							if ( $merged !== $existing ) {
								update_site_option( $opt, $merged );
							}
						}
					}
				}
			}
		}

		/**
		 * Выполняет действия при деактивации.
		 *
		 * @return void
		 */
		public function deactivate(): void {
			delete_transient( 'strt_plugin_install' );
		}

		/**
		 * Выполняет пер-сайт деинсталляцию: чистит wp_options.
		 *
		 * @return void
		 */
		public function uninstall(): void {
			delete_transient( 'strt_plugin_install' );
			delete_option( 'strt_plugin_version_db' );

			$site_options = apply_filters( 'strt_plugin_options', [] );

			if ( is_array( $site_options ) ) {
				foreach ( $site_options as $name => $_ ) {
					delete_option( $name );
				}
			}
		}

		/**
		 * Выполняет сетевую деинсталляцию: чистит wp_siteoptions.
		 *
		 * @return void
		 */
		public function network_uninstall(): void {
			delete_site_option( 'strt_plugin_version_db_network' );

			$network_options = apply_filters( 'strt_plugin_network_options', [] );

			if ( is_array( $network_options ) ) {
				foreach ( $network_options as $name => $_ ) {
					delete_site_option( $name );
				}
			}
		}

		/**
		 * Рекурсивно мержит недостающие ключи из $defaults в $existing.
		 *
		 * Используется при апдейтах для аккуратного добавления новых ключей.
		 *
		 * @param  array  $existing  Текущие значения.
		 * @param  array  $defaults  Значения по умолчанию.
		 *
		 * @return array Обновлённый массив значений.
		 */
		private function merge_defaults( array $existing, array $defaults ): array {
			foreach ( $defaults as $key => $value ) {
				if ( is_array( $value ) ) {
					$existing[ $key ] = $this->merge_defaults( isset( $existing[ $key ] ) && is_array( $existing[ $key ] ) ? $existing[ $key ] : [], $value );
				} elseif ( ! array_key_exists( $key, $existing ) ) {
					$existing[ $key ] = $value;
				}
			}

			return $existing;
		}
	}
}
