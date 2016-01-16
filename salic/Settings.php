<?php
namespace salic;

class Settings
{
    const baseDir = 'site/';
    const dataDir = 'data/';

    // cached settings
    private static $lang_settings;
    private static $general_page_settings;
    private static $template_settings;

    private static $page_settings = array();

    public static function getLangSettings()
    {
        if (isset(self::$lang_settings)) // cache for this request
            return self::$lang_settings;

        $file = 'languages.json';
        $json = self::parse(self::baseDir . $file);
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

    public static function getGeneralPageSettings()
    {
        if (isset(self::$general_page_settings)) // cache for this request
            return self::$general_page_settings;

        $file = 'pages.json';
        $json = self::parse(self::baseDir . $file);

        self::assertString('default', $json, $file);
        self::assertArray('available', $json, $file);

        $avail = $json['available'];
        if (!array_key_exists('default', $json)) {
            $json['default'] = array_shift($avail); // select first page as default
        } else if (!in_array($json['default'], $avail)) {
            throw new SalicSettingsException("default page '" . $json['default'] . "' is not in 'availiable' (in '$file')");
        }

        self::assureArray('hidden_in_nav', $json, $file);
        // TODO: warning when hidden page is invalid
        // an exception is not really necessary:
        /*foreach ($json['hidden_in_nav'] as $pagekey) {
            if (!in_array($pagekey, $avail)) {
                throw new SalicSettingsException("Hidden page '" . $json['default'] . "' is not in 'availiable' (in '$file')");
            }
        }*/

        return $json;
    }

    public static function getPageSettings($pagekey)
    {
        if (isset(self::$page_settings[$pagekey])) // cache for this request
            return self::$page_settings[$pagekey];

        $file = "data/$pagekey/page.json";
        $json = self::parse(self::baseDir . $file);
        self::assertExists('title', $json, $file);
        self::assureString('template', $json, $file);

        self::assureArray('areas', $json, $file);
        $areas = $json['areas'];
        foreach ($areas as $areaKey => $blocks) { // check all areas
            if (!is_array($blocks))
                throw new SalicSettingsException("Value for area '$areaKey' is not an array (in '$file')");
            $blockKeys = array(); // for duplicate checking

            foreach ($blocks as $i => $block) { // check all blocks
                self::assertString('key', $block, $file . ":areas>$areaKey>$i"); // use index, blockKey is not known yet

                // check for duplicate keys
                $blockKey = $block['key'];
                if (in_array($blockKey, $blockKeys))
                    throw new SalicSettingsException("Duplicate block blockKey '$blockKey' in '$file:areas>$areaKey'");
                $blockKeys[] = $blockKey;

                self::assertString('type', $block, $file . ":areas>$areaKey>$blockKey"); // eg. 'templates.json:areas>main>intro'
                // TODO: check if block exists
            }
        }

        // set default template if not specified
        if (!array_key_exists('template', $json)) {
            $json['template'] = Salic::defaultTemplate;
        }

        return $json;
    }

    public static function getTemplateSettings()
    {
        if (isset(self::$template_settings)) // cache for this request
            return self::$template_settings;

        $file = 'templates.json';
        $json = self::parse(self::baseDir . $file);
        self::assertArray('default', $json, $file);

        foreach ($json as $name => &$template) {
            $fileinfo = $file . ":$name";  // fileinfo is e.g. 'templates.json:default'

            self::assertString('file', $template, $fileinfo);
            // TODO: check if template file exists

            self::assureArray('fields', $template, $fileinfo);
            self::assureArray('areas', $template, $fileinfo);
        }
        return $json;
    }

    /**
     * Assert that $key exists in $array.
     *
     * @param $key - the key to check for in the array
     * @param array $array - the array what should be checked
     * @param string $fileinfo - a filename, as info for the exception
     * @throws SalicSettingsException - if assert fails
     */
    private static function assertExists($key, array $array, $fileinfo)
    {
        if (!array_key_exists($key, $array))
            throw new SalicSettingsException("Key '$key' not specified (in '$fileinfo')");
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
     * Checks if that array contains $key and if it is a string.
     * - doesn't exist => create it
     * - not a string => throw exception
     *
     * @param $key - the key to check for in the array
     * @param array $array - the array what should be checked
     * @param string $fileinfo - a filename, as info for the exception
     * @param string $default - [optional] the value to set if not given, default default is ""
     * @throws SalicSettingsException - if key exists, but is not a string
     */
    private static function assureString($key, &$array, $fileinfo, $default = "")
    {
        if (!array_key_exists($key, $array)) {
            $array[$key] = $default;
        } else if (!is_string($array[$key]))
            throw new SalicSettingsException("Key '$key' is not an array (in '$fileinfo')");
    }

    /**
     * Checks if that array contains $key and if it is an array.
     * - doesn't exist => create it
     * - not an array => throw exception
     *
     * @param $key - the key to check for in the array
     * @param array $array - the array what should be checked
     * @param string $fileinfo - a filename, as info for the exception
     * @param array $default - [optional] the value to set if not given, default default is an empty array
     * @throws SalicSettingsException - if key exists, but is not an array
     */
    private static function assureArray($key, &$array, $fileinfo, $default = array())
    {
        if (!array_key_exists($key, $array)) {
            $array[$key] = $default;
        } else if (!is_array($array[$key]))
            throw new SalicSettingsException("Key '$key' is not an array (in '$fileinfo')");
    }

    /**
     * Parse the given json file to array
     * + remove comments
     *
     * @param $file - well, the file
     * @return array - parsed array
     * @throws SalicSettingsException - if reading or parsing fails
     */
    private static function parse($file)
    {
        if (!is_file($file))
            throw new SalicSettingsException("File '$file' does not exist"); //TODO: default values when json files don't exist

        $raw = file_get_contents($file);
        if ($raw === false)
            throw new SalicSettingsException("Unable to read '$file'");

        // remove comments (source: https://secure.php.net/manual/en/function.json-decode.php#111551)
        $raw = preg_replace("#(/\*([^*]|[\r\n]|(\*+([^*/]|[\r\n])))*\*+/)|([\s\t](//).*)#", '', $raw);

        $json = json_decode($raw, true);
        if (!$json || json_last_error() !== JSON_ERROR_NONE)
            throw new SalicSettingsException("Unable to parse '$file': " . json_last_error_msg());
        return $json;
    }
}

?>