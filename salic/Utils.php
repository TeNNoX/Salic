<?php

namespace salic;


class Utils
{
    /*
     * makes the pages array nice and consistent
     * - generate 'href' attribute (baseUrl+pageKey)
     * - set template to default tempalte if not specified
     */
    public static function normalizePageArray(array &$pages, $baseUrl, $defaultTemplate)
    {
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