<?php
/**
 * Абстрактный класс для вкладок настроек (SITE).
 *
 * Используется для страниц настроек на уровне сайта (wp_options).
 * Поддерживает Settings API, валидацию и регистрацию опций.
 *
 * @package    Strt\Plugin\Admin\Tabs
 * @subpackage Settings
 * @since      1.2.0
 */

namespace Strt\Plugin\Admin\Tabs;

defined( 'ABSPATH' ) || exit;

/**
 * Базовый абстрактный класс для SITE-вкладок настроек.
 *
 * Регистрирует вкладку и метаданные через пользовательские фильтры плагина,
 * обеспечивает значения по умолчанию, а также чтение/сохранение опций сайта.
 *
 * @since 1.2.0
 */
abstract class AbstractSiteSettingsTab {

	/**
	 * Идентификатор вкладки (slug).
	 * @var string
	 */
	protected string $id = '';

	/**
	 * Заголовок вкладки.
	 * @var string
	 */
	protected string $label = '';

	/**
	 * Группа опций для Settings API.
	 * @var string
	 */
	protected string $option_group = '';

	/**
	 * Имя опции (wp_options).
	 * @var string
	 */
	protected string $option_name = '';

	/**
	 * Идентификатор секции (Settings API).
	 * @var string
	 */
	protected string $section_id = '';

	/**
	 * Заголовок секции.
	 * @var string
	 */
	protected string $section_title = '';

	/**
	 * Описание секции.
	 * @var string
	 */
	protected string $description = '';

	/**
	 * Делать вкладку активной по умолчанию.
	 * @var bool
	 */
	protected bool $default_tab = false;

	/**
	 * Конструктор.
	 *
	 * Подписывает вкладку на фильтры сайта и регистрирует валидатор опции.
	 */
	public function __construct() {
		if ( is_admin() ) {
			add_filter( 'strt_site_settings_tabs_array', [ $this, 'add_settings_page' ] );
			add_filter( 'strt_site_settings_tab_default', [ $this, 'default_tab' ] );
			add_filter( 'strt_site_settings', [ $this, 'add_setting' ] );
			add_filter( 'strt_plugin_options', [ $this, 'inject_defaults' ] );

			if ( $this->option_name ) {
				add_filter( "strt_settings_validate_{$this->option_name}", [ $this, 'validate_input' ] );
			}
		}
	}

	/**
	 * Добавляет вкладку в список вкладок настроек сайта.
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
	 * Возвращает вкладку по умолчанию.
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
	 * Добавляет описание секции и список полей вкладки (SITE) в конфиг настроек.
	 *
	 * @param  array  $settings  Текущий конфиг настроек.
	 *
	 * @return array Обновлённый конфиг настроек.
	 */
	public function add_setting( array $settings ): array {
		if ( ! $this->option_name ) {
			return $settings;
		}

		$settings[] = [
			'tab_id'       => $this->id,
			'option_group' => $this->option_group,
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
	 * Инъецирует значения по умолчанию опции вкладки в общий набор дефолтов плагина.
	 *
	 * @param  array  $options  Текущие дефолты плагина.
	 *
	 * @return array Обновлённые дефолты плагина.
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
	 * Возвращает текущие значения опции вкладки (SITE).
	 *
	 * @param  string  $key  Ключ внутри массива опции (dot-notation).
	 * @param  mixed|null  $default_value  Значение по умолчанию.
	 *
	 * @return array|string|mixed Текущие значения опции.
	 */
	public function get_settings( string $key = '', mixed $default_value = null ): mixed {
		return strt_get_option( $this->option_name, $key, $default_value );
	}

	/**
	 * Сохраняет значения опции вкладки (SITE).
	 *
	 * @param  string  $key  Ключ внутри массива опции (dot-notation).
	 * @param  mixed  $value  Новое значение.
	 *
	 * @return bool Успешность операции.
	 */
	public function update_settings( string $key, mixed $value = '' ): bool {
		return (bool) strt_update_option( $this->option_name, $key, $value );
	}

	/**
	 * Валидация входных данных опции (SITE).
	 *
	 * Переопределяйте в дочерних классах для специфичной логики.
	 *
	 * @param  array  $input  Входные данные.
	 *
	 * @return array Валидированные данные.
	 */
	public function validate_input( array $input ): array {
		return is_array( $input ) ? $input : [];
	}

	/**
	 * Возвращает набор полей вкладки.
	 *
	 * @return array Массив конфигураций полей.
	 */
	abstract public function get_fields(): array;

	/**
	 * Возвращает значения опции по умолчанию (SITE).
	 *
	 * @return array Ассоциативный массив дефолтов.
	 */
	protected function get_defaults(): array {
		return [];
	}
}
