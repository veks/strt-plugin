<?php
/**
 * Класс Column (Колонка) для редактора Gutenberg.
 *
 * Реализует блок "Колонка" (column) Bootstrap 5 для использования в редакторе Gutenberg.
 * Используется внутри блока "Row" (Ряд) для создания адаптивных сеток и гибкой вёрстки.
 * Позволяет задавать различные параметры отображения колонок по аналогии с компонентом Bootstrap.
 *
 * @class   Column
 * @package Strt\Plugin\Blocks\Bootstrap5\Components
 * @version 1.0.0
 */

namespace Strt\Plugin\Blocks\Bootstrap5\Layout;

defined( 'ABSPATH' ) || exit;

use Strt\Plugin\Blocks\AbstractBlock;
use WP_Block;

if ( ! class_exists( 'Strt\Plugin\Blocks\Bootstrap5\Layout\Column' ) ) {

	/**
	 * Класс, представляющий блок "column".
	 *
	 * Предоставляет методы для управления атрибутами, макетом и рендерингом блока.
	 */
	class Column extends AbstractBlock {

		/**
		 * Название блока, используемое при регистрации.
		 *
		 * @var string
		 */
		protected string $block_name = 'column';

		/**
		 * Путь к директории с макетом.
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
