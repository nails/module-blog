<?php

/**
 * This model handles everything to do with blog skins.
 *
 * @package     Nails
 * @subpackage  module-blog
 * @category    Model
 * @author      Nails Dev Team
 * @link
 */

class NAILS_Blog_skin_model extends NAILS_Model
{
    protected $aAvailable;
    protected $aSkinLocations;

    // --------------------------------------------------------------------------

    /**
     * Construct the model.
     */
    public function __construct()
    {
        parent::__construct();

        // --------------------------------------------------------------------------

        $this->aAvailable = null;

        /**
         * Skin locations
         * The model will search these directories for skins; to add more directories extend this
         * This must be an array with 2 indexes:
         * `path` => The absolute path to the directory containing the skins (required)
         * `url` => The URL to access the skin (required
         * `regex` => If the directory doesn't only contain skin then specify a regex to filter by
         */

        if (empty($this->aSkinLocations)) {

            $this->aSkinLocations = array();
        }

        //  'Official' skins
        $this->aSkinLocations[] = array(
            'path' => NAILS_PATH,
            'url' => NAILS_URL,
            'regex' => '/^blog-skin-(.*)$/'
        );

        //  App Skins
        $this->aSkinLocations[] = array(
            'path' => FCPATH . APPPATH . 'modules/blog/skins',
            'url' => site_url(APPPATH . 'modules/blog/skins', isPageSecure())
        );
    }

    // --------------------------------------------------------------------------

    /**
     * Fetches all available skins
     * @param  boolean $refresh Fetchf rom refresh - skip the cache
     * @return array
     */
    public function get_available($refresh = false)
    {
        if (!is_null($this->aAvailable) && !$refresh) {

            return $this->aAvailable;
        }

        //  Reset
        $this->aAvailable = array();

        // --------------------------------------------------------------------------

        /**
         * Look for skins, where a skin has the same name, the last one found is the
         * one which is used.
         */

        \Nails\Factory::helper('directory');

        //  Take a fresh copy
        $aSkinLocations = $this->aSkinLocations;

        //  Sanitise
        for ($i = 0; $i < count($aSkinLocations); $i++) {

            //  Ensure path is present and has a trailing slash
            if (isset($aSkinLocations[$i]['path'])) {

                if (substr($aSkinLocations[$i]['path'], -1, 1) !== '/') {

                    $aSkinLocations[$i]['path'] .= '/';
                }

            } else {

                unset($aSkinLocations[$i]);
            }

            //  Ensure URL is present and has a trailing slash
            if (isset($aSkinLocations[$i]['url'])) {

                if (substr($aSkinLocations[$i]['url'], -1, 1) !== '/') {

                    $aSkinLocations[$i]['url'] .= '/';
                }

            } else {

                unset($aSkinLocations[$i]);
            }
        }

        //  Reset array keys, possible that some may have been removed
        $aSkinLocations = array_values($aSkinLocations);
        $aChildSkins = array();

        foreach ($aSkinLocations as $aSkinLocation) {

            $sPath = $aSkinLocation['path'];
            $aSkins = is_dir($sPath) ? directory_map($sPath, 1) : array();

            if (is_array($aSkins)) {

                foreach ($aSkins as $skin) {

                    //  do we need to filter out non skins?
                    if (!empty($aSkinLocation['regex'])) {

                        if (!preg_match($aSkinLocation['regex'], $skin)) {

                            log_message('debug', '"' . $skin . '" is not a blog skin.');
                            continue;
                        }
                    }

                    // --------------------------------------------------------------------------

                    //  Exists?
                    if (file_exists($sPath . $skin . '/config.json')) {

                        $oConfig = @json_decode(file_get_contents($sPath . $skin . '/config.json'));

                    } else {

                        log_message('error', 'Could not find configuration file for skin "' . $sPath . $skin. '".');
                        continue;
                    }

                    //  Valid?
                    if (empty($oConfig)) {

                        log_message(
                            'error',
                            'Configuration file for skin "' . $sPath . $skin. '" contains invalid JSON.'
                        );
                        continue;

                    } elseif (!is_object($oConfig)) {

                        log_message(
                            'error',
                            'Configuration file for skin "' . $sPath . $skin. '" contains invalid data.'
                        );
                        continue;
                    }

                    // --------------------------------------------------------------------------

                    //  All good!
                    //  Set the slug
                    $oConfig->slug = $skin;

                    //  Set the path
                    $oConfig->path = $sPath . $skin . '/';

                    //  Set the URL
                    $oConfig->url = $aSkinLocation['url'] . $skin . '/';

                    $this->aAvailable[$skin] = $oConfig;

                    // --------------------------------------------------------------------------

                    /**
                     * If the skin is a child, make a note to test its parent exists. We do this
                     * once all skins have been loaded.
                     */

                    if (!empty($oConfig->parent)) {
                        $aChildSkins[$oConfig->slug] = $oConfig->parent;
                    }
                }
            }
        }

        //  Test any child skins to ensure their parent is available
        $aRemoveSkins = array();
        if (!empty($aChildSkins)) {
            foreach ($aChildSkins as $sSkinSlug => $sSkinParentSlug) {
                if (empty($this->aAvailable[$sSkinParentSlug])) {
                    $aRemoveSkins[] = $sSkinSlug;
                }
            }
        }

        if (!empty($aRemoveSkins)) {
            foreach ($aRemoveSkins as $sSkin) {
                $this->aAvailable[$sSkin] = null;
            }
            $this->aAvailable = array_filter($this->aAvailable);
        }

        $this->aAvailable = array_values($this->aAvailable);

        return $this->aAvailable;
    }

    // --------------------------------------------------------------------------

    /**
     * Gets a single skin
     * @param  string  $sSlug    The skin's slug
     * @param  boolean $bRefresh Skip the cache
     * @return stdClass
     */
    public function get($sSlug, $bRefresh = false)
    {
        $aSkins = $this->get_available($bRefresh);

        foreach ($aSkins as $oSkin) {

            if ($oSkin->slug == $sSlug) {

                return $oSkin;
            }
        }

        $this->_set_error('"' . $sSlug . '" was not found.');
        return false;
    }
}

// --------------------------------------------------------------------------

/**
 * OVERLOADING NAILS' MODELS
 *
 * The following block of code makes it simple to extend one of the core blog
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

if (!defined('NAILS_ALLOW_EXTENSION_BLOG_SKIN_MODEL')) {

    class Blog_skin_model extends NAILS_Blog_skin_model
    {
    }
}
