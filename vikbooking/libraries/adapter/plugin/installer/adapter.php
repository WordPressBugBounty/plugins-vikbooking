<?php
/** 
 * @package   	VikUpdater
 * @subpackage 	plugin
 * @author    	E4J s.r.l.
 * @copyright 	Copyright (C) 2023 E4J s.r.l. All Rights Reserved.
 * @license  	http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 * @link 		https://vikwp.com
 */

// No direct access
defined('ABSPATH') or die('No script kiddies please!');

JLoader::import('adapter.plugin.installer.installer');

/**
 * Adapter used to install any kind of plugin simply by accepting
 * the slug and the download url as constructor arguments.
 * 
 * @since 10.1.57
 */
class JPluginInstallerAdapter extends JPluginInstaller
{
    /** @var string */
    private $slug;

    /** @var string */
    private $url;

    /**
     * Class contructor.
     * 
     * @param  string  $slug  The plugin slug.
     * @param  string  $url   The URL used to download the plugin.
     */
    public function __construct(string $slug, string $url)
    {
        $this->slug = $slug;
        $this->url  = $url;
    }

    /**
     * Returns the plugin slug for the activation.
     * 
     * @return  string
     */
    protected function getSlug()
    {
        return $this->slug . '/' . $this->slug;
    }

    /**
     * Returns the remote URL that will be used to download the plugin.
     * 
     * @return  string
     */
    protected function getUrl()
    {
        return $this->url;
    }
}
