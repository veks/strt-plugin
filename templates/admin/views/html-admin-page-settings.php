<?php
/**
 * Settings View (site | network).
 *
 * Отдельная разметка отправки формы для SITE и NETWORK.
 * SITE отправляет в options.php (Settings API), NETWORK — в edit.php?action=strt_save_settings.
 *
 * Ожидаемые переменные:
 * - array  $tabs
 * - string $tab_default
 * - string $tab_current
 * - string $slug_settings
 * - string $version_db
 * - array  $settings
 * - string $form_action    (опционально)
 * - string $page_slug      (опционально)
 * - int    $blog_id        (опционально, для сохранения per-site из сети)
 *
 * @package Strt\Plugin\Admin
 * @version 1.2.0
 */

defined( 'ABSPATH' ) || exit;

$tabs           = isset( $tabs ) ? (array) $tabs : [];
$tab_default    = isset( $tab_default ) ? (string) $tab_default : '';
$tab_current    = isset( $tab_current ) ? (string) $tab_current : $tab_default;
$slug_settings  = isset( $slug_settings ) ? (string) $slug_settings : 'strt-plugin';
$version_db     = isset( $version_db ) ? (string) $version_db : '1.0.0';
$settings       = isset( $settings ) ? (array) $settings : [];

$is_network     = function_exists( 'is_network_admin' ) && is_network_admin();
$form_action    = isset( $form_action ) ? (string) $form_action : ( $is_network ? network_admin_url( 'edit.php?action=strt_save_settings' ) : admin_url( 'options.php' ) );

$base_admin_url = $is_network ? network_admin_url( 'admin.php' ) : admin_url( 'admin.php' );
$page_slug      = $slug_settings;
?>
<div class="wrap">
	<div class="strt-header">
		<div class="strt-title">
			<div id="icon-options-general" class="icon32"></div>
			<h2 class="wp-heading-inline">
				<?php echo esc_html( get_admin_page_title() ); ?>
				<sup style="font-size: small">v<?php echo esc_html( $version_db ); ?></sup>
			</h2>
		</div>

		<?php if ( ! empty( $tabs ) ) : ?>
			<nav class="nav-tab-wrapper">
				<?php foreach ( $tabs as $id => $label ) :
					$id            = sanitize_key( (string) $id );
					$is_active     = ( $tab_current === $id ) ? ' nav-tab-active' : '';
					$tab_url       = add_query_arg( [ 'page' => $page_slug ] + ( $id === $tab_default ? [] : [ 'tab' => $id ] ), $base_admin_url );
					?>
					<a href="<?php echo esc_url( $tab_url ); ?>" class="nav-tab<?php echo esc_attr( $is_active ); ?>">
						<?php echo esc_html( (string) $label ); ?>
					</a>
				<?php endforeach; ?>
			</nav>
		<?php endif; ?>
	</div>

	<hr class="wp-header-end">

	<?php settings_errors(); ?>

	<div id="poststuff">
		<div id="post-body" class="metabox-holder columns-2">
			<div id="post-body-content">
				<div class="meta-box-sortables ui-sortable">
					<div class="postbox">
						<div class="inside">
							<?php if ( ! empty( $tabs ) && ! empty( $settings ) ) : ?>
								<div class="tab-content">

									<form action="<?php echo esc_url( $form_action ); ?>" method="post">
										<?php
										wp_nonce_field( 'strt_settings' );

										foreach ( $settings as $setting ) {
											if ( isset( $setting['tab_id'], $setting['option_group'] ) && $setting['tab_id'] === $tab_current && ! empty( $setting['option_group'] ) ) {
												settings_fields( (string) $setting['option_group'] );
											}
										}

										foreach ( $settings as $setting ) {
											if ( isset( $setting['tab_id'], $setting['section']['id'] ) && $setting['tab_id'] === $tab_current ) {
												do_settings_sections( (string) $setting['section']['id'] );
											}
										}

										submit_button();
										?>
										<div class="clear"></div>
									</form>

								</div>
							<?php else : ?>
								<div style="text-align:center">Нет текущих настроек</div>
							<?php endif; ?>
						</div>
					</div>
				</div>
			</div>

			<div id="postbox-container-1" class="postbox-container">
				<div class="meta-box-sortables">
					<div class="postbox">
						<h2>Информация</h2>
						<div class="inside">
							Скоро заполним
						</div>
					</div>
				</div>
			</div>

		</div>
		<br class="clear">
	</div>
</div>
