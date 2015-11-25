<div class="group-blog manage">
    <p>
        This page shows all the <?=$postNamePlural?> on site and allows you to manage them.
    </p>
    <?php

        echo adminHelper('loadSearch', $search);
        echo adminHelper('loadPagination', $pagination);

    ?>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th class="image">Image</th>
                    <th class="title">Details</th>
                    <th class="status">Published</th>
                    <th class="type text-center">Type</th>
                    <th class="user">Author</th>
                    <th class="datetime">Modified</th>
                    <th class="actions">Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php

                if ($posts) {

                    $_date_format = activeUser('pref_date_format');
                    $_time_format = activeUser('pref_time_format');

                    foreach ($posts as $post) {

                        echo '<tr class="post" data-title="' . $post->title . '">';
                            echo '<td class="image">';

                            switch (strtoupper($post->type)) {
                                case 'PHOTO':

                                    if ($post->photo->id) {

                                        echo anchor(cdnServe($post->photo->id), img(cdnScale($post->photo->id, 75, 75)), 'class="fancybox"');

                                    } else {

                                        echo img(NAILS_ASSETS_URL . 'img/admin/modules/blog/image-icon.png');
                                    }
                                    break;

                                default:
                                    # code...
                                    break;
                            }

                            echo '</td>';

                            echo '<td class="title">';

                                //  Title
                                echo $post->title;

                                //  Exceprt
                                if (appSetting('use_excerpt', 'blog-' . $blog->id)) {

                                    echo '<small>' . strip_tags($post->excerpt) . '</small>';
                                }

                            echo '</td>';

                            if ($post->is_published && strtotime($post->published) <= time()) {

                                echo '<td class="status success">';
                                    echo '<span class="fa fa-check-circle"></span>';
                                    echo '<small>' . niceTime($post->published) . '</small>';
                                echo '</td>';

                            } elseif ($post->is_published && strtotime($post->published) > time()) {

                                echo '<td class="status notice">';
                                    echo '<span class="fa fa-clock-o "></span>';
                                    echo '<small>' . niceTime($post->published) . '</small>';
                                echo '</td>';

                            } else {

                                echo '<td class="status error">';
                                    echo '<span class="fa fa-times-circle"></span>';
                                echo '</td>';
                            }

                            echo '<td class="type text-center">';
                                echo !empty($postTypes[$post->type]) ? $postTypes[$post->type] : 'Unknown';
                            echo '</td>';

                            echo adminHelper('loadUserCell', $post->author);
                            echo adminHelper('loadDatetimeCell', $post->modified);

                            echo '<td class="actions">';

                                if ($post->is_published && strtotime($post->published) <= time()) {

                                    echo anchor(
                                        $post->url,
                                        lang('action_view'),
                                        'class="btn btn-xs btn-default" target="_blank"'
                                    );

                                } else {

                                    echo anchor(
                                        $post->url . '?preview=1',
                                        lang('action_preview'),
                                        'class="btn btn-xs btn-success" target="_blank"'
                                    );
                                }

                                if (userHasPermission('admin:blog:post:' . $blog->id . ':edit')) {

                                    echo anchor(
                                        'admin/blog/post/edit/' . $blog->id . '/' . $post->id,
                                        lang('action_edit'),
                                        'class="btn btn-xs btn-primary"'
                                    );
                                }

                                if (userHasPermission('admin:blog:post:' . $blog->id. ':delete')) {

                                    echo anchor(
                                        'admin/blog/post/delete/' . $blog->id . '/' . $post->id,
                                        lang('action_delete'),
                                        'class="btn btn-xs btn-danger confirm" data-title="Confirm Delete" data-body="Are you sure you want to delete this ' . $postName . '?"'
                                    );
                                }

                            echo '</td>';
                        echo '</tr>';
                    }

                } else {

                    echo '<tr>';
                        echo '<td colspan="7" class="no-data">';
                            echo 'No Posts found';
                        echo '</td>';
                    echo '</tr>';
                }

            ?>
            </tbody>
        </table>
    </div>
    <?php

        echo adminHelper('loadPagination', $pagination);

    ?>
</div>
