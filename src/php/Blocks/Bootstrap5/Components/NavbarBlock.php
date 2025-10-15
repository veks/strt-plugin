<?php
/**
 * Класс NavbarBlock — блок навигационной панели (Navbar) для редактора Gutenberg на Bootstrap 5.
 *
 * @class   NavbarBlock
 * @package Strt\Plugin\Blocks\Bootstrap5\Components
 * @version 1.0.0
 */

namespace Strt\Plugin\Blocks\Bootstrap5\Components;

defined( 'ABSPATH' ) || exit;

use Strt\Plugin\Blocks\AbstractBlock;
use Strt\Plugin\Walker\WalkerNavMenuArray;
use WP_Block;

if ( ! class_exists( 'Strt\Plugin\Blocks\Bootstrap5\Components\NavbarBlock' ) ) {

	/**
	 * Класс NavbarBlock — реализует Gutenberg-блок Bootstrap Navbar.
	 */
	class NavbarBlock extends AbstractBlock {

		/**
		 * Название блока, используемое при регистрации.
		 *
		 * @var string
		 */
		protected string $block_name = 'navbar';

		/**
		 * Относительный путь к директории текущего блока (относительно корня блоков).
		 *
		 * @var string
		 */
		protected string $dir_path = '/bootstrap5/components/';

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
		 * Подготавливает массив настроек блока для передачи в JavaScript.
		 *
		 * @param  array  $attributes  Ассоциативный массив атрибутов блока.
		 * @param  string  $content  Контент блока.
		 * @param  WP_Block|null  $block  Объект блока.
		 *
		 * @return array Массив настроек, который будет сериализован и передан на клиент.
		 */
		protected function prepare_settings_data( array $attributes, string $content, ?WP_Block $block = null ): array {
			$get_items               = ( new WalkerNavMenuArray( $attributes['menuId'] ) )->get_items();
			$attributes['menuItems'] = $get_items;
			$brand_image_id          = $attributes['brandImageID'];
			$brand_image             = '';

			if ( ! empty( $brand_image_id ) ) {
				$mime_type = get_post_mime_type( $brand_image_id );

				if ( $mime_type === 'image/svg+xml' ) {
					$brand_image = wp_get_attachment_image( $brand_image_id, 'full' );
				} else {
					$brand_image = wp_get_attachment_image( $brand_image_id, 'strt-theme-logo-200x40' );
				}
			}

			return [
				'brandImage' => $brand_image,
				...$attributes
			];
		}

		/**
		 * Рендерит HTML-код блока на фронтенде.
		 *
		 * @param  array  $attributes  Атрибуты блока, переданные из редактора.
		 * @param  string  $content  Контент блока.
		 * @param  WP_Block|null  $block  Объект блока.
		 *
		 * @return string HTML-код блока.
		 */
		public function render( array $attributes, string $content, ?WP_Block $block = null ): string {
			$wrapper_attributes = [ 'data-block-id' => esc_attr( $attributes['blockId'] ), ];

			return sprintf( '<div %1$s>%2$s</div>', get_block_wrapper_attributes( $wrapper_attributes ), $content );
		}
	}
}