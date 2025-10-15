<?php
/**
 * Вкладка настроек изображений.
 *
 * @class   ImageSettingsTab
 * @package Strt\Plugin\Admin\Tabs
 * @version 1.0.0
 */

namespace Strt\Plugin\Admin\Tabs;

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( __NAMESPACE__ . '\\ImageSettingsTab', false ) ) {

	/**
	 * Класс ImageSettingsTab.
	 *
	 * Вкладка управления обработкой изображений (движок, качество, форматы и пр.)
	 * с поддержкой Multisite (per-site и общие для сети).
	 */
	class ImageSettingsTab extends AbstractSiteSettingsTab {

		/**
		 * @var string
		 *
		 * Идентификатор вкладки.
		 */
		protected string $id = 'image';

		/**
		 * @var string
		 *
		 * Заголовок вкладки.
		 */
		protected string $label = 'Настройки изображений';

		/**
		 * @var string
		 *
		 * Группа опций (per-site Settings API).
		 */
		protected string $option_group = 'strt_settings_option_group_image';

		/**
		 * @var string
		 *
		 * Имя per-site опции (wp_options).
		 */
		protected string $option_name = 'strt_settings_image';

		/**
		 * @var string
		 *
		 * Идентификатор секции.
		 */
		protected string $section_id = 'strt_settings_section_image';

		/**
		 * @var string
		 *
		 * Заголовок секции.
		 */
		protected string $section_title = 'Настройки изображений';

		/**
		 * Поля вкладки.
		 *
		 * @return array
		 */
		public function get_fields(): array {
			return [
				[
					'id'      => 'enable_image_editor',
					'title'   => 'Расширенная обработка изображений',
					'label'   => 'Включить расширенную обработку',
					'type'    => 'checkbox',
					'default' => 0,
					'desc'    => 'Если выключено — используются стандартные редакторы WordPress.',
				],
				[
					'id'      => 'support_svg',
					'title'   => 'Поддержка SVG',
					'label'   => 'Разрешить загрузку SVG',
					'type'    => 'checkbox',
					'default' => 0,
					'desc'    => 'SVG — это код. Загружайте только доверенные файлы (рекомендована санитизация).',
				],
				[
					'id'      => 'image_editor',
					'title'   => 'Редактор изображений',
					'type'    => 'select',
					'choices' => [
						'gd'      => 'GD Library',
						'imagick' => 'Imagick (рекомендуется)',
					],
					'default' => 'imagick',
					'desc'    => 'Imagick даёт лучшее качество и поддержку форматов.',
				],
				[
					'id'         => 'quality',
					'title'      => 'Общее качество (%)',
					'type'       => 'number',
					'attributes' => [
						'min' => 1,
						'max' => 100,
					],
					'default'    => 60,
					'desc'       => 'Рекомендуется 60–90: баланс качества и размера.',
				],
				[
					'id'      => 'output_formats',
					'title'   => 'Форматы конвертирования',
					'type'    => 'checkboxes',
					'choices' => [
						'image/avif' => 'AVIF (макс. сжатие, современные браузеры)',
						'image/webp' => 'WebP (широкая поддержка)',
						'image/jpeg' => 'JPEG (классический)',
						'image/png'  => 'PNG (без потерь)',
					],
					'default' => [ 'image/avif', 'image/webp', 'image/jpeg', 'image/png' ],
					'desc'    => 'При загрузке JPEG/PNG/WebP создадим версии в выбранных форматах (если поддерживается).',
				],
				[
					'id'         => 'avif_speed',
					'title'      => 'AVIF: скорость сжатия',
					'type'       => 'number',
					'attributes' => [
						'min' => 1,
						'max' => 10,
					],
					'default'    => 3,
					'desc'       => '1 — качество выше/медленнее, 10 — быстрее/хуже. Требует Imagick.',
				],
				[
					'id'         => 'avif_preset',
					'title'      => 'AVIF: пресет сжатия',
					'type'       => 'number',
					'default'    => 6,
					'attributes' => [
						'min' => 0,
						'max' => 9,
					],
					'desc'       => '0 — max качество, 9 — max скорость. Рекомендуется 6. Требует Imagick.',
				],
				[
					'id'      => 'strip_metadata',
					'title'   => 'Удалять метаданные',
					'type'    => 'checkbox',
					'default' => 0,
					'desc'    => 'Удаление EXIF/ICC профилей для уменьшения размера. Требует Imagick.',
				],
			];
		}

		/**
		 * Возвращает значения опции по умолчанию.
		 *
		 * @return array
		 */
		public function get_defaults(): array {
			return [
				'enable_image_editor' => 0,
				'support_svg'         => 0,
				'image_editor'        => 'imagick',
				'quality'             => 60,
				'output_formats'      => [ 'image/avif', 'image/webp', 'image/jpeg', 'image/png' ],
				'avif_speed'          => 3,
				'avif_preset'         => 6,
				'strip_metadata'      => 0,
			];
		}

		/**
		 * Валидация и санитизация входных данных при сохранении.
		 *
		 * @param  array  $input  Входные значения из формы настроек.
		 *
		 * @return array Санитизированные значения.
		 */
		public function validate_input( array $input ): array {
			$old = $this->get_settings();

			$enable_image_editor = empty( $input['enable_image_editor'] ) ? 0 : 1;
			$support_svg         = empty( $input['support_svg'] ) ? 0 : 1;
			$strip_metadata      = empty( $input['strip_metadata'] ) ? 0 : 1;

			$editor_allowed = [ 'gd', 'imagick' ];
			$image_editor   = isset( $input['image_editor'] ) && in_array( $input['image_editor'], $editor_allowed, true ) ? (string) $input['image_editor'] : (string) ( $old['image_editor'] ?? 'imagick' );

			$quality = isset( $input['quality'] ) ? (int) $input['quality'] : (int) ( $old['quality'] ?? 60 );

			if ( $quality < 1 ) {
				$quality = 1;
			}

			if ( $quality > 100 ) {
				$quality = 100;
			}

			$avif_speed = isset( $input['avif_speed'] ) ? (int) $input['avif_speed'] : (int) ( $old['avif_speed'] ?? 3 );

			if ( $avif_speed < 1 ) {
				$avif_speed = 1;
			}

			if ( $avif_speed > 10 ) {
				$avif_speed = 10;
			}

			$avif_preset = isset( $input['avif_preset'] ) ? (int) $input['avif_preset'] : (int) ( $old['avif_preset'] ?? 6 );

			if ( $avif_preset < 0 ) {
				$avif_preset = 0;
			}
			if ( $avif_preset > 9 ) {
				$avif_preset = 9;
			}

			$allowed_mimes  = [ 'image/avif', 'image/webp', 'image/jpeg', 'image/png' ];
			$output_formats = [];

			if ( ! empty( $input['output_formats'] ) && is_array( $input['output_formats'] ) ) {
				foreach ( $input['output_formats'] as $mime ) {
					if ( in_array( $mime, $allowed_mimes, true ) ) {
						$output_formats[] = (string) $mime;
					}
				}
				$output_formats = array_values( array_unique( $output_formats ) );
			} else {
				$output_formats = is_array( $old['output_formats'] ?? null ) ? $old['output_formats'] : [ 'image/avif', 'image/webp', 'image/jpeg', 'image/png' ];
			}

			return [
				'enable_image_editor' => (int) $enable_image_editor,
				'support_svg'         => (int) $support_svg,
				'image_editor'        => $image_editor,
				'quality'             => (int) $quality,
				'output_formats'      => $output_formats,
				'avif_speed'          => (int) $avif_speed,
				'avif_preset'         => (int) $avif_preset,
				'strip_metadata'      => (int) $strip_metadata,
			];
		}
	}
}
