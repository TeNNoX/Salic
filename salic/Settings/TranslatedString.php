<?php
namespace Salic\Settings;

use Salic\Exception\ShouldNotHappenException;


class TranslatedString
{
    private $value;
    private $translated;

    public function __construct($value)
    {
        $this->value = $value;
        if (is_string($value))
            $this->translated = false;
        else if (is_array($value))
            $this->translated = true;
        else // should be checked before calling the constructor -> ShouldNotHappenException
            throw new ShouldNotHappenException("Value for TranslatedString needs to be string or array");
    }

    public function get($lang)
    {
        if (!$this->translated) {
            return $this->value;
        } else {
            if (!array_key_exists($lang, $this->value)) { // if translation not available...
                $lang = LangSettings::get()->default; // ... select default language...
                if (!array_key_exists($lang, $this->value)) {
                    return array_values($this->value)[0]; // ... or if that is not available either, return the first translation.
                }
            }
            return $this->value[$lang];
        }
    }

    public function isTranslated()
    {
        return $this->translated;
    }

}