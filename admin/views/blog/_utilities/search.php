<div class="search">
	<div class="mask">
		<b class="fa fa-refresh fa-spin fa-2x"></b>
	</div>
	<?php

		$_form = array(
			'method'	=> 'GET'
		);
		echo form_open( NULL, $_form );

		echo '<div class="search-text">';
		echo form_input( 'search', $this->input->get( 'search' ), 'autocomplete="off" placeholder="' . lang( 'admin_search_placeholder' ) . '"' );
		echo '</div>';

		// --------------------------------------------------------------------------

		$_sort = array(
			'bp.published'	=> 'Published Date',
			'bp.modified'	=> 'Modified Date',
			'bp.title'		=> 'Title'
		);
		echo lang( 'admin_search_sort' ) . form_dropdown( 'sort_on', $_sort, $sort_on, 'class="select2"' );

		// --------------------------------------------------------------------------

		$_order = array(
			'asc'	=> 'Ascending',
			'desc'	=> 'Descending'
		);
		echo lang( 'admin_search_order_1' ) . form_dropdown( 'order', $_order, $sort_order, 'class="select2"' ) . lang( 'admin_search_order_2' );

		// --------------------------------------------------------------------------

		$_perpage = array(
			10 => 10,
			25 => 25,
			50 => 50,
			75 => 75,
			100 => 100
		);
		echo form_dropdown( 'per_page', $_perpage, $pagination->per_page, 'class="select2" style="width:75px;' );
		echo lang( 'admin_search_per_page' );

		// --------------------------------------------------------------------------

		echo anchor( uri_string(), lang( 'action_reset' ), 'class="awesome small right"' );
		echo form_submit( 'submit', lang( 'action_search' ), 'class="awesome small right"' );

		// --------------------------------------------------------------------------

		echo form_close();

	?>
</div>

<hr />