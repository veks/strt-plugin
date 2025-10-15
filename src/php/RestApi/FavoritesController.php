<?php
/**
 * FavoritesController REST API контроллер для работы с избранными товарами.
 *
 * @class  FavoritesController
 * @package Strt\Plugin\RestApi
 * @version 1.0.0
 */

namespace Strt\Plugin\RestApi;

defined( 'ABSPATH' ) || exit;

use Exception;
use Strt\Plugin\Services\FavoritesService;
use WP_REST_Request;
use WP_REST_Response;
use WP_Error;
use WP_REST_Server;

if ( ! class_exists( 'Strt\Plugin\RestApi\FavoritesController' ) ) {

	/**
	 * REST API контроллер для работы с избранными товарами.
	 */
	class FavoritesController extends RestApiController {

		/**
		 * Базовый путь маршрута.
		 *
		 * @var string
		 */
		protected $rest_base = 'favorites';

		/**
		 * Экземпляр обработчика избранного.
		 *
		 * @var FavoritesService
		 */
		protected FavoritesService $handler;

		/**
		 * Конструктор.
		 *
		 * @param  FavoritesService  $handler
		 */
		public function __construct( FavoritesService $handler ) {
			parent::__construct();

			$this->handler = $handler;
		}

		/**
		 * Инициализация.
		 *
		 * @return void
		 */
		public function init(): void {
			$this->handler->init();
		}

		/**
		 * Регистрирует маршруты REST API.
		 *
		 * @return void
		 */
		public function register_routes(): void {
			register_rest_route( $this->namespace, '/' . $this->rest_base,
				[
					[
						'methods'             => WP_REST_Server::READABLE,
						'callback'            => [ $this, 'get_items' ],
						'permission_callback' => [ $this, 'get_items_permissions_check' ],
						'schema'              => [ $this, 'get_public_item_schema' ],
					],
					[
						'methods'             => WP_REST_Server::CREATABLE,
						'callback'            => [ $this, 'create_item' ],
						'permission_callback' => [ $this, 'create_item_permissions_check' ],
						'args'                => $this->get_endpoint_args_for_item_schema(),
					],
				]
			);

			register_rest_route( $this->namespace, '/' . $this->rest_base . '/(?P<id>[\d]+)',
				[
					[
						'methods'             => WP_REST_Server::DELETABLE,
						'callback'            => [ $this, 'delete_item' ],
						'permission_callback' => [ $this, 'delete_item_permissions_check' ],
						'args'                => [
							'id' => [
								'type'     => 'integer',
								'required' => true,
							],
						],
					],
				]
			);

			register_rest_route( $this->namespace, '/' . $this->rest_base . '/add-to-cart',
				[
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => [ $this, 'add_all_to_cart' ],
					'permission_callback' => [ $this, 'create_item_permissions_check' ],
				]
			);
		}

		/**
		 * Проверка прав на получение избранного.
		 */
		public function get_items_permissions_check( $request ): WP_Error|bool {
			return $this->check_nonce( $request );
		}

		/**
		 * Проверка прав на добавление в избранное.
		 */
		public function create_item_permissions_check( $request ): WP_Error|bool {
			return $this->check_nonce( $request );
		}

		/**
		 * Проверка прав на удаление из избранного.
		 */
		public function delete_item_permissions_check( $request ): WP_Error|bool {
			return $this->check_nonce( $request );
		}

		/**
		 * Получает текущий список избранных товаров пользователя.
		 *
		 * @param  WP_REST_Request  $request
		 *
		 * @return WP_REST_Response
		 */
		public function get_items( $request ): WP_REST_Response {
			$user_id   = is_user_logged_in() ? get_current_user_id() : null;
			$favorites = $this->handler->get( $user_id );

			return rest_ensure_response( array_values( array_map( 'absint', $favorites ) ) );
		}

		/**
		 * Добавляет товар в избранное.
		 *
		 * @param  WP_REST_Request  $request
		 *
		 * @return WP_REST_Response|WP_Error
		 */
		public function create_item( $request ): WP_Error|WP_REST_Response {
			$product_id = (int) $request['id'];

			if ( ! $product_id ) {
				return new WP_Error( 'strt_favorite_invalid_id', 'Некорректный ID товара', [ 'status' => 400 ] );
			}

			$user_id = is_user_logged_in() ? get_current_user_id() : null;
			$this->handler->add( $product_id, $user_id );

			return $this->get_items( $request );
		}

		/**
		 * Удаляет товар из избранного.
		 *
		 * @param  WP_REST_Request  $request
		 *
		 * @return WP_REST_Response|WP_Error
		 */
		public function delete_item( $request ): WP_REST_Response|WP_Error {
			$product_id = (int) $request['id'];
			$user_id    = is_user_logged_in() ? get_current_user_id() : null;

			$this->handler->delete( $product_id, $user_id );

			return $this->get_items( $request );
		}

		/**
		 * Добавляет все избранные товары в корзину.
		 *
		 * @param  WP_REST_Request  $request
		 *
		 * @return WP_REST_Response|WP_Error
		 *
		 * @throws Exception
		 */
		public function add_all_to_cart( WP_REST_Request $request ): WP_REST_Response|WP_Error {
			$user_id = is_user_logged_in() ? get_current_user_id() : null;

			$this->handler->start_session();

			$result = $this->handler->add_all_to_cart( $user_id );

			if ( ! $result['success'] ) {
				return new WP_Error(
					'strt_favorites_cart_failed',
					$result['message'] ?? 'Не удалось добавить товары в корзину.',
					[ 'status' => 400 ]
				);
			}

			return rest_ensure_response( $result );
		}

		/**
		 * Возвращает схему элемента.
		 *
		 * @return array
		 */
		public function get_item_schema(): array {
			return [
				'$schema'    => 'http://json-schema.org/draft-04/schema#',
				'title'      => 'favorite',
				'type'       => 'object',
				'properties' => [
					'id' => [
						'description' => 'ID товара',
						'type'        => 'integer',
						'required'    => true,
						'context'     => [ 'view', 'edit' ],
					],
				],
			];
		}

		/**
		 * Параметры коллекции.
		 *
		 * @return array
		 */
		public function get_collection_params(): array {
			return [];
		}

		/**
		 * Проверяет nonce из заголовка X-WP-Nonce.
		 *
		 * @param  WP_REST_Request  $request
		 *
		 * @return boolean|WP_Error
		 */
		protected function check_nonce( WP_REST_Request $request ): WP_Error|bool {
			$nonce = $request->get_header( 'X-WP-Nonce' ) ?: ( $_SERVER['HTTP_X_WP_NONCE'] ?? '' );

			if ( ! wp_verify_nonce( $nonce, 'wp_rest' ) ) {
				return new WP_Error(
					'strt_rest_invalid_nonce',
					'Ошибка: Неверный или отсутствующий nonce.',
					[ 'status' => 403 ]
				);
			}

			return true;
		}
	}
}
