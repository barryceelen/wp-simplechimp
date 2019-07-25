<form class="simplechimp" action="<?php echo esc_attr( $action ); ?>" method="post"<?php echo $id; ?>>
	<?php
	wp_nonce_field(
		'simplechimp_subscribe',
		'simplechimp_subscribe',
		'',
		true
		);
	?>
	<span class="simplechimp-feedback<?php echo esc_attr( $class ); ?>"<?php echo $style; ?>>
		<?php echo wp_kses_post( $message ); ?>
	</span>
	<input type="email" name="simplechimp_email" value="<?php echo esc_attr( $value ); ?>" placeholder="<?php echo esc_html( $options['labels']['placeholder'] ); ?>">
	<button name="simplechimp_submit" type="submit" /><?php echo esc_html( $options['labels']['submit'] ); ?></button>
</form>
