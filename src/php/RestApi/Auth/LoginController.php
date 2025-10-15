<?php
/**
 * LoginController — REST API контроллер для регистрации.
 *
 * Реализует:
 *  - POST /strt/v1/auth/       — вход (wp_signon), с учётом WooCommerce (cookie, сессия, корзина) и multisite
 *  - POST /strt/v1/auth/logout — выход (wp_logout)
 *
 * @package    Strt\Plugin\RestApi\Auth
 * @subpackage Controllers
 * @since      1.0.0
 */

namespace Strt\Plugin\RestApi\Auth;

defined( 'ABSPATH' ) || exit;

use Strt\Plugin\RestApi\RestApiController;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

if ( ! class_exists( 'Strt\Plugin\RestApi\Auth\LoginController' ) ) {

	/**
	 * Класс LoginController для регистрации пользователей.
	 */
	class LoginController extends RestApiController {

		/**
		 * Базовый путь маршрута.
		 *
		 * @var string
		 */
		protected $rest_base = 'auth';

		/**
		 * Инициализация контроллера (резерв).
		 *
		 * @return void
		 */
		public function init(): void {
		}

		/**
		 * Регистрирует маршруты REST API.
		 *
		 * @return void
		 */
		public function register_routes(): void {
			register_rest_route(
				$this->namespace,
				'/' . $this->rest_base,
				[
					[
						'methods'             => WP_REST_Server::CREATABLE,
						'callback'            => [ $this, 'login' ],
						'permission_callback' => [ $this, 'permissions_check' ],
						'args'                => $this->get_endpoint_args_for_item_schema( WP_REST_Server::CREATABLE ),
					],
					'schema' => [ $this, 'get_public_item_schema' ],
				]
			);

			register_rest_route(
				$this->namespace,
				'/' . $this->rest_base . '/logout',
				[
					[
						'methods'             => WP_REST_Server::CREATABLE,
						'callback'            => [ $this, 'logout' ],
						'permission_callback' => [ $this, 'permissions_check' ],
					],
					'schema' => [ $this, 'get_public_item_schema' ],
				]
			);
		}

		/**
		 * Проверяет права доступа через X-WP-Nonce.
		 *
		 * @param  WP_REST_Request  $request  Объект запроса.
		 *
		 * @return WP_Error|bool True при успешной проверке или WP_Error при ошибке.
		 */
		public function permissions_check( WP_REST_Request $request ): WP_Error|bool {
			$nonce = $request->get_header( 'X-WP-Nonce' ) ?: ( $_SERVER['HTTP_X_WP_NONCE'] ?? '' );

			if ( ! wp_verify_nonce( $nonce, 'wp_rest' ) ) {
				return new WP_Error(
					'strt_rest_invalid_nonce',
					'Неверный или отсутствующий nonce.',
					[ 'status' => 403 ]
				);
			}

			return true;
		}

		/**
		 * Авторизация пользователя.
		 *
		 * @param  WP_REST_Request  $request  Объект запроса.
		 *
		 * @return WP_REST_Response|WP_Error При успехе возвращает объект с полями success, message, user (id, login, email, display_name).
		 */
		public function login( WP_REST_Request $request ): WP_REST_Response|WP_Error {
			$data     = $request->get_json_params() ?: $request->get_body_params();
			$username = trim( (string) ( $data['username'] ?? '' ) );
			$password = (string) ( $data['password'] ?? '' );
			$remember = (bool) ( $data['remember'] ?? false );

			if ( $username === '' || $password === '' ) {
				return new WP_Error(
					'strt_auth_validation',
					'Введите имя пользователя и пароль.',
					[
						'status' => 422,
						'errors' => [
							'username' => $username === '' ? 'Обязательное поле.' : '',
							'password' => $password === '' ? 'Обязательное поле.' : '',
						],
					]
				);
			}

			$credentials = [
				'user_login'    => $username,
				'user_password' => $password,
				'remember'      => $remember,
			];

			$user = wp_signon( $credentials, is_ssl() );

			if ( is_wp_error( $user ) ) {
				return new WP_Error(
					'strt_auth_failed',
					$user->get_error_message(),
					[
						'status' => 401,
						'errors' => [
							'username' => 'Неверный логин.',
							'password' => 'Неверный пароль.',
						],
					]
				);
			}

			wp_set_current_user( $user->ID );

			if ( class_exists( 'WooCommerce' ) ) {
				if ( function_exists( 'wc_set_customer_auth_cookie' ) ) {
					wc_set_customer_auth_cookie( $user->ID );
				}

				if ( function_exists( 'WC' ) ) {
					if ( null === WC()->cart ) {
						wc_load_cart();
					}

					WC()->session->set( 'reload_checkout', true );
					WC()->cart->calculate_totals();
				}
			}

			if ( is_multisite() && is_user_logged_in() && ! is_user_member_of_blog( $user->ID, get_current_blog_id() ) ) {
				$result = add_user_to_blog( get_current_blog_id(), $user->ID, apply_filters( 'strt_new_user_role', 'customer', $user->ID, get_current_blog_id() ) );

				if ( is_wp_error( $result ) ) {
					return new WP_Error(
						'strt_multisite_add_user_failed',
						'Не удалось привязать пользователя к текущему сайту.',
						[ 'status' => 500 ]
					);
				}
			}

			return rest_ensure_response(
				[
					'success'  => true,
					'message'  => sprintf( 'Добро пожаловать, %s!', esc_html( $user->display_name ) ),
					'redirect' => wp_validate_redirect( wp_unslash( $data['redirect'] ?? '' ) ),
				]
			);
		}

		/**
		 * Выход пользователя.
		 *
		 * @param  WP_REST_Request  $request  Объект запроса.
		 *
		 * @return WP_REST_Response
		 */
		public function logout( WP_REST_Request $request ): WP_REST_Response {
			wp_logout();

			return rest_ensure_response(
				[
					'success' => true,
					'message' => 'Вы вышли из системы.',
				]
			);
		}

		/**
		 * Возвращает JSON Schema для логина.
		 *
		 * @return array
		 */
		public function get_item_schema(): array {
			$schema = [
				'$schema'    => 'http://json-schema.org/draft-04/schema#',
				'title'      => 'auth_login',
				'type'       => 'object',
				'properties' => [
					'username' => [
						'description' => 'Имя пользователя или email.',
						'type'        => 'string',
					],
					'password' => [
						'description' => 'Пароль пользователя.',
						'type'        => 'string',
					],
					'remember' => [
						'description' => 'Опция «Запомнить меня».',
						'type'        => 'boolean',
						'default'     => false,
					],
					'redirect' => [
						'description' => 'Необязательный URL для перенаправления после входа.',
						'type'        => 'string',
					],
				],
			];

			return $this->add_additional_fields_schema( $schema );
		}

		/**
		 * Возвращает публичную схему для логина.
		 *
		 * @return array
		 */
		public function get_public_item_schema(): array {
			return $this->get_item_schema();
		}

		/**
		 * Параметры коллекции (не используются).
		 *
		 * @return array
		 */
		public function get_collection_params(): array {
			return [];
		}
	}
}