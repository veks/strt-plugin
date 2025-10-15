<?php
/**
 * Блок "Панель пользователя магазина" для редактора Gutenberg.
 *
 * Выводит Вход/Аккаунт, Избранное, Заказы, Корзина (со счётчиками).
 * Серверный рендер через render(), плюс (опционально) гидратация данных
 * во фронт при наличии соответствующего метода в AssetDataRegistry.
 *
 * @class   ProductUserToolbarBlock
 * @package Strt\Plugin\Blocks\Product
 * @version 1.0.0
 */

namespace Strt\Plugin\Blocks\Product;

defined( 'ABSPATH' ) || exit;

use Strt\Plugin\Assets\AssetDataRegistry;
use Strt\Plugin\Blocks\AbstractBlock;
use Strt\Plugin\Services\FavoritesService;
use WP_Block;

if ( ! class_exists( 'Strt\Plugin\Blocks\Product\ProductUserToolbarBlock' ) ) {

	/**
	 * Класс ProductUserToolbarBlock.
	 *
	 * Отвечает за регистрацию, подготовку данных и рендер блока "Панель пользователя".
	 */
	class ProductUserToolbarBlock extends AbstractBlock {

		/**
		 * Название блока, используемое при регистрации.
		 *
		 * @var string
		 */
		protected string $block_name = 'product-user-toolbar';

		/**
		 * Относительный путь к директории текущего блока (относительно корня блоков).
		 *
		 * @var string
		 */
		protected string $dir_path = '/product/';

		/**
		 * Флаг включения передачи настроек блока на клиентскую часть.
		 *
		 * @var bool
		 */
		protected bool $enable_block_settings = true;

		/**
		 * Реестр данных для передачи в JS.
		 *
		 * @var AssetDataRegistry
		 */
		protected AssetDataRegistry $asset_data_registry;

		/**
		 * Обработчик избранного.
		 *
		 * @var FavoritesService
		 */
		protected FavoritesService $favorites;

		/**
		 * Инициализация блока.
		 *
		 * В этом методе можно добавить дополнительные хуки, специфичные для данного блока.
		 *
		 * @return void
		 */
		public function init(): void {

		}

		/**
		 * Конструктор.
		 *
		 * @param  AssetDataRegistry  $asset_data_registry  Экземпляр реестра данных для передачи JS.
		 * @param  FavoritesService  $favorites  Экземпляр обработчика избранного.
		 *
		 * @since 1.0.0
		 */
		public function __construct( AssetDataRegistry $asset_data_registry, FavoritesService $favorites ) {
			parent::__construct();

			$this->asset_data_registry = $asset_data_registry;
			$this->favorites           = $favorites;
		}

		/**
		 * Подготавливает массив настроек блока для передачи в JavaScript.
		 *
		 * @param  array  $attributes  Ассоциативный массив атрибутов блока.
		 * @param  string  $content  Контент блока.
		 * @param  WP_Block  $block  Объект блока.
		 *
		 * @return array Массив настроек, который будет сериализован и передан на клиент.
		 */
		protected function prepare_settings_data( array $attributes, string $content, WP_Block $block ): array {
			return [
				'accountMenuItems'  => wc_get_account_menu_items(),
			];
		}

		/**
		 * Подключает данные для фронтенда (опционально).
		 *
		 * Гидратируем сводные данные для JS под ключом `productUserToolbarData`,
		 * если в AssetDataRegistry есть совместимый метод.
		 *
		 * @param  array<string,mixed>  $attributes  Атрибуты блока.
		 *
		 * @return void
		 */
		protected function enqueue_data( array $attributes = [] ): void {

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

			return sprintf( '<div %1$s></div>', get_block_wrapper_attributes( $wrapper_attributes ) );
		}
	}
}