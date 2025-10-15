<?php
/**
 * RegisterController — REST API контроллер для регистрации.
 *
 * Реализует:
 *  - POST /strt/v1/auth/register — регистрация (без капчи). Если активен WooCommerce — через его API
 *
 * @package    Strt\Plugin\RestApi\Auth
 * @subpackage Controllers
 * @since      1.0.0
 */

namespace Strt\Plugin\RestApi\Auth;

defined( 'ABSPATH' ) || exit;

use Exception;
use Strt\Plugin\RestApi\RestApiController;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;
use WP_User_Query;

if ( ! class_exists( 'Strt\Plugin\RestApi\Auth\RegisterController' ) ) {

	/**
	 * Класс RegisterController для регистрации пользователей.
	 */
	class RegisterController extends RestApiController {

		/**
		 * Базовый путь маршрута.
		 *
		 * @var string
		 */
		protected $rest_base = 'auth/register';

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
						'callback'            => [ $this, 'register' ],
						'permission_callback' => [ $this, 'permissions_check' ],
						'args'                => $this->get_endpoint_args_for_item_schema( WP_REST_Server::CREATABLE ),
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
		 * Регистрация пользователя.
		 *
		 * @param  WP_REST_Request  $request  Объект запроса.
		 *
		 * @return WP_REST_Response|WP_Error При успехе возвращает объект с полями success, message, user (id, login, email, display_name).
		 */
		public function register( WP_REST_Request $request ): WP_REST_Response|WP_Error {
			$data = $request->get_json_params() ?: $request->get_body_params();

			error_log( print_r( $data, true ) );

			$woo_active = class_exists( 'WooCommerce' ) && function_exists( 'wc_create_new_customer' );
			$validated  = $this->validate_register_params( $data, $woo_active );

			if ( is_wp_error( $validated ) ) {
				return $validated;
			}

			$email      = $validated['email'];
			$phone      = $validated['phone'];
			$password   = $validated['password'];
			$first_name = $validated['first_name'];
			$last_name  = $validated['last_name'];

			try {
				if ( $woo_active ) {
					$user_id = wc_create_new_customer(
						$email,
						'',
						$password,
						[
							'first_name' => $first_name,
							'last_name'  => $last_name,
						]
					);

					if ( is_wp_error( $user_id ) ) {
						$code    = $user_id->get_error_code();
						$message = $user_id->get_error_message();

						return new WP_Error( "strt_reg_$code", $message, [ 'status' => 400, 'errors' => str_replace( 'wc_', '', $code ) ] );
					}

					update_user_meta( $user_id, 'billing_first_name', $first_name );
					update_user_meta( $user_id, 'billing_last_name', $last_name );
					update_user_meta( $user_id, 'billing_phone', $phone );
					update_user_meta( $user_id, 'phone_number', $phone );

					if ( function_exists( 'WC' ) ) {
						$mailer = WC()->mailer()->get_emails();
						if ( isset( $mailer['WC_Email_Customer_New_Account'] ) ) {
							$mailer['WC_Email_Customer_New_Account']->trigger( $user_id, $password );
						}
					}

					if ( function_exists( 'wc_set_customer_auth_cookie' ) ) {
						wc_set_customer_auth_cookie( $user_id );
					} else {
						$this->auth_with_cookie( $user_id );
					}

					if ( function_exists( 'WC' ) ) {
						if ( null === WC()->cart ) {
							wc_load_cart();
						}
						WC()->session->set( 'reload_checkout', true );
						WC()->cart->calculate_totals();
					}
				} else {
					$username = $this->generate_username_from_email( $email );
					$user_id  = wp_create_user( $username, $password, $email );

					if ( is_wp_error( $user_id ) ) {
						return new WP_Error( 'strt_reg_create_failed', 'Ошибка при создании аккаунта.', [ 'status' => 500 ] );
					}

					wp_update_user(
						[
							'ID'         => $user_id,
							'first_name' => $first_name,
							'last_name'  => $last_name,
						]
					);

					update_user_meta( $user_id, 'phone_number', $phone );

					$this->auth_with_cookie( $user_id );
				}

				$this->handle_user_auth_and_multisite( $user_id );

				$user = get_user_by( 'id', $user_id );

				return rest_ensure_response(
					[
						'success' => true,
						'message' => 'Регистрация прошла успешно.',
						'user'    => [
							'id'           => $user_id,
							'login'        => $user ? $user->user_login : '',
							'email'        => $email,
							'display_name' => $user ? $user->display_name : trim( $first_name . ' ' . $last_name ),
						],
						'redirect' => wp_validate_redirect( wp_unslash( $data['redirect'] ?? '' ) ),
					]
				);

			} catch ( Exception $error ) {
				return new WP_Error( 'strt_reg_exception', 'Ошибка: ' . $error->getMessage(), [ 'status' => 500 ] );
			}
		}

		/**
		 * Валидация параметров регистрации.
		 *
		 * @param  array  $data  Параметры запроса.
		 * @param  bool  $woo_active  Активен ли WooCommerce.
		 *
		 * @return WP_Error|array Массив валидированных параметров или WP_Error.
		 */
		protected function validate_register_params( array $data, bool $woo_active ): WP_Error|array {
			$errors = [];
			$tt     = static fn( $v ) => is_string( $v ) ? sanitize_text_field( $v ) : $v;

			// email
			$email = sanitize_email( $data['email'] ?? '' );
			$email = $woo_active && function_exists( 'wc_clean' ) ? wc_clean( wp_unslash( $email ) ) : $email;

			if ( empty( $email ) || ! is_email( $email ) ) {
				$errors['email'] = 'Некорректный email.';
			} elseif ( email_exists( $email ) ) {
				$errors['email'] = 'Пользователь с указанным email уже зарегистрирован.';
			}

			// phone
			$phone_raw = $woo_active && function_exists( 'wc_clean' ) ? wc_clean( wp_unslash( $data['phone'] ?? '' ) ) : $tt( $data['phone'] ?? '' );
			$phone     = $this->normalize_phone_ru( (string) $phone_raw );

			if ( empty( $phone ) || ! preg_match( '/^7\d{10}$/', $phone ) ) {
				$errors['phone'] = 'Введите номер телефона в формате +7 (900) 000-00-00';
			} else {
				$user_query = new WP_User_Query(
					[
						'meta_key'   => 'phone_number',
						'meta_value' => $phone,
						'number'     => 1,
						'fields'     => 'ID',
					]
				);
				if ( ! empty( $user_query->get_results() ) ) {
					$errors['phone'] = 'Пользователь с таким номером телефона уже зарегистрирован.';
				}
			}

			// password
			$password = $woo_active && function_exists( 'wc_clean' )
				? wc_clean( wp_unslash( $data['password'] ?? '' ) )
				: $tt( $data['password'] ?? '' );

			$password_confirm = $woo_active && function_exists( 'wc_clean' )
				? wc_clean( wp_unslash( $data['password_confirm'] ?? '' ) )
				: $tt( $data['password_confirm'] ?? '' );

			if ( empty( $password ) || strlen( (string) $password ) < 6 ) {
				$errors['password'] = 'Пароль должен быть не менее 6 символов.';
			} elseif ( $password !== $password_confirm ) {
				$errors['password_confirm'] = 'Пароли не совпадают.';
			}

			// first_name + last_name
			$first_name = $tt( $data['first_name'] ?? '' );
			$last_name  = $tt( $data['last_name'] ?? '' );

			if ( empty( $first_name ) ) {
				$errors['first_name'] = 'Имя обязательно.';
			}
			if ( empty( $last_name ) ) {
				$errors['last_name'] = 'Фамилия обязательна.';
			}

			// terms
			$terms_raw = $data['terms'] ?? '';
			$terms     = is_bool( $terms_raw ) ? $terms_raw : ( (string) $terms_raw === '1' );

			if ( ! $terms ) {
				$errors['terms'] = 'Вы должны согласиться с условиями и политикой.';
			}

			if ( ! empty( $errors ) ) {
				return new WP_Error( 'strt_reg_validation', 'Данные введены некорректно, пожалуйста, исправьте их.', [ 'status' => 400, 'errors' => $errors ] );
			}

			return compact( 'email', 'phone', 'password', 'first_name', 'last_name', 'terms' );
		}

		/**
		 * Нормализует телефон РФ к виду 7XXXXXXXXXX.
		 *
		 * @param  string  $raw_phone  Исходное значение.
		 *
		 * @return string Нормализованный номер или пустая строка.
		 */
		protected function normalize_phone_ru( string $raw_phone ): string {
			$digits = preg_replace( '/\D+/', '', $raw_phone );

			if ( $digits === '' ) {
				return '';
			}

			if ( strlen( $digits ) === 11 && $digits[0] === '8' ) {
				$digits = '7' . substr( $digits, 1 );
			}

			if ( strlen( $digits ) === 10 && $digits[0] === '9' ) {
				$digits = '7' . $digits;
			}

			return preg_match( '/^7\d{10}$/', $digits ) ? $digits : '';
		}

		/**
		 * Генерирует username из email.
		 *
		 * @param  string  $email  Email пользователя.
		 *
		 * @return string Сгенерированный логин.
		 */
		protected function generate_username_from_email( string $email ): string {
			$base  = sanitize_user( preg_replace( '/@.+$/', '', $email ), true );
			$base  = $base !== '' ? substr( $base, 0, 50 ) : 'user';
			$login = $base;
			$i     = 1;

			while ( username_exists( $login ) && $i < 100 ) {
				$login = $base . $i;
				$i ++;
			}

			if ( username_exists( $login ) ) {
				$login = substr( md5( $email . wp_rand() ), 0, 20 );
			}

			return $login;
		}

		/**
		 * Устанавливает cookie авторизации.
		 *
		 * @param  int  $user_id  ID пользователя.
		 *
		 * @return void
		 */
		protected function auth_with_cookie( int $user_id ): void {
			wp_set_current_user( $user_id );
			wp_set_auth_cookie( $user_id, true, is_ssl() );
		}

		/**
		 * Обрабатывает авторизацию и мультисайт.
		 *
		 * @param  int  $user_id  ID пользователя.
		 *
		 * @return true|WP_Error
		 */
		protected function handle_user_auth_and_multisite( int $user_id ): true|WP_Error {
			$this->auth_with_cookie( $user_id );

			if ( is_multisite() && ! is_user_member_of_blog( $user_id, get_current_blog_id() ) ) {
				$result = add_user_to_blog(
					get_current_blog_id(),
					$user_id,
					apply_filters( 'strt_new_user_role', 'customer', $user_id, get_current_blog_id() )
				);

				if ( is_wp_error( $result ) ) {
					return new WP_Error(
						'strt_multisite_add_user_failed',
						sprintf( 'Не удалось добавить пользователя к сайту: %s', $result->get_error_message() ),
						[ 'status' => 500 ]
					);
				}
			}

			return true;
		}

		/**
		 * Возвращает JSON Schema для регистрации.
		 *
		 * @return array
		 */
		public function get_item_schema(): array {
			$schema = [
				'$schema'    => 'http://json-schema.org/draft-04/schema#',
				'title'      => 'auth_register',
				'type'       => 'object',
				'properties' => [
					'email'      => [
						'description' => 'Email пользователя.',
						'type'        => 'string',
					],
					'phone'      => [
						'description' => 'Телефон пользователя.',
						'type'        => 'string',
					],
					'password'   => [
						'description' => 'Пароль пользователя.',
						'type'        => 'string',
					],
					'first_name' => [
						'description' => 'Имя.',
						'type'        => 'string',
					],
					'last_name'  => [
						'description' => 'Фамилия.',
						'type'        => 'string',
					],
					'terms'      => [
						'description' => 'Согласие с условиями и политикой.',
						'type'        => [ 'boolean', 'string' ],
					],
					'redirect'   => [
						'description' => 'Необязательный URL для перенаправления после регистрации.',
						'type'        => 'string',
					],
				],
			];

			return $this->add_additional_fields_schema( $schema );
		}

		/**
		 * Возвращает публичную схему для регистрации.
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