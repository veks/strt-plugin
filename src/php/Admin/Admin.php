<?php
/**
 * Admin class.
 *
 * Выполняет настройку админ-меню и рендер страниц настроек
 * для одиночного сайта и сетевой админки (Multisite).
 *
 * @class   Admin
 * @package Strt\Plugin\Admin
 * @version 1.0.0
 */

namespace Strt\Plugin\Admin;

defined( 'ABSPATH' ) || exit;

use Strt\Plugin\Utils\Helper;

if ( ! class_exists( __NAMESPACE__ . '\\Admin', false ) ) {

	/**
	 * Выполняет настройку админ-меню и рендер страниц настроек.
	 */
	class Admin {

		/**
		 * Вспомогательный класс.
		 *
		 * @var Helper
		 */
		protected Helper $helper;

		/**
		 * Конструктор.
		 *
		 * @param  Helper  $helper  Экземпляр Helper.
		 */
		public function __construct( Helper $helper ) {
			$this->helper = $helper;
		}

		/**
		 * Регистрирует хуки админ-меню.
		 *
		 * @return void
		 */
		public function init(): void {
			add_action( 'admin_menu', [ $this, 'add_site_menu' ] );
			add_action( 'network_admin_menu', [ $this, 'add_network_menu' ] );
		}

		/**
		 * Добавляет пункт меню на уровне отдельного сайта.
		 *
		 * @return void
		 */
		public function add_site_menu(): void {
			add_menu_page(
				sprintf( 'Плагин %s', $this->helper::get_name() ),
				$this->helper::get_name(),
				$this->helper::get_capability(),
				$this->helper::get_slug(),
				[ $this, 'render_site_settings' ]
			);
		}

		/**
		 * Добавляет пункт меню в сетевой админке (Multisite).
		 *
		 * @return void
		 */
		public function add_network_menu(): void {
			add_menu_page(
				sprintf( 'Плагин %s', $this->helper::get_name() ),
				$this->helper::get_name(),
				$this->helper::get_capability(),
				$this->helper::get_slug(),
				[ $this, 'render_network_settings' ],
				'',
				80
			);
		}

		/**
		 * Рендер страницы настроек для отдельного сайта.
		 *
		 * @return void
		 */
		public function render_site_settings(): void {
			require $this->helper::get_dir_path( 'templates/admin/views/html-admin-page-dashboard.php' );
		}

		/**
		 * Рендер страницы сетевых настроек (Multisite).
		 *
		 * @return void
		 */
		public function render_network_settings(): void {
			require $this->helper::get_dir_path( 'templates/admin/views/html-admin-page-dashboard.php' );
		}
	}
}
