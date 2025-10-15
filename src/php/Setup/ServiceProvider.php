<?php
/**
 * ServiceProvider — регистратор и загрузчик сервисов.
 *
 * Отвечает за регистрацию зависимостей в контейнере и их инициализацию.
 * Используется как центральный класс для управления сервисами.
 *
 * @class  ServiceProvider
 * @package Strt\Plugin\Setup
 * @version 1.0.0
 */

namespace Strt\Plugin\Setup;

defined( 'ABSPATH' ) || exit;

use Strt\Plugin\Admin\Admin;
use Strt\Plugin\Admin\AdminSettingsPage;
use Strt\Plugin\Admin\Tabs\ApiIntegrationSettingsTab;
use Strt\Plugin\Admin\Tabs\BlockSettingsTab;
use Strt\Plugin\Admin\Tabs\ImageSettingsTab;
use Strt\Plugin\Admin\Tabs\SiteSettingsTab;
use Strt\Plugin\Admin\Tabs\TelegramSettingsTab;
use Strt\Plugin\Assets\AssetApi;
use Strt\Plugin\Assets\AssetDataRegistry;
use Strt\Plugin\Assets\AssetRegistry;
use Strt\Plugin\Assets\Hydration;
use Strt\Plugin\Assets\WoocommerceAssetDataRegistry;
use Strt\Plugin\Blocks\AuthBlock;
use Strt\Plugin\Blocks\BlockTemplateRegistrar;
use Strt\Plugin\Blocks\Bootstrap5\Block as Bs5Block;
use Strt\Plugin\Blocks\Bootstrap5\Components\Accordion as Bs5Accordion;
use Strt\Plugin\Blocks\Bootstrap5\Components\AccordionItem as Bs5AccordionItem;
use Strt\Plugin\Blocks\Bootstrap5\Components\BreadcrumbBlock as Bs5BreadcrumbBlock;
use Strt\Plugin\Blocks\Bootstrap5\Components\BtnBlock as Bs5BtnBlock;
use Strt\Plugin\Blocks\Bootstrap5\Components\NavbarBlock as Bs5NavbarBlock;
use Strt\Plugin\Blocks\Bootstrap5\Components\NavBlock as Bs5NavBlock;
use Strt\Plugin\Blocks\Bootstrap5\Components\PaginationBlock as Bs5PaginationBlock;
use Strt\Plugin\Blocks\Bootstrap5\Layout\Column as Bs5Column;
use Strt\Plugin\Blocks\Bootstrap5\Layout\Container as Bs5Container;
use Strt\Plugin\Blocks\Bootstrap5\Layout\Hr as Bs5HR;
use Strt\Plugin\Blocks\Bootstrap5\Layout\Row as Bs5Row;
use Strt\Plugin\Blocks\CartBlock;
use Strt\Plugin\Blocks\CheckoutBlock;
use Strt\Plugin\Blocks\ContactFormBlock;
use Strt\Plugin\Blocks\NotificationsBlock;
use Strt\Plugin\Blocks\Product\ProductCollectionBlock;
use Strt\Plugin\Blocks\Product\ProductFilterPriceBlock;
use Strt\Plugin\Blocks\Product\ProductFiltersBlock;
use Strt\Plugin\Blocks\Product\ProductSearchBlock;
use Strt\Plugin\Blocks\Product\ProductTemplateBlock;
use Strt\Plugin\Blocks\Product\ProductUserToolbarBlock;
use Strt\Plugin\Blocks\YandexMapBlock;
use Strt\Plugin\Hooks\ThemeHook;
use Strt\Plugin\Hooks\WoocommerceHook;
use Strt\Plugin\Patterns\PatternRegistry;
use Strt\Plugin\RestApi\Auth\LoginController;
use Strt\Plugin\RestApi\Auth\RegisterController;
use Strt\Plugin\RestApi\ContactFormController;
use Strt\Plugin\RestApi\FavoritesController;
use Strt\Plugin\RestApi\Telegram\WebhookController as TelegramWebhookController;
use Strt\Plugin\Services\FavoritesService;
use Strt\Plugin\Services\Product\ProductQueryFilters;
use Strt\Plugin\Services\Security\LoginRouteService;
use Strt\Plugin\Services\Telegram\TelegramCommandManager;
use Strt\Plugin\Services\Telegram\TelegramOrderNotifier;
use Strt\Plugin\Utils\Helper;
use Strt\Plugin\WoocommerceStoreApi\CartSchema;
use Strt\Plugin\WoocommerceStoreApi\CheckoutSchema;
use Strt\Plugin\WoocommerceStoreApi\ProductSchema;

