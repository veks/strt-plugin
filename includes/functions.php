<?php
/**
 * Общие функции и инфраструктура мультисайта.
 *
 * @package Strt\Plugin
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'strt_is_network_context' ) ) {

	/**
	 * Возвращает true, если текущий UI-контекст — сетевая админка.
	 *
	 * @return bool
	 */
	function strt_is_network_context(): bool {
		return function_exists( 'is_network_admin' ) && is_network_admin();
	}
}

if ( ! function_exists( 'strt_get_option' ) ) {

	/**
	 * Возвращает значение ключа опции плагина из options или site_options в зависимости от контекста.
	 *
	 * @param  string  $option_name  Имя опции.
	 * @param  string  $key  Ключ внутри массива опции (dot-notation допустима, если используете array_get).
	 * @param  mixed|null  $default_value  Значение по умолчанию.
	 *
	 * @return mixed
	 */
	function strt_get_option( string $option_name = '', string $key = '', mixed $default_value = null ): mixed {
		if ( $option_name === '' ) {
			return $default_value;
		}

		$option = strt_is_network_context() ? get_site_option( $option_name ) : get_option( $option_name );

		if ( empty( $option ) ) {
			return $default_value;
		}

		return array_get( $option, $key, $default_value );
	}
}

if ( ! function_exists( 'strt_update_option' ) ) {

	/**
	 * Обновляет значение ключа опции плагина в options или site_options в зависимости от контекста.
	 *
	 * @param  string  $option_name  Имя опции.
	 * @param  string  $key  Ключ внутри массива опции (dot-notation).
	 * @param  mixed  $value  Новое значение.
	 * @param  bool  $autoload  Автозагрузка (только для per-site options).
	 *
	 * @return bool
	 */
	function strt_update_option( string $option_name, string $key, mixed $value = '', bool $autoload = true ): bool {
		if ( $option_name === '' || $key === '' ) {
			return false;
		}

		$option = strt_is_network_context() ? get_site_option( $option_name ) : get_option( $option_name );

		if ( empty( $option ) ) {
			return false;
		}

		$new_value = data_set( $option, $key, $value );

		if ( strt_is_network_context() ) {
			return (bool) update_site_option( $option_name, $new_value );
		}

		return (bool) update_option( $option_name, $new_value, $autoload );
	}
}

if ( ! function_exists( 'strt_get_site_option' ) ) {

	/**
	 * Явное чтение сетевой опции (не зависит от UI-контекста).
	 *
	 * @param  string  $option_name  Имя опции.
	 * @param  mixed|null  $default_value  Значение по умолчанию.
	 *
	 * @return mixed
	 */
	function strt_get_site_option( string $option_name, mixed $default_value = null ): mixed {
		$val = get_site_option( $option_name, null );

		return ( null === $val ) ? $default_value : $val;
	}
}

if ( ! function_exists( 'strt_update_site_option' ) ) {

	/**
	 * Явное обновление сетевой опции (не зависит от UI-контекста).
	 *
	 * @param  string  $option_name  Имя опции.
	 * @param  mixed  $value  Значение.
	 *
	 * @return bool
	 */
	function strt_update_site_option( string $option_name, mixed $value ): bool {
		return (bool) update_site_option( $option_name, $value );
	}
}

if ( ! function_exists( 'strt_delete_site_option' ) ) {

	/**
	 * Явное удаление сетевой опции (не зависит от UI-контекста).
	 *
	 * @param  string  $option_name  Имя опции.
	 *
	 * @return bool
	 */
	function strt_delete_site_option( string $option_name ): bool {
		return (bool) delete_site_option( $option_name );
	}
}

if ( ! function_exists( 'strt_multisite_for_each_site' ) ) {

	/**
	 * Вызывает callback для каждого сайта сети (или для текущего сайта, если мультисайт не активен).
	 *
	 * @param  callable  $callable  Функция вида fn( int $blog_id ) : void.
	 *
	 * @return void
	 */
	function strt_multisite_for_each_site( callable $callable ): void {
		if ( ! is_multisite() ) {
			call_user_func( $callable, (int) get_current_blog_id() );

			return;
		}

		$sites = get_sites( [ 'number' => 0, 'fields' => 'ids' ] );
		foreach ( $sites as $blog_id ) {
			switch_to_blog( (int) $blog_id );
			try {
				call_user_func( $callable, (int) $blog_id );
			}
			finally {
				restore_current_blog();
			}
		}
	}
}

