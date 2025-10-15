<?php
/**
 * ContactFormController — REST API контроллер для отправки форм.
 *
 * Обрабатывает запросы на отправку форм, выполняет валидацию данных и отправляет письма.
 *
 * @package    Strt\Plugin\RestApi
 * @subpackage Controllers
 * @since      1.0.0
 */

namespace Strt\Plugin\RestApi;

defined( 'ABSPATH' ) || exit;

use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

if ( ! class_exists( 'Strt\Plugin\RestApi\ContactFormController' ) ) {

	/**
	 * Класс ContactFormController.
	 *
	 * Реализует REST API метод для отправки форм.
	 */
	class ContactFormController extends RestApiController {

		/**
		 * Базовый путь маршрута.
		 *
		 * @var string
		 */
		protected $rest_base = 'contact-form';

		/**
		 * Инициализация контроллера.
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
			register_rest_route( $this->namespace, '/' . $this->rest_base . '/submit',
				[
					[
						'methods'             => WP_REST_Server::CREATABLE,
						'callback'            => [ $this, 'submit' ],
						'permission_callback' => [ $this, 'permissions_check' ],
						'args'                => $this->get_endpoint_args_for_item_schema( WP_REST_Server::CREATABLE ),
					],
				]
			);
		}

		/**
		 * Проверяет права доступа через X-WP-Nonce.
		 *
		 * @param  WP_REST_Request  $request  Объект запроса.
		 *
		 * @return WP_Error|bool True при успешной проверке, либо WP_Error при ошибке.
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
		 * Обрабатывает отправку формы и отправляет письмо.
		 *
		 * @param  WP_REST_Request  $request  Объект запроса.
		 *
		 * @return WP_REST_Response|WP_Error Ответ API или ошибка.
		 */
		public function submit( WP_REST_Request $request ): WP_REST_Response|WP_Error {
			$data = $request->get_json_params() ?: $request->get_body_params();

			$form_fields      = is_array( $data['fields'] ?? null ) ? $data['fields'] : [];
			$mail_to          = sanitize_email( $data['mailTo'] ?? get_option( 'admin_email' ) );
			$mail_subject     = sanitize_text_field( $data['mailSubject'] ?? 'Новая заявка' );
			$terms            = (string) ( $data['terms'] ?? '' );
			$terms_required   = array_key_exists( 'is-terms', $data ) ? (bool) $data['is-terms'] : true;
			$mail_format      = strtolower( (string) ( $data['mailFormat'] ?? 'html' ) );
			$mail_format      = in_array( $mail_format, [ 'html', 'text' ], true ) ? $mail_format : 'html';
			$enable_recaptcha = (bool) ( $data['enableRecaptcha'] ?? false );
			$recaptcha_token  = (string) ( $data['recaptchaToken'] ?? '' );
			$recaptcha_action = (string) ( $data['recaptchaAction'] ?? 'contact_form' );
			$remote_ip        = isset( $_SERVER['REMOTE_ADDR'] ) ? (string) $_SERVER['REMOTE_ADDR'] : '';

			if ( empty( $mail_to ) || ! is_email( $mail_to ) ) {
				//return new WP_Error( 'strt_cf_invalid_to', 'Некорректный адрес получателя.', [ 'status' => 400 ] );
			}

			if ( $enable_recaptcha ) {
				if ( $recaptcha_token === '' ) {
					return new WP_Error(
						'strt_recaptcha_missing_token',
						'Не передан токен reCAPTCHA.',
						[ 'status' => 400 ]
					);
				}

				$verify = $this->verify_recaptcha( $recaptcha_token, $recaptcha_action, $remote_ip );

				if ( is_wp_error( $verify ) ) {
					return $verify;
				}
			}

			$form_data = [];

			foreach ( $form_fields as $field ) {
				$field_name = sanitize_key( $field['name'] ?? '' );

				if ( $field_name === '' ) {
					continue;
				}


				$value                    = $field['value'] ?? '';
				$form_data[ $field_name ] = is_array( $value ) ? array_map( 'wp_unslash', $value ) : wp_unslash( $value );
			}

			$validation_errors = [];

			foreach ( $form_fields as $field ) {
				$field_name = sanitize_key( $field['name'] ?? '' );

				if ( $field_name === '' ) {
					continue;
				}

				$field_label = sanitize_text_field( $field['label'] ?? $field_name );
				$is_required = (bool) ( $field['attributes']['required'] ?? false );
				$value       = $form_data[ $field_name ] ?? '';
				$flat        = is_array( $value ) ? implode( ',', array_map( 'strval', $value ) ) : (string) $value;

				if ( $is_required && trim( $flat ) === '' ) {
					$validation_errors[ $field_name ] = sprintf( 'Поле «%s» обязательно для заполнения.', $field_label );
					continue;
				}

				$type = (string) ( $field['type'] ?? '' );

				if ( $type === 'email' && $flat !== '' ) {
					if ( ! is_email( $flat ) ) {
						$validation_errors[ $field_name ] = sprintf( 'Поле «%s» должно содержать корректный email.', $field_label );
						continue;
					}
				}

				if ( $type === 'tel' && $flat !== '' ) {
					if ( empty( $flat ) || ! preg_match( '/^7\d{10}$/', $flat )  ) {
						$validation_errors[ $field_name ] = sprintf( 'Поле «%s» должно быть в формате: +7 (900) 000-00-00.', $field_label );
					}
				}
			}

			if ( $terms_required && $terms !== '1' ) {
				$validation_errors['terms'] = 'Требуется согласие с политикой конфиденциальности.';
			}

			if ( ! empty( $validation_errors ) ) {
				return new WP_Error(
					'strt_cf_validation',
					'Ошибка валидации.',
					[ 'status' => 400, 'errors' => $validation_errors ]
				);
			}

			$email_items = [];

			foreach ( $form_fields as $field ) {
				$field_name = sanitize_key( $field['name'] ?? '' );

				if ( $field_name === '' ) {
					continue;
				}

				$email_items[] = [
					'name'    => $field_name,
					'label'   => sanitize_text_field( $field['label'] ?? $field_name ),
					'type'    => (string) ( $field['type'] ?? '' ),
					'options' => is_array( $field['options'] ?? null ) ? $field['options'] : [],
					'value'   => $form_data[ $field_name ] ?? '',
				];
			}

			$template_args = [
				'subject'   => $mail_subject,
				'site_name' => wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES ),
				'site_url'  => home_url( '/' ),
				'date'      => wp_date( 'Y-m-d H:i:s' ),
				'ip'        => sanitize_text_field( $_SERVER['REMOTE_ADDR'] ?? '' ),
				'referer'   => ! empty( $_SERVER['HTTP_REFERER'] ) ? esc_url_raw( $_SERVER['HTTP_REFERER'] ) : '',
				'items'     => $email_items,
			];

			if ( $mail_format === 'html' ) {
				$body    = strt_get_template_html( 'blocks/contact-form/emails/email.php', $template_args );
				$headers = [ 'Content-Type: text/html; charset=UTF-8' ];
			} else {
				$body    = strt_get_template_html( 'blocks/contact-form/emails/email.txt.php', $template_args );
				$headers = [ 'Content-Type: text/plain; charset=UTF-8' ];
			}

			foreach ( $form_fields as $field ) {
				if ( ( $field['type'] ?? '' ) === 'email' ) {
					$field_name = sanitize_key( $field['name'] ?? '' );
					$reply      = isset( $form_data[ $field_name ] ) ? sanitize_email( $form_data[ $field_name ] ) : '';

					if ( is_email( $reply ) ) {
						$headers[] = 'Reply-To: ' . $reply;
						break;
					}
				}
			}

			$is_sent = wp_mail( $mail_to, $mail_subject, $body, $headers );

			if ( ! $is_sent ) {
				return new WP_Error( 'strt_cf_mail_failed', 'Не удалось отправить письмо.', [ 'status' => 500 ] );
			}

			return rest_ensure_response( [ 'success' => true, 'message' => 'Заявка отправлена.' ] );
		}

		/**
		 * Проверка капчи.
		 *
		 * @param  string  $token  Токен.
		 * @param  string|null  $action  Действие.
		 * @param  string|null  $remote_ip  IP адрес.
		 *
		 * @return true|WP_Error
		 */
		protected function verify_recaptcha( string $token, ?string $action = null, ?string $remote_ip = null ): true|WP_Error {
			$option_name = 'strt_settings_api_integration';
			$secret      = strt_get_option( $option_name, 'api-google-secret-key', '' );

			if ( $secret === '' ) {
				return new WP_Error(
					'strt_recaptcha_no_secret',
					'Отсутствует секретный ключ reCAPTCHA.',
					[ 'status' => 500 ]
				);
			}

			$response = wp_remote_post(
				'https://www.google.com/recaptcha/api/siteverify',
				[
					'timeout' => 8,
					'body'    => array_filter( [
						'secret'   => $secret,
						'response' => $token,
						'remoteip' => $remote_ip ?: null,
					] ),
				]
			);

			if ( is_wp_error( $response ) ) {
				return new WP_Error(
					'strt_recaptcha_http_error',
					'Ошибка соединения с reCAPTCHA.',
					[ 'status' => 500, 'error' => $response->get_error_message() ]
				);
			}

			$code = (int) wp_remote_retrieve_response_code( $response );
			$body = wp_remote_retrieve_body( $response );
			$json = json_decode( (string) $body, true );

			if ( $code !== 200 || ! is_array( $json ) ) {
				return new WP_Error(
					'strt_recaptcha_bad_response',
					'Некорректный ответ reCAPTCHA.',
					[ 'status' => 500, 'body' => $body, 'http' => $code ]
				);
			}

			if ( empty( $json['success'] ) ) {
				$errs = isset( $json['error-codes'] ) ? (array) $json['error-codes'] : [];
				$msg  = 'Проверка reCAPTCHA не пройдена.';

				if ( $errs ) {
					$msg .= ' (' . implode( ', ', $errs ) . ')';
				}

				return new WP_Error(
					'strt_recaptcha_failed',
					$msg,
					[ 'status' => 400, 'error_codes' => $errs ]
				);
			}

			$score     = isset( $json['score'] ) ? (float) $json['score'] : null;
			$rcAction  = isset( $json['action'] ) ? (string) $json['action'] : null;
			$threshold = 0.5;

			if ( $rcAction && $action && $rcAction !== $action ) {
				return new WP_Error(
					'strt_recaptcha_action_mismatch',
					sprintf( 'Действие reCAPTCHA не совпадает (ожидали: %s, получено: %s).', $action, $rcAction ),
					[ 'status' => 400 ]
				);
			}

			if ( $score !== null && $score < $threshold ) {
				return new WP_Error(
					'strt_recaptcha_low_score',
					sprintf( 'Низкий score reCAPTCHA: %.2f (минимум: %.2f).', $score, $threshold ),
					[ 'status' => 400, 'score' => $score, 'minScore' => $threshold ]
				);
			}

			return true;
		}

		/**
		 * Возвращает JSON Schema полезной нагрузки.
		 *
		 * @return array Массив JSON Schema.
		 */
		public function get_item_schema(): array {
			return [
				'$schema'              => 'http://json-schema.org/draft-04/schema#',
				'title'                => 'contact_form_submit',
				'type'                 => 'object',
				'properties'           => [
					'fields'          => [
						'description' => 'Конфигурация и значения полей (value внутри каждого поля)',
						'type'        => 'array',
						'items'       => [ 'type' => 'object' ],
						'context'     => [ 'edit' ],
					],
					'mailTo'          => [
						'description' => 'Адрес получателя',
						'type'        => 'string',
						'context'     => [ 'edit' ],
					],
					'mailSubject'     => [
						'description' => 'Тема письма',
						'type'        => 'string',
						'context'     => [ 'edit' ],
					],
					'mailFormat'      => [
						'description' => 'Формат письма: html или text',
						'type'        => 'string',
						'enum'        => [ 'html', 'text' ],
						'context'     => [ 'edit' ],
					],
					'terms'           => [
						'description' => 'Согласие с политикой ("1" — да)',
						'type'        => 'string',
						'context'     => [ 'edit' ],
					],
					'is-terms'        => [
						'description' => 'Требовать согласие (по умолчанию true)',
						'type'        => 'boolean',
						'context'     => [ 'edit' ],
					],
					'enableRecaptcha' => [
						'description' => 'Включить проверку Google reCAPTCHA',
						'type'        => 'boolean',
						'context'     => [ 'edit' ],
					],
					'recaptchaToken'  => [
						'description' => 'reCAPTCHA токен, выданный на фронтенде',
						'type'        => 'string',
						'context'     => [ 'edit' ],
					],
					'recaptchaAction' => [
						'description' => 'Ожидаемое действие reCAPTCHA v3 (например contact_form)',
						'type'        => 'string',
						'context'     => [ 'edit' ],
					],
				],
				'required'             => [ 'fields' ],
				'additionalProperties' => true,
			];
		}


		/**
		 * Параметры коллекции (не используются).
		 *
		 * @return array Пустой массив.
		 */
		public function get_collection_params(): array {
			return [];
		}
	}
}
