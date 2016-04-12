<?php

namespace Salic\Settings;

use Salic\Exception\SalicSettingsException;
use Salic\Validator;


class LangSettings extends Settings
{

    public $default;
    public $available;

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
        $this->file = 'languages.json';
        parent::__construct();
    }

    public function getDefault()
    {
        return ['default' => 'en', 'available' => ['en']];
    }

    public function parseFromJson($json)
    {
        $this->default = self::getString('default', $json);
        $this->available = self::getDict('available', $json);
        //TODO: assert unique values only
    }

    public function validate()
    {
        foreach ($this->available as $key => $name) {
            if (!Validator::checkLangKey($key)) {
                throw new SalicSettingsException("Language format invalid: '$key'", $this->file . self::fis . 'available', $this->available);
            }
        }

        // check if default is in available
        if (!array_key_exists($this->default, $this->available)) {// make sure the specified homepage exists
            throw new SalicSettingsException("Default language '{$this->default}' not found found in available ",
                $this->file . self::fis . 'default', $this->available);
        }
    }

    public function exists($lang)
    {
        return array_key_exists($lang, $this->available);
    }
}