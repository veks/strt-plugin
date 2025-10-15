<?php
/**
 * Класс AssetRegistry для регистрации скриптов и стилей.
 *
 * @class  AssetRegistry
 * @package Strt\Plugin\Assets
 * @version 1.0.0
 */

namespace Strt\Plugin\Assets;

use Strt\Plugin\Utils\Helper;

if ( ! class_exists( 'Strt\Plugin\Assets\AssetRegistry' ) ) {

	/**
	 * Класс AssetRegistry для регистрации скриптов и стилей.
	 */
	class AssetRegistry {

		/**
		 * Экземпляр вспомогательного класса.
		 *
		 * @var Helper
		 */
		protected Helper $helper;

		/**
		 * Экземпляр вспомогательного класса.
		 *
		 * @var AssetApi
		 */
		protected AssetApi $asset_api;

		/**
		 * Конструктор.
		 *
		 * @param  Helper  $helper  Экземпляр класса Helper.
		 */
		public function __construct( Helper $helper, AssetApi $asset_api ) {
			$this->helper    = $helper;
			$this->asset_api = $asset_api;
		}

		/**
		 * Инициализация.
		 *
		 * @return void
		 */
		public function init(): void {
			add_action( 'init', [ $this, 'register_assets' ] );
			add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_scripts' ] );
			add_action( 'enqueue_block_assets', [ $this, 'register_block_editor_assets' ] );
		}

		/**
		 * Регистрация блочных скриптов и стилей.
		 *
		 * @return void
		 */
		public function register_assets(): void {
			$this->asset_api->register_script( 'blocks-data-store', 'blocks-data', );
			$this->asset_api->register_script( 'schema-parser' );
		}

		/**
		 * Подключает зарегистрированные скрипты.
		 *
		 * @return void
		 */
		public function enqueue_scripts(): void {

		}

		/**
		 * Подключает стили и скрипты для блоков Gutenberg.
		 *
		 * Этот метод вызывается через хук `enqueue_block_assets` и используется
		 * как для фронтенда, так и редактора.
		 */
		public function register_block_editor_assets(): void {
		}
	}
}