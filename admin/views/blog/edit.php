<div class="group-settings blog edit">
    <?=form_open()?>
    <fieldset id="settings-blog-edit-basic">
        <legend>Basic Details</legend>
        <?php


            $field             = array();
            $field['key']      = 'label';
            $field['label']    = 'Label';
            $field['required'] = true;
            $field['default']  = isset($blog->{$field['key']}) ? $blog->{$field['key']} : '';

            echo form_field($field);

            // --------------------------------------------------------------------------

            $field            = array();
            $field['key']     = 'description';
            $field['label']   = 'Description';
            $field['default'] = isset($blog->{$field['key']}) ? $blog->{$field['key']} : '';

            echo form_field($field);

        ?>
    </fieldset>
    <p>
        <?=form_submit('submit', lang('action_save_changes'), 'class="awesome"');?>
    </p>
    <?=form_close();?>
</div>