<div class="group-settings blog edit">
	<?=form_open()?>
	<fieldset id="settings-blog-edit-basic">
		<legend>Basic Details</legend>
		<?php


			$_field				= array();
			$_field['key']		= 'label';
			$_field['label']	= 'Label';
			$_field['required']	= TRUE;
			$_field['default']	= isset( $blog->label ) ? $blog->label : '';

			echo form_field( $_field );

		?>
	</fieldset>
	<p>
		<?=form_submit( 'submit', lang( 'action_save_changes' ), 'class="awesome"' );?>
	</p>
	<?=form_close();?>
</div>