if ( ! function_exists( 'strt_activation_hook' ) ) {
	/**
	 * Обёртка активации плагина.
	 *
	 * При network-активации сначала триггерит сетевой хук ('strt_network_activation'),
	 * затем обходит все сайты и триггерит per-site хук ('strt_activation_hook').
	 *
	 * @return void
	 */
	function strt_activation_hook(): void {
		$network = is_multisite() && ( ( function_exists( 'is_network_admin' ) && is_network_admin() && isset( $_GET['networkwide'] ) ) || ( function_exists( 'is_plugin_active_for_network' ) && is_plugin_active_for_network( plugin_basename( STRT_PLUGIN_FILE ) ) ) );

		if ( $network ) {
			/**
			 * Сетевой хук: отрабатывает один раз для всей сети (например, для site_options).
			 */
			do_action( 'strt_network_activation' );

			strt_multisite_for_each_site( function () {
				do_action( 'strt_activation_hook' );
			} );

			return;
		}

		do_action( 'strt_activation_hook' );
	}
}

if ( ! function_exists( 'strt_deactivation_hook' ) ) {
	/**
	 * Обёртка деактивации плагина (пер-сайт).
	 *
	 * @return void
	 */
	function strt_deactivation_hook(): void {
		do_action( 'strt_deactivation_hook' );
	}
}

if ( ! function_exists( 'strt_uninstall_hook' ) ) {

	/**
	 * Обёртка деинсталляции плагина.
	 *
	 * При network-удалении сначала триггерит сетевой хук ('strt_network_uninstall'),
	 * затем обходит все сайты и триггерит per-site хук ('strt_uninstall_hook').
	 *
	 * @return void
	 */
	function strt_uninstall_hook(): void {
		if ( is_multisite() ) {
			do_action( 'strt_network_uninstall' );

			strt_multisite_for_each_site( function () {
				do_action( 'strt_uninstall_hook' );
			} );

			return;
		}

		do_action( 'strt_uninstall_hook' );
	}
}

if ( ! function_exists( 'strt_locate_template' ) ) {
	/**
	 * Находит путь к шаблону в теме или возвращает путь по умолчанию из плагина.
	 *
	 * @param  string  $template_name  Имя файла шаблона.
	 * @param  string  $template_path  Папка в теме.
	 * @param  string  $default_path  Папка по умолчанию в плагине.
	 *
	 * @return string
	 */
	function strt_locate_template( string $template_name, string $template_path = '', string $default_path = '' ): string {
		if ( ! $template_path ) {
			$template_path = 'templates/';
		}
		if ( ! $default_path ) {
			$default_path = plugin_dir_path( STRT_PLUGIN_FILE ) . 'templates/';
		}

		$template = locate_template( [ $template_path . $template_name, $template_name ] );
		if ( ! $template ) {
			$template = $default_path . $template_name;
		}

		return apply_filters( 'strt_locate_template', $template, $template_name, $template_path, $default_path );
	}
}

if ( ! function_exists( 'strt_get_template' ) ) {
	/**
	 * Подключает шаблон и извлекает переменные из массива.
	 *
	 * @param  string  $template_name  Имя шаблона.
	 * @param  array  $args  Переменные для шаблона.
	 * @param  string  $template_path  Папка в теме.
	 * @param  string  $default_path  Папка по умолчанию в плагине.
	 *
	 * @return void
	 */
	function strt_get_template( string $template_name, array $args = [], string $template_path = '', string $default_path = '' ): void {
		if ( isset( $args ) && is_array( $args ) ) {
			extract( $args ); // phpcs:ignore WordPress.PHP.DontExtract.extract_extract
		}

		$template_file = strt_locate_template( $template_name, $template_path, $default_path );
		if ( ! file_exists( $template_file ) ) {
			_doing_it_wrong( __FUNCTION__, sprintf( '<code>%s</code> не существует.', esc_html( $template_file ) ), '1.0.0' );

			return;
		}

		include $template_file;
	}
}

