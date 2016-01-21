<?php

namespace Salic;

use Salic\Exception\SalicException;
use Salic\Exception\SalicSettingsException;
use Salic\Settings\PageSettings;
use Twig_Environment;
use Twig_Loader_Filesystem;

$loader = require(__DIR__ . '/../vendor/autoload.php');

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

    public function getTemplate($name, $pageinfo = "") // pageinfo will be added to exception message
    {
        $templateSettings = Settings\TemplateSettings::get();
        if (!$templateSettings->exists($name)) {
            if (!empty($pageinfo)) // format it, or leave it empty
                $pageinfo = " (page=$pageinfo)";
            throw new SalicException("Template '$name' not found in templates.json" . $pageinfo);
        }
        return $templateSettings->data($name);
    }

    public function initTwig()
    {
        $loader = new Twig_Loader_Filesystem('site/template');    // look into main templates first
        $loader->addPath(__DIR__ . '/template', 'salic');

        $this->twig = new Twig_Environment($loader, array(
            /*'cache' => __DIR__ . '/compilation_cache', */ //TODO: enable twig caching
            'auto_reload' => true,
            'strict_variables' => true,
            'autoescape' => false,
        ));

        $this->twig->addFilter(new \Twig_SimpleFilter('get_class', 'get_class'));
        $this->twig->addFilter(new \Twig_SimpleFilter('var_export', 'var_export'));
        $this->twig->addTest(new \Twig_SimpleTest('SettingsException', function ($value) {
            return $value instanceof SalicSettingsException;
        }));
    }

    public function renderPage($pagekey)
    {
        try {
            if (!Utils::pageExists($pagekey)) { // when querying an invalid page, go to 404
                $this->render404();
                return;
            }

            // load template and field data
            $pageSettings = Settings\PageSettings::get($pagekey);

            $template = $this->getTemplate($pageSettings->template, $pagekey);

            $data = $this->loadData($pagekey, $template);

            $data['pagetitle'] = $pageSettings->title->get($this->current_lang);

            $this->doRenderPage($template['file'], $data);
        } catch (\Exception $e) {
            $this->renderError($e, "rendering page");
        }
    }

    private function render404()
    {
        try {
            http_response_code(404);

            $data = $this->loadData('404', self::defaultTemplate);
            $this->doRenderPage(self::defaultTemplate, $data);
        } catch (\Exception $e) {
            $this->renderError($e, "rendering 404");
        }
    }

    public function loadData($pagekey, $template)
    {
        $fields = array();
        foreach ($template['fields'] as $field) {
            $data = $this->loadField($field, $pagekey); // loads the data for the field
            $fields[$field] = $data;
        }

        $variables = array();
        foreach ($template['variables'] as $var => $defaultVal) {
            $data = $this->loadVar($var, $defaultVal, $pagekey); // loads the data for the field
            $variables[$var] = $data;
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
            'variables' => $variables,
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
     * @return string The html content
     * @throws SalicException If it fails
     */
    public function loadField($field, $pagekey)
    {
        if (!is_dir("site/data/$pagekey")) {
            // TODO: notify webmaster of missing data
            //throw new SalicException("No data for page '$pagekey'");
            return "";
        }

        // field files start with '_' (to make it clear, that they don't belong to an area)
        $file = "site/data/$pagekey/_$field" . "_" . $this->current_lang . self::dataFileExtension;
        if (!is_file($file)) {
            //throw new SalicException("No data for field '$field' on page '$pagekey'");
            return "";
        }
        return file_get_contents($file);
    }

    /**
     * Load the value for $var, or throws an exception
     * if it fails AND $default is not set.
     *
     * @param string $var
     * @param string $pagekey
     * @return mixed The value
     * @throws SalicException If it fails
     */
    public function loadVar($var, $defaultVal, $pagekey)
    {
        $pageVars = PageSettings::get($pagekey)->variables;
        if (array_key_exists($var, $pageVars))
            return $pageVars[$var];
        else
            return $defaultVal;
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

        $blocks = Settings\PageSettings::get($pagekey)->areas[$area];
        $rendered = "";

        // fetch all blocks for this area
        foreach ($blocks as $block) {
            $file = "site/data/$pagekey/$area" . "_" . $block['key'] . "_" . $this->current_lang . self::dataFileExtension;
            // default to empty content
            $content = is_file($file) ? file_get_contents($file) : '';

            $salicName = $area . "_" . $block['key'];
            $rendered .= $this->twig->render('blocks/' . $block['type'] . '.html.twig', array(
                'salic_name' => $salicName,
                'content' => $content,
            ));
        }
        return $rendered;
    }

    /**
     * Render the error page, and exit (not return, exit)
     *
     * @param \Exception $e - the exception that occured
     * @param $while - exception happened while XY
     */
    function renderError(\Exception $e, $while)
    {
        http_response_code(500);
        echo $this->twig->render(self::errorTemplate, array(
            'while' => $while,
            'exception' => $e,
        ));
        exit;
    }

    protected function doRenderPage($templatefile, $vars)
    {
        $vars['baseurl'] = $this->baseUrl;
        $vars['baseurl_international'] = $this->baseUrlInternational;
        $vars['nav_pages'] = Utils::getNavPageList($this->baseUrl, $this->current_lang);
        $vars['language'] = $this->current_lang;
        $vars['languages'] = Settings\LangSettings::get()->available;
        $vars['default_page'] = Settings\NavSettings::get()->homepage;
        echo $this->twig->render($templatefile, $vars);
    }
}