<form class="simplechimp" action="<?php echo $action; ?>" method="post"<?php echo $id; ?>>
	<?php
	wp_nonce_field(
		'simplechimp_subscribe',
		'simplechimp_subscribe',
		'',
		true
		);
	?>
	<span class="simplechimp-feedback<?php echo $class ?>"<?php if ( '' == $class ) { echo 'style="display:none;"'; } ?>>
		<?php echo $message; ?>
	</span>
	<input type="text" name="email" class="simplechimp-email" value="<?php echo $value; ?>" placeholder="<?php echo self::$options['labels']['placeholder']; ?>">
	<button type="submit" class="simplechimp-submit" name="simplechimp-submit" /><?php echo self::$options['labels']['submit']; ?></button>
</form>