if ( ! class_exists( 'Strt\Plugin\Setup\ServiceProvider' ) ) {

	/**
	 * Класс ServiceProvider.
	 *
	 * Позволяет регистрировать и загружать сервисы в контейнере.
	 */
	class ServiceProvider {

		/**
		 * Экземпляр контейнера зависимостей.
		 *
		 * @var Container
		 */
		protected Container $container;

		/**
		 * Список классов для регистрации.
		 *
		 * @var array
		 */
		protected array $services = [];

		/**
		 * Конструктор класса ServiceProvider.
		 *
		 * @param  Container  $container  Экземпляр контейнера для управления зависимостями.
		 */
		public function __construct( Container $container ) {
			$this->container = $container;

			$this->init_services();
		}

		/**
		 * Инициализирует и наполняет массив сервисов в зависимости от условий.
		 *
		 * Метод используется для динамического определения, какие сервисы
		 * должны быть зарегистрированы в контейнере. Здесь можно добавлять сервисы
		 * в зависимости от окружения, настроек или других условий.
		 *
		 * @return void
		 */
		private function init_services(): void {
			$this->services['helper']              = Helper::class;
			$this->services['setup']               = Setup::class;
			$this->services['asset_api']           = AssetApi::class;
			$this->services['asset_registry']      = AssetRegistry::class;
			$this->services['asset_data_registry'] = AssetDataRegistry::class;
			$this->services['hydration']           = Hydration::class;

			if ( is_admin() ) {
				$this->services['admin']                              = Admin::class;
				$this->services['admin_settings_page']                = AdminSettingsPage::class;
				$this->services['admin_site_settings_tab']            = SiteSettingsTab::class;
				$this->services['admin_api_integration_settings_tab'] = ApiIntegrationSettingsTab::class;
				$this->services['admin_image_settings_tab']           = ImageSettingsTab::class;
				$this->services['admin_block_settings_tab']           = BlockSettingsTab::class;
				$this->services['admin_telegram_settings_tab']        = TelegramSettingsTab::class;
			}

			$this->services['telegram_command_manager']    = TelegramCommandManager::class;
			$this->services['theme_hook']                  = ThemeHook::class;
			$this->services['navbar_block']                = Bs5NavbarBlock::class;
			$this->services['nav_block']                   = Bs5NavBlock::class;
			$this->services['accordion_block']             = Bs5Accordion::class;
			$this->services['accordion_item_block']        = Bs5AccordionItem::class;
			$this->services['btn_block']                   = Bs5BtnBlock::class;
			$this->services['breadcrumb_block']            = Bs5BreadcrumbBlock::class;
			$this->services['pagination_block']            = Bs5PaginationBlock::class;
			$this->services['container_block']             = Bs5Container::class;
			$this->services['row_block']                   = Bs5Row::class;
			$this->services['column_block']                = Bs5Column::class;
			$this->services['block_block']                 = Bs5Block::class;
			$this->services['hr_block']                    = Bs5HR::class;
			$this->services['notifications_block']         = NotificationsBlock::class;
			$this->services['yandex_map_block']            = YandexMapBlock::class;
			$this->services['contact_form_block']          = ContactFormBlock::class;
			$this->services['auth_block']                  = AuthBlock::class;
			$this->services['pattern_registry']            = PatternRegistry::class;
			$this->services['block_template_registrar']    = BlockTemplateRegistrar::class;
			$this->services['login_route_service']         = LoginRouteService::class;
			$this->services['telegram_webhook_controller'] = TelegramWebhookController::class;
			$this->services['contact_form_controller']     = ContactFormController::class;
			$this->services['login_controller']            = LoginController::class;
			$this->services['register_controller']         = RegisterController::class;

			if ( function_exists( 'is_plugin_active' ) && is_plugin_active( 'woocommerce/woocommerce.php' ) ) {
				$this->services['woocommerce_asset_data_registry'] = WoocommerceAssetDataRegistry::class;

				$this->services['woocommerce_hook'] = WoocommerceHook::class;

				$this->services['cart_block']     = CartBlock::class;
				$this->services['checkout_block'] = CheckoutBlock::class;

				$this->services['product_product_collection'] = ProductCollectionBlock::class;
				$this->services['product_product_template']   = ProductTemplateBlock::class;
				$this->services['product_user_toolbar_block'] = ProductUserToolbarBlock::class;
				$this->services['product_filters_block']      = ProductFiltersBlock::class;
				$this->services['product_filter_price_block'] = ProductFilterPriceBlock::class;
				$this->services['product_search_block']       = ProductSearchBlock::class;

				$this->services['product_query_filters'] = ProductQueryFilters::class;

				//$this->services['product_single_block'] = SingleProductBlock::class;

				$this->services['favorites_service']         = FavoritesService::class;
				$this->services['store_api_cart_scheme']     = CartSchema::class;
				$this->services['store_api_checkout_scheme'] = CheckoutSchema::class;
				$this->services['store_api_product_scheme']  = ProductSchema::class;
				$this->services['favorites_controller']      = FavoritesController::class;
				$this->services['order_telegram_notifier']   = TelegramOrderNotifier::class;
			}
		}

		/**
		 * Регистрация сервисов.
		 *
		 * @return void
		 */
		public function register(): void {
			foreach ( $this->services as $key => $service_class ) {
				$this->container->bind( $key, function () use ( $service_class ) {
					return $this->container->make( $service_class );
				} );
			}
		}

		/**
		 * Инициализация зарегистрированных сервисов.
		 *
		 * @return void
		 */
		public function boot(): void {
			foreach ( $this->services as $key => $service_class ) {
				if ( $this->container->has( $key ) ) {
					$instance = $this->container->make( $key );

					if ( is_object( $instance ) && method_exists( $instance, 'init' ) ) {
						$instance->init();
					}
				}
			}
		}
	}
}
