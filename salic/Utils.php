<?php

namespace salic;

require_once 'Settings.php';

class Utils
{

    /**
     * Parse the accepted languages from the HTTP Header, and return the preferred one, if available, otherwise the default.
     *
     * @param array $lang_settings - if already loaded
     * @return string
     */
    public static function getDefaultLanguageFromHeader($lang_settings = null)
    {
        if ($lang_settings == null)
            $lang_settings = Settings::getLangSettings();

        $default = $lang_settings['default'];
        $available = array_keys($lang_settings['available']);

        require_once 'LanguageDetection.php';
        return getDefaultLanguage($available, $default);
    }

    /**
     * makes the pages array nice and consistent
     * - generate 'href' attribute (baseUrl+pageKey)
     * - set template to default tempalte if not specified
     *
     * @param array $pages
     * @param $baseUrl
     * @param $defaultTemplate
     */
    public static function normalizePageArray(array &$pages, $baseUrl, $defaultTemplate)
    {
        array_values($pages)[0]['is_default'] = true;
        foreach ($pages as $key => &$page) {
            // generate href
            $page['href'] = $baseUrl . $key;

            // set default template if not specified
            if (!array_key_exists('template', $page)) {
                $page['template'] = $defaultTemplate;
            } //TODO: add extension if not given in pages.json ?
        }
    }

    public static function returnHttpError($code, $msg = false)
    {
        http_response_code($code);

        if ($msg)
            die($msg);
        else
            die();
    }

    public static function removeHiddenPages($pages)
    {
        foreach ($pages as $key => $page) {
            if (array_key_exists('hidden', $page) && $page['hidden'] == true)
                unset($pages[$key]);
        }
        return $pages;
    }
}
