<?php

/**
 * Migration:   1
 * Started:     04/02/2015
 * Finalised:   04/02/2015
 */

namespace Nails\Blog\Database\Migration;

use Nails\Common\Console\Migrate\Base;

class Migration1 extends Base
{
    /**
     * Execute the migration
     * @return Void
     */
    public function execute()
    {
        $this->query("ALTER TABLE `{{NAILS_DB_PREFIX}}blog` ADD `description` VARCHAR(255)  NULL  DEFAULT NULL  AFTER `label`;");
    }
}