if ( ! function_exists( 'strt_get_template_html' ) ) {
	/**
	 * Возвращает HTML шаблона как строку.
	 *
	 * @param  string  $template_name  Имя шаблона.
	 * @param  array  $args  Переменные для шаблона.
	 * @param  string  $template_path  Папка в теме.
	 * @param  string  $default_path  Папка в плагине.
	 *
	 * @return string
	 */
	function strt_get_template_html( string $template_name, array $args = [], string $template_path = '', string $default_path = '' ): string {
		ob_start();
		strt_get_template( $template_name, $args, $template_path, $default_path );

		return ob_get_clean();
	}
}

if ( ! function_exists( '_strt_print_r' ) ) {
	/**
	 * Отладочный вывод только для администраторов/в WP_DEBUG.
	 *
	 * @param  mixed  $value  Данные.
	 * @param  bool  $return  Вернуть вместо вывода.
	 *
	 * @return string|null
	 */
	function _strt_print_r( mixed $value, bool $return = false ): ?string {
		if ( ! current_user_can( 'administrator' ) && ! ( defined( 'WP_DEBUG' ) && WP_DEBUG ) ) {
			return '';
		}

		$output = print_r( $value, true ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_print_r
		$output = '<pre>' . esc_html( $output ) . '</pre>';

		if ( $return ) {
			return $output;
		}

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo $output;

		return '';
	}
}

if ( ! function_exists( 'strt_array_to_css_classes' ) ) {

	/**
	 * Преобразует массив в строку CSS-классов с использованием хелпера Laravel.
	 *
	 * @param  array|string  $classes  Массив классов или строка.
	 *
	 * @return string Строка CSS-классов, разделённых пробелами.
	 */
	function strt_array_to_css_classes( array|string $classes ): string {
		if ( is_string( $classes ) ) {
			return trim( $classes );
		}

		if ( ! is_array( $classes ) ) {
			return '';
		}

		return Illuminate\Support\Arr::toCssClasses( $classes );
	}
}

if ( ! function_exists( 'strt_tg_send_message' ) ) {
	/**
	 * Отправляет сообщение в Telegram указанным получателям (из настроек).
	 *
	 * @param  string  $text  Текст.
	 * @param  array  $params  Параметры API.
	 *
	 * @return array{success:bool,results:array,message:string}
	 */
	function strt_tg_send_message( string $text, array $params = [] ): array {
		if ( ! $text ) {
			return [ 'success' => false, 'results' => [], 'message' => 'Пустое сообщение.' ];
		}

		$token       = strt_get_option( 'strt_settings_telegram', 'token', '' );
		$enabled     = strt_get_option( 'strt_settings_telegram', 'enabled', 0 );
		$admins      = strt_get_option( 'strt_settings_telegram', 'admin_ids', '' );
		$subscribers = get_option( 'strt_tg_subscribers', [] ); // per-site (если нужно — сделайте site_option)

		$admin_ids = array_filter( array_map( 'trim', explode( ',', (string) $admins ) ) );
		$chat_ids  = $admin_ids;

		if ( empty( $enabled ) ) {
			return [ 'success' => false, 'results' => [], 'message' => 'Интеграция Telegram отключена.' ];
		}
		if ( empty( $token ) || empty( $chat_ids ) ) {
			return [ 'success' => false, 'results' => [], 'message' => 'Не указан токен или нет получателей.' ];
		}

		$results = [];
		foreach ( $chat_ids as $chat_id ) {
			$api                 = new \Strt\Plugin\Services\Telegram\TelegramApi( $token, $chat_id );
			$results[ $chat_id ] = $api->send_message( $text, $params );
		}

		$all_ok = ! in_array( false, $results, true );

		return [ 'success' => $all_ok, 'results' => $results, 'message' => $all_ok ? 'Отправлено всем подписчикам!' : 'Ошибка отправки хотя бы одному получателю.' ];
	}
}

add_action( 'wpmu_new_blog', function ( $blog_id ) {
	switch_to_blog( (int) $blog_id );
	do_action( 'strt_activation_hook' );
	restore_current_blog();
}, 10, 1 );

add_action( 'wpmu_delete_blog', function ( $blog_id ) {
	switch_to_blog( (int) $blog_id );
	do_action( 'strt_uninstall_hook' );
	restore_current_blog();
}, 10, 1 );
