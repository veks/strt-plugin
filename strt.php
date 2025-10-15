<?php
/*
 * Plugin Name:       STRT Project
 * Plugin URI:        https://isvek.ru/
 * Description:       Плагин STRT.
 * Version:           1.0.0
 * Requires at least: 6.8
 * Requires PHP:      8.3 
 * Author:            veks
 * Author URI:        https://github.com/veks
 * License:           GNU General Public License v3 or later
 * License URI:       https://www.gnu.org/licenses/gpl-3.0.html
 * Network:           true
 */

defined( 'ABSPATH' ) || exit;

/**
 * Константы
 */
if ( ! defined( 'STRT_PLUGIN_FILE' ) ) {
	define( 'STRT_PLUGIN_FILE', __FILE__ );
}

if ( ! defined( 'STRT_PLUGIN_VERSION' ) ) {
	define( 'STRT_PLUGIN_VERSION', '1.0.0' );
}

if ( ! defined( 'STRT_PLUGIN_VERSION_DB' ) ) {
	define( 'STRT_PLUGIN_VERSION_DB', '106' );
}

if ( ! defined( 'STRT_PLUGIN_OPTION_NAME' ) ) {
	define( 'STRT_PLUGIN_OPTION_NAME', 'strt-plugin' );
}

if ( ! defined( 'STRT_PLUGIN_DEBUG' ) ) {
	define( 'STRT_PLUGIN_DEBUG', true );
}

if ( ! defined( 'STRT_IS_NETWORK' ) ) {
	define( 'STRT_IS_NETWORK', function_exists('is_multisite') && is_multisite() );
}

// Проверяем версию PHP
if ( version_compare( PHP_VERSION, '8.3.0', '<' ) ) {
	add_action( 'admin_notices', function () {
		echo '<div class="notice notice-error is-dismissible"><p>STRT: Ваша версия PHP ниже 8.3.0. Пожалуйста, обновите её для использования плагина.</p></div>';
	} );

	return;
}

// Автозагрузка через Composer
$autoload_path = __DIR__ . '/vendor/autoload.php';

if ( is_readable( $autoload_path ) ) {
	require $autoload_path;
} else {
	add_action( 'admin_notices', function () {
		echo '<div class="notice notice-error is-dismissible"><p>STRT: Не найден autoload.php. Проверьте установку Composer.</p></div>';
	} );

	return;
}

use Strt\Plugin\Setup\Container;
use Strt\Plugin\Setup\ServiceProvider;

if ( ! class_exists( 'StrtContainer' ) ) {

	/**
	 * StartContainer класс для хранения экземпляра контейнера зависимостей.
	 */
	final class StrtContainer {

		/**
		 * Единственный экземпляр контейнера.
		 *
		 * @var Container|null
		 */
		private static ?Container $instance = null;

		/**
		 * Получает или создаёт экземпляр контейнера.
		 *
		 * @return Container Экземпляр контейнера.
		 */
		public static function getInstance(): Container {
			if ( null === self::$instance ) {
				self::$instance = new Container();
			}

			return self::$instance;
		}
	}
}

if ( ! function_exists( 'strt_get_container' ) ) {

	/**
	 * Возвращает глобальный экземпляр контейнера.
	 *
	 * @return Container Экземпляр контейнера.
	 */
	function strt_get_container(): Container {
		return StrtContainer::getInstance();
	}
}

// Инициализация сервис-провайдера
try {

	register_activation_hook( STRT_PLUGIN_FILE, 'strt_activation_hook' );
	register_deactivation_hook( STRT_PLUGIN_FILE, 'strt_deactivation_hook' );
	register_uninstall_hook( STRT_PLUGIN_FILE, 'strt_uninstall_hook' );

	add_action( 'plugins_loaded', function () {
		try {
			$service_provider = new ServiceProvider( strt_get_container() );
			$service_provider->register();
			$service_provider->boot();
		} catch ( Exception $error ) {
			add_action( 'admin_notices', function () use ( $error ) {
				printf( '<div class="notice notice-error is-dismissible"><p>STRT: %s</p></div>', esc_html( $error->getMessage() ) );
			} );
		}
	} );
} catch ( Exception $error ) {
	add_action( 'admin_notices', function () use ( $error ) {
		printf( '<div class="notice notice-error is-dismissible"><p>STRT: %s</p></div>', esc_html( $error->getMessage() ) );
	} );
}