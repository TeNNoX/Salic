<?php
namespace Salic\Settings;

use Salic\Exception\SalicSettingsException;

abstract class Settings
{
    const baseDir = 'site/';
    /**
     * FileInfo separator (so I can change it easy later, if I want)
     */
    const fis = ':';

    /**
     * @var string The filename under 'site/'
     */
    protected $file;

    public function __construct()
    {
        $json = self::parseOrCreate();
        $this->parseFromJson($json); //TODO: sanitize keys (eg. page names, languages, blockKeys, ...)
        //TODO: warn about unrecognized setting keys
        $this->validate();
        static::$cached = $this; // store instance to cache
        //TODO: save sanitized version of settings?
    }

    public abstract function getDefault();

    public abstract function parseFromJson($json);

    public abstract function validate();


    /**
     * Assert that $key exists in $array, and is a boolean.
     *
     * @param string $key The key to check for in the array
     * @param array $array The array what should be checked
     * @param string $default The default value, if the key doesn't exist. If this is null, an exception is thrown.
     * @param string $extraFileInfo Some extra info to add to the filename in case of an exception  (eg 'areas>XYZ')
     * @return boolean The value
     * @throws SalicSettingsException If empty and no $default is given, or the existing value is not a string
     */
    protected function getBoolean($key, array $array, $default = null, $extraFileInfo = "")
    {
        $fileInfo = $this->file . ($extraFileInfo ? self::fis . $extraFileInfo : '');
        $value = self::getKey($key, $array, $default, $fileInfo);

        /*if (!is_boolean($value)) TODO: boolean settings
            throw new SalicSettingsException("Key '$key' is not a boolean", $fileInfo);*/

        return $value === true || $value === 1; // 1 is also allowed
    }

    /**
     * Assert that $key exists in $array, and is a string.
     *
     * @param string $key The key to check for in the array
     * @param array $array The array what should be checked
     * @param string $default The default value, if the key doesn't exist. If this is null, an exception is thrown.
     * @param string $extraFileInfo Some extra info to add to the filename in case of an exception  (eg 'areas>XYZ')
     * @param string $pattern Optional regex to check proper format
     * @return string The value
     * @throws SalicSettingsException If empty and no $default is given, or the existing value is not a string
     */
    protected function getString($key, array $array, $default = null, $extraFileInfo = "", $pattern = null)
    {
        $fileInfo = $this->file . ($extraFileInfo ? self::fis . $extraFileInfo : '');
        $value = self::getKey($key, $array, $default, $fileInfo);

        if (!is_string($value))
            throw new SalicSettingsException("Key '$key' is not a string", $fileInfo);
        if ($pattern && preg_match($pattern, $value) !== 1)
            throw new SalicSettingsException("Invalid value for '$key'", $fileInfo);

        return $value;
    }

    /**
     * Assert that $key exists in $array, and is an integer.
     *
     * @param string $key The key to check for in the array
     * @param array $array The array what should be checked
     * @param string $default The default value, if the key doesn't exist. If this is null, an exception is thrown.
     * @param string $extraFileInfo Some extra info to add to the filename in case of an exception (eg 'areas>XYZ')
     * @return int The value
     * @throws SalicSettingsException If empty and no $default is given, or the existing value is not an integer
     */
    protected function getInt($key, array $array, $default = null, $extraFileInfo = "")
    {
        $fileInfo = $this->file . ($extraFileInfo ? self::fis . $extraFileInfo : '');
        $value = self::getKey($key, $array, $default, $fileInfo);

        if (!is_numeric($value))
            throw new SalicSettingsException("Key '$key' is not an integer", $fileInfo);

        return intval($value);
    }

    /**
     * @param $key - the key to check for in the array
     * @param array $array - the array what should be checked
     * @param string $default The default value, if the key doesn't exist. If this is null, an exception is thrown.
     * @param string $extraFileInfo Some extra info to add to the filename in case of an exception  (eg 'areas>XYZ')
     * @return array The value
     * @throws SalicSettingsException - if assert fails
     */
    protected function getList($key, $array, $default = null, $extraFileInfo = "")
    {
        $fileInfo = $this->file . ($extraFileInfo ? self::fis . $extraFileInfo : '');
        $value = self::getKey($key, $array, $default, $fileInfo);

        if (!is_array($value))
            throw new SalicSettingsException("Key '$key' is not an array", $fileInfo);

        return array_values($value); // just convert to list, if it isn't already
    }


