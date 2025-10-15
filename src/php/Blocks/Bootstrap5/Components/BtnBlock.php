<?php
/**
 * Класс BtnBlock — кнопка Bootstrap 5 для Gutenberg.
 *
 * @class   BtnBlock
 * @package Strt\Plugin\Blocks\Bootstrap5\Components
 * @version 1.0.0
 */

namespace Strt\Plugin\Blocks\Bootstrap5\Components;

defined( 'ABSPATH' ) || exit;

use Strt\Plugin\Blocks\AbstractBlock;
use WP_Block;

if ( ! class_exists( 'Strt\Plugin\Blocks\Bootstrap5\Components\BtnBlock' ) ) {

	/**
	 * Класс BtnBlock представляет блок кнопки для использования в редакторе.
	 */
	class BtnBlock extends AbstractBlock {

		/**
		 * Имя блока, используется при регистрации блока.
		 *
		 * @var string
		 */
		protected string $block_name = 'btn';

		/**
		 * Путь к директории с блоком, используется для загрузки файлов.
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
		 * @return void
		 */
		public function init(): void {
			// TODO: Implement init() method.
		}

		/**
		 * Рендерит HTML-код блока.
		 *
		 * @param  array  $attributes  Атрибуты блока.
		 * @param  string  $content  Контент блока.
		 * @param  WP_Block  $block  Объект блока.
		 *
		 * @return string HTML-код блока.
		 */
		protected function render( array $attributes, string $content, WP_Block $block ): string {
			$wrapper_attributes = [ 'data-block-id' => esc_attr( $attributes['blockId'] ), ];

			return sprintf( '<div %1$s></div>', get_block_wrapper_attributes( $wrapper_attributes ) );
		}
	}
}
