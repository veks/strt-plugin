<?php
/**
 * Класс ContactForm для редактора Gutenberg, реализующий контактную форму.
 *
 * Этот класс отвечает за регистрацию, инициализацию и рендеринг кастомного блока для Gutenberg
 * с поддержкой стандартной контактной формы.
 *
 * @class   ContactFormBlock
 * @package Strt\Plugin\Blocks
 * @version 1.0.0
 */

namespace Strt\Plugin\Blocks;

defined( 'ABSPATH' ) || exit;

use WP_Block;

if ( ! class_exists( 'Strt\Plugin\Blocks\ContactFormBlock' ) ) {

	/**
	 * Класс ContactFormBlock для редактора Gutenberg.
	 *
	 * Реализация блока контактной формы для редактора Gutenberg.
	 */
	class ContactFormBlock extends AbstractBlock {

		/**
		 * Название блока, используемое при регистрации.
		 *
		 * @var string
		 */
		protected string $block_name = 'contact-form';

		/**
		 * Флаг включения передачи настроек блока на клиентскую часть.
		 *
		 * @var bool
		 */
		protected bool $enable_block_settings = true;

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
		 * Рендерит HTML-код блока на фронтенде.
		 *
		 * @param  array  $attributes  Атрибуты блока, переданные из редактора.
		 * @param  string  $content  Контент блока.
		 * @param  WP_Block  $block  Объект блока.
		 *
		 * @return string HTML-код блока.
		 */
		public function render( array $attributes, string $content, WP_Block $block ): string {
			$wrapper_attributes = [ 'data-block-id' => esc_attr( $attributes['blockId'] ), ];

			return sprintf( '<div %1$s></div>', get_block_wrapper_attributes( $wrapper_attributes ) );
		}
	}
}