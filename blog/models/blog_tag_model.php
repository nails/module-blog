<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Name:		blog_tag_model
 *
 * Description:	This model handles all things tag related
 *
 **/

/**
 * OVERLOADING NAILS' MODELS
 *
 * Note the name of this class; done like this to allow apps to extend this class.
 * Read full explanation at the bottom of this file.
 *
 **/

class NAILS_Blog_tag_model extends NAILS_Model
{
	public function __construct()
	{
		parent::__construct();

		// --------------------------------------------------------------------------

		$this->table			= NAILS_DB_PREFIX . 'blog_tag';
		$this->tablePrefix	= 'bt';
	}


	// --------------------------------------------------------------------------


	protected function _getcount_common( $data = array(), $_caller = NULL )
	{
		parent::_getcount_common( $data, $_caller );

		// --------------------------------------------------------------------------

		$this->db->select( $this->tablePrefix . '.*' );

		if ( ! empty( $data['include_count'] ) ) :

			$this->db->select( '(SELECT COUNT(DISTINCT post_id) FROM ' . NAILS_DB_PREFIX . 'blog_post_tag WHERE tag_id = ' . $this->tablePrefix . '.id) post_count' );

		endif;

		//	Default sort
		if ( empty( $data['sort'] ) ) :

			$this->db->order_by( $this->tablePrefix . '.label' );

		endif;
	}


	// --------------------------------------------------------------------------


	public function create( $data )
	{
		$_data = new stdClass();

		// --------------------------------------------------------------------------

		//	Some basic sanity testing
		if ( empty( $data->label ) ) :

			$this->_set_error( '"label" is a required field.' );
			return FALSE;

		else :

			$_data->label = trim( $data->label );

		endif;

		if ( empty( $data->blog_id ) ) :

			$this->_set_error( '"blog_id" is a required field.' );
			return FALSE;

		else :

			$_data->blog_id = $data->blog_id;

		endif;

		// --------------------------------------------------------------------------

		$_data->slug = $this->_generate_slug( $data->label );

		if ( isset( $data->description ) ) :

			$_data->description = $data->description;

		endif;

		if ( isset( $data->seo_title ) ) :

			$_data->seo_title = strip_tags( $data->seo_title );

		endif;

		if ( isset( $data->seo_description ) ) :

			$_data->seo_description = strip_tags( $data->seo_description );

		endif;

		if ( isset( $data->seo_keywords ) ) :

			$_data->seo_keywords = strip_tags( $data->seo_keywords );

		endif;

		return parent::create( $_data );
	}

	// --------------------------------------------------------------------------


	public function update( $id, $data )
	{
		$_data = new stdClass();

		// --------------------------------------------------------------------------

		//	Some basic sanity testing
		if ( empty( $data->label ) ) :

			$this->_set_error( '"label" is a required field.' );
			return FALSE;

		else :

			$_data->label = trim( $data->label );

		endif;

		// --------------------------------------------------------------------------

		$_data->slug = $this->_generate_slug( $data->label, '', '', NULL, NULL, $id );

		if ( isset( $data->description ) ) :

			$_data->description = $data->description;

		endif;

		if ( isset( $data->seo_title ) ) :

			$_data->seo_title = strip_tags( $data->seo_title );

		endif;

		if ( isset( $data->seo_description ) ) :

			$_data->seo_description = strip_tags( $data->seo_description );

		endif;

		if ( isset( $data->seo_keywords ) ) :

			$_data->seo_keywords = strip_tags( $data->seo_keywords );

		endif;

		return parent::update( $id, $_data );
	}


	// --------------------------------------------------------------------------


	public function format_url($slug, $blogId)
	{
		$this->load->model('blog/blog_model');
		return $this->blog_model->getBlogUrl($blogId) . 'tag/' . $slug;
	}


	// --------------------------------------------------------------------------


	protected function _format_object(&$tag)
	{
		parent::_format_object($category);

		$tag->url	= $this->format_url($tag->slug, $tag->blog_id);

		if (isset($tag->post_count)) :

			$tag->post_count = (int) $tag->post_count;

		endif;
	}
}


// --------------------------------------------------------------------------


/**
 * OVERLOADING NAILS' MODELS
 *
 * The following block of code makes it simple to extend one of the core Nails
 * models. Some might argue it's a little hacky but it's a simple 'fix'
 * which negates the need to massively extend the CodeIgniter Loader class
 * even further (in all honesty I just can't face understanding the whole
 * Loader class well enough to change it 'properly').
 *
 * Here's how it works:
 *
 * CodeIgniter instantiate a class with the same name as the file, therefore
 * when we try to extend the parent class we get 'cannot redeclare class X' errors
 * and if we call our overloading class something else it will never get instantiated.
 *
 * We solve this by prefixing the main class with NAILS_ and then conditionally
 * declaring this helper class below; the helper gets instantiated et voila.
 *
 * If/when we want to extend the main class we simply define NAILS_ALLOW_EXTENSION
 * before including this PHP file and extend as normal (i.e in the same way as below);
 * the helper won't be declared so we can declare our own one, app specific.
 *
 **/

if ( ! defined( 'NAILS_ALLOW_EXTENSION_BLOG_TAG_MODEL' ) ) :

	class Blog_tag_model extends NAILS_Blog_tag_model
	{
	}

endif;


/* End of file blog_tag_model.php */
/* Location: ./modules/blog/models/blog_tag_model.php */