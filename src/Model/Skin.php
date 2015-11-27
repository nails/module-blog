<?php

/**
 * This model manages the Blog skins
 *
 * @package     Nails
 * @subpackage  module-blog
 * @category    Model
 * @author      Nails Dev Team
 * @link
 * @todo consider consolidating this into a single, Nails-wide, skin model
 */

namespace Nails\Blog\Model;

class Skin
{
    protected $aAvailable;

    // --------------------------------------------------------------------------

    /**
     * Construct the model.
     */
    public function __construct()
    {
        $this->aAvailable = _NAILS_GET_SKINS('nailsapp/module-blog');
    }

    // --------------------------------------------------------------------------

    /**
     * Fetches all available skins
     * @return array
     */
    public function getAvailable()
    {
        return $this->aAvailable;
    }

    // --------------------------------------------------------------------------

    /**
     * Gets a single skin
     * @param  string  $sSlug The skin's slug
     * @return stdClass
     */
    public function get($sSlug)
    {
        $aSkins = $this->getAvailable();

        foreach ($aSkins as $oSkin) {
            if ($oSkin->slug == $sSlug) {
                return $oSkin;
            }
        }

        return false;
    }
}
