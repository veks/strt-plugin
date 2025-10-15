<?php
/**
 * AdminSettingsPage class.
 *
 * Управляет страницами настроек плагина для одиночного сайта и сети (Multisite).
 *
 * @package Strt\Plugin\Admin
 */

namespace Strt\Plugin\Admin;

defined( 'ABSPATH' ) || exit;

use Strt\Plugin\Utils\Fields;

if ( ! class_exists( __NAMESPACE__ . '\\AdminSettingsPage', false ) ) {

	class AdminSettingsPage extends Admin {

		/**
		 * Конфигурация вкладок и секций.
		 *
		 * @var array
		 */
		protected array $settings = [];

		/**
		 * Инициализация хуков админ-панели.
		 *
		 * @return void
		 */
		public function init(): void {
			add_action( 'admin_menu', [ $this, 'admin_menu' ] );
			add_action( 'network_admin_menu', [ $this, 'network_menu' ] );

			add_action( 'admin_init', [ $this, 'register_site_settings' ], 11 );
			add_action( 'admin_init', [ $this, 'register_network_settings' ], 12 );

			add_action( 'network_admin_edit_strt_save_settings', [ $this, 'handle_network_save' ] );

			add_filter( 'plugin_action_links_' . plugin_basename( $this->helper::get_file() ), [ $this, 'add_settings_link' ] );
		}

		/**
		 * Регистрация подменю настроек в админке сайта.
		 *
		 * @return void
		 */
		public function admin_menu(): void {
			$page_hook = add_submenu_page(
				$this->helper::get_slug(),
				'Настройки',
				'Настройки',
				$this->helper::get_capability(),
				$this->helper::get_slug_setting(),
				[ $this, 'page_site_settings' ]
			);

			add_action( 'admin_print_styles-' . $page_hook, [ $this, 'print_styles' ], 999 );
			add_action( 'admin_print_scripts-' . $page_hook, [ $this, 'print_scripts' ], 999 );
		}

		/**
		 * Регистрация подменю настроек в сетевой админке.
		 *
		 * @return void
		 */
		public function network_menu(): void {
			$page_hook = add_submenu_page(
				$this->helper::get_slug(),
				'Настройки сети',
				'Настройки сети',
				'manage_network_options',
				$this->helper::get_slug_setting(),
				[ $this, 'page_network_settings' ]
			);

			add_action( 'admin_print_styles-' . $page_hook, [ $this, 'print_styles' ], 999 );
			add_action( 'admin_print_scripts-' . $page_hook, [ $this, 'print_scripts' ], 999 );
		}

		/**
		 * Добавляет ссылку «Настройки» на экране плагинов.
		 *
		 * @param  array  $links  Существующие ссылки.
		 *
		 * @return array
		 */
		public function add_settings_link( array $links ): array {
			$links[] = sprintf(
				'<a href="%s">%s</a>',
				esc_url( admin_url( 'admin.php?page=' . $this->helper::get_slug_setting() ) ),
				'Настройки'
			);

			return $links;
		}

		/**
		 * Подключает стили страницы настроек.
		 *
		 * @return void
		 */
		public function print_styles(): void {
			wp_enqueue_style(
				$this->helper::handle( 'admin-settings' ),
				$this->helper::get_dir_url_css( 'admin-settings.min.css' ),
				[],
				$this->helper::get_version()
			);
		}

		/**
		 * Подключает скрипты страницы настроек.
		 *
		 * @return void
		 */
		public function print_scripts(): void {
			wp_enqueue_script(
				$this->helper::handle( 'admin-settings' ),
				$this->helper::get_dir_url_js( 'admin-settings.min.js' ),
				[],
				$this->helper::get_version(),
				true
			);
		}

		/**
		 * Регистрирует настройки и поля для сайта (Settings API).
		 *
		 * @return void
		 */
		public function register_site_settings(): void {
			if ( function_exists( 'is_network_admin' ) && is_network_admin() ) {
				return;
			}

			$this->settings = apply_filters( 'strt_site_settings', [] );
			$this->register_sections_and_fields( $this->settings, true );
		}

		/**
		 * Регистрирует секции и поля для сети (без Settings API).
		 *
		 * @return void
		 */
		public function register_network_settings(): void {
			if ( ! ( function_exists( 'is_network_admin' ) && is_network_admin() ) ) {
				return;
			}

			$this->settings = apply_filters( 'strt_network_settings', [] );
			$this->register_sections_and_fields( $this->settings, false );
		}

		/**
		 * Регистрация секций и полей (общая логика).
		 *
		 * @param  array  $settings  Конфигурация вкладок.
		 * @param  bool  $register_site_options  Признак регистрации опций через Settings API.
		 *
		 * @return void
		 */
		private function register_sections_and_fields( array $settings, bool $register_site_options ): void {
			if ( empty( $settings ) ) {
				return;
			}

			usort( $settings, [ $this, 'sort_array' ] );

			foreach ( $settings as $setting ) {

				if ( $register_site_options && isset( $setting['option_group'], $setting['option_name'] ) ) {
					register_setting(
						(string) $setting['option_group'],
						(string) $setting['option_name'],
						[ $this, 'settings_validate' ]
					);
				}

				if ( isset( $setting['section']['id'], $setting['section']['title'] ) ) {
					add_settings_section(
						(string) $setting['section']['id'],
						(string) $setting['section']['title'],
						[ $this, 'display_section' ],
						(string) $setting['section']['id']
					);
				}

				if ( isset( $setting['section']['id'], $setting['fields'] ) && is_array( $setting['fields'] ) && ! empty( $setting['fields'] ) ) {
					$page = isset( $setting['tab_id'] ) ? (string) $setting['tab_id'] : (string) $setting['section']['id'];

					foreach ( $setting['fields'] as $field ) {
						$field_id    = $field['id'] ?? null;
						$field_title = $field['title'] ?? ( $field['label'] ?? null );

						if ( ! $field_id || ! $field_title ) {
							continue;
						}

						$callback = ( ! empty( $field['callback'] ) && is_callable( $field['callback'] ) ) ? $field['callback'] : [ $this, 'display_field' ];

						add_settings_field(
							(string) $field_id,
							(string) $field_title,
							$callback,
							(string) $setting['section']['id'],
							(string) $setting['section']['id'],
							[
								'option_name' => (string) ( $setting['option_name'] ?? '' ),
								'field'       => $field,
								'label_for'   => sprintf(
									'%s-%s-%s',
									$page,
									(string) $field_id,
									(string) ( $field['type'] ?? 'custom' )
								),
							]
						);
					}
				}
			}
		}

		/**
		 * Вывод описания секции.
		 *
		 * @param  array  $args  Аргументы секции.
		 *
		 * @return void
		 */
		public function display_section( array $args ): void {
			if ( empty( $this->settings ) || empty( $args['id'] ) ) {
				return;
			}

			foreach ( $this->settings as $setting ) {
				if ( isset( $setting['section']['id'] ) && (string) $setting['section']['id'] === (string) $args['id'] ) {
					if ( ! empty( $setting['section']['description'] ) ) {
						printf(
							'<p class="strt-description description %1$s">%2$s</p>',
							esc_attr( (string) $setting['section']['id'] ),
							wp_kses_post( (string) $setting['section']['description'] )
						);
					}
					break;
				}
			}
		}

		/**
		 * Рендер страницы настроек сайта.
		 *
		 * @return void
		 */
		public function page_site_settings(): void {
			$this->settings = $this->settings ?: apply_filters( 'strt_site_settings', [] );

			$tabs        = apply_filters( 'strt_site_settings_tabs_array', [] );
			$tab_default = apply_filters( 'strt_site_settings_tab_default', '' );

			$tab_current = isset( $_GET['tab'] ) ? sanitize_text_field( wp_unslash( $_GET['tab'] ) ) : $tab_default;

			if ( $tab_current === '' && ! empty( $tabs ) ) {
				$tab_current = (string) array_key_first( $tabs );
			}

			$form_action = admin_url( 'options.php' );
			$page_slug   = $this->helper::get_slug_setting();

			strt_get_template(
				'admin/views/html-admin-page-settings.php',
				[
					'tabs'          => $tabs,
					'tab_default'   => $tab_default,
					'tab_current'   => $tab_current,
					'slug_settings' => $this->helper::get_slug_setting(),
					'version_db'    => $this->helper::get_version_db(),
					'settings'      => $this->settings,
					'form_action'   => $form_action,
					'page_slug'     => $page_slug,
				]
			);
		}

		/**
		 * Рендер страницы настроек сети.
		 *
		 * @return void
		 */
		public function page_network_settings(): void {
			$this->settings = $this->settings ?: apply_filters( 'strt_network_settings', [] );

			$tabs        = apply_filters( 'strt_network_settings_tabs_array', [] );
			$tab_default = apply_filters( 'strt_network_settings_tab_default', '' );

			$tab_current = isset( $_GET['tab'] ) ? sanitize_text_field( wp_unslash( $_GET['tab'] ) ) : $tab_default;

			if ( $tab_current === '' && ! empty( $tabs ) ) {
				$tab_current = (string) array_key_first( $tabs );
			}

			$form_action = network_admin_url( 'edit.php?action=strt_save_settings' );
			$page_slug   = $this->helper::get_slug();

			strt_get_template(
				'admin/views/html-admin-page-settings.php',
				[
					'tabs'          => $tabs,
					'tab_default'   => $tab_default,
					'tab_current'   => $tab_current,
					'slug_settings' => $this->helper::get_slug_setting(),
					'version_db'    => $this->helper::get_version_db(),
					'settings'      => $this->settings,
					'form_action'   => $form_action,
					'page_slug'     => $page_slug,
				]
			);
		}

		/**
		 * Валидация входных данных Settings API.
		 *
		 * @param  array  $input  Входные данные.
		 *
		 * @return array
		 */
		public function settings_validate( array $input ): array {
			$filter = str_replace( 'sanitize_option_', 'strt_settings_validate_', current_filter() );

			return apply_filters( $filter, $input );
		}

		/**
		 * Вывод одного поля.
		 *
		 * @param  array  $args  Аргументы поля.
		 *
		 * @return void
		 */
		public function display_field( array $args ): void {
			if ( class_exists( 'Strt\\Plugin\\Utils\\Fields' ) ) {
				new Fields( $args );

				return;
			}

			echo esc_html__( 'Поля не найдены', 'strt' );
		}

		/**
		 * Сортировка конфигурации по ключу order (ASC).
		 *
		 * @param  array  $a  Первый элемент.
		 * @param  array  $b  Второй элемент.
		 *
		 * @return int
		 */
		public function sort_array( array $a, array $b ): int {
			$ao = isset( $a['order'] ) ? (int) $a['order'] : 0;
			$bo = isset( $b['order'] ) ? (int) $b['order'] : 0;

			return $ao <=> $bo;
		}

		/**
		 * Обработчик сохранения формы сетевых настроек.
		 *
		 * @return void
		 */
		public function handle_network_save(): void {
			check_admin_referer( 'strt_settings' );

			if ( ! current_user_can( 'manage_network_options' ) ) {
				wp_die( 'Недостаточно прав.' );
			}

			$referer = $_POST['_wp_http_referer'] ?? '';

			parse_str( wp_parse_url( $referer, PHP_URL_QUERY ) ?? '', $q );

			$tab      = isset( $q['tab'] ) ? sanitize_key( $q['tab'] ) : '';
			$settings = apply_filters( 'strt_network_settings', [] );

			foreach ( $settings as $setting ) {
				$name = $setting['option_name'] ?? '';

				if ( ! $name ) {
					continue;
				}

				if ( ! array_key_exists( $name, $_POST ) ) {
					continue;
				}

				$posted = (array) $_POST[ $name ];

				$data = apply_filters( "strt_settings_network_validate_{$name}", $posted );

				update_site_option( $name, $data );
			}

			$args = [
				'page'    => $this->helper::get_slug_setting(),
				'updated' => 'true',
			];

			if ( $tab ) {
				$args['tab'] = $tab;
			}

			wp_safe_redirect( add_query_arg( $args, network_admin_url( 'admin.php' ) ) );
			exit;
		}
	}
}
