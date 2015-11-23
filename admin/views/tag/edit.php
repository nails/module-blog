<div class="group-blog manage tags edit">
    <p>
        Use tags to group specific <?=$postName?> topics together. For example, a tag might be 'New Year <?=date('Y')?>', or 'Coursework'.
        <?php

            if (appSetting('categories_enabled', 'blog-' . $blog->id)) {

                echo 'For broader subjects (e.g "Music" or "Travel") consider using a ' . anchor('admin/blog/category/index/' . $blog->id . $isModal, 'category') . '.';
            }

        ?>
    </p>
    <ul class="tabs disabled">
        <li class="tab">
            <?=anchor('admin/blog/tag/index/' . $blog->id . $isModal, 'Overview', 'class="confirm" data-body="Any unsaved changes will be lost."')?>
        </li>
        <li class="tab active">
            <?=anchor('admin/blog/tag/create/' . $blog->id . $isModal, 'Create Tag')?>
        </li>
    </ul>
    <section class="tabs pages">
        <div class="tab-page active">
            <?=form_open(uri_string() . '?' . $this->input->server('QUERY_STRING'))?>
            <fieldset>
                <legend>Basic Details</legend>
                <?php

                    $field                 = array();
                    $field['key']          = 'label';
                    $field['label']        = 'Label';
                    $field['required']     = true;
                    $field['default']      = isset($tag->label) ? $tag->label : '';
                    $field['placeholder']  = 'The label to give your tag';

                    echo form_field($field);

                    // --------------------------------------------------------------------------

                    $field                 = array();
                    $field['key']          = 'description';
                    $field['label']        = 'Description';
                    $field['placeholder']  = 'This text may be used on the tag\'s overview page.';
                    $field['default']      = isset($tag->description) ? $tag->description : '';

                    echo form_field_wysiwyg($field);

                ?>
            </fieldset>
            <fieldset>
                <legend>Search Engine Optimisation</legend>
                <?php

                    $field                 = array();
                    $field['key']          = 'seo_title';
                    $field['label']        = 'SEO Title';
                    $field['sub_label']    = 'Max. 150 characters';
                    $field['placeholder']  = 'An alternative, SEO specific title for the tag.';
                    $field['default']      = isset($tag->seo_title) ? $tag->seo_title : '';

                    echo form_field($field);

                    // --------------------------------------------------------------------------

                    $field                 = array();
                    $field['key']          = 'seo_description';
                    $field['label']        = 'SEO Description';
                    $field['sub_label']    = 'Max. 300 characters';
                    $field['type']         = 'textarea';
                    $field['placeholder']  = 'This text will be read by search engines when they\'re indexing the page. Keep this short and concise.';
                    $field['default']      = isset($tag->seo_description) ? $tag->seo_description : '';

                    echo form_field($field);

                    // --------------------------------------------------------------------------

                    $field                 = array();
                    $field['key']          = 'seo_keywords';
                    $field['label']        = 'SEO Keywords';
                    $field['sub_label']    = 'Max. 150 characters';
                    $field['placeholder']  = 'These comma separated keywords help search engines understand the context of the page; stick to 5-10 words.';
                    $field['default']      = isset($tag->seo_keywords) ? $tag->seo_keywords : '';

                    echo form_field($field);

                ?>
            </fieldset>
            <p style="margin-top:1em;">
                <?=form_submit('submit', 'Save', 'class="awesome"')?>
                <?=anchor('admin/blog/tag/index/' . $blog->id . $isModal, 'Cancel', 'class="awesome red confirm" data-body="All unsaved changes will be lost."')?>
            </p>
        </div>
    </section>
    <?=form_close();?>
</div>
<?php

    echo '<script type="text/javascript">';

    /**
     * This variable holds the current state of objects and is read by
     * the calling script when closing the fancybox, feel free to update
     * this during the lietime of the fancybox.
     */

    $data = array();   //  An array of objects in the format {id,label}

    foreach ($tags as $cat) {

        $temp        = new \stdClass();
        $temp->id    = $cat->id;
        $temp->label = $cat->label;

        $data[] = $temp;
    }

    echo 'var _DATA = ' . json_encode($data) . ';';
    echo '</script>';
