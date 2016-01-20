<?php

namespace Salic\Settings;

use Salic\Exception\SalicSettingsException;
use Salic\Utils;

class PageSettings extends Settings
{

    /**
     * @var TranslatedString The title of the page
     */
    public $title;

    /**
     * @var string The template for this page
     */
    public $template;

    /**
     * @var array The list of blocks by area
     */
    public $areas;

    private $pageKey;

    protected static $cached;

    public static function get($pageKey)
    {
        if (!self::$cached)
            self::$cached = [];

        if (array_key_exists($pageKey, self::$cached))
            return self::$cached[$pageKey];
        return new static($pageKey);
    }

    public function blocks($area)
    {
        return $this->areas[$area];
    }

    public function __construct($pageKey)
    {
        $this->pageKey = $pageKey;
        Utils::mkdirs(self::baseDir . "data/$pageKey");
        $this->file = 'data/' . $pageKey . '/page.json';
        parent::__construct();
    }

    public function getDefault()
    {
        $defaultAreas = $this->getDefaultAreas();

        return ['title' => ucfirst($this->pageKey),  // generate string via uppercasing first letter of pageKey
            'template' => 'default',
            'areas' => $defaultAreas]; // the area names
    }

    public function parseFromJson($json)
    {
        $this->title = self::getTranslatedString('title', $json);
        $this->template = self::getString('template', $json, 'default');

        $this->areas = self::getDict('areas', $json, []);
        //TODO: parse blocks array to Block objects ?

        foreach ($this->areas as $area => $blocks) {
            if (!is_array($blocks))
                throw new SalicSettingsException("Blocklist for '$area' is not an array", $this->file . self::fis . "areas");
        }
    }

    public function validate()
    {
        // check if template exists
        $templateSettings = TemplateSettings::get();
        if (!$templateSettings->exists($this->template))
            throw new SalicSettingsException("Template '" . $this->template . "' not found", $this->file, $templateSettings->templates);

        // create empty areas that are not present
        $templateAreas = $templateSettings->sub($this->template)['areas'];
        foreach ($templateAreas as $area) {
            if (!array_key_exists($area, $this->areas))
                $this->areas[$area] = [];
        }

        // check blocks list
        foreach ($this->areas as $areaKey => $blocks) {
            // check if area exists in template
            if (!in_array($areaKey, $templateAreas))
                throw new SalicSettingsException("Area '$areaKey' doesn't exist in the template", $this->file . self::fis . "areas");

            $blockKeys = array(); // for duplicate checking

            foreach ($blocks as $i => $block) { // check all blocks
                self::getString('key', $block, $this->file . "areas>$areaKey>$i"); // use index, blockKey is not known yet

                // check for duplicate keys
                $blockKey = $block['key'];
                if (in_array($blockKey, $blockKeys))
                    throw new SalicSettingsException("Duplicate block blockKey '$blockKey'", $this->file . self::fis . "areas>$areaKey");
                $blockKeys[] = $blockKey;

                self::getString('type', $block, "areas>$areaKey>$blockKey"); // eg. 'templates.json:areas>main>intro'
                // TODO: check if block exists
            }
        }
    }

    private function getDefaultAreas($template = 'default')
    {
        // generate default areas from template
        $defaultAreas = array();
        foreach (TemplateSettings::get()->sub($template)['areas'] as $area) {
            $defaultAreas[$area] = array(); // ['area1' => [], 'area2' => []]
        }
        return $defaultAreas;
    }
}