<?php
/**
 * Класс Fields для управления полями настроек.
 *
 * @class  Fields
 * @package Strt\Plugin\Utils
 */

namespace Strt\Plugin\Utils;

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'Strt\Plugin\Utils\Fields' ) ) {

	class Fields {

		/**
		 * ID label_for.
		 *
		 * @var string
		 */
		protected string $label_for;

		/**
		 * Конфиг поля как объект.
		 *
		 * @var object
		 */
		protected object $field;

		/**
		 * Имя опции (site или network).
		 *
		 * @var string
		 */
		protected string $option_name;

		/**
		 * Текущее значение поля.
		 *
		 * @var mixed
		 */
		protected mixed $value = '';

		/**
		 * Дефолтные параметры поля.
		 *
		 * @var array
		 */
		private array $field_defaults = [
			'id'               => '',
			'title'            => '',
			'desc'             => '',
			'conditional_desc' => [],
			'default'          => null,
			'type'             => 'text',
			'placeholder'      => '',
			'choices'          => [],
			'class'            => 'regular-text code',
			'disabled'         => false,
			'readonly'         => false,
			'autocomplete'     => 'off',
			'attributes'       => [],
			'custom_args'      => [],
			'settings'         => [],
		];

		/**
		 * Helper.
		 *
		 * @var \Strt\Plugin\Utils\Helper
		 */
		protected Helper $helper;

		/**
		 * Конструктор.
		 *
		 * @param  array  $args  Аргументы.
		 */
		public function __construct( array $args = [] ) {
			$this->helper = new Helper();

			if ( ! empty( $args['option_name'] ) && is_string( $args['option_name'] ) ) {
				$this->option_name = $args['option_name'];
			}

			if ( ! empty( $args['field'] ) && is_array( $args['field'] ) ) {
				$this->field = (object) wp_parse_args( $args['field'], $this->field_defaults );
			}

			if ( ! empty( $args['label_for'] ) ) {
				$this->label_for = $args['label_for'];
			}

			if ( isset( $this->option_name, $this->field ) && ! empty( $this->field->id ) ) {
				$is_network = function_exists( 'is_network_admin' ) && is_network_admin();
				$raw        = $is_network ? get_site_option( $this->option_name ) : get_option( $this->option_name );

				$raw         = is_array( $raw ) ? $raw : [];
				$this->value = array_get( $raw, $this->field->id, $this->field->default );
			}

			if ( isset( $this->option_name, $this->field, $this->label_for ) ) {
				$this->display_field();
			}
		}

		/**
		 * Рендер поля по типу.
		 *
		 * @return void
		 */
		public function display_field(): void {
			switch ( $this->field->type ) {
				case 'textarea':
					$this->field_textarea();
					break;
				case 'select':
				case 'multiselect':
				case 'select_2':
					$this->field_select();
					break;
				case 'checkbox':
					$this->field_checkbox();
					break;
				case 'checkboxes':
					$this->field_checkboxes();
					break;
				case 'radio':
					$this->field_radio();
					break;
				case 'wysiwyg':
					$this->field_wysiwyg();
					break;
				case 'code_editor':
					$this->field_code_editor();
					break;
				case 'color':
					$this->field_color();
					break;
				case 'hidden':
					$this->field_hidden();
					break;
				case 'file_select':
					$this->field_file_select();
					break;
				case 'image_select':
					$this->field_image_select();
					break;
				case 'text':
				case 'password':
				case 'email':
				case 'url':
				case 'tel':
				case 'number':
				case 'date':
				case 'datetime-local':
				case 'month':
				case 'week':
				case 'time':
				default:
					$this->field_default();
					break;
			}

			$this->description();
		}

		/**
		 * textarea.
		 *
		 * @return void
		 */
		protected function field_textarea(): void {
			if ( $this->field->disabled === true ) {
				$this->field->attributes['disabled'] = 'disabled';
			}
			if ( $this->field->readonly === true ) {
				$this->field->attributes['readonly'] = '';
			}

			$this->field->attributes['rows'] = ! empty( $this->field->attributes['rows'] ) ? absint( $this->field->attributes['rows'] ) : 5;
			$this->field->attributes['cols'] = ! empty( $this->field->attributes['cols'] ) ? absint( $this->field->attributes['cols'] ) : 40;
			$this->field->class              = strt_array_to_css_classes( [ $this->field->class, 'large-text', 'code' ] );

			printf(
				'<textarea name="%1$s" id="%2$s" class="%3$s" placeholder="%4$s" autocomplete="%5$s" %6$s>%7$s</textarea>',
				esc_attr( $this->generate_field_name() ),
				esc_attr( $this->generate_field_id() ),
				esc_attr( $this->field->class ),
				esc_attr( $this->field->placeholder ),
				esc_attr( $this->field->autocomplete ),
				$this->array_to_html_atts( $this->field->attributes ),
				esc_textarea( (string) $this->value )
			);
		}

		/**
		 * select / multiselect / select2.
		 *
		 * @return void
		 */
		protected function field_select(): void {
			if ( $this->field->disabled === true ) {
				$this->field->attributes['disabled'] = 'disabled';
			}
			if ( $this->field->readonly === true ) {
				$this->field->attributes['readonly'] = '';
			}

			$this->field->class = str_replace( 'regular-text', '', $this->field->class );

			if ( in_array( $this->field->type, [ 'select_2', 'multiselect' ], true ) ) {
				$this->field->attributes['multiple'] = 'multiple';
				$name                                = $this->generate_field_name() . '[]';
				if ( 'select_2' === $this->field->type ) {
					$this->field->class = strt_array_to_css_classes( [ $this->field->class, 'select-2' ] );
					$this->select_2_enqueue();
				}
			} else {
				$name = $this->generate_field_name();
			}

			printf(
				'<select name="%1$s" id="%2$s" class="%3$s" %4$s>',
				esc_attr( $name ),
				esc_attr( $this->generate_field_id() ),
				esc_attr( $this->field->class ),
				$this->array_to_html_atts( $this->field->attributes )
			);

			if ( ! empty( $this->field->choices ) ) {
				foreach ( (array) $this->field->choices as $key => $val ) {
					if ( is_array( $val ) ) {
						printf( '<optgroup label="%s">', esc_html( (string) $key ) );
						foreach ( $val as $gk => $gv ) {
							$disabled = ! empty( $this->field->disabled ) && is_array( $this->field->disabled ) && in_array( (string) $gk, $this->field->disabled, true ) ? disabled( true, true, false ) : '';
							$selected = selected( is_array( $this->value ) && in_array( (string) $gk, $this->value, true ), true, false );
							printf(
								'<option value="%1$s" %2$s %3$s>%4$s</option>',
								esc_attr( (string) $gk ),
								$disabled,
								$selected,
								esc_html( (string) $gv )
							);
						}
						echo '</optgroup>';
						continue;
					}

					$selected = is_array( $this->value )
						? selected( in_array( (string) $key, $this->value, true ), true, false )
						: selected( (string) $this->value, (string) $key, false );
					$disabled = ! empty( $this->field->disabled ) && is_array( $this->field->disabled ) && in_array( (string) $key, $this->field->disabled, true ) ? disabled( true, true, false ) : '';

					printf(
						'<option value="%1$s" %2$s %3$s>%4$s</option>',
						esc_attr( (string) $key ),
						$selected,
						$disabled,
						esc_html( (string) $val )
					);
				}
			}

			echo '</select>';
		}

		/**
		 * checkbox.
		 *
		 * @return void
		 */
		protected function field_checkbox(): void {
			if ( $this->field->disabled === true ) {
				$this->field->attributes['disabled'] = 'disabled';
			}
			if ( $this->field->readonly === true ) {
				$this->field->attributes['readonly'] = '';
			}

			$value = (string) ( $this->value ?? '0' );
			$label = ! empty( $this->field->label ) ? $this->field->label : $this->field->title;
			$desc  = is_string( $this->field->conditional_desc ) ? '<p class="description">' . $this->field->conditional_desc . '</p>' : '';
			$class = trim( preg_replace( '/\s+/', ' ', str_replace( [ 'regular-text', 'code' ], '', $this->field->class ) ) );

			echo '<fieldset>';
			printf( '<input type="hidden" name="%1$s" value="0" />', esc_attr( $this->generate_field_name() ) );
			printf(
				'<input type="checkbox" name="%1$s" value="1" id="%2$s" class="%3$s" %4$s %5$s />',
				esc_attr( $this->generate_field_name() ),
				esc_attr( $this->generate_field_id() ),
				esc_attr( $class ),
				checked( '1', $value, false ),
				$this->array_to_html_atts( $this->field->attributes )
			);
			printf(
				'<label for="%1$s">%2$s</label>%3$s',
				esc_attr( $this->generate_field_id() ),
				esc_html( (string) $label ),
				$desc
			);
			echo '</fieldset>';
		}

		/**
		 * checkboxes (мультивыбор).
		 *
		 * @return void
		 */
		protected function field_checkboxes(): void {
			if ( $this->field->disabled === true ) {
				$this->field->attributes['disabled'] = 'disabled';
			}
			if ( $this->field->readonly === true ) {
				$this->field->attributes['readonly'] = '';
			}

			if ( empty( $this->field->choices ) ) {
				return;
			}

			$desc_map = is_array( $this->field->conditional_desc ) ? $this->field->conditional_desc : [];
			$class    = trim( preg_replace( '/\s+/', ' ', str_replace( [ 'regular-text', 'code' ], '', $this->field->class ) ) );

			printf( '<input type="hidden" name="%s" value="0" />', esc_attr( $this->generate_field_name() ) );

			echo '<fieldset><ul>';
			foreach ( $this->field->choices as $key => $val ) {
				if ( is_array( $val ) ) {
					continue;
				}

				$checked   = is_array( $this->value )
					? checked( in_array( (string) $key, $this->value, true ), true, false )
					: checked( (string) $this->value, (string) $key, false );
				$desc_item = ! empty( $desc_map[ $key ] ) ? '<p class="description">' . $desc_map[ $key ] . '</p>' : '';

				printf(
					'<li><label><input type="checkbox" name="%1$s[]" value="%2$s" id="%3$s" class="%4$s" %5$s %6$s /> %7$s</label>%8$s</li>',
					esc_attr( $this->generate_field_name() ),
					esc_attr( (string) $key ),
					esc_attr( $this->generate_field_id() ),
					esc_attr( $class ),
					$checked,
					$this->array_to_html_atts( $this->field->attributes ),
					esc_html( (string) $val ),
					$desc_item
				);
			}
			echo '</ul></fieldset>';
		}

		/**
		 * radio.
		 *
		 * @return void
		 */
		protected function field_radio(): void {
			if ( $this->field->disabled === true ) {
				$this->field->attributes['disabled'] = 'disabled';
			}
			if ( $this->field->readonly === true ) {
				$this->field->attributes['readonly'] = '';
			}

			if ( empty( $this->field->choices ) ) {
				return;
			}

			$desc_map = is_array( $this->field->conditional_desc ) ? $this->field->conditional_desc : [];
			$class    = trim( preg_replace( '/\s+/', ' ', str_replace( [ 'regular-text', 'code' ], '', $this->field->class ) ) );

			echo '<fieldset><ul>';
			foreach ( $this->field->choices as $key => $val ) {
				if ( is_array( $val ) ) {
					continue;
				}
				$desc_item = ! empty( $desc_map[ $key ] ) ? '<p class="description">' . $desc_map[ $key ] . '</p>' : '<br>';

				printf(
					'<li><label><input type="radio" name="%1$s" value="%2$s" id="%3$s" class="%4$s" %5$s %6$s />%7$s</label>%8$s</li>',
					esc_attr( $this->generate_field_name() ),
					esc_attr( (string) $key ),
					esc_attr( $this->generate_field_id() ),
					esc_attr( $class ),
					checked( (string) $key, (string) $this->value, false ),
					$this->array_to_html_atts( $this->field->attributes ),
					esc_html( (string) $val ),
					$desc_item
				);
			}
			echo '</ul></fieldset>';
		}

		/**
		 * WYSIWYG.
		 *
		 * @return void
		 */
		protected function field_wysiwyg(): void {
			$settings                  = wp_parse_args(
				$this->field->settings,
				[
					'wpautop'          => 1,
					'media_buttons'    => 1,
					'textarea_name'    => '',
					'textarea_rows'    => 20,
					'tabindex'         => null,
					'editor_css'       => '',
					'editor_class'     => esc_attr( $this->field->class ),
					'teeny'            => 0,
					'dfw'              => 0,
					'tinymce'          => 1,
					'quicktags'        => 1,
					'drag_drop_upload' => false,
				]
			);
			$settings['textarea_name'] = $this->generate_field_name();

			wp_editor( (string) $this->value, $this->generate_field_id(), $settings );
		}

		/**
		 * Code editor (CodeMirror).
		 *
		 * @return void
		 */
		protected function field_code_editor(): void {
			if ( $this->field->disabled === true ) {
				$this->field->attributes['disabled'] = 'disabled';
			}
			if ( $this->field->readonly === true ) {
				$this->field->attributes['readonly'] = '';
			}

			$settings = wp_enqueue_code_editor(
				wp_parse_args(
					$this->field->settings,
					[
						'type'       => 'text/html',
						'codemirror' => [
							'indentUnit'     => 2,
							'tabSize'        => 2,
							'autoRefresh'    => true,
							'lineWrapping'   => true,
							'indentWithTabs' => true,
						],
					]
				)
			);

			$this->field->attributes['rows'] = ! empty( $this->field->attributes['rows'] ) ? absint( $this->field->attributes['rows'] ) : 5;
			$this->field->attributes['cols'] = ! empty( $this->field->attributes['cols'] ) ? absint( $this->field->attributes['cols'] ) : 40;
			$this->field->class              = strt_array_to_css_classes( [ $this->field->class, 'large-text', 'code' ] );

			printf(
				'<textarea name="%1$s" id="%2$s" class="%3$s" placeholder="%4$s" autocomplete="%5$s" %6$s>%7$s</textarea>',
				esc_attr( $this->generate_field_name() ),
				esc_attr( $this->generate_field_id() ),
				esc_attr( $this->field->class ),
				esc_attr( $this->field->placeholder ),
				esc_attr( $this->field->autocomplete ),
				/* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped */
				$this->array_to_html_atts( $this->field->attributes ),
				esc_textarea( $this->value )
			);

			wp_add_inline_script(
				'code-editor',
				sprintf(
					'jQuery(function($){
            var editorSettings = %2$s;
            var textarea = $("#%1$s");
            var editorObj = wp.codeEditor.initialize(textarea, editorSettings).codemirror;

            function hasLintErrors(editor){
                if(!editor || !editor.state.lint || !editor.state.lint.marked) return false;
                var count = 0;
                $.each(editor.state.lint.marked, function(_, mark){
                    if (mark.__annotation && mark.__annotation.severity === "error") count++;
                });
                return count > 0;
            }

            function showLintErrorMsg(show){
                var msgClass = "code-editor-error";
                if (show){
                    if (textarea.siblings("."+msgClass).length === 0){
                        textarea.after("<div class=\'" + msgClass + "\' style=\'color:#dc3232; margin-top:5px;\'><b>Ошибка синтаксиса!</b> Исправьте код перед сохранением.</div>");
                    }
                } else {
                    textarea.siblings("."+msgClass).remove();
                }
            }

            textarea.closest("form").on("submit", function(e){
                if (hasLintErrors(editorObj)){
                    e.preventDefault();
                    showLintErrorMsg(true);
                    textarea.focus();
                    var scrollTarget = textarea.closest(".form-table, .form-field, .wp-core-ui");
                    if (!scrollTarget.length) scrollTarget = textarea;
                    $("html, body").animate({scrollTop: scrollTarget.offset().top - 60}, 400);
                    return false;
                } else {
                    showLintErrorMsg(false);
                }
            });

            editorObj.on("change", function(){
                showLintErrorMsg(hasLintErrors(editorObj));
            });
        });',
					esc_js( $this->generate_field_id() ),
					wp_json_encode( $settings ?: [] )
				)
			);
		}

		/**
		 * color.
		 *
		 * @return void
		 */
		protected function field_color(): void {
			if ( $this->field->disabled === true ) {
				$this->field->attributes['disabled'] = 'disabled';
			}
			if ( $this->field->readonly === true ) {
				$this->field->attributes['readonly'] = '';
			}

			wp_enqueue_script( 'farbtastic' );

			echo '<div style="position:relative;">';
			printf(
				'<input type="text" name="%1$s" id="%2$s" value="%3$s" class="%4$s" %5$s />',
				esc_attr( $this->generate_field_name() ),
				esc_attr( $this->generate_field_id() ),
				esc_attr( (string) $this->value ),
				esc_attr( $this->field->class ),
				$this->array_to_html_atts( (array) $this->field->attributes )
			);
			printf( '<div id="color-%s" style="position:absolute;top:0;left:190px;background:#fff;z-index:9999;"></div>', esc_attr( $this->generate_field_id() ) );
			printf(
				'<script>jQuery(function($){var p=$("#color-%1$s");p.farbtastic("#%1$s");p.hide();$("#%1$s").on("focus",function(){p.show();}).on("blur",function(){p.hide();if($(this).val()=="")$(this).val("#");});});</script>',
				esc_attr( $this->generate_field_id() )
			);
			echo '</div>';
		}

		/**
		 * hidden.
		 *
		 * @return void
		 */
		protected function field_hidden(): void {
			printf(
				'<input type="hidden" name="%1$s" value="%2$s" id="%3$s" class="%4$s" %5$s />',
				esc_attr( $this->generate_field_name() ),
				esc_attr( (string) $this->value ),
				esc_attr( $this->generate_field_id() ),
				esc_attr( $this->field->class ),
				$this->array_to_html_atts( (array) $this->field->attributes )
			);
		}

		/**
		 * file_select (медиабиблиотека).
		 *
		 * @return void
		 */
		protected function field_file_select(): void {
			if ( $this->field->disabled === true ) {
				$this->field->attributes['disabled'] = 'disabled';
			}
			if ( $this->field->readonly === true ) {
				$this->field->attributes['readonly'] = '';
			}

			wp_enqueue_media();

			$settings = wp_parse_args( $this->field->settings, [ 'attachment' => 'url' ] );
			wp_localize_script( $this->helper::handle( 'admin-settings' ), 'strt_admin_settings', $settings );

			printf( '<div class="file-select %s">', esc_attr( $this->generate_field_id() ) );
			printf(
				'<input type="text" name="%1$s" value="%2$s" id="%3$s" class="%4$s file-select-input" %5$s />',
				esc_attr( $this->generate_field_name() ),
				esc_attr( (string) $this->value ),
				esc_attr( $this->generate_field_id() ),
				esc_attr( $this->field->class ),
				$this->array_to_html_atts( (array) $this->field->attributes )
			);
			echo '<div style="margin-top:.5rem">';
			printf(
				'<a href="#" id="%1$s" class="button-secondary file-select-button" style="margin-right:.5rem">%2$s</a>',
				esc_attr( $this->generate_field_id() ),
				esc_html( ! empty( $this->value ) ? 'Изменить' : 'Добавить' )
			);
			printf(
				'<a href="#" id="%1$s" class="button-secondary file-delete-button%2$s">Удалить</a>',
				esc_attr( $this->generate_field_id() ),
				empty( $this->value ) ? ' hidden' : ''
			);
			echo '</div></div>';
		}

		/**
		 * image_select (медиабиблиотека).
		 *
		 * @return void
		 */
		protected function field_image_select(): void {
			$image_html = '';

			if ( ! empty( $this->value ) && is_numeric( $this->value ) && get_post( (int) $this->value ) ) {
				$image_html = wp_get_attachment_image( (int) $this->value, 'full', false, [ 'style' => 'max-width:100%;height:auto;' ] );
			}

			if ( $this->field->disabled === true ) {
				$this->field->attributes['disabled'] = 'disabled';
			}
			if ( $this->field->readonly === true ) {
				$this->field->attributes['readonly'] = '';
			}

			wp_enqueue_media();

			printf( '<div class="image-select %s">', esc_attr( $this->generate_field_id() ) );
			printf(
				'<input type="hidden" name="%1$s" value="%2$s" id="%3$s" class="%4$s image-select-input" />',
				esc_attr( $this->generate_field_name() ),
				esc_attr( (string) $this->value ),
				esc_attr( $this->generate_field_id() ),
				esc_attr( $this->field->class )
			);
			printf(
				'<div class="image-container" style="width:150px;height:auto;">%s</div>',
				$image_html ? wp_kses_post( $image_html ) : ''
			);
			echo '<div style="margin-top:.5rem">';
			printf(
				'<a href="#" id="%1$s" class="button-secondary image-select-button" style="margin-right:.5rem">%2$s</a>',
				esc_attr( $this->generate_field_id() ),
				esc_html( $image_html ? 'Изменить' : 'Добавить' )
			);
			printf(
				'<a href="#" id="%1$s" class="button-secondary image-delete-button%2$s">Удалить</a>',
				esc_attr( $this->generate_field_id() ),
				$image_html ? '' : ' hidden'
			);
			echo '</div></div>';
		}

		/**
		 * input (по умолчанию).
		 *
		 * @return void
		 */
		protected function field_default(): void {
			if ( $this->field->disabled === true ) {
				$this->field->attributes['disabled'] = 'disabled';
			}
			if ( $this->field->readonly === true ) {
				$this->field->attributes['readonly'] = '';
			}

			printf(
				'<input type="%1$s" name="%2$s" id="%3$s" value="%4$s" class="%5$s" placeholder="%6$s" autocomplete="%7$s" %8$s />',
				esc_attr( (string) $this->field->type ),
				esc_attr( $this->generate_field_name() ),
				esc_attr( $this->generate_field_id() ),
				esc_attr( (string) $this->value ),
				esc_attr( $this->field->class ),
				esc_attr( $this->field->placeholder ),
				esc_attr( $this->field->autocomplete ),
				$this->array_to_html_atts( (array) $this->field->attributes )
			);
		}

		/**
		 * Select2 assets.
		 *
		 * @return void
		 */
		protected function select_2_enqueue(): void {
			wp_enqueue_style(
				$this->helper::handle( 'admin-settings-select-2' ),
				$this->helper->get_dir_url_css( 'select2.min.css' ),
				false,
				'4.1.0-rc.0'
			);
			wp_enqueue_script(
				$this->helper::handle( 'admin-settings-select-2' ),
				$this->helper->get_dir_url_js( 'select2.min.js' ),
				[ 'jquery' ],
				'4.1.0-rc.0',
				true
			);
			wp_enqueue_script(
				$this->helper::handle( 'admin-settings-select-2-i18n' ),
				$this->helper->get_dir_url_js( 'i18n/ru.js' ),
				[ 'jquery' ],
				'4.1.0-rc.0',
				true
			);
			wp_add_inline_script(
				$this->helper::handle( 'admin-settings-select-2' ),
				<<<'JS'
jQuery(function($){
	$('select.select-2').each(function(){
		var $el = $(this);
		if ($el.data('select2')) return; 
		$el.select2({ width: '100%', language: 'ru' });
	});
});
JS
			);
		}

		/**
		 * Описание/ссылка под полем.
		 *
		 * @return void
		 */
		protected function description(): void {
			if ( ! empty( $this->field->desc ) ) {
				echo '<p class="description">' . wp_kses(
						$this->field->desc,
						[
							'a'      => [ 'href' => [], 'target' => [], 'rel' => [] ],
							'b'      => [],
							'strong' => [],
							'em'     => [],
							'br'     => [],
							'code'   => [],
						]
					) . '</p>';
			}

			if ( ! empty( $this->field->link ) && is_array( $this->field->link ) ) {
				$text   = $this->field->link['text'] ?? '';
				$url    = $this->field->link['url'] ?? '';
				$target = (bool) ( $this->field->link['target'] ?? false ) ? ' target="_blank" rel="noopener noreferrer"' : '';
				echo '<br>';
				printf(
					'<a href="%1$s"%2$s>%3$s</a>',
					esc_url( (string) $url ),
					$target,
					esc_html( (string) $text )
				);
			}
		}

		/**
		 * Имя поля вида option[key].
		 *
		 * @return string
		 */
		protected function generate_field_name(): string {
			return sprintf( '%s[%s]', $this->option_name, $this->field->id );
		}

		/**
		 * ID поля (label_for).
		 *
		 * @return string
		 */
		protected function generate_field_id(): string {
			return $this->label_for;
		}

		/**
		 * Преобразует массив атрибутов в HTML.
		 *
		 * @param  array  $array  Атрибуты.
		 *
		 * @return string
		 */
		protected function array_to_html_atts( array $array = [] ): string {
			if ( empty( $array ) ) {
				return '';
			}

			$out = '';

			foreach ( $array as $key => $value ) {
				if ( '' === $value ) {
					$out .= sprintf( ' %s ', esc_attr( (string) $key ) );
				} else {
					$out .= sprintf( '%s="%s" ', esc_attr( (string) $key ), esc_attr( (string) $value ) );
				}
			}

			return $out;
		}
	}
}
