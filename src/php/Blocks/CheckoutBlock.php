<?php
/**
 * Класс CheckoutBlock для реализации блока оформления заказа в WordPress.
 *
 * Этот класс представляет кастомный блок "Оформление заказа" для редактора Gutenberg в WordPress.
 *
 * @class   CheckoutBlock
 * @package Strt\Plugin\Blocks
 * @version 1.0.0
 */

namespace Strt\Plugin\Blocks;

use Automattic\WooCommerce\Blocks\Domain\Services\CheckoutFields;
use Automattic\WooCommerce\Blocks\Package;
use Automattic\WooCommerce\Blocks\Utils\CartCheckoutUtils;
use Automattic\WooCommerce\StoreApi\Utilities\LocalPickupUtils;
use Automattic\WooCommerce\StoreApi\Utilities\PaymentUtils;
use Exception;
use Strt\Plugin\Assets\AssetDataRegistry;
use Strt\Plugin\Utils\Helper;
use WP_Block;
use WP_Error;

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'Strt\Plugin\Blocks\CheckoutBlock' ) ) {

	/**
	 * Класс CheckoutBlock.
	 *
	 * Блок оформление заказа.
	 */
	class CheckoutBlock extends AbstractBlock {

		/**
		 * Название блока, используемое при регистрации.
		 *
		 * @var string
		 */
		protected string $block_name = 'checkout';

		/**
		 * Экземпляр вспомогательного класса.
		 *
		 * @var Helper
		 */
		public Helper $helper;

		/**
		 * Экземпляр AssetDataRegistry.
		 *
		 * @var AssetDataRegistry
		 */
		protected AssetDataRegistry $asset_data_registry;

		/**
		 * Конструктор класса.
		 *
		 * @param  Helper  $helper  Экземпляр вспомогательного класса.
		 * @param  AssetDataRegistry  $asset_data_registry  Экземпляр гидрации данных.
		 */
		public function __construct( Helper $helper, AssetDataRegistry $asset_data_registry ) {
			parent::__construct();

			$this->helper              = $helper;
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

			add_action( 'woocommerce_init', [ $this, 'register_checkout_fields' ] );
		}

		/**
		 * Регистрируем поля для оформления заказа.
		 *
		 * @return void
		 *
		 * @throws Exception
		 */
		public function register_checkout_fields(): void {
			if ( ! function_exists( 'woocommerce_register_additional_checkout_field' ) ) {
				return;
			}

			woocommerce_register_additional_checkout_field( [
				'id'                         => sprintf( '%s/has-inn', $this->helper::NAMESPACE ),
				'label'                      => 'Покупаю как юридической лицо.',
				'location'                   => 'address',
				'type'                       => 'checkbox',
				'index'                      => 119,
				'show_in_order_confirmation' => false,
				'sanitize_callback'          => function ( $value ) {
					return ! empty( $value ) && $value !== '0' ? '1' : '';
				},
				'validate_callback'          => '__return_true',
			] );

			woocommerce_register_additional_checkout_field(
				[
					'id'                         => sprintf( '%s/inn', $this->helper::NAMESPACE ),
					'label'                      => 'ИНН юридического лица',
					'location'                   => 'address',
					'type'                       => 'text',
					'placeholder'                => 'Введите ИНН (10 цифр)',
					'autocomplete'               => 'off',
					'show_in_order_confirmation' => true,
					'index'                      => 120,
					'hidden'                     => [
						'type' => 'object',
						'if'   => [
							'properties' => [
								'customer' => [
									'type'       => 'object',
									'properties' => [
										'billing_address' => [
											'type'       => 'object',
											'properties' => [
												sprintf( '%s/has-inn', $this->helper::NAMESPACE ) => [
													'anyOf' => [
														[ 'const' => true ],
														[ 'const' => 1 ],
														[ 'const' => '1' ],
														[ 'const' => 'on' ],
													],
												],
											],
											'required'   => [ sprintf( '%s/has-inn', $this->helper::NAMESPACE ) ],
										],
									],
								],
							],
						],
						'then' => false,
						'else' => true,
					],
					'required'                   => [
						'type' => 'object',
						'if'   => [
							'properties' => [
								'customer' => [
									'type'       => 'object',
									'properties' => [
										'billing_address' => [
											'type'       => 'object',
											'properties' => [
												sprintf( '%s/has-inn', $this->helper::NAMESPACE ) => [
													'anyOf' => [
														[ 'const' => true ],
														[ 'const' => 1 ],
														[ 'const' => '1' ],
														[ 'const' => 'on' ],
													],
												],
											],
											'required'   => [ sprintf( '%s/has-inn', $this->helper::NAMESPACE ) ],
										],
									],
								],
							],
						],
						'then' => true,   // условие выполняется → required = true
						'else' => false,  // иначе required = false
					],
					'sanitize_callback'          => function ( $value ) {
						return preg_replace( '/\D+/', '', (string) $value );
					},
					'validate_callback'          => function ( $value ) {
						$digits = preg_replace( '/\D+/', '', (string) $value );
						if ( $digits !== '' ) {
							if ( ! preg_match( '/^\d{10}$/', $digits ) ) {
								return new WP_Error( 'woocommerce_invalid_checkout_field', 'Некорректный ИНН юридического лица. Должен состоять из 10 цифр.' );
							}

							$k   = [ 2, 4, 10, 3, 5, 9, 4, 6, 8 ];
							$sum = 0;

							for ( $i = 0; $i < 9; $i ++ ) {
								$sum += (int) $digits[ $i ] * $k[ $i ];
							}

							$cs = $sum % 11;

							if ( $cs == 10 ) {
								$cs = 0;
							}

							if ( (int) $digits[9] !== $cs ) {
								return new WP_Error( 'woocommerce_invalid_inn_checksum', 'ИНН юридического лица не прошёл проверку контрольной суммы.' );
							}
						}

						return true;
					},
					'validation'                 => [
						'type'         => 'string',
						'pattern'      => '^\d{10}$',
						'errorMessage' => 'Введите корректный ИНН юридического лица: 10 цифр.',
					],
				]
			);

		}

		/**
		 * Добавляем класс для оформления заказа.
		 *
		 * @param  array  $classes  Классы.
		 *
		 * @return array Массив классов.
		 */
		public function add_body_class( array $classes ): array {
			if ( is_checkout() ) {
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
			$country_data    = CartCheckoutUtils::get_country_data();
			$address_formats = WC()->countries->get_address_formats();

			foreach ( $address_formats as $country_code => $format ) {
				if ( 'default' === $country_code ) {
					continue;
				}

				$country_data[ $country_code ]['format'] = $format;
			}

			$this->asset_data_registry->add( 'countryData', $country_data );
			$this->asset_data_registry->add( 'defaultAddressFormat', $address_formats['default'] );
			$this->asset_data_registry->add(
				'checkoutAllowsGuest',
				false === filter_var( WC()->checkout()->is_registration_required(), FILTER_VALIDATE_BOOLEAN )
			);
			$this->asset_data_registry->add(
				'checkoutAllowsSignup',
				filter_var(
					WC()->checkout()->is_registration_enabled(),
					FILTER_VALIDATE_BOOLEAN
				)
			);
			$this->asset_data_registry->add( 'delayedAccountCreationEnabled', get_option( 'woocommerce_enable_delayed_account_creation', 'yes' ) === 'yes' );
			$this->asset_data_registry->add( 'registrationGeneratePassword', filter_var( get_option( 'woocommerce_registration_generate_password' ), FILTER_VALIDATE_BOOLEAN ) );

			$this->asset_data_registry->add( 'shippingCostRequiresAddress', get_option( 'woocommerce_shipping_cost_requires_address', false ) === 'yes' );
			$this->asset_data_registry->add( 'checkoutShowLoginReminder', filter_var( get_option( 'woocommerce_enable_checkout_login_reminder' ), FILTER_VALIDATE_BOOLEAN ) );
			$this->asset_data_registry->add( 'displayCartPricesIncludingTax', 'incl' === get_option( 'woocommerce_tax_display_cart' ) );
			$this->asset_data_registry->add( 'displayItemizedTaxes', 'itemized' === get_option( 'woocommerce_tax_total_display' ) );
			$this->asset_data_registry->add( 'forcedBillingAddress', 'billing_only' === get_option( 'woocommerce_ship_to_destination' ) );
			$this->asset_data_registry->add( 'generatePassword', filter_var( get_option( 'woocommerce_registration_generate_password' ), FILTER_VALIDATE_BOOLEAN ) );
			$this->asset_data_registry->add( 'taxesEnabled', wc_tax_enabled() );
			$this->asset_data_registry->add( 'couponsEnabled', wc_coupons_enabled() );
			$this->asset_data_registry->add( 'shippingEnabled', wc_shipping_enabled() );

			$pickup_location_settings = LocalPickupUtils::get_local_pickup_settings();
			$local_pickup_method_ids  = LocalPickupUtils::get_local_pickup_method_ids();

			$this->asset_data_registry->add( 'localPickupEnabled', $pickup_location_settings['enabled'] );
			$this->asset_data_registry->add( 'localPickupText', $pickup_location_settings['title'] );
			$this->asset_data_registry->add( 'localPickupCost', $pickup_location_settings['cost'] );
			$this->asset_data_registry->add( 'collectableMethodIds', $local_pickup_method_ids );
			$this->asset_data_registry->add( 'shippingMethodsExist', CartCheckoutUtils::shipping_methods_exist() );

			if ( ! $this->asset_data_registry->exists( 'localPickupLocations' ) ) {
				$this->asset_data_registry->add(
					'localPickupLocations',
					array_map(
						function ( $location ) {
							$location['formatted_address'] = WC()->countries->get_formatted_address( $location['address'], ', ' );

							return $location;
						},
						get_option( 'pickup_location_pickup_locations', [] )
					)
				);
			}

			if ( ! $this->asset_data_registry->exists( 'globalShippingMethods' ) ) {
				$shipping_methods = WC()->shipping()->get_shipping_methods();

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

			if ( ! $this->asset_data_registry->exists( 'globalPaymentMethods' ) ) {
				$payment_methods           = PaymentUtils::get_enabled_payment_gateways();
				$formatted_payment_methods = array_reduce( $payment_methods, function ( $acc, $method ) {
					$acc[] = [
						'id'          => $method->id,
						'title'       => $method->get_title() !== '' ? $method->get_title() : $method->get_method_title(),
						'description' => $method->get_description() !== '' ? $method->get_description() : $method->get_method_description(),
					];

					return $acc;
				}, [] );

				$this->asset_data_registry->add( 'globalPaymentMethods', $formatted_payment_methods );
			}

			try {
				$checkout_fields = Package::container()->get( CheckoutFields::class );
				$fields          = array_merge( $checkout_fields->get_core_fields(), $checkout_fields->get_additional_fields() );

				if ( ! $this->asset_data_registry->exists( 'defaultFields' ) ) {
					$this->asset_data_registry->add( 'defaultFields', $fields );
				}

				$fields_locations = [
					'address' => $checkout_fields->get_address_fields_keys(),
					'contact' => $checkout_fields->get_contact_fields_keys(),
					'order'   => $checkout_fields->get_order_fields_keys(),
				];

				$this->asset_data_registry->add( 'addressFieldsLocations', $fields_locations );
			} catch ( Exception $error ) {
				error_log( $error->getMessage() );
			}

			if ( ! is_admin() && ! $this->is_rest_api_request() ) {
				add_filter( 'woocommerce_store_api_disable_nonce_check', '__return_true' );

				$this->asset_data_registry->hydrate_api_request( '/wc/store/v1/cart' );
				$this->asset_data_registry->hydrate_data_from_api_request( 'cartData', '/wc/store/v1/cart' );
				$this->asset_data_registry->hydrate_data_from_api_request( 'checkoutData', '/wc/store/v1/checkout' );
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

			if ( $this->is_checkout_endpoint() ) {
				return wp_is_block_theme() ? do_shortcode( '[woocommerce_checkout]' ) : '[woocommerce_checkout]';
			}

			add_action( 'wp_enqueue_scripts', [ $this, 'dequeue_woocommerce_core_scripts' ], 20 );

			return sprintf( '<div %1$s></div>', get_block_wrapper_attributes( $wrapper_attributes ) );
		}

		/**
		 * Проверить, просматриваем ли мы конечную точку страницы оформления заказа, а не саму главную страницу оформления заказа.
		 *
		 * @return boolean
		 */
		protected function is_checkout_endpoint(): bool {
			return is_wc_endpoint_url( 'order-pay' ) || is_wc_endpoint_url( 'order-received' );
		}

		/**
		 * Отключаем скрипты, добавленные ядром WooCommerce на страницу оформления заказа.
		 *
		 * @return void
		 */
		public function dequeue_woocommerce_core_scripts(): void {
			wp_dequeue_script( 'wc-checkout' );
			wp_dequeue_script( 'wc-password-strength-meter' );
			wp_dequeue_script( 'selectWoo' );
			wp_dequeue_style( 'select2' );
		}
	}
}