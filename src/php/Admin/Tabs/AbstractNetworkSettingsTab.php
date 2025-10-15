<?php
/**
 * Абстрактный класс для вкладок настроек (NETWORK).
 *
 * Используется для страниц настроек на уровне сети (wp_siteoptions).
 * Не использует Settings API; сохранение выполняется кастомным обработчиком.
 *
 * @package    Strt\Plugin\Admin\Tabs
 * @subpackage Settings
 * @since      1.2.0
 */

namespace Strt\Plugin\Admin\Tabs;

defined( 'ABSPATH' ) || exit;

/**
 * Базовый абстрактный класс для NETWORK-вкладок настроек.
 *
 * Регистрирует вкладку и метаданные через пользовательские фильтры плагина,
 * обеспечивает значения по умолчанию, а также чтение/сохранение сетевых опций.
 *
 * @since 1.2.0
 */
abstract class AbstractNetworkSettingsTab {

	/**
	 * Идентификатор вкладки (slug).
	 *
	 * @var string
	 */
	protected string $id = '';

	/**
	 * Заголовок вкладки.
	 *
	 * @var string
	 */
	protected string $label = '';

	/**
	 * Имя сетевой опции (wp_siteoptions).
	 *
	 * @var string
	 */
	protected string $option_name = '';

	/**
	 * Идентификатор секции.
	 *
	 * @var string
	 */
	protected string $section_id = '';

	/**
	 * Заголовок секции.
	 *
	 * @var string
	 */
	protected string $section_title = '';

	/**
	 * Описание секции.
	 *
	 * @var string
	 */
	protected string $description = '';

	/**
	 * Делать вкладку активной по умолчанию.
	 *
	 * @var bool
	 */
	protected bool $default_tab = false;

	/**
	 * Конструктор.
	 *
	 * Подписывает вкладку на фильтры сети и инъекцию сетевых дефолтов.
	 */
	public function __construct() {
		if ( is_admin() ) {
			add_filter( 'strt_network_settings_tabs_array', [ $this, 'add_settings_page' ] );
			add_filter( 'strt_network_settings_tab_default', [ $this, 'default_tab' ] );
			add_filter( 'strt_network_settings', [ $this, 'add_setting' ] );
			add_filter( 'strt_plugin_network_options', [ $this, 'inject_defaults' ] );

			if ( $this->option_name ) {
				add_filter( "strt_settings_network_validate_{$this->option_name}", [ $this, 'validate_input' ] );
			}
		}
	}

	/**
	 * Добавляет вкладку в список вкладок настроек сети.
	 *
	 * @param  array  $pages  Ассоциативный массив вкладок `slug => label`.
	 *
	 * @return array Обновлённый массив вкладок.
	 */
	public function add_settings_page( array $pages ): array {
		if ( $this->id && $this->label && $this->option_name ) {
			$pages[ $this->id ] = $this->label;
		}

		return $pages;
	}

	/**
	 * Возвращает вкладку по умолчанию для сети.
	 *
	 * @param  string  $current_default  Текущий slug вкладки по умолчанию.
	 *
	 * @return string Slug вкладки по умолчанию.
	 */
	public function default_tab( string $current_default = '' ): string {
		if ( ! $current_default && $this->default_tab && $this->id ) {
			return $this->id;
		}

		return $current_default;
	}

	/**
	 * Добавляет описание секции и список полей вкладки (NETWORK) в конфиг настроек.
	 *
	 * @param  array  $settings  Текущий конфиг настроек сети.
	 *
	 * @return array Обновлённый конфиг настроек сети.
	 * @since 1.2.0
	 *
	 */
	public function add_setting( array $settings ): array {
		if ( ! $this->option_name ) {
			return $settings;
		}

		$settings[] = [
			'tab_id'       => $this->id,
			'option_group' => null,
			'option_name'  => $this->option_name,
			'option_label' => $this->label,
			'section'      => [
				'id'          => $this->section_id,
				'title'       => $this->section_title,
				'description' => $this->description,
			],
			'fields'       => $this->get_fields(),
		];

		return $settings;
	}

	/**
	 * Инъецирует значения по умолчанию сетевой опции вкладки.
	 *
	 * @param  array  $options  Текущие сетевые дефолты плагина.
	 *
	 * @return array Обновлённые сетевые дефолты плагина.
	 * @since 1.2.0
	 *
	 */
	public function inject_defaults( array $options ): array {
		if ( $this->option_name ) {
			$defs = $this->get_defaults();
			if ( $defs ) {
				$options[ $this->option_name ] = $defs;
			}
		}

		return $options;
	}

	/**
	 * Возвращает текущие значения сетевой опции вкладки.
	 *
	 * @return array Текущие значения сетевой опции.
	 * @since 1.2.0
	 *
	 */
	public function get_settings(): array {
		$value = get_site_option( $this->option_name, [] );

		return is_array( $value ) ? $value : [];
	}

	/**
	 * Сохраняет значения сетевой опции вкладки.
	 *
	 * @param  array  $settings  Значения для сохранения.
	 *
	 * @return bool Успешность операции.
	 * @since 1.2.0
	 *
	 */
	public function update_settings( array $settings ): bool {
		return (bool) update_site_option( $this->option_name, $settings );
	}

	/**
	 * Валидация входных данных сетевой опции.
	 *
	 * Переопределяйте в дочерних классах для специфичной логики.
	 *
	 * @param  array  $input  Входные данные.
	 *
	 * @return array Валидированные данные.
	 * @since 1.2.0
	 *
	 */
	public function validate_input( array $input ): array {
		return is_array( $input ) ? $input : [];
	}

	/**
	 * Возвращает набор полей вкладки (NETWORK).
	 *
	 * @return array Массив конфигураций полей.
	 * @since 1.2.0
	 *
	 */
	abstract public function get_fields(): array;

	/**
	 * Возвращает значения сетевой опции по умолчанию.
	 *
	 * @return array Ассоциативный массив дефолтов.
	 * @since 1.2.0
	 *
	 */
	protected function get_defaults(): array {
		return [];
	}
}
