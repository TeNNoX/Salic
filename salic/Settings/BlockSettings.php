<?php

namespace Salic\Settings;

use Salic\Exception\SalicSettingsException;
use Salic\Salic;


class BlockSettings extends Settings
{

    /**
     * @var array The blocktype list
     */
    public $blocktypes;

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
        $this->file = 'template/blocks.json';
        parent::__construct();
    }

    /**
     * @param string $name The blocktype
     * @return BlockType
     */
    public function data($name)
    {
        return $this->blocktypes[$name];
    }

    /**
     * @param string $name The block type
     * @return BlockType The blocktype
     * @throws SalicSettingsException
     */
    public static function data2($name) // static version
    {
        $self = self::get();
        if (!array_key_exists($name, $self->blocktypes))
            throw new SalicSettingsException("Undefined block type: " . $name, $self->file, $self->blocktypes);
        else
            return $self->blocktypes[$name];
    }

    public function exists($name)
    {
        return array_key_exists($name, $this->blocktypes);
    }

    public static function exists2($name) // static version
    {
        return array_key_exists($name, self::get()->blocktypes);
    }

    public function getDefault()
    {
        return array();
    }

    public function parseFromJson($json)
    {
        $blocks = self::getDict(null, $json, []);

        $this->blocktypes = [];
        foreach ($blocks as $type => $block) {
            $extraInfo = $type;  // fileInfo is e.g. 'blocks.json:text'

            $file = self::getString('file', $block, $type . Salic::templateExtension, $extraInfo);
            $editable = self::getBoolean('editable', $block, true, $extraInfo);
            $subblocks = self::getList('subblocks', $block, [], $extraInfo);
            $vars = self::getDict('vars', $block, [], $extraInfo);

            $this->blocktypes[$type] = new BlockType($file, $vars, $subblocks, $editable);
        }
    }

    public function validate()
    {
        // TODO: check if blocktype file exists
        // TODO: sanitize var names (no underscore?, no dot, ...)
    }
}