<?php
/**
 * Блок поиска товаров для редактора Gutenberg.
 *
 * Реализует кастомный блок "Поиск товаров" и подготавливает данные
 * для фронтенда через Store API и AssetDataRegistry.
 *
 * @class   ProductSearchBlock
 * @package Strt\Plugin\Blocks\Product
 * @version 1.0.0
 */

namespace Strt\Plugin\Blocks\Product;

defined( 'ABSPATH' ) || exit;

use Strt\Plugin\Assets\AssetDataRegistry;
use Strt\Plugin\Blocks\AbstractBlock;
use WP_Block;

if ( ! class_exists( 'Strt\Plugin\Blocks\Product\ProductSearchBlock' ) ) {

	/**
	 * Класс ProductSearchBlock.
	 *
	 * Отвечает за регистрацию, подготовку данных и рендер блока "Поиск товаров".
	 */
	class ProductSearchBlock extends AbstractBlock {

		/**
		 * Слаг блока, используемый при регистрации.
		 *
		 * @var string
		 */
		protected string $block_name = 'product-search';

		/**
		 * Относительный путь к директории текущего блока (относительно корня блоков).
		 *
		 * @var string
		 */
		protected string $dir_path = '/product/';

		/**
		 * Флаг включения передачи настроек блока на клиентскую часть.
		 *
		 * @var bool
		 */
		protected bool $enable_block_settings = true;

		/**
		 * Реестр данных для передачи в JS.
		 *
		 * @var AssetDataRegistry
		 */
		protected AssetDataRegistry $asset_data_registry;

		/**
		 * Инициализация блока.
		 *
		 * Здесь можно добавить фильтры/хуки, специфичные для блока поиска.
		 *
		 * @return void
		 */
		public function init(): void {

		}

		/**
		 * Конструктор.
		 *
		 * @param  AssetDataRegistry  $asset_data_registry  Экземпляр реестра данных для передачи JS.
		 *
		 * @since 1.0.0
		 *
		 */
		public function __construct( AssetDataRegistry $asset_data_registry ) {
			parent::__construct();

			$this->asset_data_registry = $asset_data_registry;
		}

		/**
		 * Подключает данные для фронтенда.
		 *
		 * Формирует параметры запроса к Store API на основе атрибутов и гидратирует
		 * список товаров. Ключ данных в реестре: `productSearchData`.
		 *
		 * @param  array<string,mixed>  $attributes  Атрибуты блока.
		 *
		 * @return void
		 */
		protected function enqueue_data( array $attributes = [] ): void {
			/*if ( ! $this->asset_data_registry->exists( 'productSearchData' ) ) {
				$search_query = $attributes['search'] ?? '';
				$per_page     = $attributes['perPage'] ?? 6;

				$path = add_query_arg(
					[
						'search'   => $search_query,
						'per_page' => $per_page,
					],
					'/wc/store/v1/products'
				);

				$this->asset_data_registry->hydrate_api_request( $path );
				$this->asset_data_registry->hydrate_data_from_api_request( 'productSearchData', $path );
			}*/
		}

		/**
		 * Рендерит HTML-код блока.
		 *
		 * @param  array<string,mixed>  $attributes  Атрибуты блока.
		 * @param  string  $content  Контент блока.
		 * @param  WP_Block  $block  Объект блока.
		 *
		 * @return string HTML-код блока.
		 */
		public function render( array $attributes, string $content, WP_Block $block ): string {
			$wrapper_attributes = [ 'data-block-id' => esc_attr( $attributes['blockId'] ?? '' ), ];

			return sprintf( '<div %1$s></div>', get_block_wrapper_attributes( $wrapper_attributes ) );
		}
	}
}