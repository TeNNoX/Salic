<?php
namespace salic;

class Settings
{
    protected static $baseDir = 'site/';

    private static $lang_settings;
    private static $page_settings;

    public static function getLangSettings()
    {
        if (isset(self::$lang_settings)) // cache for this request
            return self::$lang_settings;

        $file = 'languages.json';
        $json = self::parse(self::$baseDir . $file);
        self::assertArray('available', $json, $file);

        $keys = array_keys($json['available']);
        if (!array_key_exists('default', $json)) {
            $json['default'] = array_shift($keys); // select first language as default
        } else if (!in_array($json['default'], $keys)) {
            throw new SalicSettingsException("default language '" . $json['default'] . "' is not listed in 'availiable (in '$file')");
        }

        self::assertString('default', $json, $file);
        return $json;
    }

    public static function getPageSettings($baseUrl, $defaultTemplate)
    {
        if (isset(self::$page_settings)) // cache for this request
            return self::$page_settings;

        $file = 'pages.json';
        $json = self::parse(self::$baseDir . $file);
        self::assertArray('available', $json, $file);

        $keys = array_keys($json['available']);
        if (!array_key_exists('default', $json)) {
            $json['default'] = array_shift($keys); // select first page as default
        } else if (!in_array($json['default'], $keys)) {
            throw new SalicSettingsException("default page '" . $json['default'] . "' is not listed in 'availiable (in '$file')");
        }

        self::assertString('default', $json, $file);

        Utils::normalizePageArray($json['available'], $baseUrl, $defaultTemplate);
        return $json;
    }

    private static function parse($file)
    {
        $raw = file_get_contents($file);
        if ($raw === false)
            throw new SalicSettingsException("Unable to read '$file'"); //TODO: default values when json files don't exist

        $json = json_decode($raw, true);
        if (!$json)
            throw new SalicSettingsException("Unable to parse '$file'");
        return $json;
    }

    private static function assertString($key, $json, $file, $pattern = false)
    {
        if (!array_key_exists($key, $json))
            throw new SalicSettingsException("Key '$key' not specified (in '$file')");
        $value = $json[$key];
        if (!is_string($value))
            throw new SalicSettingsException("Key '$key' is not a string (in '$file')");
        if ($pattern && preg_match($pattern, $value) !== 1)
            throw new SalicSettingsException("Invalid value for '$key' (in '$file')");
    }

    private static function assertArray($key, $json, $file, $pattern = false)
    {
        if (!array_key_exists($key, $json))
            throw new SalicSettingsException("Key '$key' not specified (in '$file')");
        $value = $json[$key];
        if (!is_array($value))
            throw new SalicSettingsException("Key '$key' is not an array (in '$file')");
    }
}

?>