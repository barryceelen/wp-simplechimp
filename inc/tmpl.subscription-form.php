<form class="simplechimp" action="" method="post">
	<?php
	wp_nonce_field(
		'simplechimp_subscribe',
		'simplechimp_subscribe',
		'',
		false
		);
	?>
	<span class="simplechimp-feedback<?php echo $class ?>"<?php if ( '' == $class) echo 'style="display:none;"'; ?>>
		<?php echo $message; ?>
	</span>
	<input type="hidden" name="action" value="simplechimp-subscribe" />
	<input type="text" name="simplechimp-email" class="simplechimp-email" value="" placeholder="<?php echo $this->options['strings']['placeholder'] ?>">
	<button type="submit" class="simplechimp-submit" /><?php echo $this->options['strings']['label_submit'] ?></button>
</form>
