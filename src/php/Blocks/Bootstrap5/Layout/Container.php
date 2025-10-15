<?php
/**
 * Класс Container (Контейнер) для редактора Gutenberg.
 *
 * Реализует блок "Container" Bootstrap 5 для использования в редакторе Gutenberg.
 * Контейнер используется как обёртка для рядов (Row) и колонок (Column), обеспечивая отступы и центрирование содержимого.
 * Поддерживает типы container, container-fluid и адаптивные container-{breakpoint}.
 *
 * @class   Container
 * @package Strt\Plugin\Blocks\Bootstrap5\Layout
 * @version 1.0.0
 */

namespace Strt\Plugin\Blocks\Bootstrap5\Layout;

defined( 'ABSPATH' ) || exit;

use Strt\Plugin\Blocks\AbstractBlock;
use WP_Block;

if ( ! class_exists( 'Strt\Plugin\Blocks\Bootstrap5\Layout\Container' ) ) {

	/**
	 * Класс Container (Контейнер) для редактора Gutenberg.
	 */
	class Container extends AbstractBlock {

		/**
		 * Название блока, используемое при регистрации.
		 *
		 * @var string
		 */
		protected string $block_name = 'container';

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
	}
}