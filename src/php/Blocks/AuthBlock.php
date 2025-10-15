<?php
/**
 * Блок "Авторизация/Регистрация" для редактора Gutenberg.
 *
 * @class   AuthBlock
 * @package Strt\Plugin\Blocks
 * @version 1.1.0
 */

namespace Strt\Plugin\Blocks;

use WP_Block;

if ( ! class_exists( 'Strt\Plugin\Blocks\AuthBlock' ) ) {

	/**
	 * Блок "Авторизация/Регистрация" для редактора Gutenberg.
	 */
	class AuthBlock extends AbstractBlock {

		/**
		 * Название блока, используемое при регистрации.
		 *
		 * @var string
		 */
		protected string $block_name = 'auth';

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
			add_filter( 'body_class', [ $this, 'add_body_class' ] );
		}

		/**
		 * Добавляем класс.
		 *
		 * @param  array  $classes  Классы.
		 *
		 * @return array Массив классов.
		 */
		public function add_body_class( array $classes ): array {
			if ( function_exists( 'is_account_page' ) && is_account_page() ) {
				$classes[] = 'bg-gray-50';
			}

			return $classes;
		}

		/**
		 * Рендерит HTML-код блока.
		 *
		 * @param  array<string,mixed>  $attributes  Атрибуты блока.
		 * @param  string  $content  Контент блока.
		 * @param  WP_Block  $block  Объект блока.
		 *
		 * @return string HTML-код блока.
		 */
		public function render( array $attributes, string $content, WP_Block $block ): string {
			$wrapper_attributes = [ 'data-block-id' => esc_attr( $attributes['blockId'] ), ];

			if ( ! is_user_logged_in() ) {
				return sprintf( '<div %1$s></div>', get_block_wrapper_attributes( $wrapper_attributes ) );
			} else {
				return do_shortcode( '[woocommerce_my_account]' );
			}
		}
	}
}