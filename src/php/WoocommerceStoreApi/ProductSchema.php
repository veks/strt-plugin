<?php
/**
 * Класс ProductSchema для расширения WooCommerce Store API дополнительными пользовательскими полями продукта.
 * Позволяет добавить к данным товара в REST API информацию о вариациях, брендах и атрибутах.
 *
 * @class   ProductSchema
 * @package Strt\Plugin\WoocommerceStoreApi
 * @version 1.0.0
 */

namespace Strt\Plugin\WoocommerceStoreApi;

use Strt\Plugin\Utils\Helper;
use WC_Product;
use WP_Term;

defined( 'ABSPATH' ) || exit;
if ( ! class_exists( 'Strt\Plugin\WoocommerceStoreApi\ProductSchema' ) ) {

	/**
	 * Класс ProductSchema.
	 *
	 * Позволяет регистрировать и обрабатывать дополнительные данные в Store API WooCommerce для продуктов.
	 */
	class ProductSchema {

		/**
		 * Экземпляр вспомогательного класса.
		 *
		 * @var Helper
		 */
		public Helper $helper;

		/**
		 * Конструктор класса ProductSchema.
		 *
		 * @param  Helper  $helper  Вспомогательный объект.
		 */
		public function __construct( Helper $helper ) {
			$this->helper = $helper;
		}

		/**
		 * Инициализация.
		 *
		 * @return void
		 */
		public function init(): void {
			add_action( 'woocommerce_init', [ $this, 'register_store_api_data' ] );
		}

		/**
		 * Регистрирует данные.
		 *
		 * Добавляет данным в Store API.
		 *
		 * @return void
		 */
		public function register_store_api_data(): void {
			woocommerce_store_api_register_endpoint_data(
				[
					'endpoint'        => \Automattic\WooCommerce\StoreApi\Schemas\V1\ProductSchema::IDENTIFIER,
					'namespace'       => $this->helper::NAMESPACE,
					'data_callback'   => function ( $product ) {
						return [
							'product'            => $product->get_data(),
							'variations'         => $this->get_product_variations( $product ),
							'brands'             => $this->get_term_list( $product, 'product_brand' ),
							'product_attributes' => $this->get_product_attributes( $product ),
						];
					},
					'schema_callback' => function () {
						return [
							'properties' => [
								'product'            => [
									'description' => 'Вариативные товары',
									'type'        => 'array',
									'items'       => [
										'type' => 'object'
									],
									'context'     => [ 'view', 'edit' ],
									'readonly'    => true,
								],
								'variations'         => [
									'description' => 'Вариативные товары',
									'type'        => 'array',
									'items'       => [
										'type' => 'object'
									],
									'context'     => [ 'view', 'edit' ],
									'readonly'    => true,
								],
								'brands'             => [
									'description' => 'Бренды',
									'type'        => 'array',
									'items'       => [
										'type' => 'object'
									],
									'context'     => [ 'view', 'edit' ],
									'readonly'    => true,
								],
								'product_attributes' => [
									'description' => 'Атрибуты',
									'type'        => 'array',
									'items'       => [
										'type' => 'object'
									],
									'context'     => [ 'view', 'edit' ],
									'readonly'    => true,
								],
							]
						];
					},
					'schema_type'     => ARRAY_A,
				]
			);
		}

		/**
		 * Получить список атрибутов продукта и терминов атрибутов.
		 *
		 * @param  WC_Product  $product
		 *
		 * @return array
		 */
		protected function get_product_attributes( WC_Product $product ): array {
			$attributes         = array_filter( $product->get_attributes(), [ $this, 'filter_valid_attribute' ] );
			$default_attributes = $product->get_default_attributes();
			$return             = [];

			foreach ( $attributes as $attribute_slug => $attribute ) {
				if ( ! $attribute->get_visible() && ! $attribute->get_variation() ) {
					continue;
				}

				$terms = $attribute->is_taxonomy() ? array_map( [ $this, 'prepare_product_attribute_taxonomy_value' ],
					$attribute->get_terms() ) : array_map( [ $this, 'prepare_product_attribute_value' ], $attribute->get_options() );

				$sanitized_attribute_name = sanitize_key( $attribute->get_name() );

				if ( array_key_exists( $sanitized_attribute_name, $default_attributes ) ) {
					foreach ( $terms as $term ) {
						$term->default = $term->slug === $default_attributes[ $sanitized_attribute_name ];
					}
				}

				$return[] = (object) [
					'id'             => $attribute->get_id(),
					'name'           => wc_attribute_label( $attribute->get_name(), $product ),
					'taxonomy'       => $attribute->is_taxonomy() ? $attribute->get_name() : null,
					'has_variations' => true === $attribute->get_variation(),
					'terms'          => $terms,
				];
			}

			return $return;
		}

		/**
		 * Подготовьте атрибут term для ответа.
		 *
		 * @param  WP_Term  $term  Объект Term.
		 *
		 * @return object
		 */
		protected function prepare_product_attribute_taxonomy_value( WP_Term $term ): object {
			$link = get_term_link( $term, $term->taxonomy );

			if ( is_wp_error( $link ) || str_contains( $link, '?taxonomy=' ) ) {
				$link = '';
			} else {
				$link = esc_url( $link );
			}

			return $this->prepare_product_attribute_value( $term->name, $term->term_id, $term->slug, $link );
		}

		/**
		 * Подготовьте атрибут для ответа.
		 *
		 * @param  string  $name  Имя атрибута.
		 * @param  int  $id  Идентификатор атрибута.
		 * @param  string  $slug  Ярлык атрибута.
		 * @param  string  $link  Ссылка.
		 *
		 * @return object
		 */
		protected function prepare_product_attribute_value( string $name, int $id = 0, string $slug = '', string $link = '' ): object {
			return (object) [
				'id'   => (int) $id,
				'name' => $name,
				'slug' => $slug ? $slug : $name,
				'link' => $link,
			];
		}

		/**
		 * Получить вариативные товары.
		 *
		 * @param  WC_Product  $product  Объект продукта.
		 *
		 * @return array Array
		 */
		protected function get_product_variations( WC_Product $product ): array {
			$variations_data = [];

			if ( $product && $product->is_type( 'variable' ) ) {
				$children        = $product->get_children();
				$variations_data = array_map( function ( $id ) {
					$variation = wc_get_product( $id );

					return $variation ? $variation->get_data() : [];
				}, $children );
				$variations_data = array_filter( $variations_data );
			}

			return $variations_data;
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
		 * Возвращает true, если заданный атрибут действителен.
		 *
		 * @param  mixed  $attribute  Объект или переменная для проверки.
		 *
		 * @return boolean
		 */
		protected function filter_valid_attribute( mixed $attribute ): bool {
			return is_a( $attribute, '\WC_Product_Attribute' );
		}
	}
}