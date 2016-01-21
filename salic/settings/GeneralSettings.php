<?php

namespace Salic\Settings;

use Salic\Exception\SalicSettingsException;
use Salic\Exception\ShouldNotHappenException;
use Salic\Utils;


class GeneralSettings extends Settings
{

    public $passwordHash;

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
        $this->file = 'general.json';
        parent::__construct();
    }

    public function getDefault()
    {
        return ['password_hash' => '$2y$10$zIA615.W0w/mR5JgP7biCeSh3ORffzC1cHHSMflhKMOfQjS1Ukc6.'];
    }

    public function parseFromJson($json)
    {
        $this->passwordHash = self::getString('password_hash', $json, '$2y$10$zIA615.W0w/mR5JgP7biCeSh3ORffzC1cHHSMflhKMOfQjS1Ukc6.');
    }

    public function validate()
    {
    }
}