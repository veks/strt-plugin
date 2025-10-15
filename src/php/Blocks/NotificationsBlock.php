<?php
/**
 * Класс NotificationsBlock для реализации блока уведомлений в WordPress.
 *
 * Этот класс представляет кастомный блок "Уведомления" для редактора Gutenberg в WordPress.
 *
 * @class   NotificationsBlock
 * @package Strt\Plugin\Blocks
 * @version 1.0.0
 */

namespace Strt\Plugin\Blocks;

defined( 'ABSPATH' ) || exit;

use WP_Block;

if ( ! class_exists( 'Strt\Plugin\Blocks\NotificationsBlock' ) ) {

	/**
	 * Класс NotificationsBlock блока уведомлений.
	 */
	class NotificationsBlock extends AbstractBlock {

		/**
		 * Название блока, используемое при регистрации.
		 *
		 * @var string
		 */
		protected string $block_name = 'notifications';

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
			return sprintf( '<div %1$s></div>', get_block_wrapper_attributes() );
		}
	}
}
