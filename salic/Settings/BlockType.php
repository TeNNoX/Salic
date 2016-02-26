<?php

namespace Salic\Settings;


class BlockType
{

    /**
     * @var string
     */
    public $file;
    /**
     * @var array
     */
    public $vars;
    /**
     * @var array
     */
    public $subblocks;
    /**
     * @var bool
     */
    public $editable;

    /**
     * BlockType constructor.
     * @param string $file
     * @param array $vars
     * @param array $subblocks
     * @param bool $editable
     */
    public function __construct($file, $vars = [], $subblocks = [], $editable = true)
    {
        $this->file = $file;
        $this->vars = $vars;
        $this->subblocks = $subblocks;
        $this->editable = $editable;
    }
}