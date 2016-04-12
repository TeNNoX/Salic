<?php

namespace salic;


use Salic\Exception\SalicException;

class Validator
{

    const langKeyRegex = '/^[A-Za-z-]+$/i'; // 'page/subpage' TODO:
    const pageKeyRegex = '/^[A-Za-z0-9-]+(\/[A-Za-z0-9-]+)*$/i'; // 'page/subpage'

    /**
     * Checks a given $pageKey.
     *
     * @param string $pageKey The pageKey to check
     * @return bool If the given $pageKey is valid.
     * @throws SalicException
     */
    public static function checkPageKey($pageKey)
    {
        return self::check($pageKey, Validator::pageKeyRegex);
    }

    /**
     * Checks a given $langKey.
     *
     * @param string $langKey The langKey to check
     * @return bool If the given $langKey is valid.
     * @throws SalicException
     */
    public static function checkLangKey($langKey)
    {
        return self::check($langKey, Validator::langKeyRegex);
    }


    public static function check($subject, $pattern) {
        $result = preg_match($pattern, $subject);

        if ($result == 1) {
            return true;
        } else if ($result == 0) {
            return false;
        } else { // should only be in case $result = false
            throw new SalicException("Error during sanitization");
        }
    }
}