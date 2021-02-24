<?php

/**
 * This model handles interactions with the app's "blog" table.
 * @todo: Move the logic from the old blog model into here
 *
 * @package     Nails
 * @subpackage  module-blog
 * @category    Model
 * @author      Nails Dev Team
 * @link
 */

namespace Nails\Blog\Model;

use Nails\Common\Model\Base;

class Blog extends Base
{
    /**
     * The table this model represents
     *
     * @var string
     */
    const TABLE = NAILS_DB_PREFIX . 'blog';
}
