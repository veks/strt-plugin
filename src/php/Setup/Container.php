<?php
/**
 * Container - контейнер зависимостей.
 *
 * Этот класс реализует простой механизм внедрения зависимостей (DI),
 * позволяя регистрировать и извлекать сервисы, поддерживать синглтоны,
 * автоматически разрешать зависимости через рефлексию и управлять жизненным циклом объектов.
 *
 * @class  Container
 * @package Strt\Plugin\Setup
 * @version 1.0.0
 */

namespace Strt\Plugin\Setup;

defined( 'ABSPATH' ) || exit;

use ReflectionClass;
use ReflectionException;

if ( ! class_exists( 'Strt\Plugin\Setup\Container' ) ) {

	/**
	 * Класс Container.
	 *
	 * Контейнер зависимостей.
	 */
	class Container {

		/**
		 * Хранилище для зарегистрированных экземпляров и сервисов.
		 *
		 * @var array<string, callable>
		 */
		protected array $instances = [];

		/**
		 * Хранилище для однократных (singleton) сервисов.
		 *
		 * @var array<string, callable|object>
		 */
		protected array $singletons = [];

		/**
		 * Хранилище кешированных экземпляров.
		 *
		 * @var array<string, object>
		 */
		protected array $resolved_instances = [];

		/**
		 * Хранилище для обратных вызовов при разрешении сервисов.
		 *
		 * @var array<string, callable[]>
		 */
		protected array $resolving_callbacks = [];

		/**
		 * Регистрация нового экземпляра или сервиса.
		 *
		 * @param string $name Имя сервиса.
		 * @param callable $resolver Функция или замыкание для создания экземпляра.
		 */
		public function bind( string $name, callable $resolver ): void {
			$this->instances[ $name ] = $resolver;
		}

		/**
		 * Регистрация однократного (singleton) экземпляра или сервиса.
		 *
		 * @param string $name Имя сервиса.
		 * @param callable $resolver Функция или замыкание для создания экземпляра.
		 */
		public function singleton( string $name, callable $resolver ): void {
			$this->singletons[ $name ] = $resolver;
		}

		/**
		 * Извлечение экземпляра по имени.
		 *
		 * @template T
		 * @param class-string<T> $name Имя класса или сервиса.
		 *
		 * @return T|null Экземпляр класса или null, если объект не может быть создан.
		 */
		public function make( string $name ): ?object {
			if ( isset( $this->resolved_instances[ $name ] ) ) {
				/** @var T $instance */
				return $this->resolved_instances[ $name ];
			}

			if ( $name === self::class ) {
				/** @var T $this */
				return $this;
			}

			if ( isset( $this->singletons[ $name ] ) ) {
				if ( is_callable( $this->singletons[ $name ] ) ) {
					$this->singletons[ $name ] = $this->create_instance( $this->singletons[ $name ] );
				}

				/** @var T $instance */
				return $this->singletons[ $name ];
			}

			if ( isset( $this->instances[ $name ] ) ) {
				$instance                          = $this->create_instance( $this->instances[ $name ] );
				$this->resolved_instances[ $name ] = $instance;
				$this->run_resolving_callbacks( $name, $instance );

				/** @var T $instance */
				return $instance;
			}

			try {
				$instance                          = $this->create_instance_by_name( $name );
				$this->resolved_instances[ $name ] = $instance;
				$this->run_resolving_callbacks( $name, $instance );

				/** @var T $instance */
				return $instance;
			} catch ( ReflectionException $e ) {
				_doing_it_wrong(
					__METHOD__,
					sprintf(
						"Не удалось создать экземпляр класса '%s'. Ошибка: %s",
						esc_html( $name ),
						esc_html( $e->getMessage() )
					),
					'1.0.0'
				);

				return null;
			}
		}

		/**
		 * Проверка, зарегистрирован ли сервис.
		 *
		 * @param string $name Имя сервиса.
		 *
		 * @return bool True, если сервис зарегистрирован, иначе false.
		 */
		public function has( string $name ): bool {
			return isset( $this->instances[ $name ] ) || isset( $this->singletons[ $name ] );
		}

		/**
		 * Удаление зарегистрированного сервиса.
		 *
		 * @param string $name Имя сервиса.
		 */
		public function remove( string $name ): void {
			unset( $this->instances[ $name ], $this->singletons[ $name ], $this->resolved_instances[ $name ] );
		}

		/**
		 * Регистрация обратного вызова при разрешении сервиса.
		 *
		 * @param string $name Имя сервиса.
		 * @param callable $callback Обратный вызов.
		 */
		public function resolving( string $name, callable $callback ): void {
			$this->resolving_callbacks[ $name ][] = $callback;
		}

		/**
		 * Запуск зарегистрированных обратных вызовов при разрешении сервиса.
		 *
		 * @param string $name Имя сервиса.
		 * @param object $instance Экземпляр сервиса.
		 */
		protected function run_resolving_callbacks( string $name, object $instance ): void {
			if ( isset( $this->resolving_callbacks[ $name ] ) ) {
				foreach ( $this->resolving_callbacks[ $name ] as $callback ) {
					$callback( $instance, $this );
				}
			}
		}

		/**
		 * Создание экземпляра класса с разрешением зависимостей.
		 *
		 * @param string $name Имя класса.
		 *
		 * @return object|null
		 *
		 * @throws ReflectionException
		 */
		protected function create_instance_by_name( string $name ): ?object {
			$reflection = new ReflectionClass( $name );

			if ( ! $reflection->isInstantiable() ) {
				_doing_it_wrong(
					__METHOD__,
					sprintf(
						"Класс '%s' не может быть создан.",
						esc_html( $name )
					),
					'1.0.0'
				);
				return null;
			}

			$constructor = $reflection->getConstructor();

			if ( is_null( $constructor ) ) {
				return new $name;
			}

			$parameters   = $constructor->getParameters();
			$dependencies = [];

			foreach ( $parameters as $parameter ) {
				$type = $parameter->getType();
				if ( $type && ! $type->isBuiltin() ) {
					$dependency_class_name = $type->getName();

					if ( $dependency_class_name === $name ) {
						_doing_it_wrong(
							__METHOD__,
							sprintf(
								"Циклическая зависимость обнаружена при создании класса '%s'.",
								esc_html( $name )
							),
							'1.0.0'
						);

						return null;
					}

					$dependencies[] = $this->make( $dependency_class_name );
				} elseif ( $parameter->isDefaultValueAvailable() ) {
					$dependencies[] = $parameter->getDefaultValue();
				} else {
					_doing_it_wrong(
						__METHOD__,
						sprintf(
							"Не удалось разрешить зависимость для параметра \$%s в классе %s.",
							esc_html( $parameter->getName() ),
							esc_html( $name )
						),
						'1.0.0'
					);

					return null;
				}
			}

			return $reflection->newInstanceArgs( $dependencies );
		}

		/**
		 * Создание экземпляра через переданный резолвер.
		 *
		 * @param callable $resolver Функция создания экземпляра.
		 *
		 * @return object
		 */
		protected function create_instance( callable $resolver ): object {
			return $resolver();
		}
	}
}
