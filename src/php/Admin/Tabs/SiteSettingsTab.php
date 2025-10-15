<?php
/**
 * Вкладка настроек сайта.
 *
 * Коды для вставки в <head> и перед </body>. Работает в обычной и сетевой админке.
 *
 * @class   SiteSettingsTab
 * @package Strt\Plugin\Admin\Tabs
 * @version 1.0.0
 */

namespace Strt\Plugin\Admin\Tabs;

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'Strt\Plugin\Admin\Tabs\SiteSettingsTab' ) ) {

	/**
	 * Класс SiteSettingsTab.
	 *
	 * Вкладка «Настройки сайта»: код в шапке и подвале.
	 */
	class SiteSettingsTab extends AbstractSiteSettingsTab {

		/**
		 * @var string
		 *
		 * Идентификатор вкладки (slug).
		 */
		protected string $id = 'site';

		/**
		 * @var string
		 *
		 * Заголовок вкладки.
		 */
		protected string $label = 'Настройки сайта';

		/**
		 * @var string
		 *
		 * Группа опции (Settings API per-site).
		 */
		protected string $option_group = 'strt_settings_option_group_site';

		/**
		 * @var string
		 *
		 * Имя per-site опции (wp_options).
		 */
		protected string $option_name = 'strt_settings_site';

		/**
		 * @var string
		 *
		 * Имя сетевой опции (wp_siteoptions).
		 */
		protected string $network_option_name = 'strt_settings_site_network';

		/**
		 * @var string
		 *
		 * Идентификатор секции (Settings API).
		 */
		protected string $section_id = 'strt_settings_section_site';

		/**
		 * @var string
		 *
		 * Заголовок секции.
		 */
		protected string $section_title = 'Настройки сайта';

		/**
		 * @var string
		 *
		 * Описание секции.
		 */
		protected string $description = '';

		/**
		 * @var bool
		 *
		 * Делать вкладку активной по умолчанию.
		 */
		protected bool $default_tab = true;

		/**
		 * Возвращает массив полей вкладки.
		 *
		 * @return array
		 */
		public function get_fields(): array {
			return [
				[
					'id'      => 'hide-login-enable',
					'title'   => 'Скрыть страницу входа',
					'desc'    => 'Блокирует прямой доступ к /wp-login.php и /wp-admin/ (для неавторизованных) и включает кастомный URL входа.',
					'type'    => 'checkbox',
					'default' => 0,
				],
				[
					'id'      => 'hide-login-slug',
					'title'   => 'URL страницы входа',
					'desc'    => 'Только латиница, цифры и дефис. Пример: <code>secure-login</code>. <br>Итоговый адрес: <code>' . esc_url( home_url( '/' ) ) . '<strong>' . $this->get_settings( 'hide-login-slug',
							'secure-login' ) . '</strong>/</code>',
					'type'    => 'text',
					'default' => 'login',
				],
				[
					'id'      => 'header-code-editor',
					'title'   => 'Скрипты в шапке',
					'desc'    => 'Вставьте код — он будет добавлен перед тегом &lt;head&gt;.',
					'type'    => 'code_editor',
					'default' => '<!-- Code header -->',
				],
				[
					'id'      => 'footer-code-editor',
					'title'   => 'Скрипты в подвале',
					'desc'    => 'Вставьте код — он будет добавлен перед тегом &lt;/body&gt;.',
					'type'    => 'code_editor',
					'default' => '<!-- Code footer -->',
				],
			];
		}

		/**
		 * Возвращает значения опции по умолчанию.
		 *
		 * @return array
		 */
		protected function get_defaults(): array {
			return [
				'hide-login-enable'  => 0,
				'hide-login-slug'    => 'login',
				'header-code-editor' => '<!-- Code header -->',
				'footer-code-editor' => '<!-- Code footer -->',
			];
		}

		/**
		 * Валидация входных данных при сохранении настроек (site + network).
		 *
		 * @param  array  $input  Сырые входные данные.
		 *
		 * @return array Санитизированные/подтверждённые значения.
		 */
		public function validate_input( array $input ): array {
			$old      = $this->get_settings();
			$defaults = $this->get_defaults();

			$check_html = static function ( string $html, string $error_code, string $label, array $fallbacks ) {
				if ( trim( $html ) === '' ) {
					return $html;
				}

				libxml_use_internal_errors( true );
				$doc    = new \DOMDocument();
				$loaded = $doc->loadHTML( $html, LIBXML_NOWARNING | LIBXML_NOERROR | LIBXML_NOXMLDECL );
				$errors = libxml_get_errors();
				libxml_clear_errors();

				if ( ! $loaded || ! empty( $errors ) ) {
					add_settings_error( $error_code, $error_code, sprintf( 'Ошибка в синтаксисе HTML для поля «%s». Сохранение отменено.', $label ) );

					return $fallbacks['old'] ?? $fallbacks['default'] ?? '';
				}

				return $html;
			};

			$new_header = isset( $input['header-code-editor'] ) ? (string) $input['header-code-editor'] : ( $old['header-code-editor'] ?? '<!-- Code header -->' );
			$new_footer = isset( $input['footer-code-editor'] ) ? (string) $input['footer-code-editor'] : ( $old['footer-code-editor'] ?? '<!-- Code footer -->' );

			$new_header = $check_html(
				$new_header,
				$this->option_name . '_header_code_error',
				'Скрипты в шапке',
				[ 'old' => $old['header-code-editor'] ?? null, 'default' => '<!-- Code header -->' ]
			);

			$new_footer = $check_html(
				$new_footer,
				$this->option_name . '_footer_code_error',
				'Скрипты в подвале',
				[ 'old' => $old['footer-code-editor'] ?? null, 'default' => '<!-- Code footer -->' ]
			);

			$old_enabled       = (int) ( $old['hide-login-enable'] ?? 0 );
			$enable_hide_login = isset( $input['hide-login-enable'] ) ? (int) ( (bool) $input['hide-login-enable'] ) : $old_enabled;

			$raw_slug = isset( $input['hide-login-slug'] ) ? (string) $input['hide-login-slug'] : ( $old['hide-login-slug'] ?? $defaults['hide-login-slug'] );
			$slug     = strtolower( trim( $raw_slug ) );
			$slug     = preg_replace( '/[^a-z0-9\-]/', '-', $slug );
			$slug     = preg_replace( '/-+/', '-', $slug );
			$slug     = trim( $slug, '-' );

			$reserved = [
				'wp-admin',
				'wp-login',
				'wp-login.php',
				'admin',
				'login',
				'dashboard',
				'wp',
				'xmlrpc',
				'feed',
			];

			if ( $slug === '' || strlen( $slug ) < 3 ) {
				add_settings_error( $this->option_name . '_hide_login_slug', $this->option_name . '_hide_login_slug', 'Слог URL входа слишком короткий. Минимум 3 символа.' );
				$slug = $old['hide-login-slug'] ?? $defaults['hide-login-slug'];
			}

			if ( in_array( $slug, $reserved, true ) ) {
				add_settings_error( $this->option_name . '_hide_login_slug_reserved', $this->option_name . '_hide_login_slug_reserved', 'Вы выбрали зарезервированный URL. Укажите другой.' );
				$slug = $old['hide-login-slug'] ?? 'secure-login';
			}

			if ( isset( $old['hide-login-slug'] ) && $old['hide-login-slug'] !== $slug ) {
				set_transient( 'strt_flush_rewrite_rules', 1, 60 );
			}

			if ( $old_enabled !== $enable_hide_login ) {
				set_transient( 'strt_flush_rewrite_rules', 1, 60 );
			}

			return [
				'header-code-editor' => $new_header,
				'footer-code-editor' => $new_footer,
				'hide-login-enable'  => $enable_hide_login,
				'hide-login-slug'    => $slug ?: $defaults['hide-login-slug'],
			];
		}
	}
}
