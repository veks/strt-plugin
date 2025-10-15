<?php
/**
 * Вкладка настроек Telegram.
 *
 * @class   TelegramSettingsTab
 * @package Strt\Plugin\Admin\Tabs
 * @version 1.0.0
 */

namespace Strt\Plugin\Admin\Tabs;

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( __NAMESPACE__ . '\\TelegramSettingsTab', false ) ) {

	/**
	 * Класс TelegramSettingsTab.
	 *
	 * Вкладка настроек интеграции с Telegram (бот, вебхуки, администраторы).
	 */
	class TelegramSettingsTab extends AbstractSiteSettingsTab {

		/**
		 * @var string
		 *
		 * Идентификатор вкладки.
		 */
		protected string $id = 'telegram';

		/**
		 * @var string
		 *
		 * Заголовок вкладки.
		 */
		protected string $label = 'Telegram';

		/**
		 * @var string
		 *
		 * Заголовок секции.
		 */
		protected string $section_title = 'Настройки Telegram-бота';

		/**
		 * @var string
		 *
		 * Описание секции.
		 */
		protected string $description = 'Интеграция с Telegram для уведомлений и служебных сообщений.';

		/**
		 * @var string
		 *
		 * Группа опций (для per-site Settings API).
		 */
		protected string $option_group = 'strt_settings_option_group_telegram';

		/**
		 * @var string
		 *
		 * Имя per-site опции.
		 */
		protected string $option_name = 'strt_settings_telegram';

		/**
		 * @var string
		 *
		 * Имя секции.
		 */
		protected string $section_id = 'strt_settings_section_telegram';

		/**
		 * Поля вкладки.
		 *
		 * @return array
		 */
		public function get_fields(): array {
			return [
				[
					'id'       => 'webhook_buttons',
					'title'    => 'Управление Webhook',
					'type'     => false,
					'callback' => [ $this, 'callback_webhook_buttons' ],
				],
				[
					'id'      => 'token',
					'title'   => 'Bot Token',
					'desc'    => 'Токен Telegram-бота, полученный у @BotFather.',
					'type'    => 'text',
					'default' => '',
				],
				[
					'id'      => 'enabled',
					'title'   => 'Включить Telegram-бот',
					'desc'    => 'Активирует отправку уведомлений.',
					'type'    => 'checkbox',
					'default' => 0,
				],
				[
					'id'      => 'enabled_error',
					'title'   => 'Уведомления об ошибках',
					'desc'    => 'Отправлять сообщения об ошибках.',
					'type'    => 'checkbox',
					'default' => 0,
				],
				[
					'id'      => 'enabled_order',
					'title'   => 'Уведомления о заказах',
					'desc'    => 'Отправлять сообщения о новых заказах.',
					'type'    => 'checkbox',
					'default' => 0,
				],
				[
					'id'      => 'admin_ids',
					'title'   => 'Администраторы (chat_id)',
					'desc'    => 'Список chat_id через запятую, например: <code>10000,-100001,10002</code>.<br>Узнать chat_id: добавьте бота в чат и используйте /me или @userinfobot.',
					'type'    => 'text',
					'default' => '',
				],
				[
					'id'       => 'subscribers',
					'title'    => 'Список Telegram-подписчиков',
					'type'     => false,
					'callback' => [ $this, 'callback_subscribers' ],
				],
			];
		}

		/**
		 * Возвращает значения опции по умолчанию.
		 *
		 * @return array
		 */
		protected function get_site_defaults(): array {
			return [
				'enabled'       => 0,
				'enabled_error' => 0,
				'enabled_order' => 0,
				'token'         => '',
				'admin_ids'     => '',
			];
		}

		/**
		 * Кнопки управления веб-хуком Telegram.
		 *
		 * Callback для кастомного поля. ДОЛЖЕН принимать аргумент $args,
		 * потому что add_settings_field() всегда передаёт массив аргументов.
		 *
		 * @param  array  $args  Аргументы от add_settings_field().
		 *
		 * @return void
		 */
		public function callback_webhook_buttons( array $args = [] ): void {
			$nonce      = wp_create_nonce( 'wp_rest' );
			$manage_url = rest_url( 'strt/v1/telegram/webhook/manage' );
			?>
			<div id="telegram-webhook-controls">
				<button class="button" id="strt-set-webhook">Установить Webhook</button>
				<button class="button" id="strt-delete-webhook">Удалить Webhook</button>
				<button class="button" id="strt-info-webhook">Статус Webhook</button>
				<div id="telegram-webhook-message" style="margin-top:10px;"></div>
			</div>
			<script>
              jQuery(function ($) {
                const message = $('#telegram-webhook-message')

                function doRequest (method, successMsg) {
                  message.text('...')
                  $.ajax({
                    url       : '<?php echo esc_url( $manage_url ); ?>',
                    method    : method,
                    beforeSend: function (xhr) {
                      xhr.setRequestHeader('X-WP-Nonce', '<?php echo esc_js( $nonce ); ?>')
                    },
                    success   : function (resp) {
                      if (resp && resp.ok && successMsg) {
                        message.html('<span style="color:green;">' + successMsg + '</span>')
                      } else if (resp && resp.result) {
                        message.html('<pre>' + JSON.stringify(resp.result, null, 2) + '</pre>')
                      } else if (resp && resp.description) {
                        message.html('<span style="color:green;">' + resp.description + '</span>')
                      } else {
                        message.html('<span style="color:green;">OK</span>')
                      }
                    },
                    error     : function (xhr) {
                      let err = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Ошибка'
                      message.html('<span style="color:red;">' + err + '</span>')
                    },
                  })
                }

                $('#strt-set-webhook').on('click', function (e) {
                  e.preventDefault()
                  doRequest('POST', 'Webhook установлен!')
                })
                $('#strt-delete-webhook').on('click', function (e) {
                  e.preventDefault()
                  doRequest('DELETE', 'Webhook удалён!')
                })
                $('#strt-info-webhook').on('click', function (e) {
                  e.preventDefault()
                  doRequest('GET')
                })
              })
			</script>
			<?php
		}

		/**
		 * Выводит список подписчиков (read-only).
		 *
		 * @param  array  $args  Аргументы от add_settings_field().
		 *
		 * @return void
		 */
		public function callback_subscribers( array $args = [] ): void {
			$is_net     = function_exists( 'is_network_admin' ) && is_network_admin();
			$option_get = $is_net ? 'get_site_option' : 'get_option';

			$subscribers = $option_get( 'strt_tg_subscribers', [] );
			$output      = '';

			if ( is_array( $subscribers ) && ! empty( $subscribers ) ) {
				foreach ( $subscribers as $id => $subscriber ) {
					$output .= "id {$id} - {$subscriber}\n";
				}
			}

			printf(
				'<textarea rows="8" cols="40" class="large-text" readonly>%s</textarea>',
				esc_textarea( trim( $output ) )
			);
		}

		/**
		 * Валидация входных данных при сохранении настроек.
		 *
		 * @param  array  $input  Входные данные.
		 *
		 * @return array Санитизированные данные.
		 */
		public function validate_input( array $input ): array {
			$old = $this->get_settings();

			$token         = isset( $input['token'] ) ? sanitize_text_field( (string) $input['token'] ) : ( $old['token'] ?? '' );
			$enabled       = empty( $input['enabled'] ) ? 0 : 1;
			$enabled_error = empty( $input['enabled_error'] ) ? 0 : 1;
			$enabled_order = empty( $input['enabled_order'] ) ? 0 : 1;
			$admins_raw    = isset( $input['admin_ids'] ) ? sanitize_text_field( (string) $input['admin_ids'] ) : ( $old['admin_ids'] ?? '' );
			$admins_raw    = preg_replace( '/\s+/', '', (string) $admins_raw );
			$admins_raw    = preg_replace( '/,+/', ',', (string) $admins_raw );

			if ( $admins_raw !== '' && ! preg_match( '/^-?\d+(,-?\d+)*$/', (string) $admins_raw ) ) {
				add_settings_error(
					$this->option_name,
					'admin_ids_format_error',
					'Ошибка в поле «Администраторы (chat_id)»: допускаются только целые числа, разделённые запятыми (например: 12345,-1001234,67890).'
				);
				$admins_raw = $old['admin_ids'] ?? '';
			}

			return [
				'enabled'       => (int) $enabled,
				'enabled_error' => (int) $enabled_error,
				'enabled_order' => (int) $enabled_order,
				'token'         => $token,
				'admin_ids'     => (string) $admins_raw,
			];
		}

		/**
		 * Генерирует случайную хеш-строку.
		 *
		 * @return string
		 */
		protected function rand_hash(): string {
			if ( ! function_exists( 'openssl_random_pseudo_bytes' ) ) {
				return sha1( wp_rand() );
			}

			return bin2hex( openssl_random_pseudo_bytes( 20 ) );
		}
	}
}
