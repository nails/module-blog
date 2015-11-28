<div class="group-settings blog configure">
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
                    echo '<button type="submit" class="btn btn-xs btn-primary">Select</button>';
                echo '</noscript>';
            echo form_close();

        } else {

            echo '<p>';
                echo 'Configure your blog using the options below.';
            echo '</p>';
        }

        echo '<hr />';

        if (!empty($selectedBlogId)) {

            echo form_open('admin/blog/settings?blog_id=' . $selectedBlogId);
            $sActiveTab = $this->input->post('active_tab') ?: 'tab-general';
            echo '<input type="hidden" name="active_tab" value="' . $sActiveTab . '" id="active-tab">';

            ?>
            <ul class="tabs" data-active-tab-input="#active-tab">
                <li class="tab">
                    <a href="#" data-tab="tab-general">General</a>
                </li>
                <li class="tab">
                    <a href="#" data-tab="tab-skin">Skin</a>
                </li>
                <li class="tab">
                    <a href="#" data-tab="tab-commenting">Commenting</a>
                </li>
                <li class="tab">
                    <a href="#" data-tab="tab-social">Social Tools</a>
                </li>
                <li class="tab">
                    <a href="#" data-tab="tab-sidebar">Sidebar</a>
                </li>
            </ul>
            <section class="tabs">
                <div class="tab-page tab-general">
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
                    <fieldset>
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
                    <fieldset>
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
                    <fieldset>
                        <legend>RSS</legend>
                        <?php

                            $aField            = array();
                            $aField['key']     = 'rss_enabled';
                            $aField['label']   = 'RSS Enabled';
                            $aField['default'] = !empty($settings[$aField['key']]) ? true : false;

                            echo form_field_boolean($aField);
                        ?>
                    </fieldset>
                </div>
                <div class="tab-page tab-skin">
                    <?php

                    if ($skins) {

                        ?>
                        <table>
                            <thead>
                                <tr>
                                    <th class="selected">Selected</th>
                                    <th class="label">Label</th>
                                    <th class="configure">Configure</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php

                                foreach ($skins as $skin) {

                                    if ($this->input->post()) {

                                        $bSelected = $skin->slug == $this->input->post('skin') ? true : false;

                                    } else {

                                        $bSelected = $skin->slug == $skinSelected->slug ? true : false;
                                    }

                                    ?>
                                    <tr>
                                        <td class="selected">
                                            <?=form_radio('skin', $skin->slug, $bSelected)?>
                                        </td>
                                        <td class="label">
                                            <?php

                                            echo $skin->name;
                                            if (!empty($skin->description)) {

                                                echo '<small>';
                                                echo $skin->description;
                                                echo '</small>';
                                            }

                                            ?>
                                        </td>
                                        <td class="configure">
                                            <?php

                                            if (!empty($skin->data->settings)) {

                                                echo anchor(
                                                    'admin/blog/settings/skin?&slug=' . $skin->slug,
                                                    'Configure',
                                                    'data-fancybox-type="iframe" class="fancybox btn btn-xs btn-primary"'
                                                );
                                            }

                                            ?>
                                        </td>
                                    </tr>
                                    <?php
                                }

                                ?>
                            </tbody>
                        </table>
                        <?php

                    } else {

                        ?>
                        <p class="alert alert-danger">
                            <strong>Error:</strong> I'm sorry, but I couldn't find any front of house skins to use.
                            This is a configuration error and should be raised with the developer.
                        </p>
                        <?php
                    }

                    ?>
                </div>
                <div class="tab-page tab-commenting">
                    <p>
                        Customise how commenting works on your blog.
                    </p>
                    <hr />
                    <fieldset id="blog-settings-comments">
                        <legend>Post comments enabled</legend>
                        <?php

                        $aField            = array();
                        $aField['key']     = 'comments_enabled';
                        $aField['label']   = 'Comments Enabled';
                        $aField['default'] = !empty($settings[$aField['key']]) ? true : false;

                        echo form_field_boolean($aField);

                        ?>
                    </fieldset>
                    <fieldset>
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

                        $sDisplay = empty($settings[$aField['key']]) || $settings[$aField['key']] == 'NATIVE' ? 'block' : 'none'

                        ?>
                        <p id="native-settings" class="alert alert-warning" style="display:<?=$sDisplay?>">
                            <strong>Coming Soon!</strong> Native commenting is in the works and will be available soon.
                            <?php

                                //  TODO: Need to be able to handle a lot with native commenting, e.g
                                //  - anonymous comments/forced login etc
                                //  - pingbacks?
                                //  - anything else WordPress might do?

                            ?>
                        </p>
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
                </div>
                <div class="tab-page tab-social">
                    <p>
                        Place social sharing tools on your blog post pages.
                    </p>
                    <hr />
                    <fieldset>
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
                </div>
                <div class="tab-page tab-sidebar">
                    <p>
                        Configure the sidebar widgets.
                    </p>
                    <hr />
                    <fieldset>
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

                            $associations = $this->blog_model->getAssociations();

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
                </div>
            </section>
            <p>
                <?=form_submit('submit', lang('action_save_changes'), 'class="btn btn-primary"')?>
            </p>
            <?php

        } else {

            ?>
            <p class="alert alert-warning" id="alert-choose-blog" style="margin-bottom:5px;">
                Please choose a blog above to begin configuring.
            <p>
            <?php
        }

    ?>
</div>
