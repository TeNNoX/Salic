<?php

namespace Salic\Settings;

use Salic\Exception\SalicSettingsException;
use Salic\Utils;


class NavSettings extends Settings
{

    public $homepage;
    public $displayed;
    public $external_links;

    /**
     * @var self A cached instance of this, if available
     */
    protected static $cached;

    /**
     * @return self A cached or fresh instance of this
     */
    public static function get()
    {
        if (self::$cached)
            return self::$cached;
        return new self();
    }


    public function __construct()
    {
        $this->file = 'navigation.json';
        parent::__construct();
    }

    public function exists($name)
    {
        return array_key_exists($name, $this->displayed);
    }

    public function getDefault()
    {
        return ['homepage' => 'home', 'displayed' => ['home']];
    }

    public function parseFromJson($json)
    {
        $this->homepage = self::getString('homepage', $json);
        $this->displayed = self::getList('displayed', $json, []); // default to empty array

        $this->external_links = self::getDict('external_links', $json, []);
    }

    public function validate()
    {
        // check homepage
        if (!PageSettings::pageExists($this->homepage)) {// make sure the specified homepage exists
            throw new SalicSettingsException("Page '{$this->homepage}' not found found in pages/", $this->file . self::fis . 'homepage');
        }


        // check displayed pages
        $externals = array_keys($this->external_links);
        foreach ($this->displayed as $page) {
            if (in_array($page, $externals))
                continue; // if it is an external link, it doesn't need to exist

            if (!PageSettings::pageExists($page)) {// make sure the specified page exists
                throw new SalicSettingsException("Page '{$page}' is neither in data/ nor an external_link", $this->file . self::fis . 'displayed');
            }
        }
    }
}