<form class="simplechimp" action="<?php echo $action; ?>" method="post"<?php echo $id; ?>>
	<?php
	wp_nonce_field(
		'simplechimp_subscribe',
		'simplechimp_subscribe',
		'',
		true
		);
	?>
	<span class="simplechimp-feedback<?php echo $class ?>"<?php echo $style; ?>>
		<?php echo $message; ?>
	</span>
	<?php
	// todo set input type to 'email'
	?>
	<input type="text" name="simplechimp_email" value="<?php echo $value; ?>" placeholder="<?php echo self::$options['labels']['placeholder']; ?>">
	<button name="simplechimp_submit" type="submit" /><?php echo self::$options['labels']['submit']; ?></button>
</form>
