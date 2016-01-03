<?php
namespace salic;

class Settings
{

    public static function getLangSettings()
    {
        $file = 'languages.json';
        $json = self::parse('site/' . $file);
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

    private static function parse($file)
    {
        $raw = file_get_contents($file);
        if ($raw === false)
            throw new SalicSettingsException("Unable to read '$file'"); //TODO: default values when json files don't exist

        $json = json_decode($raw, true);
        if (!$json)
            throw new SalicSettingsException("Unable to parse '$file'");
        return $json;
    }

    private static function assertString($key, $json, $file, $pattern = false)
    {
        if (!array_key_exists($key, $json))
            throw new SalicSettingsException("Key '$key' not specified (in '$file')");
        $value = $json[$key];
        if (!is_string($value))
            throw new SalicSettingsException("Key '$key' is not a string (in '$file')");
        if ($pattern && preg_match($pattern, $value) !== 1)
            throw new SalicSettingsException("Invalid value for '$key' (in '$file')");
    }

    private static function assertArray($key, $json, $file, $pattern = false)
    {
        if (!array_key_exists($key, $json))
            throw new SalicSettingsException("Key '$key' not specified (in '$file')");
        $value = $json[$key];
        if (!is_array($value))
            throw new SalicSettingsException("Key '$key' is not an array (in '$file')");
    }
}

?>