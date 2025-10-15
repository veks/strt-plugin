<?php
/**
 * Класс BreadcrumbBlock Навигационная цепочка для редактора Gutenberg.
 *
 * @class   BreadcrumbBlock
 * @package Strt\Plugin\Blocks\Bootstrap5\Components
 * @version 1.0.0
 */

namespace Strt\Plugin\Blocks\Bootstrap5\Components;

defined( 'ABSPATH' ) || exit;

use Strt\Plugin\Blocks\AbstractBlock;
use Strt\Plugin\Breadcrumb\Breadcrumb;
use WP_Block;

if ( ! class_exists( 'Strt\Plugin\Blocks\Bootstrap5\Components\BreadcrumbBlock' ) ) {

	/**
	 * Класс BreadcrumbBlock Навигационная цепочка для редактора Gutenberg.
	 */
	class BreadcrumbBlock extends AbstractBlock {

		/**
		 * Название блока, используемое при регистрации.
		 *
		 * @var string
		 */
		protected string $block_name = 'breadcrumb';

		/**
		 * Относительный путь к директории текущего блока (относительно корня блоков).
		 *
		 * @var string
		 */
		protected string $dir_path = '/bootstrap5/components/';

		/**
		 * @var array
		 */
		protected array $breadcrumb_args = [
			'output_echo' => false,
		];

		/**
		 * Инициализация блока.
		 *
		 * В этом методе можно добавить дополнительные хуки, специфичные для данного блока.
		 *
		 * @return void
		 */
		public function init(): void {
			add_filter( 'strt_default_breadcrumb_args', [ $this, 'breadcrumb_args' ], 10, 1 );
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
			/*if ( $this->is_rest_request() ) {
				return $this->block_name; //strt_get_template_html( 'blocks/breadcrumb/render-demo.php', compact( 'attributes', 'block' ) );
			}*/

			$breadcrumb = strt_get_container()->make( Breadcrumb::class )->generate()->render();

			return sprintf( '<div %1$s>%2$s</div>', get_block_wrapper_attributes(), $breadcrumb );
		}

		/**
		 * Параметры для хлебных крошек.
		 *
		 * @param  array  $args  Параметры для хлебных крошек.
		 *
		 * @return array
		 */
		public function breadcrumb_args( array $args ): array {
			return array_merge( $args, $this->breadcrumb_args );
		}
	}
}