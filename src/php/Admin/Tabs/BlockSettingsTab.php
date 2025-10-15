<?php
/**
 * Вкладка настроек блоков.
 *
 * @class   BlockSettingsTab
 * @package Strt\Plugin\Admin\Tabs
 * @version 1.0.0
 */

namespace Strt\Plugin\Admin\Tabs;

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( __NAMESPACE__ . '\\BlockSettingsTab', false ) ) {

	/**
	 * Класс BlockSettingsTab.
	 */
	class BlockSettingsTab extends AbstractSiteSettingsTab {

		/**
		 * @var string
		 *
		 * Идентификатор вкладки.
		 */
		protected string $id = 'block';

		/**
		 * @var string
		 *
		 * Заголовок вкладки.
		 */
		protected string $label = 'Блоки';

		/**
		 * @var string
		 *
		 * Заголовок секции.
		 */
		protected string $section_title = 'Настройки блоков';

		/**
		 * @var string
		 *
		 * Группа опций (per-site Settings API).
		 */
		protected string $option_group = 'strt_settings_option_group_block';

		/**
		 * @var string
		 *
		 * Идентификатор секции.
		 */
		protected string $section_id = 'strt_settings_section_block';

		/**
		 * @var string
		 *
		 * Имя per-site опции (wp_options).
		 */
		protected string $option_name = 'strt_settings_block';

		/**
		 * @var string
		 *
		 * Имя сетевой опции (wp_siteoptions). Пусто — если не требуется.
		 */
		protected string $network_option_name = 'strt_network_settings_block';

		/**
		 * Поля вкладки.
		 *
		 * @return array
		 */
		public function get_fields(): array {
			return [];
		}
	}
}
