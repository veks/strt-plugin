<?php
/**
 * Класс Hr для редактора Gutenberg, реализующий блок Bootstrap 5 "hr".
 *
 * Класс обеспечивает регистрацию, инициализацию и рендеринг кастомного блока "hr" для редактора Gutenberg,
 * что позволяет добавлять горизонтальные разделители с поддержкой Bootstrap 5.
 *
 * @class   Hr
 * @package Strt\Plugin\Blocks\Bootstrap5\Layout
 * @version 1.0.0
 */

namespace Strt\Plugin\Blocks\Bootstrap5\Layout;

defined( 'ABSPATH' ) || exit;

use Strt\Plugin\Blocks\AbstractBlock;
use WP_Block;

if ( ! class_exists( 'Strt\Plugin\Blocks\Bootstrap5\Layout\Hr' ) ) {

	/**
	 * Класс Hr для редактора Gutenberg.
	 *
	 * Реализация блока "hr" для редактора Gutenberg с поддержкой Bootstrap 5.
	 */
	class Hr extends AbstractBlock {

		/**
		 * Название блока, используемое при регистрации.
		 *
		 * @var string
		 */
		protected string $block_name = 'hr';

		/**
		 * Относительный путь к директории текущего блока (относительно корня блоков).
		 *
		 * @var string
		 */
		protected string $dir_path = '/bootstrap5/layout/';

		/**
		 * Инициализирует метод, выполняющий необходимые действия при запуске.
		 *
		 * @return void Метод не возвращает значение.
		 */
		public function init(): void {
			// TODO: Implement init() method.
		}

		/**
		 * Рендерит HTML-код блока.
		 *
		 * Должен быть реализован в дочернем классе для генерации HTML-кода на основе атрибутов и контента.
		 *
		 * @param  array  $attributes  Атрибуты блока.
		 * @param  string  $content  Контент блока.
		 * @param  WP_Block  $block  Объект блока.
		 *
		 * @return string HTML-код блока.
		 */
		protected function render( array $attributes, string $content, WP_Block $block ): string {
			return sprintf( '<hr %s>', get_block_wrapper_attributes() );
		}
	}
}