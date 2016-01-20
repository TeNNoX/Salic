<?php

namespace salic;

require_once 'Settings.php';

class Utils
{

    /**
     * Parse the accepted languages from the HTTP Header, and return the preferred one, if available, otherwise the default.
     *
     * @return string
     */
    public static function getDefaultLanguageFromHeader()
    {
        $lang_settings = Settings::getLangSettings();

        $default = $lang_settings['default'];
        $available = array_keys($lang_settings['available']);

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
     * @param $navSettings
     * @param $baseUrl
     * @param $lang
     * @return array
     * @throws SalicSettingsException
     */
    public static function getNavPageList($navSettings, $baseUrl, $lang) // = general page settings
    {
        $nav_array = array();
        $pages = $navSettings['displayed'];
        $external = $navSettings['external_links'];
        foreach ($pages as $key) {
            $title = Utils::getPageTitle($key, $lang);
            $href = array_key_exists($key, $external) ? $external[$key] : ($baseUrl . $key);
            $nav_array[$key] = array(
                'title' => $title,
                'href' => $baseUrl . $key,
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
                throw new SalicSettingsException("Couldn't create directory '$path'");
        }
    }

    /**
     * Gets the appropriate title for a page.
     * If translations are available, use the one needed, if not, take what's there.
     *
     * @param string $pageKey - the page of which we should get the title
     * @param string $lang - the language we should get it in (if translations exist)
     * @return string - the (translated?) title
     * @throws SalicSettingsException - if the translation does not exist (but there are some translations)
     */
    private static function getPageTitle($pageKey, $lang)
    {
        $pageSettings = Settings::getPageSettings($pageKey);
        if (!is_array($pageSettings['title']))
            return $pageSettings['title'];
        else if (!array_key_exists($lang, $pageSettings['title']))
            throw new SalicSettingsException("Page title doesn't have translation for '$lang' (" . $pageSettings . ")");
        else
            return $pageSettings['title'][$lang];
    }

    public static function pageExists($pagekey)
    {
        return is_dir(Settings::baseDir . "data/$pagekey");
    }
}
