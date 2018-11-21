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

use Nails\Components;
use Nails\Blog\Exception\SkinException;

class Skin
{
    protected $aAvailable;
    protected $aEnabled;
    protected $iActiveBlogId;

    // --------------------------------------------------------------------------

    const DEFAULT_SKIN = 'nails/skin-blog-classic';

    // --------------------------------------------------------------------------

    /**
     * Construct the model.
     */
    public function __construct()
    {
        $this->aAvailable = array();
        $this->aEnabled   = array();
    }

    // --------------------------------------------------------------------------

    /**
     * Setup the model for use with a particular skin
     */
    public function init($iBlogId)
    {
        $this->iActiveBlogId        = $iBlogId;
        $this->aAvailable[$iBlogId] = array();
        $this->aEnabled[$iBlogId]   = array();

        //  Get available skins
        $this->aAvailable[$iBlogId] = Components::skins('nails/module-blog');

        if (empty($this->aAvailable[$iBlogId])) {
            throw new SkinException(
                'No skins are available.'
            );
        }

        //  Get the skin
        $sSkinSlug                = appSetting('skin', 'blog-' . $iBlogId) ?: self::DEFAULT_SKIN;
        $this->aEnabled[$iBlogId] = $this->get($sSkinSlug);
        if (empty($this->aEnabled[$iBlogId])) {
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
    public function getAll()
    {
        if (empty($this->iActiveBlogId)) {
            throw new SkinException(
                'No blog selected.'
            );
        }

        return $this->aAvailable[$this->iActiveBlogId];
    }

    // --------------------------------------------------------------------------

    /**
     * Returns the config for the enabled skin
     * @return stdClass
     */
    public function getEnabled()
    {
        if (empty($this->iActiveBlogId)) {
            throw new SkinException(
                'No blog selected.'
            );
        }

        return $this->aEnabled[$this->iActiveBlogId];
    }

    // --------------------------------------------------------------------------

    /**
     * Gets a single skin
     * @param  string  $sSlug The skin's slug
     * @return stdClass
     */
    public function get($sSlug)
    {
        $aSkins = $this->getAll();

        foreach ($aSkins as $oSkin) {
            if ($oSkin->slug == $sSlug) {
                return $oSkin;
            }
        }

        return false;
    }
}
