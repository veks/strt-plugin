<?php
/**
 * Аккордеон (Accordion) — компонент Bootstrap 5 для Gutenberg-блока.
 *
 * Реализует навигационный аккордеон в редакторе Gutenberg.
 *
 * @class   Accordion
 * @package Strt\Plugin\Blocks\Bootstrap5\Components
 * @version 1.0.0
 */

namespace Strt\Plugin\Blocks\Bootstrap5\Components;

defined( 'ABSPATH' ) || exit;

use Strt\Plugin\Blocks\AbstractBlock;
use WP_Block;

if ( ! class_exists( 'Strt\Plugin\Blocks\Bootstrap5\Components\Accordion' ) ) {

	/**
	 * Класс Accordion — компонент Bootstrap 5 для Gutenberg-блока.
	 */
	class Accordion extends AbstractBlock {

		/**
		 * Название блока, используемое при регистрации.
		 *
		 * @var string
		 */
		protected string $block_name = 'accordion';

		/**
		 * Относительный путь к директории текущего блока (относительно корня блоков).
		 *
		 * @var string
		 */
		protected string $dir_path = '/bootstrap5/components/';

		/**
		 * Инициализация блока.
		 *
		 * @return void
		 */
		public function init(): void {
			// TODO: Implement init() method.
		}

		/**
		 * Рендерит HTML-код блока на фронтенде.
		 *
		 * @param  array  $attributes  Атрибуты блока, переданные из редактора.
		 * @param  string  $content  Контент блока.
		 * @param  WP_Block  $block  Объект блока.
		 *
		 * @return string HTML-код блока.
		 */
		public function render( array $attributes, string $content, WP_Block $block ): string {
			$wrapper_attributes = [
				'data-block-id' => esc_attr( $attributes['blockId'] ),
				'class'         => 'accordion',
				'id'            => 'accordion-' . esc_attr( $attributes['blockId'] ),
			];

			return sprintf( '<div %1$s>%2$s</div>', get_block_wrapper_attributes( $wrapper_attributes ), $content );
		}
	}
}