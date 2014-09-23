<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Name:		NALS_BLOG_Controller
 *
 * Description:	This controller executes various bits of common Blog functionality
 *
 **/


class NAILS_Blog_Controller extends NAILS_Controller
{
	protected $_blog_id;


	// --------------------------------------------------------------------------


	public function __construct()
	{
		parent::__construct();

		// --------------------------------------------------------------------------

		$this->_blog_id = $this->uri->rsegment( 2 );
		$this->data['blog_id'] = $this->_blog_id;

		// --------------------------------------------------------------------------

		//	Check this module is enabled in settings
		if ( ! module_is_enabled( 'blog' ) ) :

			//	Cancel execution, module isn't enabled
			show_404();

		endif;

		// --------------------------------------------------------------------------

		//	Load language file
		$this->lang->load( 'blog/blog' );

		// --------------------------------------------------------------------------

		//	Load the models
		$this->load->model( 'blog/blog_model' );
		$this->load->model( 'blog/blog_post_model' );
		$this->load->model( 'blog/blog_widget_model' );
		$this->load->model( 'blog/blog_skin_model' );

		// --------------------------------------------------------------------------

		if ( app_setting( 'categories_enabled', 'blog-' . $this->_blog_id ) ) :

			$this->load->model( 'blog/blog_category_model' );

		endif;


		if ( app_setting( 'tags_enabled', 'blog-' . $this->_blog_id ) ) :

			$this->load->model( 'blog/blog_tag_model' );

		endif;

		// --------------------------------------------------------------------------

		//	Load up the blog's skin
		$_skin = app_setting( 'skin', 'blog-' . $this->_blog_id ) ? app_setting( 'skin', 'blog-' . $this->_blog_id ) : 'skin-blog-gettingstarted';

		$this->_skin = $this->blog_skin_model->get( $_skin );

		if ( ! $this->_skin ) :

			show_fatal_error( 'Failed to load blog skin "' . $_skin . '"', 'Blog skin "' . $_skin . '" failed to load at ' . APP_NAME . ', the following reason was given: ' . $this->blog_skin_model->last_error() );

		endif;

		// --------------------------------------------------------------------------

		//	Pass to $this->data, for the views
		$this->data['skin'] = $this->_skin;

		// --------------------------------------------------------------------------

		//	Blog name
		$this->_blog_name = app_setting( 'name', 'blog-' . $this->_blog_id ) ? app_setting( 'name', 'blog-' . $this->_blog_id ) : 'Blog';
	}
}

/* End of file _blog.php */
/* Location: ./application/modules/blog/controllers/_blog.php */