<?php

namespace Salic\Settings;

use Salic\Exception\SalicSettingsException;
use Salic\Salic;


class BlockSettings extends Settings
{

    public $blocks;

    /**
     * @var self A cached instance of this, if available
     */
    protected static $cached;

    /**
     * @return self A cached or fresh instance of this
     */
    public static function get()
    {
        if (self::$cached)
            return self::$cached;
        return new self();
    }

    public function __construct()
    {
        $this->file = 'template/blocks/blocks.json';
        parent::__construct();
    }

    /**
     * @param string $name The block type
     * @return array
     */
    public function data($name)
    {
        return $this->blocks[$name];
    }

    /**
     * @param string $name The block type
     * @return array The block settings
     * @throws SalicSettingsException
     */
    public static function data2($name) // static version
    {
        $self = self::get();
        if (!array_key_exists($name, $self->blocks))
            throw new SalicSettingsException("Undefined block type: " . $name, $self->file, $self->blocks);
        else
            return $self->blocks[$name];
    }

    public function exists($name)
    {
        return array_key_exists($name, $this->blocks);
    }

    public static function exists2($name) // static version
    {
        return array_key_exists($name, self::get()->blocks);
    }

    public function getDefault()
    {
        return array();
    }

    public function parseFromJson($json)
    {
        $this->blocks = self::getDict(null, $json, []);
    }

    public function validate()
    {
        foreach ($this->blocks as $type => &$block) {
            $extraInfo = $type;  // fileInfo is e.g. 'blocks.json:text'

            $block['file'] = self::getString('file', $block, $type . Salic::templateExtension, $extraInfo);
            // TODO: check if blocktype file exists

            $block['editable'] = self::getBoolean('editable', $block, true, $extraInfo);

            //TODO: parse blocks to Block objects
            //TODO: sanitize var names (no underscore?, no dot, ...)
            $block['vars'] = self::getDict('vars', $block, [], $extraInfo);
        }
    }
}