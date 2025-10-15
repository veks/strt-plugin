<?php
/**
 * Класс Row (Ряд) для редактора Gutenberg.
 *
 * Реализует блок "Ряд" (row) Bootstrap 5 для использования в редакторе Gutenberg.
 * Предназначен для создания горизонтальных групп колонок (Column) в сетке Bootstrap,
 * обеспечивая удобное построение адаптивных макетов с помощью блоков.
 *
 * @class   Row
 * @package Strt\Plugin\Blocks\Bootstrap5\Layout
 * @version 1.0.0
 */

namespace Strt\Plugin\Blocks\Bootstrap5\Layout;

defined( 'ABSPATH' ) || exit;

use Strt\Plugin\Blocks\AbstractBlock;
use WP_Block;
use WP_Block_Supports;

if ( ! class_exists( 'Strt\Plugin\Blocks\Bootstrap5\Layout\Row' ) ) {

	/**
	 * Класс Row (Ряд) для редактора Gutenberg.
	 */
	class Row extends AbstractBlock {

		/**
		 * Название блока, используемое при регистрации.
		 *
		 * @var string
		 */
		protected string $block_name = 'row';

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