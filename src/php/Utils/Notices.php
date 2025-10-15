<?php
/**
 * Класс для управления уведомлениями.
 *
 * Этот класс предоставляет методы для добавления, отображения и управления уведомлениями
 * в административной панели WordPress. Уведомления могут быть различного типа
 * (ошибки, предупреждения, информационные сообщения) и поддерживают возможность закрытия.
 *
 * @class   Notices
 * @package Strt\Plugin\Utils
 * @version 1.0.0
 */

namespace Strt\Plugin\Utils;

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'Strt\Plugin\Utils\Notices' ) ) {

	/**
	 * Класс Notices.
	 *
	 * Уведомления в панели администратора.
	 */
	class Notices {

		/**
		 * Имя опции уведомления.
		 *
		 * @var string
		 */
		protected static string $option_name = 'strt-plugin-notices';

		/**
		 * Подключает основные действия и фильтры для настройки темы.
		 *
		 * @return void
		 */
		public function init(): void {
			add_action( 'strt_plugin_notices', [ $this, 'display' ], 12 );
			add_action( 'admin_notices', [ $this, 'display' ], 12 );
		}

		/**
		 * Добавляет уведомление в список опций.
		 *
		 * @param  string  $setting  Наименование настройки уведомления.
		 * @param  string  $notice  Текст уведомления.
		 * @param  string  $type  Тип уведомления (по умолчанию 'error').
		 * @param  bool  $dismissible  Можно ли закрыть уведомление (по умолчанию true).
		 */
		public static function add( string $setting = '', string $notice = '', string $type = 'error', bool $dismissible = true ): void {
			$notices             = get_option( self::$option_name, [] );
			$dismissible_text    = ( $dismissible ) ? 'is-dismissible' : '';
			$notices[ $setting ] = [
				'notice'      => $notice,
				'type'        => $type,
				'dismissible' => $dismissible_text,
			];

			update_option( self::$option_name, $notices );
		}

		/**
		 * Отображение уведомлений.
		 *
		 * @return void
		 */
		public static function display(): void {
			$notices = get_option( self::$option_name, [] );

			if ( ! empty( $notices ) ) {
				foreach ( $notices as $notice ) {
					printf(
						'<div class="notice notice-%1$s %2$s"><p>%3$s</p></div>',
						esc_attr( $notice['type'] ),
						esc_attr( $notice['dismissible'] ),
						esc_html( $notice['notice'] )
					);
				}
			}

			if ( ! empty( $notices ) ) {
				update_option( self::$option_name, [] );
			}
		}

		/**
		 * Получает определенное уведомление по его наименованию.
		 *
		 * @param  string  $setting  Наименование уведомления для получения.
		 *
		 * @return void
		 */
		public static function get( string $setting = '' ): void {
			$notices = get_option( self::$option_name, [] );

			if ( '' !== $setting && isset( $notices[ $setting ] ) ) {
				$notice = $notices[ $setting ];

				printf(
					'<div class="notice notice-%1$s %2$s"><p>%3$s</p></div>',
					esc_attr( $notice['type'] ),
					esc_attr( $notice['dismissible'] ),
					esc_html( $notice['notice'] )
				);
			}

			if ( ! empty( $notices ) ) {
				update_option( self::$option_name, [] );
			}
		}
	}
}