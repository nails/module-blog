<div class="group-settings blog">

    <?php

        if (count($blogs) > 1) {

            echo '<p>';
                echo 'Please select which blog you\'d like to configure:';
            echo '</p>';

            echo form_open(null, 'method="GET"');
                echo '<select name="blog_id" class="select2" id="blog-id" placeholder="Please select a blog">';
                    echo '<option></option>';

                    foreach ($blogs as $id => $label) {

                        if (userHasPermission('admin:blog:settings:' . $id . ':update')) {

                            $selected = !empty($selectedBlogId) && $selectedBlogId == $id ? 'selected="selected"' : '';
                            echo '<option ' . $selected . ' value="' . $id . '">' . $label . '</option>';
                        }
                    }

                echo '</select>';
                echo '<noscript>';
                    echo '<button type="submit" class="awesome small">Select</button>';
                echo '</noscript>';
            echo form_close();

        } else {

            echo '<p>';
                echo 'Configure your blog using the options below.';
            echo '</p>';
        }

        echo '<hr />';

        if (!empty($selectedBlogId)) {

            ?>
            <ul class="tabs">
                <?php $active = $this->input->post('update') == 'settings' || !$this->input->post() ? 'active' : ''?>
                <li class="tab <?=$active?>">
                    <a href="#" data-tab="tab-general">General</a>
                </li>

                <?php $active = $this->input->post('update') == 'skin' ? 'active' : ''?>
                <li class="tab <?=$active?>">
                    <a href="#" data-tab="tab-skin">Skin</a>
                </li>

                <?php $active = $this->input->post('update') == 'commenting' ? 'active' : ''?>
                <li class="tab <?=$active?>">
                    <a href="#" data-tab="tab-commenting">Commenting</a>
                </li>

                <?php $active = $this->input->post('update') == 'social' ? 'active' : ''?>
                <li class="tab <?=$active?>">
                    <a href="#" data-tab="tab-social">Social Tools</a>
                </li>

                <?php $active = $this->input->post('update') == 'sidebar' ? 'active' : ''?>
                <li class="tab <?=$active?>">
                    <a href="#" data-tab="tab-sidebar">Sidebar</a>
                </li>
            </ul>

            <section class="tabs">
                <?php $display = $this->input->post('update') == 'settings' || !$this->input->post() ? 'active' : ''?>
                <div class="tab-page tab-general <?=$display?>">
                    <?=form_open('admin/blog/settings?blog_id=' . $selectedBlogId, 'style="margin-bottom:0;"')?>
                    <?=form_hidden('update', 'settings')?>
                    <p>
                        Generic blog settings. Use these to control some blog behaviours.
                    </p>
                    <hr />
                    <fieldset id="blog-settings-name">
                        <legend>Name</legend>
                        <p>
                            Is this a blog? Or is it news? Or something else altogether...
                        </p>
                        <?php

                            //  Blog Name
                            $field                = array();
                            $field['key']         = 'name';
                            $field['label']       = 'Blog Name';
                            $field['default']     = !empty($settings[$field['key']]) ? $settings[$field['key']] : 'Blog';
                            $field['placeholder'] = 'Customise the Blog\'s Name';

                            echo form_field($field);

                        ?>
                    </fieldset>

                    <fieldset id="blog-settings-url">
                        <legend>URL</legend>
                        <p>
                            Customise the blog's URL by specifying it here.
                        </p>
                        <?php

                            //  Blog URL
                            $field                = array();
                            $field['key']         = 'url';
                            $field['label']       = 'Blog URL';
                            $field['default']     = !empty($settings[$field['key']]) ? $settings[$field['key']] : 'blog/';
                            $field['placeholder'] = 'Customise the Blog\'s URL (include trialing slash)';

                            echo form_field($field);

                        ?>
                    </fieldset>

                    <fieldset id="blog-settings-excerpts">
                        <legend>Post Excerpts</legend>
                        <p>
                            Excerpts are short post summaries of posts. If enabled these sumamries will be shown
                            beneath the title of the post on the main blog page (i.e to read the full post the
                            user will have to click through to the post itself).
                        </p>
                        <?php

                            //  Enable/disable post excerpts
                            $field            = array();
                            $field['key']     = 'use_excerpts';
                            $field['label']   = 'Use excerpts';
                            $field['default'] = !empty($settings[$field['key']]) ? $settings[$field['key']] : '';

                            echo form_field_boolean($field);
                        ?>
                    </fieldset>

                    <fieldset id="blog-settings-galcattag">
                        <legend>Galleries, Categories &amp; Tags</legend>
                        <?php

                            //  Enable/disable Gallery
                            $field            = array();
                            $field['key']     = 'gallery_enabled';
                            $field['label']   = 'Post Gallery';
                            $field['default'] = !empty($settings[$field['key']]) ? $settings[$field['key']] : '';

                            echo form_field_boolean($field);

                            // --------------------------------------------------------------------------

                            //  Enable/disable categories
                            $field            = array();
                            $field['key']     = 'categories_enabled';
                            $field['label']   = 'Categories';
                            $field['default'] = !empty($settings[$field['key']]) ? $settings[$field['key']] : '';

                            echo form_field_boolean($field);

                            // --------------------------------------------------------------------------

                            //  Enable/disable tags
                            $field            = array();
                            $field['key']     = 'tags_enabled';
                            $field['label']   = 'Tags';
                            $field['default'] = !empty($settings[$field['key']]) ? $settings[$field['key']] : '';

                            echo form_field_boolean($field);

                        ?>
                    </fieldset>

                    <fieldset id="blog-settings-rss">
                        <legend>RSS</legend>
                        <?php

                            $field            = array();
                            $field['key']     = 'rss_enabled';
                            $field['label']   = 'RSS Enabled';
                            $field['default'] = !empty($settings[$field['key']]) ? true : false;

                            echo form_field_boolean($field);
                        ?>
                    </fieldset>
                    <p style="margin-top:1em;margin-bottom:0;">
                        <?=form_submit('submit', lang('action_save_changes'), 'class="awesome" style="margin-bottom:0;"')?>
                    </p>
                    <?=form_close()?>
                </div>

                <?php $display = $this->input->post('update') == 'skin' ? 'active' : ''?>
                <div class="tab-page tab-skin <?=$display?>">
                    <?=form_open('admin/blog/settings?blog_id=' . $selectedBlogId, 'style="margin-bottom:0;"')?>
                    <?=form_hidden('update', 'skin')?>
                    <p>
                        The following Blog skins are available to use.
                    </p>
                    <hr />
                    <?php

                        if ($skins) {

                            $selectedSkin = !empty($settings['skin']) ? $settings['skin'] : 'blog-skin-classic';

                            echo '<ul class="skins">';
                            foreach ($skins as $skin) {

                                $name        = !empty($skin->name) ? $skin->name : 'Untitled';
                                $description = !empty($skin->description) ? $skin->description : '';

                                if (file_exists($skin->path . 'icon.png')) {

                                    $icon = $skin->url . 'icon.png';

                                } elseif (file_exists($skin->path . 'icon.jpg')) {

                                    $icon = $skin->url . 'icon.jpg';

                                } elseif (file_exists($skin->path . 'icon.gif')) {

                                    $icon = $skin->url . 'icon.gif';

                                } else {

                                    $icon = NAILS_ASSETS_URL . 'img/admin/modules/settings/blog-skin-no-icon.png';
                                }

                                $selected  = $skin->slug == $selectedSkin ? true : false;
                                $class     = $selected ? 'selected' : '';

                                echo '<li class="skin ' . $class . '" rel="tipsy" title="' . $description . '">';
                                    echo '<div class="icon">' . img($icon) . '</div>';
                                    echo '<div class="name">';
                                        echo $name;
                                        echo '<span class="fa fa-check-circle"></span>';
                                    echo '</div>';
                                    echo form_radio('skin', $skin->slug, $selected);
                                echo '</li>';
                            }
                            echo '</ul>';

                            echo '<hr class="clearfix" />';

                        } else {

                            echo '<p class="system-alert error">';
                                echo '<strong>Error:</strong> ';
                                echo 'I\'m sorry, but I couldn\'t find any skins to use. This is a configuration error and should be raised with the developer.';
                            echo '</p>';
                        }

                    ?>
                    <p>
                        <?=form_submit('submit', lang('action_save_changes'), 'class="awesome" style="margin-bottom:0;"')?>
                    </p>
                    <?=form_close()?>
                </div>

                <?php $display = $this->input->post('update') == 'commenting' ? 'active' : ''?>
                <div class="tab-page tab-commenting <?=$display?>">
                    <?=form_open('admin/blog/settings?blog_id=' . $selectedBlogId, 'style="margin-bottom:0;"')?>
                    <?=form_hidden('update', 'commenting')?>
                    <p>
                        Customise how commenting works on your blog.
                    </p>
                    <hr />
                    <fieldset id="blog-settings-comments">
                        <legend>Post comments enabled</legend>
                        <?php

                            $field             = array();
                            $field['key']      = 'comments_enabled';
                            $field['label']    = 'Comments Enabled';
                            $field['default']  = !empty($settings[$field['key']]) ? true : false;

                            echo form_field_boolean($field);
                        ?>
                    </fieldset>

                    <fieldset id="blog-settings-comments-engine">
                        <legend>Post comments powered by</legend>
                        <p>
                            Choose which engine to use for blog post commenting. Please note that
                            existing comments will not be carried through to another service should
                            this value be changed.
                        </p>
                        <?php

                            $field             = array();
                            $field['key']      = 'comments_engine';
                            $field['label']    = 'Comment Engine';
                            $field['default']  = !empty($settings[$field['key']]) ? $settings[$field['key']] : 'NATIVE';
                            $field['class']    = 'select2';
                            $field['id']       = 'comment-engine';

                            $options           = array();
                            $options['NATIVE'] = 'Native';
                            $options['DISQUS'] = 'Disqus';

                            echo form_field_dropdown($field, $options);
                        ?>

                        <hr />

                        <div id="native-settings" style="display:<?=empty($settings[$field['key']]) || $settings[$field['key']] == 'NATIVE' ? 'block' : 'none'?>">
                            <p class="system-alert message">
                                <strong>Coming Soon!</strong> Native commenting is in the works and will be available soon.
                                <?php

                                    //  TODO: Need to be able to handle a lot with native commenting, e.g
                                    //  - anonymous comments/forced login etc
                                    //  - pingbacks?
                                    //  - anything else WordPress might do?

                                ?>
                            </p>
                        </div>

                        <div id="disqus-settings" style="display:<?=!empty($settings[$field['key']]) && $settings[$field['key']] == 'DISQUS' ? 'block' : 'none'?>">
                        <?php

                            //  Blog URL
                            $field                 = array();
                            $field['key']          = 'comments_disqus_shortname';
                            $field['label']        = 'Disqus Shortname';
                            $field['default']      = !empty($settings[$field['key']]) ? $settings[$field['key']] : '';
                            $field['placeholder']  = 'The Disqus shortname for this website.';

                            echo form_field($field, 'Create a shortname at disqus.com.');

                        ?>
                        </div>
                    </fieldset>
                    <p style="margin-top:1em;margin-bottom:0;">
                        <?=form_submit('submit', lang('action_save_changes'), 'class="awesome" style="margin-bottom:0;"')?>
                    </p>
                    <?=form_close()?>
                </div>

                <?php $display = $this->input->post('update') == 'social' ? 'active' : ''?>
                <div class="tab-page tab-social <?=$display?>">
                    <?=form_open('admin/blog/settings?blog_id=' . $selectedBlogId, 'style="margin-bottom:0;"')?>
                    <?=form_hidden('update', 'social')?>
                    <p>
                        Place social sharing tools on your blog post pages.
                    </p>
                    <hr />
                    <fieldset id="blog-settings-social">
                        <legend>Enable Services</legend>
                        <?php

                            $field             = array();
                            $field['key']      = 'social_facebook_enabled';
                            $field['label']    = 'Facebook';
                            $field['id']       = 'social-service-facebook';
                            $field['default']  = !empty($settings[$field['key']]) ? true : false;

                            echo form_field_boolean($field);

                            // --------------------------------------------------------------------------

                            $field             = array();
                            $field['key']      = 'social_twitter_enabled';
                            $field['label']    = 'Twitter';
                            $field['id']       = 'social-service-twitter';
                            $field['default']  = !empty($settings[$field['key']]) ? true : false;

                            echo form_field_boolean($field);

                            // --------------------------------------------------------------------------

                            $field             = array();
                            $field['key']      = 'social_googleplus_enabled';
                            $field['label']    = 'Google+';
                            $field['id']       = 'social-service-googleplus';
                            $field['default']  = !empty($settings[$field['key']]) ? true : false;

                            echo form_field_boolean($field);

                            // --------------------------------------------------------------------------

                            $field             = array();
                            $field['key']      = 'social_pinterest_enabled';
                            $field['label']    = 'Pinterest';
                            $field['id']       = 'social-service-pinterest';
                            $field['default']  = !empty($settings[$field['key']]) ? true : false;

                            echo form_field_boolean($field);
                        ?>
                    </fieldset>
                    <fieldset id="blog-settings-social-twitter" style="display:<?=!empty($settings['social_twitter_enabled']) ? 'block' : 'none' ?>">
                        <legend>Twitter Settings</legend>
                        <?php

                            $field                 = array();
                            $field['key']          = 'social_twitter_via';
                            $field['label']        = 'Via';
                            $field['default']      = !empty($settings[$field['key']]) ? $settings[$field['key']] : '';
                            $field['placeholder']  = 'Put your @username here to add it to the tweet';

                            echo form_field($field);
                        ?>
                    </fieldset>
                    <fieldset id="blog-settings-social-config" style="display:<?=!empty($settings['social_enabled']) ? 'block' : 'none' ?>">
                        <legend>Customisation</legend>
                        <?php

                            $field                 = array();
                            $field['key']          = 'social_skin';
                            $field['label']        = 'Skin';
                            $field['class']        = 'select2';
                            $field['default']      = !empty($settings[$field['key']]) ? $settings[$field['key']] : 'CLASSIC';

                            $options               = array();
                            $options['CLASSIC']    = 'Classic';
                            $options['FLAT']       = 'Flat';
                            $options['BIRMAN']     = 'Birman';

                            echo form_field_dropdown($field, $options);

                            // --------------------------------------------------------------------------

                            $field                 = array();
                            $field['key']          = 'social_layout';
                            $field['label']        = 'Layout';
                            $field['class']        = 'select2';
                            $field['id']           = 'blog-settings-social-layout';
                            $field['default']      = !empty($settings[$field['key']]) ? $settings[$field['key']] : 'HORIZONTAL';

                            $options               = array();
                            $options['HORIZONTAL'] = 'Horizontal';
                            $options['VERTICAL']   = 'Vertical';
                            $options['SINGLE']     = 'Single Button';

                            echo form_field_dropdown($field, $options);

                            // --------------------------------------------------------------------------

                            $display = !empty($settings[$field['key']]) && $settings[$field['key']] == 'SINGLE' ? 'block' : 'none';

                            echo '<div id="blog-settings-social-layout-single-text" style="display:' . $display . '">';

                                $field                 = array();
                                $field['key']          = 'social_layout_single_text';
                                $field['label']        = 'Button Text';
                                $field['default']      = !empty($settings[$field['key']]) ? $settings[$field['key']] : 'Share';
                                $field['placeholder']  = 'Specify what text should be rendered on the button';

                                echo form_field($field);

                            echo '</div>';


                            // --------------------------------------------------------------------------

                            $field             = array();
                            $field['key']      = 'social_counters';
                            $field['label']    = 'Show Counters';
                            $field['id']       = 'social-counters';
                            $field['default']  = !empty($settings[$field['key']]) ? true : false;

                            echo form_field_boolean($field);
                        ?>
                    </fieldset>
                    <p style="margin-top:1em;margin-bottom:0;">
                        <?=form_submit('submit', lang('action_save_changes'), 'class="awesome" style="margin-bottom:0;"')?>
                    </p>
                    <?=form_close()?>
                </div>

                <?php $display = $this->input->post('update') == 'sidebar' ? 'active' : ''?>
                <div class="tab-page tab-sidebar <?=$display?>">
                    <?=form_open('admin/blog/settings?blog_id=' . $selectedBlogId, 'style="margin-bottom:0;"')?>
                    <?=form_hidden('update', 'sidebar')?>
                    <p>
                        Configure the sidebar widgets.
                    </p>
                    <hr />
                    <fieldset id="blog-settings-blog-sidebar">
                        <legend>Enable Widgets</legend>
                        <?php

                            $field             = array();
                            $field['key']      = 'sidebar_latest_posts';
                            $field['label']    = 'Latest Posts';
                            $field['default']  = !empty($settings[$field['key']]) ? true : false;

                            echo form_field_boolean($field);

                            // --------------------------------------------------------------------------

                            if (!empty($settings['categories_enabled'])) :

                                $field             = array();
                                $field['key']      = 'sidebar_categories';
                                $field['label']    = 'Categories';
                                $field['default']  = !empty($settings[$field['key']]) ? true : false;

                                echo form_field_boolean($field);

                            endif;

                            // --------------------------------------------------------------------------

                            if (!empty($settings['tags_enabled'])) :

                                $field             = array();
                                $field['key']      = 'sidebar_tags';
                                $field['label']    = 'Tags';
                                $field['default']  = !empty($settings[$field['key']]) ? true : false;

                                echo form_field_boolean($field);

                            endif;

                            // --------------------------------------------------------------------------

                            $field             = array();
                            $field['key']      = 'sidebar_popular_posts';
                            $field['label']    = 'Popular Posts';
                            $field['default']  = !empty($settings[$field['key']]) ? true : false;

                            echo form_field_boolean($field);

                            $associations = $this->blog_model->get_associations();

                            if (is_array($associations)) :

                                foreach ($associations as $assoc) :

                                    $field             = array();
                                    $field['key']      = 'sidebar_association_' . $assoc->slug;
                                    $field['label']    = $assoc->widget->label;
                                    $field['default']  = !empty($settings[$field['key']]) ? true : false;

                                    echo form_field_boolean($field);

                                endforeach;

                            endif;

                        ?>
                    </fieldset>
                    <p style="margin-top:1em;margin-bottom:0;">
                        <?=form_submit('submit', lang('action_save_changes'), 'class="awesome" style="margin-bottom:0;"')?>
                    </p>
                    <?=form_close()?>
                </div>
            </section>
            <?php

        } else {

            echo '<p class="system-alert" id="alert-choose-blog" style="margin-bottom:5px;">';
                echo 'Please choose a blog above to begin configuring.';
            echo '<p>';
        }

    ?>
</div>