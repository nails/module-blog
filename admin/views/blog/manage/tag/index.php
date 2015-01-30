<div class="group-blog manage tags overview">
	<?php

		if ( $is_fancybox ) :

			echo '<h1>' . $page->title . '</h1>';
			$_class = 'system-alert';

		else :

			$_class = '';

		endif;

	?>
	<p class="<?=$_class?>">
		Use tags to group specific post topics together. For example, a tag might be 'New Year <?=date( 'Y')?>', or 'Coursework'.
		<?php

			if ( app_setting( 'categories_enabled', 'blog-' . $blog_id ) ) :

				echo 'For broader subjects (e.g "Music" or "Travel") consider using a ' . anchor( 'admin/blog/' . $blog_id . '/manage/category' . $is_fancybox, 'category' ) . '.';

			endif;

		?>
	</p>
	<?=$is_fancybox ? '' : '<hr />'?>
	<ul class="tabs disabled">
		<li class="tab active">
			<?=anchor( 'admin/blog/' . $blog_id . '/manage/tag' . $is_fancybox, 'Overview' )?>
		</li>
		<?php if ( user_has_permission( 'admin.blog:' . $this->_blog_id . '.tag_create' ) ) : ?>
		<li class="tab">
			<?=anchor( 'admin/blog/' . $blog_id . '/manage/tag/create' . $is_fancybox, 'Create Tag' )?>
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

					if ( $tags ) :

						foreach ( $tags as $tag ) :

							echo '<tr>';
								echo '<td class="label">';
									echo $tag->label;
									echo $tag->description ? '<small>' . character_limiter( strip_tags( $tag->description ), 225 ) . '</small>' : '';
								echo '</td>';
								echo '<td class="count">';
									echo isset( $tag->post_count ) ? $tag->post_count : '&mdash;';
								echo '</td>';
								echo $this->load->view( '_utilities/table-cell-datetime', array( 'datetime' => $tag->modified ), TRUE );
								echo '<td class="actions">';

									if ( user_has_permission( 'admin.blog:' . $blog_id. '.tag_edit' ) ) :

										echo anchor( 'admin/blog/' . $blog_id . '/manage/tag/edit/' . $tag->id . $is_fancybox, lang( 'action_edit' ), 'class="awesome small"' );

									endif;

									if ( user_has_permission( 'admin.blog:' . $blog_id . '.tag_delete' ) ) :

										echo anchor( 'admin/blog/' . $blog_id . '/manage/tag/delete/' . $tag->id . $is_fancybox, lang( 'action_delete' ), 'class="awesome small red confirm" data-title="Are you sure?" data-body="This action cannot be undone."' );

									endif;

								echo '</td>';
							echo '</tr>';

						endforeach;

					else :

						echo '<tr>';
							echo '<td colspan="4" class="no-data">';
								echo 'No Tags, add one!';
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

	$this->load->view( 'admin/blog/manage/tag/_footer' );