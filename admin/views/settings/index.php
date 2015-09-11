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
                    <fieldset>
                        <legend>General</legend>
                        <?php

                        //  Blog Name
                        $aField                = array();
                        $aField['key']         = 'name';
                        $aField['label']       = 'Blog Name';
                        $aField['default']     = !empty($settings[$aField['key']]) ? $settings[$aField['key']] : 'Blog';
                        $aField['placeholder'] = 'Customise the Blog\'s Name';
                        $aField['tip']         = 'This is used in the page titles as wella s for the RSS Feed.';

                        echo form_field($aField);

                        // --------------------------------------------------------------------------

                        //  Blog URL
                        $aField                = array();
                        $aField['key']         = 'url';
                        $aField['label']       = 'Blog URL';
                        $aField['default']     = !empty($settings[$aField['key']]) ? $settings[$aField['key']] : 'blog/';
                        $aField['placeholder'] = 'Customise the Blog\'s URL (include trialing slash)';

                        echo form_field($aField);

                        // --------------------------------------------------------------------------

                        //  Post name
                        $aField                = array();
                        $aField['key']         = 'postName';
                        $aField['label']       = 'Post Name';
                        $aField['default']     = !empty($settings[$aField['key']]) ? $settings[$aField['key']] : 'post';
                        $aField['placeholder'] = 'What should individual posts be referred to as?';
                        $aField['tip']         = 'Define how an individual post should be referred to.';

                        echo form_field($aField);

                        // --------------------------------------------------------------------------

                        //  Post name
                        $aField                = array();
                        $aField['key']         = 'postNamePlural';
                        $aField['label']       = 'Post Name (plural)';
                        $aField['default']     = !empty($settings[$aField['key']]) ? $settings[$aField['key']] : 'posts';
                        $aField['placeholder'] = 'What should a group of posts be referred to as?';
                        $aField['tip']         = 'Define how a group of posts should be referred to.';

                        echo form_field($aField);

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
                        $aField            = array();
                        $aField['key']     = 'use_excerpts';
                        $aField['label']   = 'Use excerpts';
                        $aField['default'] = !empty($settings[$aField['key']]) ? $settings[$aField['key']] : '';

                        echo form_field_boolean($aField);

                        ?>
                    </fieldset>

                    <fieldset id="blog-settings-galcattag">
                        <legend>Galleries, Categories &amp; Tags</legend>
                        <?php

                            //  Enable/disable Gallery
                            $aField            = array();
                            $aField['key']     = 'gallery_enabled';
                            $aField['label']   = 'Post Gallery';
                            $aField['default'] = !empty($settings[$aField['key']]) ? $settings[$aField['key']] : '';

                            echo form_field_boolean($aField);

                            // --------------------------------------------------------------------------

                            //  Enable/disable categories
                            $aField            = array();
                            $aField['key']     = 'categories_enabled';
                            $aField['label']   = 'Categories';
                            $aField['default'] = !empty($settings[$aField['key']]) ? $settings[$aField['key']] : '';

                            echo form_field_boolean($aField);

                            // --------------------------------------------------------------------------

                            //  Enable/disable tags
                            $aField            = array();
                            $aField['key']     = 'tags_enabled';
                            $aField['label']   = 'Tags';
                            $aField['default'] = !empty($settings[$aField['key']]) ? $settings[$aField['key']] : '';

                            echo form_field_boolean($aField);

                        ?>
                    </fieldset>

                    <fieldset id="blog-settings-rss">
                        <legend>RSS</legend>
                        <?php

                            $aField            = array();
                            $aField['key']     = 'rss_enabled';
                            $aField['label']   = 'RSS Enabled';
                            $aField['default'] = !empty($settings[$aField['key']]) ? true : false;

                            echo form_field_boolean($aField);
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

                            $aField             = array();
                            $aField['key']      = 'comments_enabled';
                            $aField['label']    = 'Comments Enabled';
                            $aField['default']  = !empty($settings[$aField['key']]) ? true : false;

                            echo form_field_boolean($aField);
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

                            $aField             = array();
                            $aField['key']      = 'comments_engine';
                            $aField['label']    = 'Comment Engine';
                            $aField['default']  = !empty($settings[$aField['key']]) ? $settings[$aField['key']] : 'NATIVE';
                            $aField['class']    = 'select2';
                            $aField['id']       = 'comment-engine';

                            $options           = array();
                            $options['NATIVE'] = 'Native';
                            $options['DISQUS'] = 'Disqus';

                            echo form_field_dropdown($aField, $options);
                        ?>

                        <hr />

                        <div id="native-settings" style="display:<?=empty($settings[$aField['key']]) || $settings[$aField['key']] == 'NATIVE' ? 'block' : 'none'?>">
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

                        <div id="disqus-settings" style="display:<?=!empty($settings[$aField['key']]) && $settings[$aField['key']] == 'DISQUS' ? 'block' : 'none'?>">
                        <?php

                            //  Blog URL
                            $aField                 = array();
                            $aField['key']          = 'comments_disqus_shortname';
                            $aField['label']        = 'Disqus Shortname';
                            $aField['default']      = !empty($settings[$aField['key']]) ? $settings[$aField['key']] : '';
                            $aField['placeholder']  = 'The Disqus shortname for this website.';

                            echo form_field($aField, 'Create a shortname at disqus.com.');

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

                            $aField             = array();
                            $aField['key']      = 'social_facebook_enabled';
                            $aField['label']    = 'Facebook';
                            $aField['id']       = 'social-service-facebook';
                            $aField['default']  = !empty($settings[$aField['key']]) ? true : false;

                            echo form_field_boolean($aField);

                            // --------------------------------------------------------------------------

                            $aField             = array();
                            $aField['key']      = 'social_twitter_enabled';
                            $aField['label']    = 'Twitter';
                            $aField['id']       = 'social-service-twitter';
                            $aField['default']  = !empty($settings[$aField['key']]) ? true : false;

                            echo form_field_boolean($aField);

                            // --------------------------------------------------------------------------

                            $aField             = array();
                            $aField['key']      = 'social_googleplus_enabled';
                            $aField['label']    = 'Google+';
                            $aField['id']       = 'social-service-googleplus';
                            $aField['default']  = !empty($settings[$aField['key']]) ? true : false;

                            echo form_field_boolean($aField);

                            // --------------------------------------------------------------------------

                            $aField             = array();
                            $aField['key']      = 'social_pinterest_enabled';
                            $aField['label']    = 'Pinterest';
                            $aField['id']       = 'social-service-pinterest';
                            $aField['default']  = !empty($settings[$aField['key']]) ? true : false;

                            echo form_field_boolean($aField);
                        ?>
                    </fieldset>
                    <fieldset id="blog-settings-social-twitter" style="display:<?=!empty($settings['social_twitter_enabled']) ? 'block' : 'none' ?>">
                        <legend>Twitter Settings</legend>
                        <?php

                            $aField                 = array();
                            $aField['key']          = 'social_twitter_via';
                            $aField['label']        = 'Via';
                            $aField['default']      = !empty($settings[$aField['key']]) ? $settings[$aField['key']] : '';
                            $aField['placeholder']  = 'Put your @username here to add it to the tweet';

                            echo form_field($aField);
                        ?>
                    </fieldset>
                    <fieldset id="blog-settings-social-config" style="display:<?=!empty($settings['social_enabled']) ? 'block' : 'none' ?>">
                        <legend>Customisation</legend>
                        <?php

                            $aField                 = array();
                            $aField['key']          = 'social_skin';
                            $aField['label']        = 'Skin';
                            $aField['class']        = 'select2';
                            $aField['default']      = !empty($settings[$aField['key']]) ? $settings[$aField['key']] : 'CLASSIC';

                            $options               = array();
                            $options['CLASSIC']    = 'Classic';
                            $options['FLAT']       = 'Flat';
                            $options['BIRMAN']     = 'Birman';

                            echo form_field_dropdown($aField, $options);

                            // --------------------------------------------------------------------------

                            $aField                 = array();
                            $aField['key']          = 'social_layout';
                            $aField['label']        = 'Layout';
                            $aField['class']        = 'select2';
                            $aField['id']           = 'blog-settings-social-layout';
                            $aField['default']      = !empty($settings[$aField['key']]) ? $settings[$aField['key']] : 'HORIZONTAL';

                            $options               = array();
                            $options['HORIZONTAL'] = 'Horizontal';
                            $options['VERTICAL']   = 'Vertical';
                            $options['SINGLE']     = 'Single Button';

                            echo form_field_dropdown($aField, $options);

                            // --------------------------------------------------------------------------

                            $display = !empty($settings[$aField['key']]) && $settings[$aField['key']] == 'SINGLE' ? 'block' : 'none';

                            echo '<div id="blog-settings-social-layout-single-text" style="display:' . $display . '">';

                                $aField                 = array();
                                $aField['key']          = 'social_layout_single_text';
                                $aField['label']        = 'Button Text';
                                $aField['default']      = !empty($settings[$aField['key']]) ? $settings[$aField['key']] : 'Share';
                                $aField['placeholder']  = 'Specify what text should be rendered on the button';

                                echo form_field($aField);

                            echo '</div>';


                            // --------------------------------------------------------------------------

                            $aField             = array();
                            $aField['key']      = 'social_counters';
                            $aField['label']    = 'Show Counters';
                            $aField['id']       = 'social-counters';
                            $aField['default']  = !empty($settings[$aField['key']]) ? true : false;

                            echo form_field_boolean($aField);
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

                            $aField             = array();
                            $aField['key']      = 'sidebar_latest_posts';
                            $aField['label']    = !empty($settings['postNamePlural']) ? 'Latest ' . ucfirst($settings['postNamePlural']) : 'Latest Posts';
                            $aField['default']  = !empty($settings[$aField['key']]) ? true : false;

                            echo form_field_boolean($aField);

                            // --------------------------------------------------------------------------

                            if (!empty($settings['categories_enabled'])) :

                                $aField             = array();
                                $aField['key']      = 'sidebar_categories';
                                $aField['label']    = 'Categories';
                                $aField['default']  = !empty($settings[$aField['key']]) ? true : false;

                                echo form_field_boolean($aField);

                            endif;

                            // --------------------------------------------------------------------------

                            if (!empty($settings['tags_enabled'])) :

                                $aField             = array();
                                $aField['key']      = 'sidebar_tags';
                                $aField['label']    = 'Tags';
                                $aField['default']  = !empty($settings[$aField['key']]) ? true : false;

                                echo form_field_boolean($aField);

                            endif;

                            // --------------------------------------------------------------------------

                            $aField             = array();
                            $aField['key']      = 'sidebar_popular_posts';
                            $aField['label']    = !empty($settings['postNamePlural']) ? 'Popular ' . ucfirst($settings['postNamePlural']) : 'Popular Posts';
                            $aField['default']  = !empty($settings[$aField['key']]) ? true : false;

                            echo form_field_boolean($aField);

                            $associations = $this->blog_model->get_associations();

                            if (is_array($associations)) :

                                foreach ($associations as $assoc) :

                                    $aField             = array();
                                    $aField['key']      = 'sidebar_association_' . $assoc->slug;
                                    $aField['label']    = $assoc->widget->label;
                                    $aField['default']  = !empty($settings[$aField['key']]) ? true : false;

                                    echo form_field_boolean($aField);

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