    /**
     * @param $key - the key to check for in the array
     * @param array $array - the array what should be checked
     * @param string $default The default value, if the key doesn't exist. If this is null, an exception is thrown.
     * @param string $extraFileInfo Some extra info to add to the filename in case of an exception  (eg 'areas>XYZ')
     * @return array The value
     * @throws SalicSettingsException - if assert fails
     */
    protected function getDict($key, $array, $default = null, $extraFileInfo = "")
    {
        $fileInfo = $this->file . ($extraFileInfo ? self::fis . $extraFileInfo : '');
        $value = self::getKey($key, $array, $default, $fileInfo);

        if (!is_array($value))
            throw new SalicSettingsException("Key '$key' is not an array", $fileInfo);
        if (sizeof($value) > 0 && array_values($value) === $value) // empty arrays can't be associative, right? :P
            throw new SalicSettingsException("Array '$key' is not associative", $fileInfo, $value);

        return $value;
    }

    /**
     * Get a (possibly) translated string
     *
     * @param string $key The key to check for in the array
     * @param array $array The array what should be checked
     * @param string $default The default value, if the key doesn't exist. If this is null, an exception is thrown.
     * @param string $extraFileInfo Some extra info to add to the filename in case of an exception  (eg 'areas>XYZ')
     * @param string $pattern Optional regex to check proper format
     * @return TranslatedString The value
     * @throws SalicSettingsException If empty and no $default is given, or the existing value is not a string
     */
    protected function getTranslatedString($key, array $array, $default = null, $extraFileInfo = "", $pattern = null)
    {
        $fileInfo = $this->file . ($extraFileInfo ? self::fis . $extraFileInfo : '');
        $value = self::getKey($key, $array, $default, $fileInfo);

        if (is_string($value)) {
            if ($pattern && preg_match($pattern, $value) !== 1)
                throw new SalicSettingsException("Invalid value for '$key'", $fileInfo);
        } else if (is_array($value)) {
            // check if array is associative
            if (array_values($array) === $array)
                throw new SalicSettingsException("Array '$key' is not associative", $fileInfo);

            if ($pattern) { // check pattern
                foreach ($value as $lang => $val) {
                    if (preg_match($pattern, $val) !== 1)
                        throw new SalicSettingsException("Invalid value for '$key'", $fileInfo . '>' . $lang);
                }
            }
        } else
            throw new SalicSettingsException("Key '$key' is neither string nor array", $fileInfo);

        return new TranslatedString($value);
    }


    /**
     * Parse the given json file to array
     * + remove comments
     * + If if doesn't exist, try to create it
     *
     * @return array - parsed array
     * @throws SalicSettingsException - if parsing or creating fails
     */
    protected function parseOrCreate()
    {
        $filepath = self::baseDir . $this->file;

        if (!is_file($filepath))
            self::saveJsonFile($filepath, $this->getDefault());

        // Why still read it after that?
        // because there could be situations, where writing works, but reading doesn't
        // -> it would work one time, when the file does not exist, but not when it does.
        //    (stability > efficiency - at least during the setup process)
        //     ... and that creation of the default file doesn't happen in production, hopefully

        $raw = file_get_contents($filepath);
        if ($raw === false)
            throw new SalicSettingsException("Unable to read file", $filepath);

        // remove comments (source: https://secure.php.net/manual/en/function.json-decode.php#111551)
        $raw = preg_replace("#(/\*([^*]|[\r\n]|(\*+([^*/]|[\r\n])))*\*+/)|([\s\t](//).*)#", '', $raw);

        $json = json_decode($raw, true);
        if (!is_array($json) || json_last_error() !== JSON_ERROR_NONE)
            throw new SalicSettingsException("Unable to parse: " . json_last_error_msg(), $filepath);
        return $json;
    }

    /**
     * Saves (or creates) that json file
     *
     * @param $file
     * @param $data
     */
    protected static function saveJsonFile($file, $data)
    {
        $raw = json_encode($data, JSON_PRETTY_PRINT);
        file_put_contents($file, $raw);
    }

    protected static function getKey($key, $array, $default, $fileInfo)
    {
        if ($key == null) // if key is null, return the array itself
            return $array;

        if (!array_key_exists($key, $array)) {
            if ($default !== null)
                return $default;
            else
                throw new SalicSettingsException("Key '$key' not specified", $fileInfo, $array);
        } else
            return $array[$key];
    }
}

?>