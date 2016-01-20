<?php

namespace salic;

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
    {
        $this->doRenderPage($this->mainEditTemplate, array(
            'pages' => Settings::getNavSettings()['displayed'],
        ));
    }

    protected function doRenderPage($templatefile, $vars)
    {
        $vars['parent_template'] = $templatefile;
        parent::doRenderPage('@salic/edit.html.twig', $vars);
    }

    public function savePage($pagekey)
    {
        if (!array_key_exists('regions', $_POST)) {
            Utils::returnHttpError(400, "Error: missing regions in POST data");
        }
        $regions = $_POST['regions'];

        if ($pagekey !== '404' && !array_key_exists($pagekey, Settings::getNavSettings()['displayed'])) {
            //TODO: error handling
            Utils::returnHttpError(400, "Error: Unknown pagekey '$pagekey'");
        }

        $this->doSavePage($pagekey, $regions);
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

            $filename = $key . "_" . $this->current_lang . self::dataFileExtension; // save as 'pagekey_lang.ext'
            $flag = file_put_contents($page_dir . $filename, $val, LOCK_EX); // lock the file exclusively while writing
            if ($flag === false) {
                throw new \Exception("Failed to write file '$page_dir$key" . self::dataFileExtension . "'");
            }
            //TODO: set file permissions
        }
    }

}

?>