<div class="group-blog manage">
    <p>
        This page shows all the posts on site and allows you to manage them.
    </p>
    <hr />
    <?php

        echo \Nails\Admin\Helper::loadSearch($search);
        echo \Nails\Admin\Helper::loadPagination($pagination);

    ?>
    <table>
        <thead>
            <tr>
                <th class="image">Image</th>
                <th class="title">Details</th>
                <th class="status">Published</th>
                <th class="user">Author</th>
                <th class="datetime">Modified</th>
                <th class="actions">Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php

            if ($posts) {

                $_date_format = active_user('pref_date_format');
                $_time_format = active_user('pref_time_format');

                foreach ($posts as $post) {

                    echo '<tr class="post" data-title="' . $post->title . '">';
                        echo '<td class="image">';

                        if ($post->image_id) {

                            echo anchor(cdn_serve($post->image_id), img(cdn_scale($post->image_id, 75, 75)), 'class="fancybox"');

                        } else {

                            echo img(NAILS_ASSETS_URL . 'img/admin/modules/blog/image-icon.png');
                        }

                        echo '</td>';

                        echo '<td class="title">';

                            //  Title
                            echo $post->title;

                            //  Exceprt
                            if (app_setting('use_excerpt', 'blog-' . $blog->id)) {

                                echo '<small>' . strip_tags($post->excerpt) . '</small>';
                            }

                        echo '</td>';

                        if ($post->is_published && strtotime($post->published) <= time()) {

                            echo '<td class="status success">';
                                echo '<span class="fa fa-check-circle"></span>';
                                echo niceTime($post->published);
                            echo '</td>';

                        } elseif ($post->is_published && strtotime($post->published) > time()) {

                            echo '<td class="status notice">';
                                echo '<span class="fa fa-clock-o "></span>';
                                echo niceTime($post->published);
                            echo '</td>';

                        } else {

                            echo '<td class="status error">';
                                echo '<span class="fa fa-times-circle"></span>';
                            echo '</td>';
                        }

                        echo \Nails\Admin\Helper::loadUserCell($post->author);
                        echo \Nails\Admin\Helper::loadDatetimeCell($post->modified);

                        echo '<td class="actions">';

                            if ($post->is_published && strtotime($post->published) <= time()) {

                                echo anchor($post->url, lang('action_view'), 'class="awesome small green" target="_blank"');

                            } else {

                                echo anchor($post->url . '?preview=1', lang('action_preview'), 'class="awesome small green" target="_blank"');
                            }

                            if (userHasPermission('admin.blog:' . $blog->id . '.post_edit')) {

                                echo anchor(
                                    'admin/blog/post/edit/' . $blog->id . '/' . $post->id,
                                    lang('action_edit'),
                                    'class="awesome small"'
                                );
                            }

                            if (userHasPermission('admin.blog:' . $blog->id. '.post_delete')) {

                                echo anchor(
                                    'admin/blog/post/delete/' . $blog->id . '/' . $post->id,
                                    lang('action_delete'),
                                    'class="awesome small red confirm" data-title="Confirm Delete" data-body="Are you sure you want to delete this post?"'
                                );
                            }

                        echo '</td>';
                    echo '</tr>';
                }

            } else {

                echo '<tr>';
                    echo '<td colspan="6" class="no-data">';
                        echo 'No Posts found';
                    echo '</td>';
                echo '</tr>';
            }

        ?>
        </tbody>
    </table>
    <?php

        echo \Nails\Admin\Helper::loadPagination($pagination);

    ?>
</div>