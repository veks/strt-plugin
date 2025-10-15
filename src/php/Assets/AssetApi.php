<?php
/**
 * Класс AssetApi для регистрации скриптов и стилей.
 *
 * @class  AssetApi
 * @package Strt\Plugin\Assets
 * @version 1.0.0
 */

namespace Strt\Plugin\Assets;

defined( 'ABSPATH' ) || exit;

use Strt\Plugin\Utils\Helper;

if ( ! class_exists( 'Strt\Plugin\Assets\AssetApi' ) ) {

	/**
	 * Класс AssetApi.
	 *
	 * Регистрирует скрипты и стили.
	 */
	class AssetApi {

		/**
		 * Экземпляр вспомогательного класса.
		 *
		 * @var Helper
		 */
		protected Helper $helper;

		/**
		 * Конструктор.
		 *
		 * @param  Helper  $helper  Экземпляр класса Helper.
		 */
		public function __construct( Helper $helper ) {
			$this->helper = $helper;
		}

		public function init() {

		}

		public function register_style(
			string $handle,
			?string $file_name = null,
			array $deps = [],
			?string $version = null,
			string $media = 'all'
		): void {
			[ $handle, $src, $version ] = $this->get_asset_props( $handle, $file_name, 'css', $version );

			wp_register_style( $handle, $src, $deps, $version, $media );
		}

		/**
		 * Регистрирует стиль (без непосредственного подключения).
		 *
		 * @param  string  $handle  Уникальный идентификатор стиля.
		 * @param  string|null  $file_name  Имя файла без расширения (если отличается от $handle).
		 * @param  array  $deps  Массив зависимостей.
		 * @param  string|null  $version  Версия файла (по умолчанию версия темы).
		 * @param  string  $media  Медиа-тип (по умолчанию 'all').
		 *
		 * @return void
		 */
		public function enqueue_style(
			string $handle,
			?string $file_name = null,
			array $deps = [],
			?string $version = null,
			string $media = 'all'
		): void {
			[ $handle, $src, $version ] = $this->get_asset_props( $handle, $file_name, 'css', $version );

			wp_enqueue_style( $handle, $src, $deps, $version, $media );
		}

		/**
		 * Регистрирует скрипт (без непосредственного подключения).
		 *
		 * @param  string  $handle  Уникальный идентификатор скрипта.
		 * @param  string|null  $file_name  Имя файла без расширения (если отличается от $handle).
		 * @param  array  $deps  Массив зависимостей.
		 * @param  string|null  $version  Версия файла (по умолчанию версия темы).
		 * @param  bool  $in_footer  Подключать скрипт в футере (по умолчанию true).
		 *
		 * @return void
		 */
		public function register_script(
			string $handle,
			?string $file_name = null,
			array $deps = [],
			?string $version = null,
			bool $in_footer = true
		): void {
			[ $handle, $src, $version ] = $this->get_asset_props( $handle, $file_name, 'js', $version );

			wp_register_script( $handle, $src, $deps, $version, $in_footer );
		}

		/**
		 * Подключает скрипт (регистрирует и подключает, если нужно).
		 *
		 * @param  string  $handle  Уникальный идентификатор скрипта.
		 * @param  string|null  $file_name  Имя файла без расширения (если отличается от $handle).
		 * @param  array  $deps  Массив зависимостей.
		 * @param  string|null  $version  Версия файла (по умолчанию версия темы).
		 * @param  bool  $in_footer  Подключать скрипт в футере (по умолчанию true).
		 *
		 * @return void
		 */
		public function enqueue_script(
			string $handle,
			?string $file_name = null,
			array $deps = [],
			?string $version = null,
			bool $in_footer = true
		): void {
			[ $handle, $src, $version ] = $this->get_asset_props( $handle, $file_name, 'js', $version );

			wp_enqueue_script( $handle, $src, $deps, $version, $in_footer );
		}

		/**
		 * Добавляет данные к скрипту (например, type="module").
		 *
		 * @param  string  $handle  Уникальный идентификатор скрипта.
		 * @param  string  $key  Ключ параметра.
		 * @param  mixed  $value  Значение.
		 *
		 * @return void
		 */
		public function script_add_data( string $handle, string $key, mixed $value ): void {
			[ $handle ] = $this->get_asset_props( $handle );

			wp_script_add_data( $handle, $key, $value );
		}

		/**
		 * Локализует данные для скрипта.
		 *
		 * @param  string  $handle  Уникальный идентификатор скрипта.
		 * @param  string  $object_name  Имя JS-объекта.
		 * @param  array  $data  Массив данных для передачи в скрипт.
		 *
		 * @return void
		 */
		public function localize_script( string $handle, string $object_name, array $data ): void {
			[ $handle ] = $this->get_asset_props( $handle );

			wp_localize_script( $handle, $object_name, $data );
		}

		/**
		 * Добавляет инлайновый скрипт.
		 *
		 * @param  string  $handle  Уникальный идентификатор скрипта.
		 * @param  string  $data  Код скрипта.
		 * @param  string  $position  Позиция вставки ('before' или 'after', по умолчанию 'after').
		 *
		 * @return void
		 */
		public function add_inline_script( string $handle, string $data, string $position = 'after' ): void {
			[ $handle ] = $this->get_asset_props( $handle );

			wp_add_inline_script( $handle, $data, $position );
		}

		/**
		 * Генерирует URL и версию для ассета.
		 *
		 * @param  string  $handle  Уникальный идентификатор ассета.
		 * @param  string|null  $file_name  Имя файла без расширения (по умолчанию равно $handle).
		 * @param  string|null  $ext  Расширение файла ('js' или 'css').
		 * @param  string|null  $version  Версия ассета (если не указано, берётся версия темы).
		 *
		 * @return array [ $handle, $src, $version ]
		 */
		protected function get_asset_props( string $handle, ?string $file_name = null, ?string $ext = 'js', ?string $version = null ): array {
			$suffix    = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';
			$version   = $version ?? $this->helper::get_version();
			$file_name = $file_name ?? $handle;
			$handle    = $this->helper::handle( $handle );

			if ( $ext === 'js' ) {
				$src = $this->helper::get_dir_url_js( "{$file_name}{$suffix}.js" );
			} elseif ( $ext === 'css' ) {
				$src = $this->helper::get_dir_url_css( "{$file_name}{$suffix}.css" );
			} else {
				$src = '';
			}

			return [ $handle, $src, $version ];
		}


		/**
		 * Удаляет стиль из очереди и отменяет его регистрацию.
		 *
		 * @param  string  $handle  Идентификатор стиля.
		 *
		 * @return void
		 */
		public function dequeue_style( string $handle ): void {
			wp_dequeue_style( $handle );
			wp_deregister_style( $handle );
		}

		/**
		 * Удаляет скрипт из очереди и отменяет его регистрацию.
		 *
		 * @param  string  $handle  Идентификатор скрипта.
		 *
		 * @return void
		 */
		public function dequeue_script( string $handle ): void {
			wp_dequeue_script( $handle );
			wp_deregister_script( $handle );
		}
	}
}
