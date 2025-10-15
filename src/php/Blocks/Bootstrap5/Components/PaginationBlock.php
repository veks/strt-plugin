<?php
/**
 * Класс PaginationBlock пагинация страниц для редактора Gutenberg.
 *
 * @class   PaginationBlock
 * @package Strt\Plugin\Blocks\Bootstrap5\Components
 * @version 1.0.0
 */

namespace Strt\Plugin\Blocks\Bootstrap5\Components;

defined( 'ABSPATH' ) || exit;

use Strt\Plugin\Blocks\AbstractBlock;
use Strt\Plugin\Pagination\Pagination;
use WP_Block;

if ( ! class_exists( 'Strt\Plugin\Blocks\Bootstrap5\Components\PaginationBlock' ) ) {

	/**
	 * Класс PaginationBlock пагинация страниц для редактора Gutenberg.
	 */
	class PaginationBlock extends AbstractBlock {

		/**
		 * Название блока, используемое при регистрации.
		 *
		 * @var string
		 */
		protected string $block_name = 'pagination';

		/**
		 * Относительный путь к директории текущего блока (относительно корня блоков).
		 *
		 * @var string
		 */
		protected string $dir_path = '/bootstrap5/components/';

		/**
		 * @var array
		 */
		protected array $pagination_args = [
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
			add_filter( 'strt_pagination_defaults', [ $this, 'pagination_args' ], 10, 1 );
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
			if ( $this->is_rest_request() ) {
				return $this->block_name; //strt_get_template_html( 'blocks/pagination/render-demo.php', compact( 'attributes', 'block' ) );
			}

			$pagination = strt_get_container()->make( Pagination::class )->render();

			return sprintf( '<div %1$s>%2$s</div>', get_block_wrapper_attributes(), $pagination );
		}

		/**
		 * Параметры для хлебных крошек.
		 *
		 * @param  array  $args  Параметры для хлебных крошек.
		 *
		 * @return array
		 */
		public function pagination_args( array $args ): array {
			return array_merge( $args, $this->pagination_args );
		}
	}
}