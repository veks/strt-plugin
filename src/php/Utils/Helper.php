<?php
/**
 * Класс вспомогательных методов для плагина.
 *
 * Этот класс содержит статические методы для работы с настройками темы,
 * генерации URL и путей к ресурсам, а также другие утилитарные функции.
 *
 * @class  Helper
 * @package Strt\Plugin\Utils
 * @version 1.0.0
 */

namespace Strt\Plugin\Utils;

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'Strt\Plugin\Utils\Helper' ) ) {

	/**
	 * Класс Helper.
	 *
	 * Предоставляет вспомогательные методы для работы с темой.
	 */
	class Helper {

		/**
		 * Пространство имени.
		 *
		 * @var string
		 */
		const string NAMESPACE = 'strt';

		/**
		 * Название опции.
		 *
		 * @var string
		 */
		public static string $option_name = STRT_PLUGIN_OPTION_NAME;

		/**
		 * Получить значение опции темы.
		 *
		 * @param  string  $key  Ключ для получения значения.
		 * @param  mixed  $default  Значение по умолчанию, если ключ не найден.
		 *
		 * @return mixed Значение опции или значение по умолчанию.
		 */
		public static function get_option( string $key = '', mixed $default = false ): mixed {
			$get_option = get_option( self::$option_name, [] );

			if ( ! empty( $get_option ) ) {
				return array_get( $get_option, $key, $default );
			}

			return $default;
		}

		/**
		 * Получает настройки темы из опций WordPress.
		 *
		 * @param  string  $option_name  Название опции в базе данных.
		 * @param  string  $key  Ключ внутри массива опций (необязательно).
		 * @param  mixed  $default  Значение по умолчанию, если опция не найдена.
		 *
		 * @return mixed Возвращает значение настройки, если найдено, иначе возвращает значение по умолчанию.
		 */
		public static function get_settings( string $option_name = '', string $key = '', mixed $default = false ): mixed {
			$get_option = get_option( $option_name, [] );

			if ( ! empty( $get_option ) ) {
				return array_get( $get_option, $key, $default );
			}

			return $default;
		}

		/**
		 * Обновить значение опции темы.
		 *
		 * @param  string  $key  Ключ для обновления значения.
		 * @param  null|string  $value  Новое значение.
		 *
		 * @return void
		 */
		public static function update_option( string $key = '', null|string $value = '' ): void {
			$get_option = get_option( self::$option_name, [] );

			array_set( $get_option, $key, $value );

			update_option( self::$option_name, $get_option );
		}

		/**
		 * Получить имя темы.
		 *
		 * @return string Имя темы.
		 */
		public static function get_name(): string {
			return 'STRT';
		}

		/**
		 * Получить путь к файлу темы.
		 *
		 * @return string Путь к файлу темы.
		 */
		public static function get_file(): string {
			return STRT_PLUGIN_FILE;
		}

		/**
		 * Получить путь к директории темы.
		 *
		 * @param  string  $path  Путь, который добавляется к корню директории.
		 *
		 * @return string Путь к директории.
		 */
		public static function get_dir_path( string $path = '' ): string {
			return trailingslashit( plugin_dir_path( self::get_file() ) ) . ltrim( $path, '/' );
		}

		/**
		 * Получить URL директории темы.
		 *
		 * @param  string  $path  Путь, который добавляется к URL директории.
		 *
		 * @return string URL директории темы.
		 */
		public static function get_dir_url( string $path = '' ): string {
			return trailingslashit( plugin_dir_url( self::get_file() ) ) . ltrim( $path, '/' );
		}

		/**
		 * Получить URL директории с CSS файлами.
		 *
		 * @param  string  $path  Путь к конкретному CSS файлу.
		 *
		 * @return string URL директории с CSS файлами.
		 */
		public static function get_dir_url_css( string $path = '' ): string {
			return self::get_dir_url( 'assets/css/' . ltrim( $path, '/' ) );
		}

		/**
		 * Получить URL директории с JS файлами.
		 *
		 * @param  string  $path  Путь к конкретному JS файлу.
		 *
		 * @return string URL директории с JS файлами.
		 */
		public static function get_dir_url_js( string $path = '' ): string {
			return self::get_dir_url( 'assets/js/' . ltrim( $path, '/' ) );
		}

		/**
		 * Получить URL директории с IMG файлами.
		 *
		 * @param  string  $path  Путь к конкретному IMG файлу.
		 *
		 * @return string URL директории с JS файлами.
		 */
		public static function get_dir_url_img( string $path = '' ): string {
			return self::get_dir_url( 'assets/img/' . ltrim( $path, '/' ) );
		}

		/**
		 * Получить директорию блока с файлами.
		 *
		 * @param  string  $path  Путь к конкретному JS файлу.
		 *
		 * @return string URL директории с JS файлами.
		 */
		public static function get_blocks_dir_path( string $path = '' ): string {
			return self::get_dir_path( 'assets/blocks/' . ltrim( $path, '/' ) );
		}

		/**
		 * Получить URL директории блока с файлами.
		 *
		 * @param  string  $path  Путь к конкретному JS файлу.
		 *
		 * @return string URL директории с JS файлами.
		 */
		public static function get_blocks_url_path( string $path = '' ): string {
			return self::get_dir_url( 'assets/blocks/' . ltrim( $path, '/' ) );
		}

		/**
		 * Возвращает строку, представляющую категорию блока.
		 *
		 * @return string Строка с именем категории блока.
		 */
		public static function get_block_category_slug(): string {
			return 'strt-plugin';
		}

		/**
		 * Получить директорию загрузок.
		 *
		 * @param  string  $path  Дополнительный путь.
		 *
		 * @return string|false Путь к директории загрузок или false в случае ошибки.
		 */
		public static function get_upload_dir( string $path = '' ): false|string {
			$upload_dir = wp_upload_dir();

			if ( ! empty( $upload_dir['error'] ) ) {
				_doing_it_wrong(
					__METHOD__,
					'Ошибка получения директории загрузок: ' . esc_html( $upload_dir['error'] ),
					'1.0.0'
				);

				return false;
			}

			return trailingslashit( $upload_dir['basedir'] ) . ltrim( $path, '/' );
		}

		/**
		 * Получить slug темы.
		 *
		 * @return string Slug темы.
		 */
		public static function get_slug(): string {
			return 'strt-plugin';
		}

		/**
		 * Генерирует уникальный идентификатор (handle) с префиксом `re-theme`.
		 * Если параметр `$handle` пустой, возвращается только `re-theme`.
		 *
		 * @param  string|null  $handle  Идентификатор, который будет добавлен к префиксу (необязательный).
		 *
		 * @return string Сформированный идентификатор.
		 */
		public static function handle( ?string $handle = null ): string {
			return $handle ? self::get_slug() . '-' . $handle : self::get_slug();
		}

		/**
		 * Получить slug темы настроек.
		 *
		 * @return string Slug темы.
		 */
		public static function get_slug_setting(): string {
			return 'strt-plugin-setting';
		}

		/**
		 * Получить версию темы.
		 *
		 * @return string Версия темы.
		 */
		public static function get_version(): string {
			return STRT_PLUGIN_VERSION;
		}

		/**
		 * Получает версию базы данных темы.
		 *
		 * @return string Версия базы данных темы.
		 */
		public static function get_version_db(): string {
			return STRT_PLUGIN_VERSION_DB;
		}

		/**
		 * Получить право доступа для управления темой.
		 *
		 * @return string Право доступа (capability).
		 */
		public static function get_capability(): string {
			return 'administrator';
		}

		/**
		 * Метод для получения события reset nonce, используемого для REST API.
		 *
		 * @return string
		 */
		public static function get_rest_nonce_action(): string {
			return 'wp_rest';
		}

		/**
		 * Метод для получения nonce, используемого для REST API.
		 *
		 * Этот метод генерирует nonce с использованием функции WordPress wp_create_nonce(),
		 * которая применяется для защиты REST API запросов от CSRF-атак.
		 *
		 * @return string Возвращает сгенерированный nonce для использования в REST API.
		 */
		public static function get_rest_nonce(): string {
			return wp_create_nonce( self::get_rest_nonce_action() );
		}

		/**
		 * Проверяет, активирован ли плагин WooCommerce на сайте.
		 *
		 * @return bool True, если WooCommerce активен, иначе false.
		 */
		public static function is_active_woocommerce(): bool {
			$cache_key   = 'strt_plugin_is_active_woocommerce';
			$cache_group = 'strt_plugin';

			$is_active = wp_cache_get( $cache_key, $cache_group );

			if ( false !== $is_active ) {
				return $is_active;
			}

			if ( class_exists( 'WooCommerce' ) ) {
				$is_active = true;
			} elseif ( is_multisite() ) {
				$sitewide_plugins = get_site_option( 'active_sitewide_plugins' );
				$active_plugins   = get_option( 'active_plugins' );

				$sitewide_plugins = is_array( $sitewide_plugins ) ? $sitewide_plugins : [];
				$active_plugins   = is_array( $active_plugins ) ? $active_plugins : [];

				$plugins = array_merge( $sitewide_plugins, $active_plugins );

				$is_active = isset( $plugins['woocommerce/woocommerce.php'] ) || in_array( 'woocommerce/woocommerce.php', $plugins, true );
			} else {
				$active_plugins = get_option( 'active_plugins' );
				$active_plugins = is_array( $active_plugins ) ? $active_plugins : [];

				$is_active = in_array( 'woocommerce/woocommerce.php', $active_plugins, true );
			}

			wp_cache_set( $cache_key, $is_active, $cache_group );

			return $is_active;
		}
	}
}
