<?php
namespace salic;

class Settings
{
    protected static $baseDir = 'site/';

    private static $lang_settings;
    private static $page_settings;
    private static $template_settings;

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

    public static function getTemplateSettings()
    {
        if (isset(self::$template_settings)) // cache for this request
            return self::$template_settings;

        $file = 'templates.json';
        $json = self::parse(self::$baseDir . $file);
        self::assertArray('default', $json, $file);

        foreach($json as $name => &$template) {
            $fileinfo = $file . ":$name";
            self::assertString('file', $template, $fileinfo); // filename is e.g. 'templates.json:default'
            // TODO: check if template file exists

            self::assureArray('fields', $template, $fileinfo);
        }
        return $json;
    }

    /**
     *
     *
     * @param $file -
     * @return array - parsed array
     * @throws SalicSettingsException - if reading or parsing fails
     */
    private static function parse($file)
    {
        $raw = file_get_contents($file);
        if ($raw === false)
            throw new SalicSettingsException("Unable to read '$file'"); //TODO: default values when json files don't exist

        // remove comments (source: https://secure.php.net/manual/en/function.json-decode.php#111551)
        $raw = preg_replace("#(/\*([^*]|[\r\n]|(\*+([^*/]|[\r\n])))*\*+/)|([\s\t](//).*)#", '', $raw);

        $json = json_decode($raw, true);
        if (!$json || json_last_error() !== JSON_ERROR_NONE)
            throw new SalicSettingsException("Unable to parse '$file': ".json_last_error_msg());
        return $json;
    }

    /**
     * Assert that $key exists in $array, and is a string.
     *
     * @param $key - the key to check for in the array
     * @param array $array - the array what should be checked
     * @param string $fileinfo - a filename, as info for the exception
     * @param string $pattern - [optional] a regex that will be checked
     * @throws SalicSettingsException - if assert fails
     */
    private static function assertString($key, array $array, $fileinfo, $pattern = null)
    {
        if (!array_key_exists($key, $array))
            throw new SalicSettingsException("Key '$key' not specified (in '$fileinfo')");
        $value = $array[$key];
        if (!is_string($value))
            throw new SalicSettingsException("Key '$key' is not a string (in '$fileinfo')");
        if ($pattern && preg_match($pattern, $value) !== 1)
            throw new SalicSettingsException("Invalid value for '$key' (in '$fileinfo')");
    }


    /**
     * Assert that $key exists in $array, and is an array.
     *
     * @param $key - the key to check for in the array
     * @param array $array - the array what should be checked
     * @param string $fileinfo - a filename, as info for the exception
     * @throws SalicSettingsException - if assert fails
     */
    private static function assertArray($key, $array, $fileinfo)
    {
        if (!array_key_exists($key, $array))
            throw new SalicSettingsException("Key '$key' not specified (in '$fileinfo')");
        $value = $array[$key];
        if (!is_array($value))
            throw new SalicSettingsException("Key '$key' is not an array (in '$fileinfo')");
    }

    /**
     * Checks if that array contains $key and if it is an array.
     * - no key => create empty one in $array[$key]
     * - not array => throw exception
     *
     * @param $key - the key to check for in the array
     * @param array $array - the array what should be checked
     * @param string $fileinfo - a filename, as info for the exception
     * @throws SalicSettingsException - if key exists, but is not an array
     */
    private static function assureArray($key, &$array, $fileinfo)
    {
        if (!array_key_exists($key, $array)) {
            $array[$key] = array();
        } else if (!is_array($array[$key]))
            throw new SalicSettingsException("Key '$key' is not an array (in '$fileinfo')");
    }
}

?>