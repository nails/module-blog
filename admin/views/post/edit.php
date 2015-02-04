<div class="group-blog edit">

    <?=form_open(NULL, 'id="post-form"')?>

        <ul class="tabs">
            <li class="tab active">
                <a href="#" data-tab="tab-meta" id="tabber-meta">Basic Information</a>
            </li>
            <li class="tab">
                <a href="#" data-tab="tab-body" id="tabber-body">Body</a>
            </li>

            <?php if (app_setting('categories_enabled', 'blog-' . $blog->id)) : ?>
            <li class="tab">
                <a href="#" data-tab="tab-categories" id="tabber-categories">Categories</a>
            </li>
            <?php endif; ?>

            <?php if (app_setting('tags_enabled', 'blog-' . $blog->id)) : ?>
            <li class="tab">
                <a href="#" data-tab="tab-tags" id="tabber-tags">Tags</a>
            </li>
            <?php endif; ?>

            <?php if ($associations) : ?>
            <li class="tab">
                <a href="#" data-tab="tab-associations" id="tabber-associations">Associations</a>
            </li>
            <?php endif; ?>

            <?php if (app_setting('gallery_enabled', 'blog-' . $blog->id)) : ?>
            <li class="tab">
                <a href="#" data-tab="tab-gallery" id="tabber-gallery">Gallery</a>
            </li>
            <?php endif; ?>

            <li class="tab">
                <a href="#" data-tab="tab-seo" id="tabber-seo">SEO</a>
            </li>
        </ul>
        <section class="tabs pages">

            <div class="tab page fieldset active" id="tab-meta">
            <?php

                //  Title
                $field                 = array();
                $field['key']          = 'title';
                $field['label']        = 'Title';
                $field['required']     = TRUE;
                $field['default']      = isset($post->title) ? $post->title : '';
                $field['placeholder']  = 'The title of the post';

                echo form_field($field);

                // --------------------------------------------------------------------------

                //  Excerpt
                if (app_setting('use_excerpts', 'blog-' . $blog->id)) :

                    $field                 = array();
                    $field['key']          = 'excerpt';
                    $field['class']        = 'wysiwyg-basic';
                    $field['label']        = 'Excerpt';
                    $field['default']      = isset($post->excerpt) ? $post->excerpt : '';
                    $field['placeholder']  = 'A short excerpt of the post, this will be shown in locations where a summary is required. If not specified the a truncated version of the main body will be used instead.';

                    echo form_field_wysiwyg($field);

                endif;

                // --------------------------------------------------------------------------

                //  Featured Image
                $field                 = array();
                $field['key']          = 'image_id';
                $field['label']        = 'Featured Image';
                $field['bucket']       = 'blog';
                $field['default']      = isset($post->image_id) ? $post->image_id : '';

                echo form_field_mm_image($field);

                // --------------------------------------------------------------------------

                //  Published
                $field                 = array();
                $field['key']          = 'is_published';
                $field['label']        = 'Published';
                $field['text_on']      = 'YES';
                $field['text_off']     = 'NO';
                $field['default']      = isset($post->is_published) ? $post->is_published : FALSE;
                $field['id']           = 'is-published';

                echo form_field_boolean($field);

                // --------------------------------------------------------------------------

                //  Published Date
                echo '<div id="publish-date">';

                    $field                 = array();
                    $field['key']          = 'published';
                    $field['label']        = 'Publish Date';
                    $field['required']     = TRUE;
                    $field['default']      = isset($post->published) ? userMysqlDatetime($post->published) : '';
                    $field['placeholder']  = 'The publish date for this blog post';

                    echo form_field_datetime($field);

                echo '</div>';

            ?>
            </div>

            <?php

                $_key       = 'body';
                $_default   = isset($post->body) ? $post->body : '';

            ?>
            <div class="tab page" id="tab-body">
                <?=form_error('body', '<p class="system-alert error">', '</p>')?>
                <?=form_textarea($_key, set_value($_key, $_default), 'class="wysiwyg" id="post_body"')?>
            </div>

            <?php if (app_setting('categories_enabled', 'blog-' . $blog->id)) : ?>
                <div class="tab page" id="tab-categories">
                    <p>
                        Organise your posts and help user's find them by assigning <u rel="tipsy" title="Categories allow for a broad grouping of post topics and should be considered top-level 'containers' for posts of similar content.">categories</u>.
                    </p>
                    <p>
                        <select name="categories[]" multiple="multiple" class="select2 categories">
                        <?php

                            $_post_cats = array();
                            if (isset($post->categories)) :

                                foreach ($post->categories as $cat) :

                                    $_post_cats[] = $cat->id;

                                endforeach;

                            endif;

                            $_post_raw  = $this->input->post('categories') ? $this->input->post('categories') : $_post_cats;
                            $_post      = array();

                            foreach ($_post_raw as $key => $value) :

                                $_post[$value] = TRUE;

                            endforeach;

                            foreach ($categories as $category) :

                                $_selected = isset($_post[$category->id]) ? 'selected="selected"' : '';
                                echo '<option value="' . $category->id . '" ' . $_selected . '>' . $category->label . '</option>';

                            endforeach;

                        ?>
                        </select>
                    </p>
                    <p>
                        <a href="#" class="manage-categories awesome orange small">Manage Categories</a>
                    </p>
                </div>
            <?php endif; ?>

            <?php if (app_setting('tags_enabled', 'blog-' . $blog->id)) : ?>
                <div class="tab page" id="tab-tags">
                    <p>
                        Organise your posts and help user's find them by assigning <u rel="tipsy" title="Tags are generally used to describe your post in more detail.">tags</u>.
                    </p>
                    <p>
                        <select name="tags[]" multiple="multiple" class="tags select2">
                        <?php

                            $_post_tags = array();
                            if (isset($post->tags)) :

                                foreach ($post->tags as $tag) :

                                    $_post_tags[] = $tag->id;

                                endforeach;

                            endif;

                            $_post_raw  = $this->input->post('tags') ? $this->input->post('tags') : $_post_tags;
                            $_post      = array();

                            foreach ($_post_raw as $key => $value) :

                                $_post[$value] = TRUE;

                            endforeach;

                            foreach ($tags as $tag) :

                                $_selected = isset($_post[$tag->id]) ? 'selected="selected"' : '';
                                echo '<option value="' . $tag->id . '" ' . $_selected . '>' . $tag->label . '</option>';

                            endforeach;

                        ?>
                        </select>
                    </p>
                    <p>
                        <a href="#" class="manage-tags awesome orange small">Manage Tags</a>
                    </p>
                </div>
            <?php endif; ?>


            <?php if ($associations) : ?>
            <div class="tab page" id="tab-associations">

                <p>
                    It's possible for you to associate this blog post with other bits of related content. The following associations can be defined.
                </p>
                <?php

                    foreach ($associations as $index => $assoc) :

                        echo '<fieldset class="association" id="edit-blog-post-association-' . $index . '">';
                        echo isset($assoc->legend) && $assoc->legend ? '<legend>' . $assoc->legend . '</legend>' : '';
                        echo isset($assoc->description) && $assoc->description ? '<p>' . $assoc->description . '</p>' : '';

                        $_multiple = isset($assoc->multiple) && $assoc->multiple ? 'multiple="multiple"' : '';

                        if ($this->input->post()) :

                            $_selected = $this->input->post('associations');
                            $_selected = $_selected[$index];

                        else :

                            $_selected = array();

                            foreach ($assoc->current as $current) :

                                $_selected[] = $current->associated_id;

                            endforeach;

                        endif;

                        echo '<select name="associations[' . $index . '][]" ' . $_multiple . ' class="select2">';

                            foreach ($assoc->data as $data) :

                                $_checked = array_search($data->id, $_selected) !== FALSE ? 'selected="selected"' : '';
                                echo '<option value="' . $data->id . '" ' . $_checked . '>' . $data->label . '</option>';

                            endforeach;

                        echo '</select>';
                        echo '</fiedset>';

                    endforeach;

                ?>

            </div>
            <?php endif; ?>

            <?php if (app_setting('gallery_enabled', 'blog-' . $blog->id)) : ?>
            <div class="tab page" id="tab-gallery">
                <p>
                    Upload images to the post gallery.
                    <small>
                    <?php

                        $_max_upload    = ini_get('upload_max_filesize');
                        $_max_upload    = return_bytes($_max_upload);

                        $_max_post      = ini_get('post_max_size');
                        $_max_post      = return_bytes($_max_post);

                        $_memory_limit  = ini_get('memory_limit');
                        $_memory_limit  = return_bytes($_memory_limit);

                        $_upload_mb     = min($_max_upload, $_max_post, $_memory_limit);
                        $_upload_mb     = format_bytes($_upload_mb);

                        echo 'Images only, max file size is ' . $_upload_mb . '.';

                    ?>
                    </small>
                </p>
                <p>
                    <input type="file" id="file_upload" />
                </p>
                <p class="system-alert notice" id="upload-message" style="display:none">
                    <strong>Please be patient while files upload.</strong>
                    <br />Tabs have been disabled until uploads are complete.
                </p>
                <?php

                    //  Determine gallery items to render
                    if ($this->input->post('gallery')) :

                        $_gallery_items = (array) $this->input->post('gallery');

                    elseif (! empty($post->gallery)) :

                        $_gallery_items = array();

                        foreach ($post->gallery as $item) :

                            $_gallery_items[] = $item->image_id;

                        endforeach;

                    else :

                        $_gallery_items = array();

                    endif;

                ?>
                <ul id="gallery-items" class="<?=$_gallery_items ? '' : 'empty' ?>">
                    <li class="empty">
                        No images, why not upload some?
                    </li>
                    <?php

                        foreach ($_gallery_items as $image) :


                            echo \Nails\Admin\Helper::loadInlineView(
                                '_utilities/template-mustache-gallery-item',
                                array('objectId' => $image)
                            );

                        endforeach;

                    ?>
                </ul>
            </div>
            <?php endif; ?>

            <div class="tab page fieldset" id="tab-seo">
                <?php

                //  Keywords
                $field                 = array();
                $field['key']          = 'seo_title';
                $field['label']        = 'Title';
                $field['default']      = isset($post->seo_title) ? $post->seo_title : '';
                $field['placeholder']  = 'The SEO optimised title of the post.';

                echo form_field($field, 'Should you want or need to specify a different title for the page for SEO purposes do so here.');

                // --------------------------------------------------------------------------

                //  Description
                $field                 = array();
                $field['key']          = 'seo_description';
                $field['type']         = 'textarea';
                $field['label']        = 'Description';
                $field['default']      = isset($post->seo_description) ? $post->seo_description : '';
                $field['placeholder']  = 'The post\'s SEO description';

                echo form_field($field, 'This should be kept short (< 160 characters) and concise. It\'ll be shown in search result listings and search engines will use it to help determine the post\'s content.');

                // --------------------------------------------------------------------------

                //  Keywords
                $field                 = array();
                $field['key']          = 'seo_keywords';
                $field['label']        = 'Keywords';
                $field['default']      = isset($post->seo_keywords) ? $post->seo_keywords : '';
                $field['placeholder']  = 'Comma separated keywords relating to the content of the post.';

                echo form_field($field, 'SEO good practice recommend keeping the number of keyword phrases below 10 and less than 160 characters in total.');

                ?>
            </div>

        </section>

        <p>
            <?=form_submit('submit', lang('action_save_changes'), 'class="awesome"')?>
        </p>

    <?=form_close()?>
</div>
<script type="text/template" id="template-gallery-item">
<?php

    echo \Nails\Admin\Helper::loadInlineView(
        '_utilities/template-mustache-gallery-item',
        array('objectId' => null)
    );

?>
</script>
<script type="text/template" id="template-uploadify">
    <li class="gallery-item uploadify-queue-item" id="${fileID}" data-instance_id="${instanceID}" data-file_id="${fileID}">
        <a href="#" data-instance_id="${instanceID}" data-file_id="${fileID}" class="remove"></a>
        <div class="progress" style="height:0%"></div>
        <div class="data data-cancel">CANCELLED</div>
    </li>
</script>
<div id="dialog-confirm-delete" title="Confirm Delete" style="display:none;">
    <p>
        <span class="ui-icon ui-icon-alert" style="float: left; margin: 0 7px 0 0;"></span>
        This item will be removed from the interface and cannot be recovered.
        <strong>Are you sure?</strong>
    </p>
</div>