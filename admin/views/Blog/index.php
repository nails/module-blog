<div class="group-settings blog index">
    <p>
        The following blogs are enabled on your site.
    </p>
    <hr />
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th class="label">Label</th>
                    <th class="actions">Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php

            if (! empty($blogs)) {

                foreach ($blogs as $blog) {

                    ?>
                    <tr>
                        <td class="label">
                            <?=$blog->label?>
                            <?=$blog->description ? '<small>' . $blog->description . '</small>' : ''?>
                        </td>
                        <td class="actions">
                            <?php

                            if (userHasPermission('admin:blog:blog:edit')) {

                                echo anchor(
                                    'admin/blog/blog/edit/' . $blog->id,
                                    lang('action_edit'),
                                    'class="btn btn-xs btn-primary"'
                                );
                            }

                            if (userHasPermission('admin:blog:blog:delete')) {

                                echo anchor(
                                    'admin/blog/blog/delete/' . $blog->id,
                                    lang('action_delete'),
                                    'class="btn btn-xs btn-danger confirm" data-body="Deleting a blog will delete all associated posts, categories and tags. This action cannot be undone."'
                                );
                            }

                            ?>
                        </td>
                    </tr>
                    <?php
                }

            } else {

                ?>
                <tr>
                    <td class="no-data" colspan="2">
                        No blogs found
                    </td>
                </tr>
                <?php
            }

            ?>
            </tbody>
        </table>
    </div>
</div>