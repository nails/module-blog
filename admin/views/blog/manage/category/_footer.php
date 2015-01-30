<?php

	echo '<script type="text/javascript">';

	//	This variable holds the current state of objects and is read
	//	by the calling script when closing the fancybox, feel free to
	//	update this during the lietime of the fancybox.

	$_data = array();	//	An array of objects in the format {id,label}

	foreach ( $categories as $cat ) :

		$_temp			= new stdClass();
		$_temp->id		= $cat->id;
		$_temp->label	= $cat->label;

		$_data[] = $_temp;

	endforeach;

	echo 'var _DATA = ' . json_encode( $_data ) . ';';
	echo '</script>';