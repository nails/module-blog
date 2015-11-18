<div class="group-blog manage tags overview">
    <p>
        Use tags to group specific <?=$postName?> topics together. For example, a tag might be 'New Year <?=date('Y')?>', or 'Coursework'.
        <?php

            if (app_setting('categories_enabled', 'blog-' . $blog->id)) {

                echo 'For broader subjects (e.g "Music" or "Travel") consider using a ' . anchor('admin/blog/category/index/' . $blog->id . $isModal, 'category') . '.';
            }

        ?>
    </p>
    <ul class="tabs disabled">
        <li class="tab active">
            <?=anchor('admin/blog/tag/index/' . $blog->id . $isModal, 'Overview')?>
        </li>
        <?php if (userHasPermission('admin:blog:tag:' . $blog->id . ':create')) { ?>
        <li class="tab">
            <?=anchor('admin/blog/tag/create/' . $blog->id . $isModal, 'Create Tag')?>
        </li>
        <?php } ?>
    </ul>
    <section class="tabs pages">
        <div class="tab-page active">
            <table>
                <thead>
                    <tr>
                        <th class="label">Label</th>
                        <th class="count">Posts</th>
                        <th class="modified">Modified</th>
                        <th class="actions">Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php

                    if ($tags) {

                        foreach ($tags as $tag) {

                            echo '<tr>';
                                echo '<td class="label">';
                                    echo $tag->label;
                                    echo $tag->description ? '<small>' . character_limiter(strip_tags($tag->description), 225) . '</small>' : '';
                                echo '</td>';
                                echo '<td class="count">';
                                    echo isset($tag->post_count) ? $tag->post_count : '&mdash;';
                                echo '</td>';
                                echo adminHelper('loadDatetimeCell', $tag->modified);
                                echo '<td class="actions">';

                                    if (userHasPermission('admin:blog:tag:' . $blog->id . ':edit')) {

                                        echo anchor('admin/blog/tag/edit/' . $blog->id . '/' . $tag->id . $isModal, lang('action_edit'), 'class="awesome small"');
                                    }

                                    if (userHasPermission('admin:blog:tag:' . $blog->id . ':delete')) {

                                        echo anchor('admin/blog/tag/delete/' . $blog->id . '/' . $tag->id . $isModal, lang('action_delete'), 'class="awesome small red confirm" data-body="This action cannot be undone."');
                                    }

                                echo '</td>';
                            echo '</tr>';
                        }

                    } else {

                        echo '<tr>';
                            echo '<td colspan="4" class="no-data">';
                                echo 'No Tags, add one!';
                            echo '</td>';
                        echo '</tr>';
                    }

                ?>
                </tbody>
            </table>
        </div>
    </section>
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
