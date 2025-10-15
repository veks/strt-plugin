<?php
/**
 * Контроллер webhook для Telegram-бота.
 *
 * @class   WebhookController
 * @package Strt\Plugin\RestApi\Telegram
 * @version 1.0.0
 */

namespace Strt\Plugin\RestApi\Telegram;

defined( 'ABSPATH' ) || exit;

use Strt\Plugin\RestApi\RestApiController;
use Strt\Plugin\Services\Telegram\TelegramApi;
use Strt\Plugin\Services\Telegram\TelegramCommandManager;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

if ( ! class_exists( 'Strt\Plugin\RestApi\Telegram\WebhookController' ) ) {

	/**
	 * Класс WebhookController.
	 *
	 * Контроллер webhook для Telegram-бота.
	 */
	class WebhookController extends RestApiController {

		/**
		 * Базовый путь для endpoint.
		 *
		 * @var string
		 */
		protected $rest_base = 'telegram/webhook';

		/**
		 * Экземпляр TelegramCommandManager.
		 *
		 * @var TelegramCommandManager
		 */
		protected TelegramCommandManager $telegram_command_manager;

		/**
		 * Конструктор.
		 *
		 * @param  TelegramCommandManager  $telegram_command_manager  Менеджер команд Telegram-бота.
		 */
		public function __construct( TelegramCommandManager $telegram_command_manager ) {
			parent::__construct();

			$this->telegram_command_manager = $telegram_command_manager;
		}

		/**
		 * Регистрирует REST API endpoint-ы.
		 */
		public function register_routes(): void {
			register_rest_route(
				$this->namespace,
				'/' . $this->rest_base,
				[
					[
						'methods'             => WP_REST_Server::CREATABLE,
						'callback'            => [ $this, 'webhook' ],
						'permission_callback' => '__return_true',
						'args'                => $this->get_endpoint_args_for_item_schema( WP_REST_Server::CREATABLE ),
					],
					'schema' => [ $this, 'get_item_schema' ],
				]

			);

			register_rest_route(
				$this->namespace,
				'/' . $this->rest_base . '/manage',
				[
					[
						'methods'             => WP_REST_Server::CREATABLE,
						'callback'            => [ $this, 'create_item' ],
						'permission_callback' => [ $this, 'permissions_check' ],
					],
					[
						'methods'             => WP_REST_Server::DELETABLE,
						'callback'            => [ $this, 'delete_item' ],
						'permission_callback' => [ $this, 'permissions_check' ],
					],
					[
						'methods'             => WP_REST_Server::READABLE,
						'callback'            => [ $this, 'get_item' ],
						'permission_callback' => [ $this, 'permissions_check' ],
					],

					'schema' => [ $this, 'get_manage_schema' ],
				]
			);
		}

		/**
		 * Возвращает токен Telegram-бота из настроек.
		 *
		 * @return string|false Telegram Bot API token или null, если не задан.
		 */
		protected function get_telegram_token(): false|string {
			return strt_get_option( 'strt_settings_telegram', 'token', '' );
		}

		/**
		 * Обрабатывает входящий webhook-запрос от Telegram.
		 *
		 * @param  WP_REST_Request  $request  Входящий запрос.
		 *
		 * @return WP_Error|array|WP_REST_Response Ответ для Telegram (ок или ошибка).
		 */
		public function webhook( WP_REST_Request $request ): WP_Error|array|WP_REST_Response {
			$data  = $request->get_json_params();
			$token = $this->get_telegram_token();

			$msg = $data['message'] ?? $data['edited_message'] ?? null;

			if ( empty( $msg ) || empty( $msg['text'] ) || empty( $msg['chat']['id'] ) ) {
				return new WP_Error( 'strt_telegram_no_message', 'Нет сообщения', [ 'status' => 400 ] );
			}

			$chat_id = $msg['chat']['id'];
			$text    = trim( $msg['text'] );
			$parsed  = $this->parse_command( $text );

			if ( $parsed['command'] ) {
				$result = $this->telegram_command_manager->handle( $parsed['command'], $msg, $parsed['args'] );

				if ( is_array( $result ) ) {
					$reply = [ ...$result ];
				} else {
					$reply = $result === null ? 'Неизвестная команда!' : $result;
				}
			} else {
				$reply = 'Пожалуйста, используйте команду начиная с /';
			}

			$api = new TelegramApi( $token, $chat_id );

			if ( is_array( $reply ) && isset( $reply['text'], $reply['params'] ) ) {
				$api->send_message( $reply['text'], $reply['params'] );
			} else {
				$api->send_message( $reply );
			}

			return rest_ensure_response( [ 'ok' => true ] );
		}

		/**
		 * Устанавливает webhook для Telegram-бота (POST /manage).
		 *
		 * @param  WP_REST_Request  $request  Входящий запрос.
		 *
		 * @return array|WP_Error Результат установки webhook.
		 */
		public function create_item( $request ): WP_Error|array {
			$token = $this->get_telegram_token();

			if ( empty( $token ) ) {
				return new WP_Error( 'strt_telegram_no_token', 'Токен не установлен', [ 'status' => 400 ] );
			}

			$webhook_url = home_url( '/wp-json/strt/v1/telegram/webhook' );
			$api         = new TelegramApi( $token, '' );
			$response    = $api->set_webhook( $webhook_url );

			if ( ! empty( $response['ok'] ) ) {
				return [ 'ok' => true, 'description' => 'Webhook установлен' ];
			}

			return new WP_Error( 'strt_telegram_error', 'Ошибка Telegram: ' . ( $response['description'] ?? '' ), [ 'status' => 500 ] );
		}

		/**
		 * Удаляет webhook для Telegram-бота (DELETE /manage).
		 *
		 * @param  WP_REST_Request  $request  Входящий запрос.
		 *
		 * @return array|WP_Error Результат удаления webhook.
		 */
		public function delete_item( $request ): WP_Error|array {
			$token = $this->get_telegram_token();

			if ( empty( $token ) ) {
				return new WP_Error( 'strt_telegram_no_token', 'Токен не установлен', [ 'status' => 400 ] );
			}

			$api      = new TelegramApi( $token, '' );
			$response = $api->delete_webhook();

			if ( ! empty( $response['ok'] ) ) {
				return [ 'ok' => true, 'description' => 'Webhook удалён' ];
			}

			return new WP_Error( 'strt_telegram_error', 'Ошибка Telegram: ' . ( $response['description'] ?? '' ), [ 'status' => 500 ] );
		}

		/**
		 * Получает информацию о текущем webhook Telegram (GET /manage).
		 *
		 * @param  WP_REST_Request  $request  Входящий запрос.
		 *
		 * @return array|WP_Error Информация о webhook или ошибка.
		 */
		public function get_item( $request ): WP_Error|array {
			$token = $this->get_telegram_token();

			if ( empty( $token ) ) {
				return new WP_Error( 'strt_telegram_no_token', 'Токен не установлен', [ 'status' => 400 ] );
			}

			$api      = new TelegramApi( $token, '' );
			$response = $api->info_webhook();

			if ( ! empty( $response['ok'] ) ) {
				return [ 'ok' => true, 'result' => $response['result'] ];
			}

			return new WP_Error( 'strt_telegram_error', 'Ошибка Telegram: ' . ( $response['description'] ?? '' ), [ 'status' => 500 ] );
		}

		/**
		 * Проверяет, является ли текущий пользователь администратором.
		 *
		 * @param  WP_REST_Request  $request  Входящий запрос.
		 *
		 * @return WP_Error|bool True, если пользователь — администратор.
		 */
		public function permissions_check( WP_REST_Request $request ): WP_Error|bool {
			$nonce = $request->get_header( 'X-WP-Nonce' ) ?: ( $_SERVER['HTTP_X_WP_NONCE'] ?? '' );

			if ( ! wp_verify_nonce( $nonce, 'wp_rest' ) ) {
				return new WP_Error(
					'strt_rest_invalid_nonce',
					'Ошибка: Неверный или отсутствующий nonce.',
					[ 'status' => 403 ]
				);
			}

			return current_user_can( 'manage_options' );
		}

		/**
		 * Возвращает JSON Schema для Telegram webhook endpoint.
		 *
		 * @return array
		 */
		public function get_item_schema(): array {
			return [
				'$schema'    => 'http://json-schema.org/draft-04/schema#',
				'title'      => 'telegram_webhook',
				'type'       => 'object',
				'properties' => [
					'update_id' => [
						'description' => 'ID обновления Telegram',
						'type'        => 'integer',
						'required'    => false,
					],
					'message'   => [
						'description' => 'Сообщение Telegram',
						'type'        => 'object',
						'properties'  => [
							'message_id' => [
								'type'        => 'integer',
								'description' => 'ID сообщения',
								'required'    => false,
							],
							'from'       => [
								'type'        => 'object',
								'description' => 'Информация об отправителе',
								'properties'  => [
									'id'         => [
										'type'        => 'integer',
										'description' => 'ID пользователя',
									],
									'first_name' => [
										'type'        => 'string',
										'description' => 'Имя',
									],
									'last_name'  => [
										'type'        => 'string',
										'description' => 'Фамилия',
									],
									'username'   => [
										'type'        => 'string',
										'description' => 'Username',
									],
								],
							],
							'chat'       => [
								'type'        => 'object',
								'description' => 'Информация о чате',
								'properties'  => [
									'id'   => [
										'type'        => 'integer',
										'description' => 'ID чата',
									],
									'type' => [
										'type'        => 'string',
										'description' => 'Тип чата',
									],
								],
							],
							'date'       => [
								'type'        => 'integer',
								'description' => 'Дата отправки',
							],
							'text'       => [
								'type'        => 'string',
								'description' => 'Текст сообщения',
							],
						],
					],
				],
			];
		}

		/**
		 * Возвращает JSON Schema для Telegram webhook endpoint.
		 *
		 * @return array
		 */
		public function get_manage_schema(): array {
			return [
				'$schema'    => 'http://json-schema.org/draft-04/schema#',
				'title'      => 'telegram_webhook_manage',
				'type'       => 'object',
				'properties' => [
					'ok'          => [
						'type'        => 'boolean',
						'description' => 'Успех операции',
					],
					'description' => [
						'type'        => 'string',
						'description' => 'Описание результата',
					],
					'result'      => [
						'type'        => 'object',
						'description' => 'Информация о webhook (для GET)',
					],
				],
			];
		}

		/**
		 * Параметры коллекции (не используются).
		 *
		 * @return array
		 */
		public function get_collection_params(): array {
			return [];
		}

		/**
		 * Парсит команду Telegram из текста.
		 *
		 * @param  string  $text  Входящий текст.
		 *
		 * @return array [ 'command' => string, 'args' => string ]
		 */
		protected function parse_command( string $text ): array {
			$text = trim( $text );

			if ( preg_match( '/^(\/[a-zA-Z0-9_]+)(?:@[\w\d_]+)?(?:\s+(.*))?$/u', $text, $matches ) ) {
				$command  = $matches[1];
				$args_str = isset( $matches[2] ) ? trim( $matches[2] ) : '';

				preg_match_all(
					'/"([^"\\\\]*(?:\\\\.[^"\\\\]*)*)"|\'([^\'\\\\]*(?:\\\\.[^\'\\\\]*)*)\'|(\w+=[^\s]+)|([^\s]+)/u',
					$args_str,
					$arg_matches, PREG_SET_ORDER
				);

				$args = [];

				foreach ( $arg_matches as $match ) {
					if ( ! empty( $match[1] ) ) {
						$args[] = stripcslashes( $match[1] );
					} elseif ( ! empty( $match[2] ) ) {
						$args[] = stripcslashes( $match[2] );
					} elseif ( ! empty( $match[3] ) ) {
						$args[] = $match[3];
					} elseif ( ! empty( $match[4] ) ) {
						$args[] = $match[4];
					}
				}

				return [
					'command' => $command,
					'args'    => $args,
				];
			}

			return [
				'command' => '',
				'args'    => [],
			];
		}
	}
}
