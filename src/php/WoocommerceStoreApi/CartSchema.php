<?php
/**
 * Класс CartSchema для расширения WooCommerce Store API дополнительными пользовательскими полями.
 * Используется для добавления/обновления пользовательских данных (например, координат доставки) в структуру корзины.
 *
 * @class   CartSchema
 * @package Strt\Plugin\WoocommerceStoreApi
 * @version 1.0.0
 */

namespace Strt\Plugin\WoocommerceStoreApi;

defined( 'ABSPATH' ) || exit;

use Strt\Plugin\Utils\Helper;
use WC_Customer;
use WP_REST_Request;

if ( ! class_exists( 'Strt\Plugin\WoocommerceStoreApi\CartSchema' ) ) {

	/**
	 * Класс CartSchema.
	 *
	 * Позволяет регистрировать и обрабатывать дополнительные данные.
	 */
	class CartSchema {

		/**
		 * Экземпляр вспомогательного класса.
		 *
		 * @var Helper
		 */
		public Helper $helper;

		/**
		 * Конструктор класса CartSchema.
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
			add_action( 'woocommerce_store_api_cart_update_customer_from_request', [ $this, 'handle_store_api_update_extensions' ], 10, 2 );
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
					'endpoint'        => \Automattic\WooCommerce\StoreApi\Schemas\V1\CartSchema::IDENTIFIER,
					'namespace'       => $this->helper::NAMESPACE,
					'data_callback'   => function () {
						$coords = is_user_logged_in()
							? get_user_meta( get_current_user_id(), 'coords', true )
							: ( function_exists( 'WC' ) && WC()->session ? WC()->session->get( 'coords' ) : [] );

						if ( ! is_array( $coords ) ) {
							$coords = [];
						}

						return [
							'coords' => $coords,
						];
					},
					'schema_callback' => function () {
						return [
							'properties' => [
								'coords' => [
									'description' => 'Координаты доставки',
									'type'        => [ 'array', 'null' ],
									'items'       => [
										'type' => 'array'
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
		 * Обрабатывает обновление пользовательских расширений корзины (например, координаты) через Store API.
		 *
		 * Сохраняет координаты в user meta для авторизованных пользователей, либо в сессию WooCommerce для гостей.
		 *
		 * @param  WC_Customer  $customer  Объект покупателя WooCommerce.
		 * @param  WP_REST_Request  $request  REST-запрос, содержащий пользовательские данные.
		 *
		 * @return void
		 */
		public function handle_store_api_update_extensions( WC_Customer $customer, WP_REST_Request $request ): void {
			$extensions = $request->get_param( 'extensions' );

			if ( ! empty( $extensions[ $this->helper::NAMESPACE ]['coords'] ) && is_array( $extensions['strt']['coords'] ) ) {
				$coords = $extensions[ $this->helper::NAMESPACE ]['coords'];

				if ( is_user_logged_in() ) {
					update_user_meta( get_current_user_id(), 'coords', $coords );
				} else {
					if ( function_exists( 'WC' ) && WC()->session ) {
						WC()->session->set( 'coords', $coords );
					}
				}
			}
		}
	}
}