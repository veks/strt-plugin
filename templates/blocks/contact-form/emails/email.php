<?php
/**
 * Нейтральное (серое) HTML-письмо: заявка с формы
 *
 * Переменные:
 * @var string $subject
 * @var string $site_name
 * @var string $site_url
 * @var string $date
 * @var string $ip
 * @var string $referer
 * @var array $items
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


$bgColor     = '#F3F4F6';
$cardBg      = '#FFFFFF';
$headerBg    = '#374151';
$headerText  = '#FFFFFF';
$textColor   = '#111827';
$mutedColor  = '#6B7280';
$borderColor = '#E5E7EB';
$linkColor   = '#374151';

function strt_email_linkify_single( string $type, string $val ): string {
	$val = trim( $val );
	$esc = esc_html( $val === '' ? '—' : $val );
	if ( $val === '' ) {
		return $esc;
	}

	if ( $type === 'email' ) {
		return '<a href="' . esc_url( 'mailto:' . $val ) . '" style="color:#374151;text-decoration:none;">' . $esc . '</a>';
	}

	if ( $type === 'tel' ) {
		$href = preg_replace( '~\s+~', '', $val );

		return '<a href="' . esc_url( 'tel:' . $href ) . '" style="color:#374151;text-decoration:none;">' . $esc . '</a>';
	}

	if ( $type === 'url' ) {
		return '<a href="' . esc_url( $val ) . '" style="color:#374151;text-decoration:none;">' . $esc . '</a>';
	}

	return $esc;
}

?>
<!doctype html>
<html lang="ru">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>
	<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
	<title><?php echo esc_html( $subject ); ?></title>
	<style type="text/css">
		body {
			margin: 0;
			padding: 0;
			background: <?php echo esc_attr($bgColor); ?>;
			-webkit-text-size-adjust: 100%;
		}

		table {
			border-collapse: collapse;
		}

		.container {
			width: 100%;
			background: <?php echo esc_attr($bgColor); ?>;
			padding: 50px;
		}

		.card {
			width: 100%;
			max-width: 640px;
			background: <?php echo esc_attr($cardBg); ?>;
			border-radius: 12px;
			overflow: hidden;
			margin: 50px;
		}

		.header {
			padding: 20px 24px;
			background: <?php echo esc_attr($headerBg); ?>;
			color: <?php echo esc_attr($headerText); ?>;
			font-family: Arial, Helvetica, sans-serif;
		}

		.header h1 {
			margin: 0;
			font-size: 20px;
			line-height: 1.3;
		}

		.content {
			padding: 24px;
			font-family: Arial, Helvetica, sans-serif;
			color: <?php echo esc_attr($textColor); ?>;
			font-size: 14px;
		}

		.row-table {
			width: 100%;
			border-top: 1px solid<?php echo esc_attr($borderColor); ?>;
		}

		.cell-label {
			padding: 12px 0;
			font-weight: bold;
			color: <?php echo esc_attr($textColor); ?>;
			vertical-align: top;
			width: 38%;
		}

		.cell-value {
			padding: 12px 0;
			color: <?php echo esc_attr($textColor); ?>;
			vertical-align: top;
			text-align: right;
			width: 62%;
		}

		.value-wrap {
			word-break: break-word;
			padding: 15px;
		}

		.badge {
			display: inline-block;
			padding: 3px 10px;
			background: #F3F4F6;
			color: #111827;
			border: 1px solid<?php echo esc_attr($borderColor); ?>;
			border-radius: 999px;
			font-size: 12px;
			line-height: 1.6;
			margin: 2px 0 2px 6px;
		}

		.meta {
			color: <?php echo esc_attr($mutedColor); ?>;
			font-size: 12px;
			margin-top: 16px;
		}

		.footer {
			padding: 16px 24px;
			background: #FAFAFA;
			font-family: Arial, Helvetica, sans-serif;
			color: <?php echo esc_attr($mutedColor); ?>;
			font-size: 12px;
			text-align: center;
		}

		a {
			color: <?php echo esc_attr($linkColor); ?>;
			text-decoration: none;
		}

		@media (max-width: 600px) {
			.content {
				padding: 18px;
			}

			.cell-label, .cell-value {
				display: block;
				width: auto;
				text-align: left;
				padding: 8px 0;
			}
		}
	</style>
</head>
<body>
<table role="presentation" class="container" cellpadding="0" cellspacing="0" width="100%">
	<tr>
		<td align="center">
			<table role="presentation" class="card" cellpadding="0" cellspacing="0" width="100%">
				<tr>
					<td class="header" align="center">
						<h1><?php echo esc_html( $subject ); ?></h1>
					</td>
				</tr>
				<tr align="center">
					<td class="content">
						<?php if ( ! empty( $items ) ) : ?>
							<?php foreach ( $items as $it ) :
								$label = isset( $it['label'] ) ? (string) $it['label'] : (string) ( $it['name'] ?? '' );
								$type = (string) ( $it['type'] ?? '' );
								$raw = $it['value'] ?? '';
								$options = is_array( $it['options'] ?? null ) ? $it['options'] : [];

								$opt_map = [];

								foreach ( $options as $opt ) {
									$ov = isset( $opt['value'] ) ? (string) $opt['value'] : '';
									$ol = isset( $opt['label'] ) ? (string) $opt['label'] : $ov;
									if ( $ov !== '' ) {
										$opt_map[ $ov ] = $ol;
									}
								}

								$display = [];

								if ( $type === 'checkbox' ) {
									if ( empty( $options ) ) {
										$yes = false;
										if ( is_array( $raw ) ) {
											$yes = in_array( '1', $raw, true ) || in_array( 'on', $raw, true ) || in_array( 'true', $raw, true );
										} else {
											$yes = in_array( (string) $raw, [ '1', 'on', 'true' ], true );
										}
										$display = [ $yes ? 'Да' : 'Нет' ];
									} else {
										$vals = is_array( $raw ) ? $raw : [ (string) $raw ];
										foreach ( $vals as $v ) {
											$v = (string) $v;
											if ( $v === '' ) {
												continue;
											}
											$display[] = $opt_map[ $v ] ?? $v;
										}
										if ( empty( $display ) ) {
											$display = [ '—' ];
										}
									}
								} elseif ( $type === 'radio' || $type === 'select' ) {
									$first = '';
									if ( is_array( $raw ) ) {
										foreach ( $raw as $v ) {
											if ( trim( (string) $v ) !== '' ) {
												$first = (string) $v;
												break;
											}
										}
									} else {
										$first = trim( (string) $raw );
									}
									$mapped  = $first !== '' ? ( $opt_map[ $first ] ?? $first ) : '';
									$display = [ $mapped !== '' ? $mapped : '—' ];
								} else {
									if ( is_array( $raw ) ) {
										foreach ( $raw as $v ) {
											$display[] = (string) $v;
										}
										if ( empty( $display ) ) {
											$display = [ '—' ];
										}
									} else {
										$display = [ (string) $raw ];
										if ( trim( (string) $raw ) === '' ) {
											$display = [ '—' ];
										}
									}
								}
								?>
								<table role="presentation" class="row-table" cellpadding="0" cellspacing="0" align="center" style="margin:0 auto; width:100%;">
									<tr>
										<td class="cell-label" style="vertical-align:middle; text-align:left; padding:8px 12px;">
											<?php echo esc_html( $label !== '' ? $label : 'Поле' ); ?>
										</td>
										<td class="cell-value" style="vertical-align:middle; text-align:left; padding:8px 12px;">
											<div class="value-wrap">
												<?php if ( count( $display ) > 1 ): ?>
													<?php foreach ( $display as $dv ): ?>
														<span class="badge"><?php echo esc_html( $dv === '' ? '—' : $dv ); ?></span>
													<?php endforeach; ?>
												<?php else:
													$val = (string) ( $display[0] ?? '—' );
													echo strt_email_linkify_single( $type, $val );
												endif; ?>
											</div>
										</td>
									</tr>
								</table>
							<?php endforeach; ?>
						<?php endif; ?>

						<table role="presentation" class="row-table" cellpadding="0" cellspacing="0" style="border-top:1px solid <?php echo esc_attr( $borderColor ); ?>;margin-top:8px;">
							<tr>
								<td colspan="2" style="padding-top:12px;">
									<div class="meta">
										Отправлено: <?php echo esc_html( $date ); ?><br/>
										Сайт: <a href="<?php echo esc_url( $site_url ); ?>"><?php echo esc_html( $site_name ); ?></a><br/>
										IP: <?php echo esc_html( $ip ); ?>
										<?php if ( ! empty( $referer ) ) : ?>
											<br/>Страница: <a href="<?php echo esc_url( $referer ); ?>"><?php echo esc_html( $referer ); ?></a>
										<?php endif; ?>
									</div>
								</td>
							</tr>
						</table>
					</td>
				</tr>
				<tr>
					<td class="footer">
						Это письмо сформировано автоматически. Пожалуйста, не отвечайте на него.
						<div><a href="https://wordpress.isvek.ru" target="_blank">wordpress.isvek.ru</a></div>
					</td>
				</tr>
			</table>
		</td>
	</tr>
</table>
</body>
</html>
