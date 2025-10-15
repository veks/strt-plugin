<?php
/**
 * Класс Breadcrumb для управления и отображения хлебных крошек в теме WordPress.
 *
 * Этот класс предоставляет функциональность для генерации навигационной цепочки
 * (хлебных крошек), которая улучшает пользовательский опыт и SEO-оптимизацию.
 *
 * @class   Breadcrumb
 * @package Strt\Plugin\Breadcrumb
 * @version 1.0.0
 */

namespace Strt\Plugin\Breadcrumb;

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'Strt\Plugin\Breadcrumb\Breadcrumb' ) ) {

	/**
	 * Класс Breadcrumb для управления и отображения хлебных крошек в WordPress.
	 *
	 * Этот класс предоставляет методы для создания, настройки и отображения навигационной цепочки
	 * (хлебных крошек) в темах WordPress. Хлебные крошки используются для улучшения навигации и
	 * повышения SEO-оптимизации.
	 *
	 * @class   Breadcrumb
	 * @package Strt\Plugin\Breadcrumb
	 */
	class Breadcrumb {

		/**
		 * Хлебные крошки.
		 *
		 * @var array Массив для хранения элементов хлебных крошек.
		 */
		protected array $crumbs = [];

		/**
		 * Позиция хлебной крошки.
		 *
		 * @var int Текущая позиция элемента в цепочке хлебных крошек.
		 */
		protected int $position = 1;

		/**
		 * Аргументы хлебных крошек.
		 *
		 * @var object Объект аргументов для настройки хлебных крошек.
		 */
		protected object $args;

		/**
		 * Конструктор класса.
		 */
		public function __construct() {
			$this->args = (object) $this->get_default_breadcrumb_args();
		}

		/**
		 * Получить параметры по умолчанию для хлебных крошек.
		 *
		 * @return array Параметры по умолчанию для хлебных крошек.
		 */
		protected function get_default_breadcrumb_args(): array {
			$defaults = [
				'container_element'            => 'nav',
				'container_class'              => 'container my-4',
				'container_id'                 => '',
				'container_attributes'         => [
					'aria-label' => 'Навигационная цепочка',
				],
				'before_html'                  => '',
				'after_html'                   => '',
				'breadcrumb_attributes'        => [],
				'breadcrumb_class'             => 'breadcrumb breadcrumb-scrollable-lg m-0',
				'breadcrumb_item_class'        => 'breadcrumb-item',
				'breadcrumb_link_class'        => '',
				'breadcrumb_active_item_class' => 'active',
				'home_label'                   => 'Главная',
				'home_url'                     => get_home_url(),
				'output_echo'                  => true,
			];

			return apply_filters( 'strt_default_breadcrumb_args', $defaults );
		}

		/**
		 * Генерация HTML для хлебных крошек.
		 *
		 * @return string|null Возвращает сгенерированный HTML или выводит его.
		 */
		public function render(): ?string {
			$html                    = '';
			$container_attributes    = '';
			$breadcrumb_attributes   = '';
			$container_class         = $this->args->container_class ? ' class="' . esc_attr( $this->args->container_class ) . '"' : '';
			$container_id            = $this->args->container_id ? ' id="' . esc_attr( $this->args->container_id ) . '"' : '';
			$breadcrumb_class        = $this->args->breadcrumb_class ? ' class="' . esc_attr( $this->args->breadcrumb_class ) . '"' : '';
			$breadcrumb_item_a_class = $this->args->breadcrumb_link_class ? ' class="' . esc_attr( $this->args->breadcrumb_link_class ) . '"' : '';
			$position                = $this->position + 1;

			if ( ! empty( $this->get_breadcrumb() ) ) {
				if ( ! empty( $this->args->container_attributes ) ) {
					foreach ( $this->args->container_attributes as $attribute => $value ) {
						if ( ! in_array( $attribute, [ 'class', 'id' ], true ) ) {
							$container_attributes .= esc_attr( $attribute ) . "='" . esc_attr( $value ) . "'" . ' ';
						}
					}
				}

				if ( ! empty( $this->args->breadcrumb_attributes ) ) {
					foreach ( $this->args->breadcrumb_attributes as $attribute => $value ) {
						if ( ! in_array( $attribute, [ 'class', 'id' ], true ) ) {
							$breadcrumb_attributes .= esc_attr( $attribute ) . "='" . esc_attr( $value ) . "'" . ' ';
						}
					}
				}

				$html .= sprintf(
					'%1$s<%2$s %3$s %4$s %5$s>',
					$this->args->before_html,
					$this->args->container_element,
					$container_id,
					$container_class,
					$container_attributes
				);
				$html .= sprintf(
					'<ol %1$s %2$s itemscope="" itemtype="https://schema.org/BreadcrumbList">',
					$breadcrumb_class,
					$breadcrumb_attributes
				);
				$html .= sprintf(
					'<li class="%1$s" itemprop="itemListElement" itemscope="" itemtype="https://schema.org/ListItem"><a class="%2$s" itemprop="item" itemid="%3$s" href="%3$s"><span itemprop="name">%4$s</span><meta itemprop="position" content="%5$s" /></a></li>',
					esc_attr( $this->args->breadcrumb_item_class ),
					esc_attr( $this->args->breadcrumb_link_class ),
					$this->args->home_url,
					$this->args->home_label,
					$this->position
				);

				foreach ( $this->get_breadcrumb() as $crumb ) {
					if ( $crumb['active'] === 'true' ) {
						$html .= sprintf(
							'<li class="%1$s %2$s" itemprop="itemListElement" itemscope="" itemtype="https://schema.org/ListItem"><span itemprop="name">%3$s</span><meta itemprop="position" content="%4$s" /></li>',
							esc_attr( $this->args->breadcrumb_item_class ),
							esc_attr( $this->args->breadcrumb_active_item_class ),
							esc_html( $crumb['name'] ),
							$position
						);
					} else {
						$html .= sprintf(
							'<li class="%1$s" itemprop="itemListElement" itemscope="" itemtype="https://schema.org/ListItem"><a class="%2$s" itemprop="item" itemid="%3$s" href="%3$s"><span itemprop="name">%4$s</span><meta itemprop="position" content="%5$s" /></a></li>',
							esc_attr( $this->args->breadcrumb_item_class ),
							esc_attr( $this->args->breadcrumb_link_class ),
							esc_url( $crumb['link'] ),
							esc_html( $crumb['name'] ),
							$position
						);
					}

					$position ++;
				}

				$html .= sprintf(
					'</ol></%1$s>%2$s',
					$this->args->container_element,
					$this->args->after_html
				);
			}

			if ( $this->args->output_echo ) {
				echo esc_html( $html );

				return null;
			} else {
				return $html;
			}
		}

		/**
		 * Получить текущие хлебные крошки.
		 *
		 * @return array Массив хлебных крошек.
		 */
		public function get_breadcrumb(): array {
			return $this->crumbs;
		}

		/**
		 * Добавить новую крошку.
		 *
		 * @param  string  $name  Имя крошки.
		 * @param  string  $link  URL крошки.
		 * @param  bool  $active  Является ли крошка активной.
		 * @param  int|bool  $id  ID элемента хлебной крошки, если применимо (иначе 0)
		 */
		public function add_crumb( string $name, string $link = '', bool $active = false, bool|int $id = 0 ): void {
			$this->crumbs[] = apply_filters(
				'strt_breadcrumb_crumb',
				[
					'name'   => wp_strip_all_tags( $name ),
					'link'   => $link,
					'active' => $active ? 'true' : 'false',
					'id'     => $id,
				],
				$id
			);
		}

		/**
		 * Генерация хлебных крошек в зависимости от условий.
		 *
		 * @return $this
		 */
		public function generate(): static {
			$conditionals = [
				'is_home',
				'is_404',
				'is_attachment',
				'is_single',
				'is_product_category',
				'is_product_tag',
				'is_shop',
				'is_page',
				'is_post_type_archive',
				'is_category',
				'is_tag',
				'is_author',
				'is_date',
				'is_tax',
				'is_search',
			];

			if ( ! is_front_page() ) {
				foreach ( $conditionals as $conditional ) {
					$method = 'add_crumbs_' . substr( $conditional, 3 );

					if ( function_exists( $conditional ) && $conditional() && method_exists( $this, $method ) ) {
						$this->$method();
						break;
					}
				}

				$this->paged();
			}

			return $this;
		}

		/**
		 * Добавление страницы "Главная".
		 */
		protected function add_crumbs_home(): void {
			$this->add_crumb( single_post_title( '', false ), esc_url( get_home_url() ), false, get_the_ID() );
		}

		/**
		 * Добавление хлебных крошек для страниц.
		 */
		protected function add_crumbs_page(): void {
			$id = get_the_ID();

			if ( wp_get_post_parent_id( $id ) ) {
				$post_ancestors = get_post_ancestors( $id );

				if ( is_array( $post_ancestors ) ) {
					foreach ( array_reverse( $post_ancestors ) as $post_ancestor ) {
						$this->add_crumb( get_the_title( $post_ancestor ), get_permalink( $post_ancestor ), false, $post_ancestor );
					}
				}
			}

			$this->add_crumb( get_the_title(), get_permalink(), true, $id );
		}

		/**
		 * Добавление хлебных крошек для категорий.
		 */
		protected function add_crumbs_category(): void {
			$queried_object = get_queried_object();
			$category       = get_category( $queried_object );

			if ( 0 !== intval( $category->parent ) ) {
				$this->term_ancestors( $category->term_id, 'category' );
			}

			$active = ! ( get_query_var( 'paged' ) || get_query_var( 'product-page' ) );

			$this->add_crumb( single_cat_title( '', false ), get_category_link( $category->term_id ), $active, $category->term_id );
		}

		/**
		 * Добавление хлебных крошек для вложений.
		 */
		protected function add_crumbs_attachment(): void {
			$this->add_crumb( get_the_title(), get_permalink(), true, get_the_ID() );
		}

		/**
		 * Добавление хлебных крошек для одиночных записей.
		 */
		public function add_crumbs_single(): void {
			$get_post_type = get_post_type();
			$id            = get_the_ID();

			if ( function_exists( 'wc_get_product_terms' ) && 'product' === $get_post_type ) {
				$this->prepend_shop_page();

				$product_terms = wc_get_product_terms(
					$id,
					'product_cat',
					[
						'orderby' => 'parent',
						'order'   => 'DESC',
					]
				);

				if ( $product_terms ) {
					$main_term = apply_filters( 'strt_breadcrumb_main_term', $product_terms[0], $product_terms );
					$this->term_ancestors( $main_term->term_id, 'product_cat' );
					$this->add_crumb( $main_term->name, get_term_link( $main_term ), false, $main_term->term_id );
				}
			} elseif ( 'post' === $get_post_type ) {
				if ( is_array( get_the_category() ) ) {
					foreach ( get_the_category() as $category ) {
						$this->add_crumb( get_cat_name( $category->term_id ), get_term_link( $category->term_id ), false, $category->term_id );
					}
				}
			} else {
				$post_type_object = get_post_type_object( $get_post_type );

				if ( ! empty( $post_type_object ) ) {

					$this->add_crumb( $post_type_object->labels->singular_name, get_post_type_archive_link( $get_post_type ), false, 0 );

					$ancestors = get_ancestors( $id, $get_post_type );

					if ( is_array( $ancestors ) ) {
						foreach ( array_reverse( $ancestors ) as $ancestor ) {
							$this->add_crumb( get_the_title( $ancestor ), get_permalink( $ancestor ), false, $ancestor );
						}
					}
				}
			}

			$this->add_crumb( get_the_title(), '', true, $id );
		}

		/**
		 * Добавление страницы магазина в хлебные крошки.
		 */
		protected function prepend_shop_page(): void {
			if ( function_exists( 'wc_get_permalink_structure' ) && function_exists( 'wc_get_page_id' ) ) {
				$permalinks   = wc_get_permalink_structure();
				$shop_page_id = wc_get_page_id( 'shop' );
				$shop_page    = get_post( $shop_page_id );

				// If permalinks contain the shop page in the URI prepend the breadcrumb with shop.
				if ( $shop_page_id && $shop_page && isset( $permalinks['product_base'] ) && strstr( $permalinks['product_base'],
						'/' . $shop_page->post_name ) && intval( get_option( 'page_on_front' ) ) !== $shop_page_id ) {
					$this->add_crumb( get_the_title( $shop_page ), get_permalink( $shop_page ), false, $shop_page_id );
				}
			}
		}

		/**
		 * Добавление хлебных крошек для магазина.
		 */
		protected function add_crumbs_shop(): void {
			$_name = function_exists( 'wc_get_page_id' ) && wc_get_page_id( 'shop' ) ? get_the_title( wc_get_page_id( 'shop' ) ) : '';

			if ( ! $_name ) {
				$product_post_type = get_post_type_object( 'product' );
				$_name             = $product_post_type->labels->name;
			}

			$active = ! get_query_var( 'paged' );
			$this->add_crumb( $_name, get_post_type_archive_link( 'product' ), $active, 0 );
		}

		/**
		 * Добавление хлебных крошек для меток товаров.
		 */
		protected function add_crumbs_product_tag(): void {
			$get_queried_object = get_queried_object();

			$this->add_crumb(
				sprintf( '%s: %s', 'Товары с меткой', $get_queried_object->name ),
				get_term_link( $get_queried_object, 'product_tag' ),
				true,
				$get_queried_object->term_id
			);
		}

		/**
		 * Добавление хлебных крошек для категорий товаров.
		 */
		protected function add_crumbs_product_category(): void {
			$current_term = get_queried_object();

			$this->prepend_shop_page();
			$this->term_ancestors( $current_term->term_id, 'product_cat' );

			$active = ! ( get_query_var( 'paged' ) || get_query_var( 'product-page' ) );

			$this->add_crumb( $current_term->name, get_term_link( $current_term, 'product_cat' ), $active, $current_term->term_id );
		}

		/**
		 * Добавление хлебных крошек для архивов типов записей.
		 */
		protected function add_crumbs_post_type_archive(): void {
			$post_type = get_post_type_object( get_post_type() );

			if ( $post_type ) {
				$active = ! ( get_query_var( 'paged' ) || get_query_var( 'product-page' ) );

				$this->add_crumb( post_type_archive_title( '', false ), get_post_type_archive_link( get_post_type() ), $active, 0 );

			}
		}

		/**
		 * Добавление хлебных крошек для таксономий.
		 */
		public function add_crumbs_tax(): void {
			$get_queried_object = get_queried_object();
			$taxonomy           = get_taxonomy( $get_queried_object->taxonomy );

			if ( mb_substr( $taxonomy->name, 0, 3 ) === 'pa_' ) {
				$this->add_crumb( $taxonomy->labels->name, home_url( $taxonomy->rewrite['slug'] ), false, $get_queried_object->term_id );
			} else {
				$this->add_crumb( $taxonomy->labels->name, '', false, $get_queried_object->term_id );
			}

			if ( 0 !== intval( $get_queried_object->parent ) ) {
				$this->term_ancestors( $get_queried_object->term_id, $get_queried_object->taxonomy );
			}

			$active = ! ( get_query_var( 'paged' ) || get_query_var( 'product-page' ) );

			$this->add_crumb( single_term_title( '', false ), get_term_link( $get_queried_object->term_id, $get_queried_object->taxonomy ), $active,
				$get_queried_object->term_id );
		}

		/**
		 * Добавление хлебных крошек для архивов по дате.
		 */
		protected function add_crumbs_date(): void {
			if ( is_year() || is_month() || is_day() ) {
				$this->add_crumb( get_the_time( 'Y' ), get_year_link( get_the_time( 'Y' ) ), false, 0 );
			}

			if ( is_month() || is_day() ) {
				$this->add_crumb( get_the_time( 'F' ), get_month_link( get_the_time( 'Y' ), get_the_time( 'm' ) ), false, 0 );
			}

			if ( is_day() ) {
				$this->add_crumb( get_the_time( 'd' ), '', true, 0 );
			}
		}

		/**
		 * Добавление хлебных крошек для меток.
		 */
		public function add_crumbs_tag(): void {
			$queried_object = get_queried_object();

			$this->prepend_shop_page();
			$this->add_crumb(
				sprintf( '%s: %s', 'Публикации помечены как', single_tag_title( '', false ) ),
				get_tag_link( $queried_object->term_id ),
				true,
				$queried_object->term_id
			);
		}

		/**
		 * Добавление хлебных крошек для страниц автора.
		 */
		protected function add_crumbs_author(): void {
			$this->add_crumb( 'Автор: ' . get_the_author_meta( 'display_name' ), '', true, get_the_author_meta( 'ID' ) );
		}

		/**
		 * Добавление пагинации в хлебные крошки.
		 */
		protected function paged(): void {
			if ( get_query_var( 'paged' ) ) {
				$this->add_crumb( sprintf( '%s %d', 'Страница', get_query_var( 'paged' ) ), '', true, 0 );
			}

			if ( get_query_var( 'product-page' ) ) {
				$this->add_crumb( sprintf( '%s %d', 'Страница', get_query_var( 'product-page' ) ), '', true, 0 );
			}
		}

		/**
		 * Добавление активной страницы "Ошибка 404" в хлебные крошки.
		 */
		protected function add_crumbs_404(): void {
			$this->add_crumb( 'Страница не найдена - ошибка 404', '', true, 0 );
		}

		/**
		 * Добавление поисковых результатов в хлебные крошки.
		 */
		protected function add_crumbs_search(): void {
			$this->add_crumb( 'Поиск: ' . get_search_query(), '', true, 0 );
		}

		/**
		 * Добавление страниц для термина таксономии.
		 *
		 * @param  int  $term_id  ID термина.
		 * @param  string  $taxonomy  Название таксономии.
		 */
		protected function term_ancestors( int $term_id, string $taxonomy ): void {
			$ancestors = get_ancestors( $term_id, $taxonomy );
			$ancestors = array_reverse( $ancestors );

			foreach ( $ancestors as $ancestor ) {
				$ancestor = get_term( $ancestor, $taxonomy );

				if ( ! is_wp_error( $ancestor ) && $ancestor ) {
					$this->add_crumb( $ancestor->name, get_term_link( $ancestor ), false, $ancestor->term_id );
				}
			}
		}
	}
}