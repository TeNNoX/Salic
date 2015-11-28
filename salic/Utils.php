<?php

namespace salic;


class Utils
{

    /*
     * generate the href attribute for the pages
     * (adds a 'href' => '...' to each page array)
     */
    public static function generatePageHrefs(array &$pages)
    {
        foreach ($pages as $key => &$page) {
            $page['href'] = "index.php?page=$key";
        }
    }
}