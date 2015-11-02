<div class="group-blog edit">
    <?php

        echo form_open(null, 'id="post-form"');
        echo '<input type="hidden" name="isPreview" value="0" id="isPreview" />';
        echo '<input type="hidden" name="activeTab" value="' . set_value('activeTab') . '" id="activeTab" />';

    ?>
    <ul class="tabs">
    <?php

        $sActive = $this->input->post('activeTab') == 'tab-title-body' || !$this->input->post('activeTab') ? 'active' : '';

        ?>
        <li class="tab <?=$sActive?>">
            <a href="#" data-tab="tab-title-body" id="tabber-title-body">Main</a>
        </li>
        <?php

        $sActive = $this->input->post('activeTab') == 'tab-meta' ? 'active' : '';

        ?>
        <li class="tab <?=$sActive?>">
            <a href="#" data-tab="tab-meta" id="tabber-meta">Meta</a>
        </li>
        <?php

        if (app_setting('categories_enabled', 'blog-' . $blog->id)) {

            $sActive = $this->input->post('activeTab') == 'tab-categories' ? 'active' : '';

            ?>
            <li class="tab <?=$sActive?>">
                <a href="#" data-tab="tab-categories" id="tabber-categories">Categories</a>
            </li>
            <?php

        }

        if (app_setting('tags_enabled', 'blog-' . $blog->id)) {

            $sActive = $this->input->post('activeTab') == 'tab-tags' ? 'active' : '';

            ?>
            <li class="tab <?=$sActive?>">
                <a href="#" data-tab="tab-tags" id="tabber-tags">Tags</a>
            </li>
            <?php

        }

        if ($associations) {

            $sActive = $this->input->post('activeTab') == 'tab-associations' ? 'active' : '';

            ?>
            <li class="tab <?=$sActive?>">
                <a href="#" data-tab="tab-associations" id="tabber-associations">Associations</a>
            </li>
            <?php

        }

        if (app_setting('gallery_enabled', 'blog-' . $blog->id)) {

            $sActive = $this->input->post('activeTab') == 'tab-gallery' ? 'active' : '';

            ?>
            <li class="tab <?=$sActive?>">
                <a href="#" data-tab="tab-gallery" id="tabber-gallery">Gallery</a>
            </li>
            <?php

        }

        $sActive = $this->input->post('activeTab') == 'tab-seo' ? 'active' : '';

        ?>
        <li class="tab <?=$sActive?>">
            <a href="#" data-tab="tab-seo" id="tabber-seo">SEO</a>
        </li>
    </ul>
    <section class="tabs">
    <?php

        $sActive = $this->input->post('activeTab') == 'tab-title-body' || !$this->input->post('activeTab') ? 'active' : '';

        ?>
        <div class="tab-page tab-title-body <?=$sActive?> fieldset">
            <div class="row">
                <div class="col-md-10" id="post-editor">
                <?php

                    //  Title
                    $aField                = array();
                    $aField['key']         = 'title';
                    $aField['label']       = 'Title';
                    $aField['required']    = true;
                    $aField['default']     = isset($post->title) ? $post->title : '';
                    $aField['placeholder'] = 'The title of the ' . $postName;

                    echo form_field($aField);

                    // --------------------------------------------------------------------------

                    $aField                = array();
                    $aField['key']         = 'slug';
                    $aField['label']       = 'Slug';
                    $aField['default']     = isset($post->slug) ? $post->slug : '';
                    $aField['placeholder'] = 'The slug, leave blank to auto-generate';
                    $aField['tip']         = 'The slug is the posts unique identifier which is shown in the URL. Best ';
                    $aField['tip']        .= 'practice dictates that the slug be "pretty", i.e, human readable.';

                    echo form_field($aField);

                    // --------------------------------------------------------------------------

                    $aField                = array();
                    $aField['key']         = 'body';
                    $aField['label']       = 'Body';
                    $aField['default']     = isset($post->body) ? $post->body : '';
                    $aField['placeholder'] = 'The body of the ' . $postName;

                    echo form_field_wysiwyg($aField);

                    // --------------------------------------------------------------------------

                    //  Excerpt
                    if (app_setting('use_excerpts', 'blog-' . $blog->id)) {

                        $aField                 = array();
                        $aField['id']           = 'post_excerpt';
                        $aField['key']          = 'excerpt';
                        $aField['class']        = 'wysiwyg-basic';
                        $aField['label']        = 'Excerpt';
                        $aField['default']      = isset($post->excerpt) ? $post->excerpt : '';
                        $aField['placeholder']  = 'A short excerpt of the ' . $postName . ', this will be shown in ';
                        $aField['placeholder'] .= 'locations where a summary is required. If not specified the a ';
                        $aField['placeholder'] .= 'truncated version of the main body will be used instead.';

                        echo form_field_wysiwyg($aField);
                    }

                ?>
                </div>
                <div class="col-md-2">
                    <div id="post-type-area">
                        <strong>Post Type</strong>
                        <select name="type" class="select2" id="post-type">
                        <?php

                        if (!empty($_POST['type'])) {

                            $sDefault = $_POST['type'];

                        } else {

                            $sDefault = !empty($post->type) ? $post->type : 'PHOTO';
                        }

                        foreach ($postTypes as $sValue => $sLabel) {

                            $sSelected = $sDefault == $sValue ? 'selected="selected"' : '';
                            echo '<option value="' . $sValue . '" ' . $sSelected . '>';
                                echo $sLabel;
                            echo '</option>';
                        }

                        ?>
                        </select>
                        <div class="type-fields" id="post-type-fields-video">
                            <strong>Video URL</strong>
                            <?php

                                $sDefault = !empty($post->video->url) ? $post->video->url : '';

                                echo form_url(
                                    'video_url',
                                    set_value('video_url', $sDefault),
                                    'placeholder="Video Source URL"'
                                );

                                echo form_error('video_url', '<div class="system-alert error">', '</div>');
                            ?>
                            <small>
                                Copy and paste a URL from YouTube or Vimeo.
                                <a href="#post-help-video" class="fancybox">
                                    <b class="fa fa-question-circle fa-lg"></b>
                                </a>
                            </small>
                            <div id="post-help-video" style="display:none;width:400px;">
                                <p>
                                    <strong>Getting the video's URL</strong>
                                </p>
                                <ol>
                                    <li>Find the video you wish to embed on YouTube or Vimeo</li>
                                    <li>Copy the page's URL from the address bar</li>
                                    <li>Paste this link in the box on this page</li>
                                </ol>
                                <img src="">
                            </div>
                        </div>
                        <div class="type-fields" id="post-type-fields-audio">
                            <strong>Audio URL</strong>
                            <?php

                                $sDefault = !empty($post->audio->url) ? $post->audio->url : '';

                                echo form_url(
                                    'audio_url',
                                    set_value('audio_url', $sDefault),
                                    'placeholder="Audio Source URL"'
                                );

                                echo form_error('audio_url', '<div class="system-alert error">', '</div>');
                            ?>
                            <small>
                                Copy and paste a URL for a track on Spotify.
                                <a href="#post-help-audio" class="fancybox">
                                    <b class="fa fa-question-circle fa-lg"></b>
                                </a>
                            </small>
                            <div id="post-help-audio" style="display:none;width:400px;">
                                <p>
                                    <strong>Getting the track's URL</strong>
                                </p>
                                <ol>
                                    <li>Search for the track you wish to embed</li>
                                    <li>Right click the track</li>
                                    <li>click on "Copy Track Link"</li>
                                    <li>Paste this link in the box on this page</li>
                                </ol>
                                <img src="">
                            </div>
                        </div>
                        <div
                            class="type-fields"
                            id="post-type-fields-photo"
                            data-manager-url="<?=cdnManageUrl('blog', array('_EDIT', 'typePhotoCdnManagerCallback'))?>"
                            data-url-scheme="<?=$cdnUrlScaleScheme?>"
                        >
                            <strong>Featured Image</strong>
                            <?php

                            if ($this->input->post()) {

                                if ($this->input->post('image_id')) {

                                    $sSrc = cdnScale($this->input->post('image_id'), 500, 500);

                                } else {

                                    $sSrc = '';
                                }

                            } elseif (!empty($post->photo->id)) {

                                $sSrc = cdnScale($post->photo->id, 500, 500);

                            } else {

                                $sSrc = '';
                            }

                            ?>
                            <img src="<?=$sSrc?>"/>
                            <?php

                                $sDefault = !empty($post->photo->id) ? $post->photo->id : '';
                                echo form_hidden(
                                    'image_id',
                                    set_value('image_id', $sDefault)
                                );

                                echo form_error('image_id', '<div class="system-alert error">', '</div>');

                            ?>
                            <a href="#" class="awesome small green">
                                Choose
                            </a href="#">
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php

        $sActive = $this->input->post('activeTab') == 'tab-meta' ? 'active' : '';

        ?>
        <div class="tab-page tab-meta <?=$sActive?> fieldset">
        <?php

            $aField             = array();
            $aField['key']      = 'is_published';
            $aField['label']    = 'Published';
            $aField['text_on']  = ucfirst(lang('yes'));
            $aField['text_off'] = ucfirst(lang('no'));
            $aField['default']  = isset($post->is_published) ? $post->is_published : false;
            $aField['id']       = 'is-published';

            echo form_field_boolean($aField);

            // --------------------------------------------------------------------------

            $aField                = array();
            $aField['key']         = 'published';
            $aField['label']       = 'Publish Date';
            $aField['id']          = 'publish-date';
            $aField['required']    = true;
            $aField['default']     = isset($post->published) ? toUserDate($post->published, 'Y-m-d H:i') : '';
            $aField['placeholder'] = 'The publish date for this ' . $postName;
            $aField['data']        = array('datepicker-timeformat' => 'HH:mm');

            echo form_field_datetime($aField);

            // --------------------------------------------------------------------------

            $aField             = array();
            $aField['key']      = 'comments_enabled';
            $aField['label']    = 'Comments';
            $aField['default']  = isset($post->comments_enabled) ? $post->comments_enabled : true;
            $aField['id']       = 'comments-enabled';

            echo form_field_boolean($aField);

            // --------------------------------------------------------------------------

            if (app_setting('comments_engine', 'blog-' . $blog->id) === 'NATIVE') {

                $aField                = array();
                $aField['key']         = 'comments_expire';
                $aField['label']       = 'Auto-Close Comments';
                $aField['id']          = 'comments-expire';
                $aField['default']     = isset($post->comments_expire) ? toUserDate($post->comments_expire, 'Y-m-d H:i') : '';
                $aField['placeholder'] = 'When comments should auto-close, leave blank to never auto-close';
                $aField['data']        = array('datepicker-timeformat' => 'HH:mm');
                $aField['info']        = 'Comments can be automatically closed on a certain date. Leave blank to prevent ';
                $aField['info']       .= 'auto-closing of comments.';

                echo form_field_datetime($aField);
            }
        ?>
        </div>
        <?php

        if (app_setting('categories_enabled', 'blog-' . $blog->id)) {

            $sActive = $this->input->post('activeTab') == 'tab-categories' ? 'active' : '';

            ?>
            <div class="tab-page tab-categories <?=$sActive?>">
                <p>
                    Organise your <?=$postNamePlural?> and help user's find them by assigning <u rel="tipsy"
                    title="Categories allow for a broad grouping of post topics and should be considered top-level
                    'containers' for posts of similar content.">categories</u>.
                </p>
                <p>
                    <select name="categories[]" multiple="multiple" class="select2 categories">
                    <?php

                        $aPostCats = array();
                        if (isset($post->categories)) {
                            foreach ($post->categories as $cat) {

                                $aPostCats[] = $cat->id;
                            }
                        }

                        $aPostRaw = $this->input->post('categories') ? $this->input->post('categories') : $aPostCats;
                        $aPost    = array();

                        foreach ($aPostRaw as $key => $value) {

                            $aPost[$value] = true;
                        }

                        foreach ($categories as $oCategory) {

                            $sSelected = isset($aPost[$oCategory->id]) ? 'selected="selected"' : '';
                            echo '<option value="' . $oCategory->id . '" ' . $sSelected . '>';
                            echo $oCategory->label;
                            echo '</option>';
                        }

                    ?>
                    </select>
                </p>
                <p>
                    <a href="#" class="manage-categories awesome orange small">Manage Categories</a>
                </p>
            </div>
            <?php

        }

        if (app_setting('tags_enabled', 'blog-' . $blog->id)) {

            $sActive = $this->input->post('activeTab') == 'tab-tags' ? 'active' : '';

            ?>
            <div class="tab-page tab-tags <?=$sActive?>">
                <p>
                    Organise your <?=$postNamePlural?> and help user's find them by assigning <u rel="tipsy"
                    title="Tags are generally used to describe your post in more detail.">tags</u>.
                </p>
                <p>
                    <select name="tags[]" multiple="multiple" class="tags select2">
                    <?php

                        $aPostTags = array();
                        if (isset($post->tags)) {
                            foreach ($post->tags as $tag) {

                                $aPostTags[] = $tag->id;
                            }
                        }

                        $aPostRaw = $this->input->post('tags') ? $this->input->post('tags') : $aPostTags;
                        $aPost    = array();

                        foreach ($aPostRaw as $key => $value) {

                            $aPost[$value] = true;
                        }

                        foreach ($tags as $oTag) {

                            $sSelected = isset($aPost[$oTag->id]) ? 'selected="selected"' : '';
                            echo '<option value="' . $oTag->id . '" ' . $sSelected . '>';
                            echo $oTag->label;
                            echo '</option>';
                        }
                    ?>
                    </select>
                </p>
                <p>
                    <a href="#" class="manage-tags awesome orange small">Manage Tags</a>
                </p>
            </div>
            <?php
        }

        if ($associations) {

            $sActive = $this->input->post('activeTab') == 'tab-associations' ? 'active' : '';

            ?>
            <div class="tab-page tab-associations <?=$sActive?> fieldset">
                <p>
                    It's possible for you to associate this <?=$postName?> with other bits of related content.
                    The following associations can be defined.
                </p>
                <?php

                    foreach ($associations as $index => $assoc) {

                        echo '<fieldset class="association" id="edit-blog-post-association-' . $index . '">';
                        echo isset($assoc->legend) && $assoc->legend ? '<legend>' . $assoc->legend . '</legend>' : '';
                        echo isset($assoc->description) && $assoc->description ? '<p>' . $assoc->description . '</p>' : '';

                        $_multiple = isset($assoc->multiple) && $assoc->multiple ? 'multiple="multiple"' : '';

                        if ($this->input->post()) {

                            $aSelected = $this->input->post('associations');
                            if (!empty($aSelected)) {
                                $aSelected = $aSelected[$index];
                            } else {
                                $aSelected = array();
                            }

                        } else {

                            $aSelected = array();

                            foreach ($assoc->current as $current) {

                                $aSelected[] = $current->associated_id;
                            }
                        }

                        echo '<select name="associations[' . $index . '][]" ' . $_multiple . ' class="select2">';

                            foreach ($assoc->data as $data) {

                                $_checked = array_search($data->id, $aSelected) !== false ? 'selected="selected"' : '';
                                echo '<option value="' . $data->id . '" ' . $_checked . '>' . $data->label . '</option>';
                            }

                        echo '</select>';
                        echo '</fiedset>';
                    }
                ?>
            </div>
            <?php
        }

        if (app_setting('gallery_enabled', 'blog-' . $blog->id)) {

            $sActive = $this->input->post('activeTab') == 'tab-gallery' ? 'active' : '';

            ?>
            <div class="tab-page tab-gallery <?=$sActive?>">
                <p>
                    Upload images to the <?=$postName?> gallery.
                    <small>
                    <?php

                        $sMaxUpload = ini_get('upload_max_filesize');
                        $iMaxUpload = return_bytes($sMaxUpload);

                        $sMaxPost = ini_get('post_max_size');
                        $iMaxPost = return_bytes($sMaxPost);

                        $sMemoryLimit  = ini_get('memory_limit');
                        $iMemoryLimit  = return_bytes($sMemoryLimit);

                        $iUploadMb = min($iMaxUpload, $iMaxPost, $iMemoryLimit);
                        $sUploadMb = format_bytes($iUploadMb);

                        echo 'Images only, max file size is ' . $sUploadMb . '.';

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
                    if ($this->input->post('gallery')) {

                        $aGalleryItems = (array) $this->input->post('gallery');

                    } elseif (!empty($post->gallery)) {

                        $aGalleryItems = array();

                        foreach ($post->gallery as $item) {

                            $aGalleryItems[] = $item->image_id;
                        }

                    } else {

                        $aGalleryItems = array();
                    }

                ?>
                <ul id="gallery-items" class="<?=$aGalleryItems ? '' : 'empty' ?>">
                    <li class="empty">
                        No images, why not upload some?
                    </li>
                    <?php

                    foreach ($aGalleryItems as $image) {

                        echo \Nails\Admin\Helper::loadInlineView(
                            '_utilities/template-mustache-gallery-item',
                            array('objectId' => $image)
                        );
                    }

                    ?>
                </ul>
            </div>
            <?php
        }

        $sActive = $this->input->post('activeTab') == 'tab-seo' ? 'active' : '';

        ?>
        <div class="tab-page tab-seo <?=$sActive?> fieldset">
        <?php

            //  Keywords
            $aField                = array();
            $aField['key']         = 'seo_title';
            $aField['label']       = 'Title';
            $aField['default']     = isset($post->seo_title) ? $post->seo_title : '';
            $aField['placeholder'] = 'The SEO optimised title of the ' . $postName . '.';
            $aField['tip']         = 'Should you want or need to specify a different title for the page for SEO ';
            $aField['tip']        .= 'purposes do so here.';

            echo form_field($aField);

            // --------------------------------------------------------------------------

            //  Description
            $aField                = array();
            $aField['key']         = 'seo_description';
            $aField['type']        = 'textarea';
            $aField['label']       = 'Description';
            $aField['default']     = isset($post->seo_description) ? $post->seo_description : '';
            $aField['placeholder'] = 'The ' . $postName . ' SEO description';
            $aField['tip']         = 'This should be kept short (< 160 characters) and concise. It\'ll be shown in ';
            $aField['tip']        .= 'search result listings and search engines will use it to help determine the ';
            $aField['tip']        .= 'post\'s content.';

            echo form_field($aField);

            // --------------------------------------------------------------------------

            //  Keywords
            $aField                = array();
            $aField['key']         = 'seo_keywords';
            $aField['label']       = 'Keywords';
            $aField['default']     = isset($post->seo_keywords) ? $post->seo_keywords : '';
            $aField['placeholder'] = 'Comma separated keywords relating to the content of the ' . $postName . '.';
            $aField['tip']         = 'SEO good practice recommend keeping the number of keyword phrases below 10 and ';
            $aField['tip']        .= 'less than 160 characters in total.';

            echo form_field($aField);

        ?>
        </div>
    </section>
    <p>
        <button type="submit" class="awesome" id="btnSubmit">
            <?=lang('action_save_changes')?>
        </button>
        <button class="awesome js-only" id="btnPreview">Preview</button>
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
