<?php
/**
 * Блок одиночного продукта для редактора Gutenberg.
 *
 * Реализует кастомный блок "Single Product" и подготавливает данные
 * для фронтенда через Store API и AssetDataRegistry.
 *
 * @class   ProductSingleBlock
 * @package Strt\Plugin\Blocks\Product
 * @version 1.0.0
 */

namespace Strt\Plugin\Blocks\Product;

defined( 'ABSPATH' ) || exit;

use Strt\Plugin\Assets\AssetDataRegistry;
use Strt\Plugin\Blocks\AbstractBlock;
use WC_Product;
use WP_Block;

if ( ! class_exists( 'Strt\Plugin\Blocks\Product\ProductSingleBlock' ) ) {

	/**
	 * Класс ProductSingleBlock.
	 *
	 * Отвечает за регистрацию, подготовку данных и рендер блока "Одиночный продукт".
	 */
	class ProductSingleBlock extends AbstractBlock {

		/**
		 * Название блока, используемое при регистрации.
		 *
		 * @var string
		 */
		protected string $block_name = 'product-single';

		/**
		 * Относительный путь к директории текущего блока (относительно корня блоков).
		 *
		 * @var string
		 */
		protected string $dir_path = '/product/';

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
		 * В этом методе можно добавить дополнительные хуки, специфичные для данного блока.
		 *
		 * @return void
		 */
		public function init(): void {
			// TODO: Implement init() method.
		}

		/**
		 * Подключает скрипты и стили на фронтенде.
		 *
		 * Этот метод можно переопределить для загрузки специфичных для блока ресурсов на клиентской стороне.
		 *
		 * @param  array  $attributes  Любые атрибуты, которые в данный момент доступны для блока.
		 *
		 * @return void
		 */
		protected function enqueue_data( array $attributes = [] ): void {
			if ( is_product() && ! $this->asset_data_registry->exists( 'productData' ) ) {
				$path = '/wc/store/v1/products/' . get_the_ID();
				$this->asset_data_registry->hydrate_api_request( $path );
				$this->asset_data_registry->hydrate_data_from_api_request( 'productData', $path );
			}

			/*
			 * 			$product = wc_get_product( get_the_ID() );
			 * if ( $product && ! $this->asset_data_registry->exists( 'productBrands' ) ) {
				$this->asset_data_registry->add( 'productBrands', $this->get_term_list( $product, 'product_brand' ) );
			}

			if ( $product && $product->is_type( 'variable' ) && ! $this->asset_data_registry->exists( 'productVariableData' ) ) {
				$children        = $product->get_children();
				$variations_data = array_map( function ( $id ) {
					$variation = wc_get_product( $id );

					return $variation ? $variation->get_data() : [];
				}, $children );

				$variations_data = array_filter( $variations_data );
				$this->asset_data_registry->add( 'productVariableData', $variations_data );
			}*/
		}

		/**
		 * Возвращает список терминов, назначенных продукту.
		 *
		 * @param  WC_Product  $product  Объект продукта.
		 * @param  string  $taxonomy  Имя таксономии.
		 *
		 * @return array Array of terms (id, name, slug).
		 */
		protected function get_term_list( WC_Product $product, string $taxonomy = '' ): array {
			if ( ! $taxonomy ) {
				return [];
			}

			$terms = get_the_terms( $product->get_id(), $taxonomy );

			if ( ! $terms || is_wp_error( $terms ) ) {
				return [];
			}

			$return           = [];
			$default_category = (int) get_option( 'default_product_cat', 0 );

			foreach ( $terms as $term ) {
				$link = get_term_link( $term, $taxonomy );

				if ( is_wp_error( $link ) ) {
					continue;
				}

				if ( $term->term_id === $default_category ) {
					continue;
				}

				$thumbnail_id = get_term_meta( $term->term_id, 'thumbnail_id', true );
				$image_attr   = [ 'alt' => $term->name, 'class' => 'img-fluid object-fit-contain' ];
				$image        = $thumbnail_id ? wp_get_attachment_image( $thumbnail_id, 'full', '', $image_attr ) : '';

				$return[] = (object) [
					'id'    => $term->term_id,
					'name'  => $term->name,
					'slug'  => $term->slug,
					'link'  => $link,
					'image' => $image,
				];
			}

			return $return;
		}

		/**
		 * Рендерит HTML-код блока на фронтенде.
		 *
		 * @param  array  $attributes  Атрибуты блока, переданные из редактора.
		 * @param  string  $content  Контент блока.
		 * @param  WP_Block  $block  Объект блока.
		 *
		 * @return string HTML-код блока.
		 */
		public function render( array $attributes, string $content, WP_Block $block ): string {
			$wrapper_attributes = [
				'data-block-id' => esc_attr( $attributes['blockId'] ),
			];

			return sprintf( '<div %1$s></div>', get_block_wrapper_attributes( $wrapper_attributes ) );
		}
	}
}