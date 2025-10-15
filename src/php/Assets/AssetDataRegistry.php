<?php
/**
 * Класс AssetDataRegistry для регистрации и гидрации данных в WordPress.
 *
 * Этот класс управляет передачей данных между PHP и JavaScript, включая регистрацию скриптов,
 * предзагрузку API-запросов и ленивую загрузку данных.
 *
 * @class  AssetDataRegistry
 * @package Strt\Plugin\Assets
 * @version 1.0.0
 */

namespace Strt\Plugin\Assets;

defined( 'ABSPATH' ) || exit;

use Strt\Plugin\Utils\Helper;

if ( ! class_exists( 'Strt\Plugin\Assets\AssetDataRegistry' ) ) {

	/**
	 * Класс для управления данными ресурса (AssetDataRegistry).
	 *
	 * Позволяет регистрировать, извлекать и управлять данными, включая ленивую
	 * загрузку, гидрацию данных из REST API, а также объединение массивов данных.
	 */
	class AssetDataRegistry {

		/**
		 * Зарегистрированные данные.
		 *
		 * @var array
		 */
		private array $data = [];

		/**
		 * Ленивые данные (замыкания для отложенной загрузки).
		 *
		 * @var array
		 */
		private array $lazy_data = [];

		/**
		 * Предзагруженные API-запросы.
		 *
		 * @var array
		 */
		private array $preloaded_api_requests = [];

		/**
		 * Путь к скрипту.
		 *
		 * @var string
		 */
		private string $script_path = 'settings.js';

		/**
		 * Экземпляр вспомогательного класса.
		 *
		 * @var Helper
		 */
		public Helper $helper;

		/**
		 * Экземпляр Hydration.
		 *
		 * Обеспечивает безопасную предзагрузку данных из REST API WordPress.
		 *
		 * @var Hydration
		 */
		protected Hydration $hydration;

		/**
		 * Обработчик для зарегистрированных данных.
		 *
		 * @var string
		 */
		private string $handle;

		/**
		 * Конструктор класса AssetDataRegistry.
		 *
		 * @param  Helper  $helper  Экземпляр вспомогательного класса.
		 * @param  Hydration  $hydration  Экземпляр гидратора для работы с REST API.
		 */
		public function __construct( Helper $helper, Hydration $hydration ) {
			$this->helper    = $helper;
			$this->hydration = $hydration;
			$this->handle    = $this->helper::handle( 'settings' );
		}

		/**
		 * Инициализация.
		 *
		 * @return void
		 */
		public function init(): void {
			add_action( 'init', [ $this, 'register_data_script' ] );
			if ( ! is_admin() && function_exists( 'wp_is_block_theme' ) && ! wp_is_block_theme() ) {
				add_action( 'wp_print_footer_scripts', [ $this, 'enqueue_script' ], 1 );
				add_action( 'wp_print_footer_scripts', [ $this, 'enqueue_asset_data' ], 1 );
			} else {
				add_action( 'enqueue_block_assets', [ $this, 'enqueue_asset_data' ], 1 );
			}
		}

		/**
		 * Предоставляет частные зарегистрированные данные дочерним классам.
		 *
		 * @return array Зарегистрированные данные в свойстве private data.
		 */
		public function get( string $key = '' ): array {
			if ( $key !== '' && $this->exists( $key ) ) {
				return $this->data[ $key ];
			}

			return $this->data;
		}

		/**
		 * Добавление данных в реестр.
		 *
		 * @param  string  $key  Ключ данных.
		 * @param  array|callable|string|int|bool|object  $value  Значение данных (или callback).
		 */
		public function add( string $key, mixed $value ): void {
			if ( $this->exists( $key ) ) {
				return;
			}

			if ( is_callable( $value ) ) {
				$this->lazy_data[ $key ] = $value;

				return;
			}

			$this->data[ $key ] = $value;
		}

		/**
		 * Объединяет новый массив данных с существующими по заданному ключу.
		 *
		 * Если данных по ключу нет — устанавливает их. Если данные уже есть и это массив,
		 * выполняет слияние массивов с сохранением всех элементов.
		 *
		 * Используется для добавления новых элементов в уже зарегистрированные данные без перезаписи.
		 *
		 * @param  string  $key  Ключ, по которому хранятся данные.
		 * @param  array  $value  Массив значений для слияния с существующими.
		 *
		 * @return void
		 */
		public function merge( string $key, array $value ): void {
			if ( $this->exists( $key ) && is_array( $this->data[ $key ] ) ) {
				foreach ( $value as $k => $v ) {
					if ( ! array_key_exists( $k, $this->data[ $key ] ) ) {
						$this->data[ $key ][ $k ] = $v;
					}
				}
			} else {
				$this->data[ $key ] = $value;
			}
		}

		/**
		 * Проверяет, существует ли ключ в данных.
		 *
		 * @param  string  $key  Ключ данных.
		 *
		 * @return bool
		 */
		public function exists( string $key ): bool {
			return array_key_exists( $key, $this->data );
		}

		/**
		 * Выполняет все отложенные загрузки данных.
		 */
		protected function execute_lazy_data(): void {
			foreach ( $this->lazy_data as $key => $callback ) {
				$this->data[ $key ] = $callback();
			}
		}

		/**
		 * Гидрация данных из API.
		 *
		 * @param  string  $path  REST API путь.
		 */
		public function hydrate_api_request( string $path ): void {
			if ( ! isset( $this->preloaded_api_requests[ $path ] ) ) {
				$this->preloaded_api_requests[ $path ] = $this->hydration->get_rest_api_response_data( $path );
			}
		}

		/**
		 * Гидрация данных по запросу из REST API.
		 *
		 * @param  string  $key  Ключ для добавления гидрированных данных.
		 * @param  string  $path  Путь API, с которого будут загружены данные.
		 *
		 * @return void
		 */
		public function hydrate_data_from_api_request( string $key, string $path ): void {
			$this->add( $key, function () use ( $path ) {
				if ( isset( $this->preloaded_api_requests[ $path ]['body'] ) ) {
					return $this->preloaded_api_requests[ $path ]['body'];
				}

				$response = $this->hydration->get_rest_api_response_data( $path );

				return $response['body'] ?? [];
			} );
		}

		/**
		 * Инициализирует основные данные путем объединения их с текущими данными.
		 *
		 * @return void
		 */
		public function initialize_core_data(): void {
			$this->data = array_merge( $this->data, $this->get_core_data() );
		}

		/**
		 * Получает основные данные для работы с интерфейсом.
		 *
		 * @return array Ассоциативный массив содержащий ключевые данные, такие как URL админ-панели, домашний URL, статус пользователя, роль администратора, nonce для REST API и данные интеграции с API.
		 */
		protected function get_core_data(): array {
			return [
				'adminUrl'           => admin_url(),
				'homeUrl'            => esc_url( home_url( '/' ) ),
				'isUserLoggedIn'     => is_user_logged_in(),
				'currentUserIsAdmin' => current_user_can( 'administrator' ),
				'wpResetNonce'       => $this->helper->get_rest_nonce(),
				'apiIntegration'     => $this->get_api_integration(),
			];
		}

		/**
		 * Возвращает массив API-ключей интеграций.
		 *
		 * Извлекает значения API-ключей.
		 *
		 * @return array Массив значений API-ключей интеграций.
		 */
		protected function get_api_integration(): array {
			$option_name = 'strt_settings_api_integration';

			return [
				'apiGoogleKey'        => strt_get_option( $option_name, 'api-google-key', '' ),
				'apiGoogleSecretKey'  => strt_get_option( $option_name, 'api-google-secret-key', '' ),
				'apiYandexMaps'       => strt_get_option( $option_name, 'api-yandex-maps', '' ),
				'apiYandexGeoSuggest' => strt_get_option( $option_name, 'api-yandex-geo-suggest', '' ),
				'apiDadata'           => strt_get_option( $option_name, 'api-dadata', '' ),
				'apiDadataSecret'     => strt_get_option( $option_name, 'api-dadata-secret', '' ),
			];
		}

		/**
		 * Добавляет настройки конкретного блока по blockId.
		 *
		 * @param  string  $blockId
		 * @param  array  $settings
		 */
		public function add_block_settings( string $blockId, array $settings ): void {
			if ( ! isset( $this->data['blockSettings'] ) ) {
				$this->data['blockSettings'] = [];
			}

			if ( isset( $this->data['blockSettings'][ $blockId ] ) ) {
				$this->data['blockSettings'][ $blockId ] = array_merge( $this->data['blockSettings'][ $blockId ], $settings );
			} else {
				$this->data['blockSettings'][ $blockId ] = $settings;
			}
		}

		/**
		 * Получить настройки всех блоков.
		 *
		 * @return array
		 */
		public function get_block_settings(): array {
			return $this->data['blockSettings'] ?? [];
		}

		/**
		 * Обратный вызов для регистрации скрипта данных через WordPress API.
		 *
		 * @return void
		 */
		public function register_data_script(): void {
			wp_register_script(
				$this->handle,
				$this->helper::get_dir_url_js( $this->script_path ),
				[ 'wp-api-fetch' ],
				$this->helper::get_version(),
			);
		}

		/**
		 * Обратный вызов WordPress API.
		 *
		 * @return void
		 */
		public function enqueue_script(): void {
			wp_enqueue_script( $this->handle );
		}

		/**
		 * Обратный вызов для постановки данных активов в очередь через API WP.
		 */
		public function enqueue_asset_data(): void {
			if ( wp_script_is( $this->handle, 'registered' ) ) {
				$this->initialize_core_data();
				$this->execute_lazy_data();

				$data                          = rawurlencode( wp_json_encode( $this->data ) );
				$settings_script               = sprintf(
					"var %sSettings = %sSettings || JSON.parse(decodeURIComponent('%s'));",
					$this->helper::NAMESPACE,
					$this->helper::NAMESPACE,
					esc_js( $data )
				);
				$preloaded_api_requests_script = '';

				if ( count( $this->preloaded_api_requests ) > 0 ) {
					$preloaded_api_requests        = rawurlencode( wp_json_encode( $this->preloaded_api_requests ) );
					$preloaded_api_requests_script = "wp.apiFetch.use( wp.apiFetch.createPreloadingMiddleware( JSON.parse( decodeURIComponent( '" . esc_js( $preloaded_api_requests ) . "' ) ) ) );";
				}

				wp_add_inline_script( $this->handle, $settings_script . $preloaded_api_requests_script, 'before' );
			}
		}

		/**
		 * Показывает, находится ли текущий сайт в режиме отладки или нет.
		 *
		 * @return boolean True означает, что сайт находится в режиме отладки.
		 */
		protected function debug(): bool {
			return defined( 'WP_DEBUG' ) && WP_DEBUG;
		}
	}
}
