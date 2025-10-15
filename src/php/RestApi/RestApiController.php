<?php
/**
 * Абстрактный класс RestApiController для создания REST API контроллеров в WordPress.
 *
 * Этот класс служит базовым для всех REST API контроллеров в теме, предоставляя
 * стандартные методы регистрации маршрутов, обработки запросов и проверки разрешений.
 * Дочерние классы должны реализовать свои собственные маршруты API.
 *
 * @abstract
 * @class  RestApiController
 * @package Strt\Plugin\RestApi
 * @version 1.0.0
 */

namespace Strt\Plugin\RestApi;

use WP_REST_Controller;

/**
 * Абстрактный класс RestApiController для работы с REST API.
 */
abstract class RestApiController extends WP_REST_Controller {

	/**
	 * Пространство имен API.
	 *
	 * @var string
	 */
	protected $namespace = 'strt/v1';

	/**
	 * Базовый путь эндпоинта.
	 *
	 * @var string
	 */
	protected $rest_base = '';

	/**
	 * Конструктор: регистрирует маршруты REST API.
	 */
	public function __construct() {
		add_action( 'init', [ $this, 'init' ] );
		add_action( 'rest_api_init', [ $this, 'register_routes' ] );
	}

	/**
	 * Инициализирует регистрацию всех фильтров и действий.
	 *
	 * @return void
	 */
	public function init(): void {
		// TODO: Implement init() method.
	}

	/**
	 * Добавить схему из дополнительных полей в массив схем.
	 *
	 * Тип объекта определяется из переданной схемы.
	 *
	 * @param  array  $schema  Массив схем.
	 *
	 * @return array
	 */
	protected function add_additional_fields_schema( $schema ): array {
		if ( empty( $schema['title'] ) ) {
			return $schema;
		}

		/**
		 * Нельзя использовать $this->get_object_type, иначе возникнет инфоцикл.
		 */
		$object_type = $schema['title'];

		$additional_fields = $this->get_additional_fields( $object_type );

		foreach ( $additional_fields as $field_name => $field_options ) {
			if ( ! $field_options['schema'] ) {
				continue;
			}

			$schema['properties'][ $field_name ] = $field_options['schema'];
		}

		$schema['properties'] = apply_filters( 'strt_rest_' . $object_type . '_schema', $schema['properties'] );

		return $schema;
	}
}