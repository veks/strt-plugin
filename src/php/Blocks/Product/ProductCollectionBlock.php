<?php
/**
 * Блок «Коллекция товаров» (WooCommerce Store API).
 *
 * Отрисовывает контейнер для React-приложения, которое получает товары
 * и метаданные коллекции через Store API:
 * - /wc/store/v1/products — список товаров и пагинация (X-WP-TotalPages)
 * - /wc/store/v1/product-collection-data — метаданные коллекции (диапазон цен, стоки, рейтинги)
 *
 * React-скрипт берёт настройки из data-attrs и рендерит список + фильтры.
 *
 * @class   ProductCollectionBlock
 * @package Strt\Plugin\Blocks
 * @version 1.0.0
 */

namespace Strt\Plugin\Blocks\Product;

defined( 'ABSPATH' ) || exit;

use Strt\Plugin\Blocks\AbstractBlock;
use WP_Block;

if ( ! class_exists( 'Strt\Plugin\Blocks\Product\ProductCollectionBlock' ) ) {

	/**
	 * Класс блока «Коллекция товаров».
	 */
	class ProductCollectionBlock extends AbstractBlock {

		/**
		 * Имя блока, используется при регистрации блока.
		 *
		 * @var string
		 */
		protected string $block_name = 'product-collection';

		/**
		 * Путь к директории с блоком, используется для загрузки файлов.
		 *
		 * @var string
		 */
		protected string $dir_path = '/product/';

		public function __construct() {
			parent::__construct();
		}

		/**
		 * Инициализация блока.
		 *
		 * Должен быть реализован в дочернем классе.
		 *
		 * @return void
		 */
		public function init(): void {

		}

		protected function render( array $attributes, string $content, WP_Block $block ): string {
			return $content;
		}
	}
}