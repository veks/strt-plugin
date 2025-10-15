<?php
/**
 * Класс CartBlock для реализации блока корзины в WordPress.
 *
 * Этот класс представляет кастомный блок "Корзины" для редактора Gutenberg в WordPress.
 *
 * @class   CartBlock
 * @package Strt\Plugin\Blocks
 * @version 1.0.0
 */

namespace Strt\Plugin\Blocks;

defined( 'ABSPATH' ) || exit;

use Automattic\WooCommerce\Blocks\Package;
use Automattic\WooCommerce\Blocks\Domain\Services\CheckoutFields;
use Automattic\WooCommerce\Blocks\Utils\CartCheckoutUtils;
use Automattic\WooCommerce\StoreApi\Utilities\LocalPickupUtils;
use Exception;
use Strt\Plugin\Assets\AssetDataRegistry;
use WP_Block;

if ( ! class_exists( 'Strt\Plugin\Blocks\CartBlock' ) ) {

	/**
	 * Класс CartBlock.
	 *
	 * Блок корзина.
	 */
	class CartBlock extends AbstractBlock {

		/**
		 * Название блока, используемое при регистрации.
		 *
		 * @var string
		 */
		protected string $block_name = 'cart';

		/**
		 * Экземпляр AssetDataRegistry.
		 *
		 * @var AssetDataRegistry
		 */
		protected AssetDataRegistry $asset_data_registry;

		/**
		 * Конструктор класса CartBlock.
		 *
		 * @param  AssetDataRegistry  $asset_data_registry  Экземпляр основного реестра данных для передачи JS.
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
			add_filter( 'body_class', [ $this, 'add_body_class' ] );
		}

		/**
		 * Добавляем класс для корзины.
		 *
		 * @param  array  $classes  Классы.
		 *
		 * @return array Массив классов.
		 */
		public function add_body_class( array $classes ): array {
			if ( is_cart() ) {
				$classes[] = 'bg-gray-50';
			}

			return $classes;
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
			$this->asset_data_registry->add( 'taxesEnabled', wc_tax_enabled() );
			$this->asset_data_registry->add( 'couponsEnabled', wc_coupons_enabled() );
			$this->asset_data_registry->add( 'shippingEnabled', wc_shipping_enabled() );

			$pickup_location_settings = LocalPickupUtils::get_local_pickup_settings();
			$local_pickup_method_ids  = LocalPickupUtils::get_local_pickup_method_ids();

			$this->asset_data_registry->add( 'localPickupEnabled', $pickup_location_settings['enabled'] );
			$this->asset_data_registry->add( 'localPickupText', $pickup_location_settings['title'] );
			$this->asset_data_registry->add( 'localPickupCost', $pickup_location_settings['cost'] );
			$this->asset_data_registry->add( 'collectableMethodIds', $local_pickup_method_ids );
			$this->asset_data_registry->add( 'shippingMethodsExist', CartCheckoutUtils::shipping_methods_exist() > 0 );

			if ( ! $this->asset_data_registry->exists( 'localPickupLocations' ) ) {
				$this->asset_data_registry->add(
					'localPickupLocations',
					array_map(
						function ( $location ) {
							$location['formatted_address'] = wc()->countries->get_formatted_address( $location['address'], ', ' );

							return $location;
						},
						get_option( 'pickup_location_pickup_locations', [] )
					)
				);
			}

			if ( ! $this->asset_data_registry->exists( 'globalShippingMethods' ) ) {
				$shipping_methods           = WC()->shipping()->get_shipping_methods();
				$formatted_shipping_methods = array_reduce(
					$shipping_methods,
					function ( $acc, $method ) use ( $local_pickup_method_ids ) {
						if ( in_array( $method->id, $local_pickup_method_ids, true ) ) {
							return $acc;
						}
						if ( $method->supports( 'settings' ) ) {
							$acc[] = [
								'id'          => $method->id,
								'title'       => $method->method_title,
								'description' => $method->method_description,
							];
						}

						return $acc;
					},
					[]
				);
				$this->asset_data_registry->add( 'globalShippingMethods', $formatted_shipping_methods );
			}

			if ( ! $this->asset_data_registry->exists( 'activeShippingZones' ) && class_exists( 'WC_Shipping_Zones' ) ) {
				$this->asset_data_registry->add( 'activeShippingZones', CartCheckoutUtils::get_shipping_zones() );
			}

			try {
				$checkout_fields = Package::container()->get( CheckoutFields::class );
				$fields          = array_merge(
					$checkout_fields->get_core_fields(),
					$checkout_fields->get_additional_fields()
				);

				if ( ! $this->asset_data_registry->exists( 'defaultFields' ) ) {
					$this->asset_data_registry->add( 'defaultFields', $fields );
				}

				$fields_locations = [
					'address' => array_merge( \array_diff_key( $checkout_fields->get_core_fields_keys(), [ 'email' ] ) ),
					'contact' => [ 'email' ],
					'order'   => [],
				];

				$this->asset_data_registry->add( 'addressFieldsLocations', $fields_locations );
			} catch ( Exception $error ) {
				error_log( $error->getMessage() );
			}

			if ( ! is_admin() && ! $this->is_rest_api_request() ) {
				add_filter( 'woocommerce_store_api_disable_nonce_check', '__return_true' );

				$this->asset_data_registry->hydrate_api_request( '/wc/store/v1/cart' );
				$this->asset_data_registry->hydrate_data_from_api_request( 'cartData', '/wc/store/v1/cart' );
			}
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
