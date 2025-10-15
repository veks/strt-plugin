<?php
/**
 * Абстрактный AbstractBlock класс для блоков Gutenberg.
 *
 * Этот класс предоставляет базовую структуру для регистрации блоков в редакторе Gutenberg,
 * а также методы для подключения стилей, скриптов и рендеринга блоков.
 *
 * @abstract
 * @class  AbstractBlock
 * @package Strt\Plugin\Blocks
 * @version 1.0.0
 */

namespace Strt\Plugin\Blocks;

defined( 'ABSPATH' ) || exit;

use Strt\Plugin\Assets\AssetDataRegistry;
use Strt\Plugin\Utils\Helper;
use WP_Block;

/**
 * Абстрактный класс AbstractBlock для создания блоков WordPress.
 */
abstract class AbstractBlock {

	/**
	 * Имя блока, используется при регистрации блока.
	 *
	 * @var string
	 */
	protected string $block_name;

	/**
	 * Слаг блока, используется для группировки блоков в редакторе.
	 *
	 * @var string
	 */
	private string $block_slug;

	/**
	 * Путь к директории с блоком, используется для загрузки файлов.
	 *
	 * @var string
	 */
	private string $block_dir_path;

	/**
	 * URL-адрес директории с блоком, используется для загрузки файлов.
	 *
	 * @var string
	 */
	protected string $block_url_path;

	/**
	 * Заголовок категории блока.
	 *
	 * @var string
	 */
	private string $title_category = '';

	/**
	 * Экземпляр вспомогательного класса.
	 *
	 * @var Helper
	 */
	private Helper $helper;

	/**
	 * Экземпляр AssetDataRegistry.
	 *
	 * @var AssetDataRegistry
	 */
	private AssetDataRegistry $asset_data_registry;

	/**
	 * Относительный путь к директории текущего блока (относительно корня блоков).
	 *
	 * @var string
	 */
	protected string $dir_path = '';

	/**
	 * Отслеживает, были ли активы поставлены в очередь.
	 *
	 * @var boolean
	 */
	private bool $enqueued_assets = false;

	/**
	 * Флаг включения передачи настроек блока на клиентскую часть.
	 *
	 * @var bool
	 */
	protected bool $enable_block_settings = false;

	/**
	 * Конструктор класса.
	 *
	 * Регистрирует блок и подключает скрипты и стили.
	 */
	public function __construct() {
		$this->helper              = strt_get_container()->make( Helper::class );
		$this->asset_data_registry = strt_get_container()->make( AssetDataRegistry::class );
		$this->block_slug          = $this->helper::get_block_category_slug();
		$this->block_dir_path      = $this->helper::get_blocks_dir_path( $this->dir_path );
		$this->block_url_path      = $this->helper::get_blocks_url_path( $this->dir_path );
		$this->title_category      = $this->helper::get_name();

		add_action( 'init', [ $this, 'register_block' ] );
		add_action( 'enqueue_block_editor_assets', [ $this, 'enqueue_editor_assets' ] );

		add_filter( 'block_categories_all', [ $this, 'add_block_categories' ], 10, 2 );
	}

	/**
	 * Инициализация блока.
	 *
	 * Должен быть реализован в дочернем классе.
	 *
	 * @return void
	 */
	abstract public function init(): void;

	/**
	 * Регистрирует блок в WordPress.
	 *
	 * Если имя блока установлено, регистрирует его с настройками.
	 *
	 * @return void
	 */
	public function register_block(): void {
		if ( isset( $this->block_name ) ) {
			register_block_type( $this->block_dir_path . $this->block_name,
				[
					'render_callback' => [ $this, 'render_callback' ],
				]
			);
		}
	}

	/**
	 * Добавляет категорию блоков для редактора WordPress.
	 *
	 * @param  array  $categories  Список существующих категорий блоков.
	 *
	 * @return array Модифицированный список категорий блоков.
	 */
	public function add_block_categories( array $categories ): array {
		return array_merge(
			$categories,
			[
				[
					'slug'  => $this->block_slug,
					'title' => $this->title_category
				],
			]
		);
	}

	/**
	 * Обратный вызов render_callback по умолчанию для всех блоков. Это обеспечит своевременную регистрацию активов, а затем рендеринг
	 * блока (если применимо).
	 *
	 * @param  array  $attributes  Атрибуты блока, или экземпляр WP_Block. По умолчанию - пустой массив.
	 * @param  string  $content  Содержимое блока. По умолчанию пустая строка.
	 * @param  WP_Block|null  $block  Экземпляр блока.
	 *
	 * @return string Вывод рендерированного типа блока.
	 */
	public function render_callback( array $attributes = [], string $content = '', ?WP_Block $block = null ): string {
		$render_callback_attributes = $this->parse_render_callback_attributes( $attributes );

		if ( empty( $render_callback_attributes['blockId'] ) ) {
			$render_callback_attributes['blockId'] = wp_unique_prefixed_id( $this->get_block_name() );
		} else {
			$render_callback_attributes['blockId'] = wp_unique_prefixed_id( $render_callback_attributes['blockId'] );
		}

		if ( $this->enable_block_settings ) {
			$settings_data = $this->prepare_settings_data( $render_callback_attributes, $content, $block );

			$this->asset_data_registry->add_block_settings( $render_callback_attributes['blockId'], $settings_data );
		}

		if ( ! is_admin() && ! $this->is_rest_api_request() ) {
			$this->enqueue_assets( $render_callback_attributes, $content, $block );
		}

		return $this->render( $render_callback_attributes, $content, $block );
	}

