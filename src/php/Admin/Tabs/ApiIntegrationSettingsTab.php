<?php
/**
 * Вкладка интеграции с API (только для сайта).
 *
 * Отвечает за отображение и обработку настроек интеграции с внешними API-сервисами
 * (Google, Яндекс, Dadata) в административной панели WordPress.
 * Настройки сохраняются только в контексте сайта (wp_options), для мультисайта
 * (wp_siteoptions) вкладка не используется.
 *
 * @package    Strt\Plugin\Admin\Tabs
 * @subpackage Settings
 * @since      1.1.0
 */

namespace Strt\Plugin\Admin\Tabs;

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( __NAMESPACE__ . '\\ApiIntegrationSettingsTab' ) ) {

	/**
	 * Класс ApiIntegrationSettingsTab.
	 *
	 * Вкладка настроек интеграций с внешними API-сервисами. Используется только
	 * в контексте сайта. Позволяет задать ключи и секреты для сервисов Google,
	 * Яндекс и Dadata.
	 *
	 * @since 1.1.0
	 */
	class ApiIntegrationSettingsTab extends AbstractSiteSettingsTab {

		/**
		 * Идентификатор вкладки (slug).
		 *
		 * @var string
		 */
		protected string $id = 'api-integration';

		/**
		 * Заголовок вкладки.
		 *
		 * @var string
		 */
		protected string $label = 'Интеграция API';

		/**
		 * Группа опций для Settings API.
		 *
		 * @var string
		 */
		protected string $option_group = 'strt_settings_option_group_api_integration';

		/**
		 * Имя опции для хранения настроек на уровне сайта.
		 *
		 * @var string
		 */
		protected string $option_name = 'strt_settings_api_integration';

		/**
		 * Идентификатор секции настроек.
		 *
		 * @var string
		 */
		protected string $section_id = 'strt_settings_section_api_integration';

		/**
		 * Заголовок секции настроек.
		 *
		 * @var string
		 */
		protected string $section_title = 'Интеграция с внешними API-сервисами';

		/**
		 * Возвращает массив полей для вкладки.
		 *
		 * Метод требуется базовым абстрактным классом и проксирует вызов к {@see get_fields_site()}.
		 *
		 * @return array[] Массив полей настройки.
		 */
		public function get_fields(): array {
			return $this->get_fields_site();
		}

		/**
		 * Возвращает список полей для настроек сайта.
		 *
		 * @return array[] Массив конфигураций полей.
		 */
		public function get_fields_site(): array {
			return [
				[
					'id'    => 'api-google-key',
					'title' => 'Google API Key',
					'type'  => 'text',
					'desc'  => 'Ключ доступа к Google API.',
					'link'  => [
						'text'   => 'Получить ключ',
						'url'    => 'https://www.google.com/recaptcha/admin/',
						'target' => true,
					],
				],
				[
					'id'    => 'api-google-secret-key',
					'title' => 'Google API Secret Key',
					'type'  => 'text',
					'desc'  => 'Секретный ключ Google API.',
					'link'  => [
						'text'   => 'Получить ключ',
						'url'    => 'https://www.google.com/recaptcha/admin/',
						'target' => true,
					],
				],
				[
					'id'    => 'api-yandex-maps',
					'title' => 'API Яндекс Карты',
					'type'  => 'text',
					'desc'  => 'Ключ доступа для сервиса Яндекс.Карты.',
					'link'  => [
						'text'   => 'Получить ключ',
						'url'    => 'https://developer.tech.yandex.ru/services/',
						'target' => true,
					],
				],
				[
					'id'    => 'api-yandex-geo-suggest',
					'title' => 'API Yandex GeoSuggest',
					'type'  => 'text',
					'desc'  => 'Ключ для автодополнения адресов (Яндекс).',
					'link'  => [
						'text'   => 'Получить ключ',
						'url'    => 'https://developer.tech.yandex.ru/services/',
						'target' => true,
					],
				],
				[
					'id'    => 'api-dadata',
					'title' => 'Dadata API Key',
					'type'  => 'text',
					'desc'  => 'API-ключ Dadata.',
					'link'  => [
						'text'   => 'Получить ключ Dadata',
						'url'    => 'https://dadata.ru/profile/#info',
						'target' => true,
					],
				],
				[
					'id'    => 'api-dadata-secret',
					'title' => 'Dadata Secret Key',
					'type'  => 'text',
					'desc'  => 'Секретный ключ Dadata.',
					'link'  => [
						'text'   => 'Получить ключ Dadata',
						'url'    => 'https://dadata.ru/profile/#info',
						'target' => true,
					],
				],
			];
		}

		/**
		 * Валидация входных данных при сохранении настроек.
		 *
		 * @param  array  $input  Входные данные.
		 *
		 * @return array
		 */
		public function validate_input( array $input ): array {
			/*$old = get_option( $this->option_name );

			$fields = [
				'api-google-key'         => 'Google API Key',
				'api-google-secret-key'  => 'Google API Secret Key',
				'api-yandex-maps'        => 'Yandex Maps API Key',
				'api-yandex-geo-suggest' => 'Yandex GeoSuggest API Key',
				'api-dadata'             => 'Dadata API Key',
				'api-dadata-secret'      => 'Dadata Secret Key',
			];

			foreach ( $fields as $key => $title ) {
				if ( isset( $input[ $key ] ) && trim( $input[ $key ] ) === '' ) {
					add_settings_error(
						$this->option_name,
						"{$key}_empty",
						"Поле \"{$title}\" не может быть пустым."
					);
					$input[ $key ] = $old[ $key ] ?? '';
				}
			}*/

			return $input;
		}
	}
}
