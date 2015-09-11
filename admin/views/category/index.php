<div class="group-blog manage categories overview">
    <p>
        Use categories to group broad <?=$postName?> topics together. For example, a category might be 'Music', or 'Travel'.
        <?php

            if (app_setting('tags_enabled', 'blog-' . $blog->id)) {

                echo 'For specific details (e.g New Year ' . date('Y') . ') consider using a ' . anchor('admin/blog/tag/index/' . $blog->id . $isModal, 'tag') . '.';
            }

        ?>
    </p>
    <ul class="tabs disabled">
        <li class="tab active">
            <?=anchor('admin/blog/category/index/' . $blog->id . $isModal, 'Overview')?>
        </li>
        <?php if (userHasPermission('admin:blog:category:' . $blog->id . '.create')) { ?>
        <li class="tab">
            <?=anchor('admin/blog/category/create/' . $blog->id . $isModal, 'Create Category')?>
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

                    if ($categories) {

                        foreach ($categories as $category) {

                            echo '<tr>';
                                echo '<td class="label">';
                                    echo $category->label;
                                    echo $category->description ? '<small>' . character_limiter(strip_tags($category->description), 225) . '</small>' : '';
                                echo '</td>';
                                echo '<td class="count">';
                                    echo isset($category->post_count) ? $category->post_count : '&mdash;';
                                echo '</td>';
                                echo \Nails\Admin\Helper::loadDatetimeCell($category->modified);
                                echo '<td class="actions">';

                                    if (userHasPermission('admin:blog:category:' . $blog->id . ':edit')) {

                                        echo anchor('admin/blog/category/edit/' . $blog->id . '/' . $category->id . $isModal, lang('action_edit'), 'class="awesome small"');
                                    }

                                    if (userHasPermission('admin:blog:category:' . $blog->id . ':delete')) {

                                        echo anchor('admin/blog/category/delete/' . $blog->id . '/' . $category->id . $isModal, lang('action_delete'), 'class="awesome small red confirm" data-title="Are you sure?" data-body="This action cannot be undone."');
                                    }

                                echo '</td>';
                            echo '</tr>';
                        }

                    } else {

                        echo '<tr>';
                            echo '<td colspan="4" class="no-data">';
                                echo 'No Categories, add one!';
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

    foreach ($categories as $cat) {

        $temp        = new \stdClass();
        $temp->id    = $cat->id;
        $temp->label = $cat->label;

        $data[] = $temp;
    }

    echo 'var _DATA = ' . json_encode($data) . ';';
    echo '</script>';
