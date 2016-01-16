<?php

namespace salic;

require(__DIR__ . '/../vendor/autoload.php');
require_once('Exceptions.php');
require_once('Utils.php');
require_once('SalicMng.php');

/**
 * SaLiC = Sassy Little CMS
 *  by TeNNoX
 */
class Salic
{
    const defaultTemplate = 'default';
    const errorTemplate = '@salic/error.html.twig';
    const dataFileExtension = '.html';

    protected $baseUrl; // baseUrls are not constants, because correctly overriding them is a bit... 'straightsideways' :P
    protected $baseUrlInternational;
    protected $twig;
    protected $current_lang;

    /**
     * Salic constructor.
     * @param $lang string the language for this request
     */
    public function __construct($lang)
    {
        $this->current_lang = $lang;
        $this->baseUrlInternational = '/';
        $this->baseUrl = $this->baseUrlInternational . "$lang/";
    }

    /**
     * Loads the page-specific settings
     *
     * @param string $pagekey - the pagekey
     * @return array - the settings array
     * @throws SalicSettingsException - if it fails
     */
    public function getPageSettings($pagekey)
    {
        // those other params are for generation of href values
        return Settings::getPageSettings($pagekey);
    }

    public function getTemplate($name, $pageinfo = "") // pageinfo will be added to exception message
    {
        $templates = Settings::getTemplateSettings();
        if (!array_key_exists($name, $templates)) {
            $pageinfo = " (page=$pageinfo)";
            throw new SalicException("Template '$name' not found in templates.json" . $pageinfo);
        }
        return $templates[$name];
    }

    public function initTwig()
    {
        $loader = new \Twig_Loader_Filesystem('site/template');    // look into main templates first
        $loader->addPath(__DIR__ . '/template', 'salic');

        $this->twig = new \Twig_Environment($loader, array(
            /*'cache' => __DIR__ . '/compilation_cache', */ //TODO: enable twig caching
            'auto_reload' => true,
            'strict_variables' => true,
            'autoescape' => false,
        ));
    }

    public function renderPage($pagekey)
    {
        try {
            $pages = Settings::getGeneralPageSettings()['available'];
            if (!in_array($pagekey, $pages)) { // when querying an invalid page, go to 404
                $this->render404();
                return;
            }

            // load template and field data
            $pageSettings = $this->getPageSettings($pagekey);

            $template = $this->getTemplate($pageSettings['template'], $pagekey);

            $data = $this->loadData($pagekey, $template);

            $data['pagetitle'] = $pageSettings['title'];

            $this->doRenderPage($template['file'], $data);
        } catch (\Exception $e) {
            $this->renderError($e);
        }
    }

    private function render404()
    {
        try {
            http_response_code(404);

            $data = $this->loadData('404', self::defaultTemplate);
            $this->doRenderPage(self::defaultTemplate, $data);
        } catch (\Exception $e) {
            $this->renderError($e);
        }
    }

    public function loadData($pagekey, $template)
    {
        $fields = array();
        foreach ($template['fields'] as $field) {
            $data = $this->loadField($field, $pagekey); // loads the data for the field
            $fields[$field] = $data;
        }

        $areas = array();
        foreach ($template['areas'] as $area) {
            $data = $this->loadArea($area, $pagekey); // loads the data for the area
            $areas[$area] = $data;
        }

        $data = array(
            'pagekey' => $pagekey,
            'pagetitle' => $pagekey, // can be changed by the calling function later
            'fields' => $fields,
            'areas' => $areas,
        );
        return $data;
    }

    /**
     * Load the content for $field, or throws an exception
     * if it fails AND $default is not set.
     *
     * @param string $field - the field name
     * @param string $pagekey
     * @param string $default - [optional] instead of throwing exception, return this
     * @return string - the html content
     * @throws SalicException - if it fails AND $default is not set
     */
    public function loadField($field, $pagekey, $default = null)
    {
        if (!is_dir("site/data/$pagekey")) {
            if ($default != null)
                return $default;
            else // TODO: notify webmaster of missing data
                //throw new SalicException("No data for page '$pagekey'");
                return "";
        }

        $file = "site/data/$pagekey/$field" . "_" . $this->current_lang . self::dataFileExtension;
        if (!is_file($file)) {
            if ($default != null)
                return $default;
            else
                //throw new SalicException("No data for field '$field' on page '$pagekey'");
                return "";
        }
        return file_get_contents($file);
    }

    /**
     * Load the content for $area
     *
     * @param string $area - the area name
     * @param string $pagekey
     * @return string - the html content
     * @throws SalicException - if it fails
     */
    private function loadArea($area, $pagekey)
    {
        if (!is_dir("site/data/$pagekey")) {
            throw new SalicException("No data for page '$pagekey'");
        }
        return "";
    }

    private function renderError(\Exception $e)
    {
        http_response_code(500);
        echo $this->twig->render(self::errorTemplate, array(
            'exception' => $e
        ));
    }

    protected function doRenderPage($templatefile, $vars)
    {
        $vars['baseurl'] = $this->baseUrl;
        $vars['baseurl_international'] = $this->baseUrlInternational;
        $vars['nav_pages'] = Utils::getNavPageList(Settings::getGeneralPageSettings(), $this->baseUrl);
        $vars['language'] = $this->current_lang;
        $vars['languages'] = Settings::getLangSettings()['available'];
        $vars['default_page'] = Settings::getGeneralPageSettings()['default'];
        echo $this->twig->render($templatefile, $vars);
    }
}