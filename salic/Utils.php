<?php

namespace Salic;

use Salic\Exception\SalicSettingsException;
use Salic\Settings\LangSettings;
use Salic\Settings\NavSettings;
use Salic\Settings\PageSettings;
use Salic\Settings\Settings;

require_once 'Settings/Settings.php';

class Utils
{

    /**
     * Parse the accepted languages from the HTTP Header, and return the preferred one, if available, otherwise the default.
     *
     * @return string
     */
    public static function getDefaultLanguageFromHeader()
    {
        $lang_settings = LangSettings::get();

        $default = $lang_settings->default;
        $available = array_keys($lang_settings->available);

        require_once 'LanguageDetection.php';
        return getDefaultLanguage($available, $default);
    }

    public static function returnHttpError($code, $msg = false)
    {
        http_response_code($code);

        if ($msg)
            die($msg);
        else
            die();
    }

    /**
     * Gets the pagelist for nav
     * - without hidden pages
     * - with title and href values generated
     *
     * eg: ['page1' => ['title' => "Page 1", 'href' => "/page1"]]
     *
     * @param $baseUrl
     * @param $lang
     * @return array
     * @throws SalicSettingsException
     */
    public static function getNavPageList($baseUrl, $lang) // = general page settings
    {
        $navSettings = NavSettings::get();
        $nav_array = array();
        $pages = $navSettings->displayed;
        $external = $navSettings->external_links;
        foreach ($pages as $key) {
            $title = PageSettings::get($key)->title->get($lang);
            $href = array_key_exists($key, $external) ? $external[$key] : ($baseUrl . $key);
            $nav_array[$key] = array(
                'title' => $title,
                'href' => $href,
            );
        }
        return $nav_array;
    }

    /**
     * Example get values of childKey 'a' of:
     *    [{"a"=>1, "b" =>2},{"a"=>1, "x" =>12},{"a"=>3, "b" =>5}]
     * ->[1,2,3]
     *
     * @param array $array - the array to get the values from
     * @param string $childKey - the key that we should get the values from
     * @return array
     */
    public static function childValues($array, $childKey)
    {
        $arr = array();
        foreach ($array as $k => $child) {
            $arr[] = $child[$childKey];
        }
        return $arr;
    }

    public static function mkdirs($path, $mode = 0777)
    {
        if (!is_dir($path)) {
            if (!mkdir($path, $mode, true))
                throw new SalicSettingsException("Couldn't create directory", $path);
        }
    }

    public static function pageExists($pagekey)
    {
        return is_dir(Settings::baseDir . "data/$pagekey");
    }
}
