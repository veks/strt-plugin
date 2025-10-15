<?php

defined( 'ABSPATH' ) || exit;

$is_show = ! empty( $attributes['show'] ) ? ' show' : '';
?>

<div class="accordion-item">
	<h3 class="accordion-header">
		<button
			class="accordion-button"
			type="button"
			data-bs-toggle="collapse"
			data-bs-target="#collapse-<?php echo esc_attr( $attributes['blockId'] ); ?>"
			aria-expanded="false"
			aria-controls="collapseOne"
		>
			<?php echo esc_attr( $attributes['title'] ); ?>
		</button>
	</h3>
	<div
		id="collapse-<?php echo esc_attr( $attributes['blockId'] ); ?>"
		class="accordion-collapse collapse<?php echo esc_attr( $is_show ); ?>"
		data-bs-parent="#accordion-<?php echo esc_attr( $block->context['strt-plugin/accordionId'] ?? $attributes['parentId'] ?? '' ); ?>"
	>
		<div class="accordion-body">
			<?php echo $content; ?>
		</div>
	</div>
</div>