	/**
	 * Записывает активы, используемые для рендеринга блока в контексте редактора.
	 *
	 * Это необходимо, если блок еще не находится в содержимом посте - ``render`` и ``enqueue_assets`` могут быть не запущены.
	 *
	 * @return void
	 */
	public function enqueue_editor_assets(): void {
		if ( $this->enqueued_assets ) {
			return;
		}

		$this->enqueue_data();
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
		return [ ...$attributes ];
	}

	/**
	 * Разбирает атрибуты блока из render_callback.
	 *
	 * @param  WP_Block|array  $attributes  Атрибуты блока, или экземпляр WP_Block. По умолчанию - пустой массив.
	 *
	 * @return WP_Block|array
	 */
	protected function parse_render_callback_attributes( WP_Block|array $attributes ): WP_Block|array {
		return is_a( $attributes, 'WP_Block' ) ? $attributes->attributes : $attributes;
	}

	/**
	 * Выдача активов фронтенда для этого блока, как раз к моменту рендеринга.
	 *
	 * @param  array  $attributes  Любые атрибуты, которые в данный момент доступны для блока.
	 * @param  string  $content  Содержимое блока.
	 * @param  WP_Block  $block  Объект блока.
	 *
	 * @internal Это предотвращает загрузку скрипта блока на всех страницах. Он вызывается только по мере необходимости.
	 */
	protected function enqueue_assets( array $attributes, string $content, WP_Block $block ): void {
		if ( $this->enqueued_assets ) {
			return;
		}

		$this->enqueue_data( $attributes );
		$this->enqueued_assets = true;
	}

	/**
	 * Подключает данные, необходимые для блока на стороне клиента.
	 *
	 * Этот метод можно переопределить в дочернем классе, чтобы передавать специфичные данные
	 * из PHP в JavaScript. Он вызывается перед рендерингом блока, если это не REST API-запрос.
	 *
	 * @param  array  $attributes  Атрибуты блока, доступные в момент рендеринга.
	 *
	 * @return void
	 */
	protected function enqueue_data( array $attributes = [] ) {
		// TODO: enqueue_data() method.
	}

	/**
	 * Рендерит HTML-код блока.
	 *
	 * Должен быть реализован в дочернем классе для генерации HTML-кода на основе атрибутов и контента.
	 *
	 * @param  array  $attributes  Атрибуты блока.
	 * @param  string  $content  Контент блока.
	 * @param  WP_Block  $block  Объект блока.
	 *
	 * @return string HTML-код блока.
	 */
	protected function render( array $attributes, string $content, WP_Block $block ): string {
		return $content;
	}

	/**
	 * Возвращает true, если запрос не является устаревшим запросом REST API.
	 *
	 * Устаревшие REST-запросы все равно должны выполнять некоторый дополнительный код для обратной совместимости.
	 *
	 * @return bool
	 */
	protected function is_rest_api_request(): bool {
		if ( empty( $_SERVER['REQUEST_URI'] ) ) {
			return false;
		}

		$request_uri = sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) );
		$rest_prefix = trailingslashit( rest_get_url_prefix() );

		return str_contains( $request_uri, $rest_prefix );
	}

	/**
	 * Проверяет, является ли текущий запрос REST API запросом WordPress.
	 *
	 * @return bool True если запрос выполняется через REST API WordPress, иначе false.
	 */
	public function is_rest_request(): bool {
		return defined( 'REST_REQUEST' ) && REST_REQUEST;
	}

	/**
	 * Получает полное имя блока, используемое в API интерактивности.
	 *
	 * @return string Полное имя блока в формате "category/block-name".
	 */
	protected function get_full_block_name(): string {
		return $this->block_slug . '/' . $this->block_name;
	}

	/**
	 * Получает имя блока в формате "slug-name".
	 *
	 * Используется для генерации уникального префикса для скриптов и стилей.
	 *
	 * @return string Имя блока.
	 */
	protected function get_block_name(): string {
		return $this->block_slug . '-' . $this->block_name;
	}

	/**
	 * Мы сейчас находимся на экране редактора блоков администратора?
	 *
	 * @return bool
	 */
	protected function is_block_editor(): bool {
		if ( ! is_admin() || ! function_exists( 'get_current_screen' ) ) {
			return false;
		}

		$screen = get_current_screen();

		return $screen && $screen->is_block_editor();
	}
}
