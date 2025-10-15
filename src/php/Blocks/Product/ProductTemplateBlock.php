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
 * @class   ProductTemplateBlock
 * @package Strt\Plugin\Blocks
 * @version 1.0.0
 */

namespace Strt\Plugin\Blocks\Product;

defined( 'ABSPATH' ) || exit;

use Strt\Plugin\Assets\AssetDataRegistry;
use Strt\Plugin\Blocks\AbstractBlock;
use WP_Block;
use WP_Query;

if ( ! class_exists( 'Strt\Plugin\Blocks\Product\ProductTemplateBlock' ) ) {

	/**
	 * Класс блока «Коллекция товаров».
	 */
	class ProductTemplateBlock extends AbstractBlock {

		/**
		 * Имя блока, используется при регистрации блока.
		 *
		 * @var string
		 */
		protected string $block_name = 'product-template';

		/**
		 * Путь к директории с блоком, используется для загрузки файлов.
		 *
		 * @var string
		 */
		protected string $dir_path = '/product/';

		/**
		 * Отслеживает, были ли активы поставлены в очередь.
		 *
		 * @var boolean
		 */
		protected bool $enqueued_assets = false;

		/**
		 * Экземпляр AssetDataRegistry.
		 *
		 * @var AssetDataRegistry
		 */
		protected AssetDataRegistry $asset_data_registry;

		/**
		 * Конструктор класса.
		 */
		public function __construct( AssetDataRegistry $asset_data_registry ) {
			parent::__construct();

			$this->asset_data_registry = $asset_data_registry;
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
			$query = $this->prepare_and_execute_query( $block );

			//var_dump($query->post_count);
			//_strt_print_r( $block->context );

			if ( ! $query->have_posts() ) {
				return sprintf( '<div %s></div>', get_block_wrapper_attributes( [ 'class' => 'row g-3 row-cols-md-3 row-cols-1' ] ) );
			}

			$wrapper_attrs = get_block_wrapper_attributes( [ 'class' => 'row g-3 row-cols-md-3 row-cols-1' ] );
			$out           = '';

			while ( $query->have_posts() ) {
				$query->the_post();

				$product_id = get_the_ID();
				$product    = wc_get_product( $product_id );

				if ( $product ) {
					$out .= '<div class="col"><div class="card"><div class="card-body"><h5 class="card-title">' . $product->get_title() . '</h5><div class="h4 fw-bolder fs-base">' . $product->get_price_html() . '</div><a href="#" class="btn btn-primary">В корзину</a></div></div></div>';
				}

			}

			wp_reset_postdata();

			return sprintf( '<div %1$s>%2$s</div>', $wrapper_attrs, $out );
		}

		/**
		 * Подготовка и выполнение запроса для блока «Коллекция продуктов».
		 * Этот метод используется блоком «Коллекция продуктов» и блоком «Нет результатов».
		 *
		 * Экземпляр блока @param  WP_Block  $block  .
		 */
		protected function prepare_and_execute_query( WP_Block $block ) {
			$page_key = isset( $block->context['queryId'] ) ? 'query-' . $block->context['queryId'] . '-page' : 'query-page';
			// phpcs:ignore WordPress.Security.NonceVerification
			$page = empty( $_GET[ $page_key ] ) ? 1 : (int) $_GET[ $page_key ];

			$use_global_query = ( isset( $block->context['query']['inherit'] ) && $block->context['query']['inherit'] );

			if ( $use_global_query ) {
				global $wp_query;
				$query = clone $wp_query;
			} else {
				$query_args = build_query_vars_from_query_block( $block, $page );
				$query      = new WP_Query( $query_args );
			}

			return $query;
		}
	}
}