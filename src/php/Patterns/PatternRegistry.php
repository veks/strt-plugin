<?php
/**
 * Класс PatternRegistry для регистрации и организации паттернов блоков.
 *
 * @class   PatternRegistry
 * @package Strt\Plugin\Patterns
 * @version 1.0.0
 */

namespace Strt\Plugin\Patterns;

use Strt\Plugin\Utils\Helper;
use WP_Block_Pattern_Categories_Registry;

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'Strt\Plugin\PatternRegistry\PatternRegistry' ) ) {

	/**
	 * Класс PatternRegistry.
	 *
	 * Отвечает за регистрацию block pattern категорий и самих паттернов для блочной темы.
	 */
	class PatternRegistry {

		/**
		 * Экземпляр вспомогательного класса.
		 *
		 * @var Helper
		 */
		protected Helper $helper;

		/**
		 * Конструктор.
		 *
		 * @param  Helper  $helper  Экземпляр класса Helper.
		 */
		public function __construct( Helper $helper ) {
			$this->helper = $helper;
		}

		/**
		 * Инициализация регистрации паттернов.
		 *
		 * @return void
		 */
		public function init(): void {
			add_action( 'init', [ $this, 'register_block_patterns' ], 9 );
		}

		/**
		 * Регистрирует категории и паттерны блоков.
		 *
		 * @return void
		 */
		public function register_block_patterns(): void {
			$block_pattern_categories = [
				'strt-plugin-header' => [ 'label' => 'Заголовок' ],
				'strt-plugin-footer' => [ 'label' => 'Подвал' ],
			];

			/**
			 * Фильтр для изменения категорий паттернов.
			 *
			 * @param  array[]  $block_pattern_categories  Массив категорий паттернов.
			 */
			$block_pattern_categories = apply_filters( 'strt_block_pattern_categories', $block_pattern_categories );

			foreach ( $block_pattern_categories as $name => $properties ) {
				if ( ! WP_Block_Pattern_Categories_Registry::get_instance()->is_registered( $name ) ) {
					register_block_pattern_category( $name, $properties );
				}
			}

			$patterns_dir   = __DIR__ . '/patterns/';
			$block_patterns = [];

			if ( is_dir( $patterns_dir ) ) {
				foreach ( glob( $patterns_dir . '*.php' ) as $file ) {
					$block_patterns[] = basename( $file, '.php' );
				}
			}

			/**
			 * Фильтр для изменения списка паттернов.
			 *
			 * @param  array  $block_patterns  Массив имён файлов паттернов (без .php).
			 */
			$block_patterns = apply_filters( 'strt_block_patterns', $block_patterns );

			foreach ( $block_patterns as $block_pattern ) {
				$pattern_file = $patterns_dir . $block_pattern . '.php';

				if ( file_exists( $pattern_file ) ) {
					register_block_pattern( 'strt/' . $block_pattern, require $pattern_file );
				}
			}
		}
	}
}
