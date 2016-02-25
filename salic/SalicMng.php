<?php

namespace Salic;

use Salic\Settings\BlockSettings;
use Salic\Settings\PageSettings;
use Salic\Settings\TemplateSettings;

class SalicMng extends Salic //TODO: implement backend
{
    const baseUrlInternational = "/edit/";

    private $mainEditTemplate = '@salic/backend.html.twig';

    /**
     * SalicMng constructor.
     * @param string $lang The language for this request
     */
    public function __construct($lang)
    {
        parent::__construct($lang);
        $this->baseUrlInternational = '/edit/';
        $this->baseUrl = $this->baseUrlInternational . "$lang/";
    }

    public function renderBackend()
    { //TODO: implement/fix backend
        echo "<h1>Imagine a backend over here.</h1>";
        /*$this->doRenderPage($this->mainEditTemplate, array(
            'pages' => Settings\NavSettings::get()->displayed,
        ));*/
    }

    protected function doRenderPage($templatefile, $vars)
    {
        $vars['parent_template'] = $templatefile;
        parent::doRenderPage('@salic/edit.html.twig', $vars);
    }

    public function savePage($pagekey)
    {
        $result = array(
            "success" => false,
        );

        try {
            if (!array_key_exists('regions', $_POST)) {
                Utils::returnHttpError(400, "Error: missing regions in POST data");
            }
            $regions = $_POST['regions'];

            if ($pagekey !== '404' && !Utils::pageExists($pagekey)) { //TODO: sanitize pagekey
                //TODO: error handling
                Utils::returnHttpError(400, "Error: Unknown pagekey '$pagekey'");
            }

            $this->doSavePage($pagekey, $regions);
            $result['success'] = true;
        } catch (\Exception $e) {
            $result['error'] = "PHPException - " . $e->getMessage();
        }

        header('Content-Type: application/json');
        echo json_encode($result);
    }

    public function doSavePage($pagekey, array $regions)
    {
        foreach ($regions as $key => $val) {
            $page_dir = "site/data/$pagekey/";
            if (!is_dir($page_dir)) {
                if (!mkdir($page_dir, 0750, true)) { // rwxr-x---, TODO: configurable directory permissions
                    throw new \Exception("Failed to create directory '$page_dir'");
                }
            }

            $pageSettings = PageSettings::get($pagekey);
            $blockSettings = BlockSettings::get();

            // check sanity of key (only letters, digits, -, _)
            if (preg_match('/^([a-z0-9-]*)_([a-z0-9-]+)(?:\.([a-z0-9-]+))?$/', $key, $matches) !== 1) { // matches 'main-bar_foo-bar' or 'main_baz.boo'
                throw new \Exception("Invalid key format: '$key'");
            }
            $areaKey = $matches[1];
            $blockKey = $matches[2];
            $subblock = @$matches[3]; // subblock is optional

            if (!array_key_exists($areaKey, $pageSettings->areas)) {
                throw new \Exception('Invalid area: ' . $areaKey);
            }
            if (($myBlock = PageSettings::getBlock($pageSettings->areas[$areaKey], $blockKey)) == null) {
                throw new \Exception('Invalid block: ' . $blockKey);
            }
            $blockType = $blockSettings->data($myBlock['type']);
            if ($subblock && !in_array($subblock, $blockType['subblocks'])) {
                throw new \Exception('Invalid subblock: ' . $subblock . "[type={$myBlock['type']}]");
            }
            if (!$blockType['editable']) {
                throw new \Exception('Block not editable: ' . $blockKey . "[type={$myBlock['type']}]");
            }

            // save as 'area_block/subblock_lang.ext'
            if (!$subblock) {
                $filename = $areaKey . '_' . $blockKey . '_' . $this->current_lang . self::dataFileExtension;
            } else {
                Utils::mkdirs($page_dir . $areaKey . '_' . $blockKey);
                $filename = $areaKey . '_' . $blockKey . '/' . $subblock . '_' . $this->current_lang . self::dataFileExtension;
            }
            $flag = file_put_contents($page_dir . $filename, $val, LOCK_EX); // lock the file exclusively while writing
            if ($flag === false) {
                throw new \Exception("Failed to write file '{$page_dir}{$filename}" . self::dataFileExtension . "'");
            }
            //TODO: set file permissions
        }
    }

}

?>