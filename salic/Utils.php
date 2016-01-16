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

    public static function getNavPageList($pageSettings, $baseUrl) // = general page settings
    {
        $nav_array = array();
        $pages = $pageSettings['available'];
        foreach ($pages as $key) {
            if (array_key_exists($key, $pageSettings['hidden_in_nav']))
                continue;

            $title = Settings::getPageSettings($key)['title'];
            $nav_array[$key] = array(
                'title' => $title,
                'href' => $baseUrl . $key,
            );
        }
        return $nav_array;
    }
}
