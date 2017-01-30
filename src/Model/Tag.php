<?php

/**
 * This model handles interactions with the app's "blog_tag" table.
 * @todo: Move the logic from the old blog_tag model into here
 *
 * @package     Nails
 * @subpackage  module-blog
 * @category    Model
 * @author      Nails Dev Team
 * @link
 */

namespace Nails\Blog\Model;

use Nails\Common\Model\Base;

class Tag extends Base
{
    public function __construct()
    {
        parent::__construct();
        $this->table = NAILS_DB_PREFIX . 'blog_tag';
    }
}
