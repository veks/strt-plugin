<?php
/**
 * Класс Block для редактора Gutenberg, реализующий блок Bootstrap 5.
 *
 * Этот класс отвечает за регистрацию, инициализацию и рендеринг кастомного блока для Gutenberg
 * с использованием компонентов Bootstrap 5.
 *
 * @class   Block
 * @package Strt\Plugin\Blocks\Bootstrap5\Layout
 * @version 1.0.0
 */

namespace Strt\Plugin\Blocks\Bootstrap5;

defined( 'ABSPATH' ) || exit;

use Strt\Plugin\Blocks\AbstractBlock;
use WP_Block;

if ( ! class_exists( 'Strt\Plugin\Blocks\Bootstrap5\Block' ) ) {

	/**
	 * Класс Block для редактора Gutenberg.
	 *
	 * Реализация блока для редактора Gutenberg с поддержкой Bootstrap 5.
	 */
	class Block extends AbstractBlock {

		/**
		 * Название блока, используемое при регистрации.
		 *
		 * @var string
		 */
		protected string $block_name = 'block';

		/**
		 * Относительный путь к директории текущего блока (относительно корня блоков).
		 *
		 * @var string
		 */
		protected string $dir_path = '/bootstrap5/';

		/**
		 * Инициализация блока.
		 *
		 * В этом методе можно добавить дополнительные хуки, специфичные для данного блока.
		 *
		 * @return void
		 */
		public function init(): void {
			// TODO: Implement init() method.
		}

		/**
		 * Преобразует массив стилей в CSS-строку.
		 *
		 * @param  array  $style  Массив стилей.
		 *
		 * @return string
		 */
		protected function style_array_to_string( array $style = [] ): string {
			if ( ! is_array( $style ) || empty( $style ) ) {
				return '';
			}

			$string = '';

			foreach ( $style as $prop => $val ) {
				if ( $val !== '' ) {
					$string .= esc_attr( $prop ) . ':' . esc_attr( $val ) . ';';
				}
			}

			return $string;
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
			return $content;
		}
	}
}