<div class="group-blog manage categories overview">
	<?php

		if ( $is_fancybox ) :

			echo '<h1>' . $page->title . '</h1>';
			$_class = 'system-alert';

		else :

			$_class = '';

		endif;

	?>
	<p class="<?=$_class?>">
		Use categories to group broad post topics together. For example, a category might be 'Music', or 'Travel'.
		<?php

			if ( app_setting( 'tags_enabled', 'blog-' . $blog_id ) ) :

				echo 'For specific details (e.g New Year ' . date( 'Y') . ') consider using a ' . anchor( 'admin/blog/' . $blog_id . '/manage/tag' . $is_fancybox, 'tag' ) . '.';

			endif;

		?>
	</p>
	<?=$is_fancybox ? '' : '<hr />'?>
	<ul class="tabs disabled">
		<li class="tab active">
			<?=anchor( 'admin/blog/' . $blog_id . '/manage/category' . $is_fancybox, 'Overview' )?>
		</li>
		<?php if ( user_has_permission( 'admin.blog:' . $this->_blog_id . '.category_create' ) ) : ?>
		<li class="tab">
			<?=anchor( 'admin/blog/' . $blog_id . '/manage/category/create' . $is_fancybox, 'Create Category' )?>
		</li>
		<?php endif; ?>
	</ul>
	<section class="tabs pages">
		<div class="tab page active">
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

					if ( $categories ) :

						foreach ( $categories as $category ) :

							echo '<tr>';
								echo '<td class="label">';
									echo $category->label;
									echo $category->description ? '<small>' . character_limiter( strip_tags( $category->description ), 225 ) . '</small>' : '';
								echo '</td>';
								echo '<td class="count">';
									echo isset( $category->post_count ) ? $category->post_count : '&mdash;';
								echo '</td>';
								echo $this->load->view( '_utilities/table-cell-datetime', array( 'datetime' => $category->modified ), TRUE );
								echo '<td class="actions">';

									if ( user_has_permission( 'admin.blog:' . $blog_id . '.category_edit' ) ) :

										echo anchor( 'admin/blog/' . $blog_id . '/manage/category/edit/' . $category->id . $is_fancybox, lang( 'action_edit' ), 'class="awesome small"' );

									endif;

									if ( user_has_permission( 'admin.blog:' . $blog_id . '.category_delete' ) ) :

										echo anchor( 'admin/blog/' . $blog_id . '/manage/category/delete/' . $category->id . $is_fancybox, lang( 'action_delete' ), 'class="awesome small red confirm" data-title="Are you sure?" data-body="This action cannot be undone."' );

									endif;

								echo '</td>';
							echo '</tr>';

						endforeach;

					else :

						echo '<tr>';
							echo '<td colspan="4" class="no-data">';
								echo 'No Categories, add one!';
							echo '</td>';
						echo '</tr>';

					endif;

				?>
				</tbody>
			</table>
		</div>
	</section>
</div>
<?php

	$this->load->view( 'admin/blog/manage/category/_footer' );