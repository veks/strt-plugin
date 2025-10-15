<?php
/**
 * TXT-версия письма
 *
 * Переменные: те же, что в HTML.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

echo $subject . "\n\n";

if ( ! empty( $items ) ) {
	foreach ( $items as $it ) {
		$label   = isset( $it['label'] ) ? (string) $it['label'] : (string) ( $it['name'] ?? '' );
		$type    = (string) ( $it['type'] ?? '' );
		$raw     = $it['value'] ?? '';
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

		echo $label . ': ' . implode( ', ', $display ) . "\n";
	}
}

echo "\nОтправлено: " . $date;
echo "\nСайт: " . $site_name . ' (' . $site_url . ')';
echo "\nIP: " . $ip;

if ( ! empty( $referer ) ) {
	echo "\nСтраница: " . $referer;
}

echo "\n";
