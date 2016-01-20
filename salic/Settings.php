<?php
namespace salic;

class Settings
{
    const baseDir = 'site/';

    // cached settings
    private static $lang_settings;
    private static $nav_settings;
    private static $template_settings;

    private static $page_settings = array();

    public static function getLangSettings()
    {
        if (isset(self::$lang_settings)) // cache for this request
            return self::$lang_settings;

        $file = 'languages.json';
        $json = self::parseOrCreate(self::baseDir . $file,
            ['default' => 'en', 'available' => ['en']]);
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

    public static function getNavSettings()
    {
        if (isset(self::$nav_settings)) // cache for this request
            return self::$nav_settings;

        $file = 'navigation.json';
        $json = self::parseOrCreate(self::baseDir . $file,
            ['homepage' => 'home', 'displayed' => ['home']]);

        self::assertArray('displayed', $json, $file);
        $displayed = $json['displayed'];


        if (!array_key_exists('homepage', $json)) { // if not set, select first page as default
            $json['default'] = array_shift($displayed);
        } else if (!Utils::pageExists($json['homepage'])) {// make sure the specified homepage exists
            throw new SalicSettingsException("No page '" . $json['homepage'] . "' found in data/ (mentioned in '$file:homepage')");
        }

        self::assureArray('external_links', $json, $file);

        return $json;
    }

    public static function getPageSettings($pagekey)
    {
        if (isset(self::$page_settings[$pagekey])) // cache for this request
            return self::$page_settings[$pagekey];

        Utils::mkdirs(self::baseDir . "data/$pagekey");
        $file = "data/$pagekey/page.json";

        // generate default areas (needed for parseOrCreate)
        $defaultAreas = array(); // ['area1' => [], 'area2' => []]
        foreach (self::getTemplateSettings()['default']['areas'] as $area) {
            $defaultAreas[$area] = array();
        }
        $json = self::parseOrCreate(self::baseDir . $file,
            ['title' => ucfirst($pagekey),  // generate string via uppercasing first letter of pagekey
                'template' => 'default',
                'areas' => $defaultAreas]); // the area names

        // check title
        self::assureNotEmptyStringOrArray('title', $json, $file, ucfirst($pagekey)); //TODO: validate page title translations

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
        if (!array_key_exists('template', $json) || !$json['template']) {
            $json['template'] = Salic::defaultTemplate;
        }

        return $json;
    }

    public static function getTemplateSettings()
    {
        if (isset(self::$template_settings)) // cache for this request
            return self::$template_settings;

        $file = 'templates.json';
        $json = self::parseOrCreate(self::baseDir . $file,
            ['file' => 'default.html.twig', 'fields' => [], "areas" => []]);
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
     * Assert that $key exists in $array.
     *
     * @param $key - the key to check for in the array
     * @param array $array - the array what should be checked
     * @param string $fileinfo - a filename, as info for the exception
     * @throws SalicSettingsException - if assert fails
     */
    private static function assureNotEmptyStringOrArray($key, array &$array, $fileinfo, $default)
    {
        if (!array_key_exists($key, $array))
            $array[$key] = $default;
        else if (!is_string($array[$key]) && !is_array($array[$key]))
            throw new SalicSettingsException("Key '$key' is neither string nor array (in '$fileinfo')");
        else if (empty($array[$key]))
            $array[$key] = $default;
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
            throw new SalicSettingsException("File '$file' does not exist");

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

    /**
     * Parse the given json file to array
     * + remove comments
     * + If if doesn't exist, try to create it
     *
     * @param $file - well, the file
     * @param $default - the default content
     * @return array - parsed array
     * @throws SalicSettingsException - if parsing or creating fails
     */
    private static function parseOrCreate($file, $default)
    {
        if (!is_file($file))
            self::saveJsonFile($file, $default);
        // still read if after that, because there could be situations, where writing works, but reading doesn't
        // -> it would work one time, when the file does not exist, but not when it does. (stability > efficiency)

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

    /**
     * Saves (or creates) that json file
     *
     * @param $file
     * @param $data
     */
    private static function saveJsonFile($file, $data)
    {
        $raw = json_encode($data, JSON_PRETTY_PRINT);
        file_put_contents($file, $raw);
    }
}

?>