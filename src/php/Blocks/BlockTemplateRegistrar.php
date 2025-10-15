<?php
/**
 * Класс BlockTemplateRegistrar для регистрации блок-шаблонов.
 *
 * Регистрирует кастомные шаблоны для блочной темы WordPress через register_block_template.
 *
 * @class   BlockTemplateRegistrar
 * @package Strt\Plugin\Blocks
 * @version 1.0.0
 */

namespace Strt\Plugin\Blocks;

use Strt\Plugin\Utils\Helper;

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'Strt\Plugin\Blocks\BlockTemplateRegistrar' ) ) {

	/**
	 * Класс BlockTemplateRegistrar.
	 *
	 * Отвечает за регистрацию шаблонов для блочных тем.
	 */
	class BlockTemplateRegistrar {

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
		 * Инициализация регистрации шаблонов.
		 *
		 * @return void
		 */
		public function init(): void {
			if ( $this->supports_block_templates() ) {
				add_action( 'init', [ $this, 'register_block_template' ] );
			}
		}

		/**
		 * Регистрирует кастомный блок-шаблон.
		 *
		 * @return void
		 */
		public function register_block_template(): void {
			register_block_template(
				$this->helper::NAMESPACE . '//page',
				[
					'title'      => 'Страница [Плагин]',
					'content'    => strt_get_template_html( 'page.html' ),
					'post_types' => [ 'page' ],
				]
			);

			/*if ( function_exists( 'is_plugin_active' ) && is_plugin_active( 'woocommerce/woocommerce.php' ) ) {
				register_block_template(
					$this->helper::NAMESPACE . '//single-product',
					[
						'title'      => 'Страница товара [Плагин]',
						'content'    => strt_get_template_html( 'single-product.html' ),
						'post_types' => [ 'product' ],
					]
				);
			}*/

			/*if ( function_exists( 'is_plugin_active' ) && is_plugin_active( 'woocommerce/woocommerce.php' ) ) {
				register_block_template(
					$this->helper::NAMESPACE . '//page-checkout',
					[
						'title'      => 'Каталог товаров [Плагин]',
						'content'    => strt_get_template_html( 'page-checkout.html' ),
						'post_types' => [ 'page-checkout' ],
					]
				);
			}*/
		}

		/**
		 * Проверяет, поддерживает ли тема блочные шаблоны.
		 *
		 * @param  string  $template_type  Тип шаблона: 'wp_template' или 'wp_template_part'.
		 *
		 * @return bool
		 */
		protected function supports_block_templates( string $template_type = 'wp_template' ): bool {
			if ( 'wp_template_part' === $template_type && ( wp_is_block_theme() || current_theme_supports( 'block-template-parts' ) ) ) {
				return true;
			} elseif ( 'wp_template' === $template_type && wp_is_block_theme() ) {
				return true;
			}

			return false;
		}
	}
}
