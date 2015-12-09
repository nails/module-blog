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

use Nails\Blog\Exception\SkinException;

class Skin
{
    protected $aAvailable;
    protected $aEnabled;

    // --------------------------------------------------------------------------

    const DEFAULT_SKIN = 'nailsapp/skin-blog-classic';

    // --------------------------------------------------------------------------

    /**
     * Construct the model.
     */
    public function __construct()
    {
        $this->aAvailable = array();
        $this->aEnabled   = array();

        //  Get available skins
        $this->aAvailable = _NAILS_GET_SKINS('nailsapp/module-blog');

        if (empty($this->aAvailable)) {
            throw new SkinException(
                'No skins are available.'
            );
        }

        //  Get the skin
        $sSkinSlug      = appSetting('skin', 'blog') ?: self::DEFAULT_SKIN;
        $this->aEnabled = $this->get($sSkinSlug);
        if (empty($this->aEnabled)) {
            throw new SkinException(
                'Skin "' . $sSkinSlug . '" does not exist.'
            );
        }
    }

    // --------------------------------------------------------------------------

    /**
     * Fetches all available front skins
     * @return array
     */
    public function getAvailable()
    {
        return $this->aAvailable;
    }

    // --------------------------------------------------------------------------

    /**
     * Returns the config for the enabled skin
     * @return stdClass
     */
    public function getEnabled()
    {
        return $this->aEnabled;
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

    // --------------------------------------------------------------------------

    /**
     * Retrives a skin setting
     * @param  string $sKey  The key to retrieve
     * @param  string $sType The skin's type
     * @return mixed
     */
    public function getSetting($sKey)
    {
        return appSetting($sKey, $this->aEnabled->slug);
    }
}
