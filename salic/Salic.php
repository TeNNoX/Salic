<?php

namespace Salic;

use Salic\Exception\SalicException;
use Salic\Exception\SalicSettingsException;
use Salic\Settings\BlockSettings;
use Salic\Settings\GeneralSettings;
use Salic\Settings\PageSettings;
use Salic\Settings\Template;
use Salic\Settings\TemplateSettings;
use Twig_Environment;
use Twig_Loader_Filesystem;

require(__DIR__ . '/../vendor/autoload.php');

/**
 * SaLiC = Sassy Little CMS
 *  by TeNNoX
 */
class Salic
{
    const errorTemplate = '@salic/error.html.twig';
    const dataFileExtension = '.html';
    const templateExtension = '.html.twig';
    const defaultTemplateName = 'default';

    protected $baseUrl; // baseUrls are not constants, because correctly overriding them is a bit... 'straightsideways' :P
    protected $baseUrlInternational;
    /**
     * @var Twig_Environment
     */
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
            'cache' => __DIR__ . '/../cache/twig_compilation_cache',
            'auto_reload' => true, //TODO: disable some twig settings for performance
            'strict_variables' => true, // TODO: configurable strict_vars (for salic vars too)
            'autoescape' => false, //TODO: autoescape variables?
            'debug' => GeneralSettings::get()->debugMode,
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
            if (!PageSettings::pageExists($pagekey)) { // when querying an invalid page, go to 404
                $this->render404();
                return;
            }

            // load template and field data
            $pageSettings = Settings\PageSettings::get($pagekey);

            $template = $this->getTemplate($pageSettings->template, $pagekey);

            $data = $this->loadData($pagekey, $template);

            $data['pagetitle'] = $pageSettings->title->get($this->current_lang);

            $this->doRenderPage($template->filename, $data);
        } catch (\Exception $e) {
            Utils::dieWithError($e, 'page rendering', $this);
        }
    }

    public function render404()
    {
        try {
            http_response_code(404);

            $defaultTemplate = TemplateSettings::data2(self::defaultTemplateName);
            $data = $this->loadData('404', $defaultTemplate);
            $this->doRenderPage($defaultTemplate->filename, $data);
        } catch (\Exception $e) {
            Utils::dieWithError($e, '404 rendering', $this);
            exit;
        }
    }

    public function loadData($pagekey, Template $template)
    {
        $fields = array();
        foreach ($template->fields as $field) {
            $data = $this->loadField($field, $pagekey); // loads the data for the field
            $fields[$field] = $data;
        }

        $variables = array();
        foreach ($template->variables as $var => $defaultVal) {
            $data = $this->loadVar($var, $defaultVal, $pagekey); // loads the data for the field
            $variables[$var] = $data;
        }

        $areas = array();
        foreach ($template->areas as $area) {
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
     * @param $defaultVal
     * @param string $pagekey
     * @return mixed The value
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
            $pageDir = "site/data/$pagekey/";//$area" . "_" . $block['key'] . "_" . $this->current_lang . self::dataFileExtension;
            $salicName = $area . "_" . $block['key'];

            $rendered .= $this->loadBlock($pageDir, $block, $salicName);
        }
        return $rendered;
    }

    private function loadBlock($pageDir, $block, $salicName)
    {
        $blockType = BlockSettings::data2($block['type']);

        if (!$blockType->subblocks) {
            // default content: '<blockkey>'
            $file = $pageDir . $salicName . "_" . $this->current_lang . self::dataFileExtension;
            $content = is_file($file) ? file_get_contents($file) : '<p><i>&lt; ' . $block['key'] . ' &gt;</i></p>'; //TODO: ?block id as default content
        } else { // subblock = each file, eg. main_blockname_en/left.html.twig, gets loaded into $content['left']
            $dir = $pageDir . $salicName . '/';
            $content = array();

            if (is_dir($dir)) {
                if ($blockType->subblocks === true) { // -> variable subblocks
                    $subblocks = range(1, $block['subblock-count']); //TODO: backend solution or better solution
                } else { // predefined subblocks
                    $subblocks = $blockType->subblocks;
                }

                foreach ($subblocks as $subblock) {
                    $filename = $dir . $subblock . "_" . $this->current_lang . self::dataFileExtension;
                    $subcontent = is_file($filename) ? file_get_contents($filename) : '<p><i>&lt; ' . $block['key'] . '.' . $subblock . ' &gt;</i></p>';
                    $content[$subblock] = $subcontent;
                }

            } else if ($blockType->subblocks === true) { // -> variable subblocks, initialize empty elements
                foreach (range(1, $block['subblock-count']) as $subblock) {
                    $subcontent = '<p><i>&lt; ' . $block['key'] . '.' . $subblock . ' &gt;</i></p>';
                    $content[$subblock] = $subcontent;
                }
            }
        }

        $vars = $blockType->vars;
        foreach ($block['vars'] as $var => $val) {
            $vars[$var] = $val;
        }

        $data = array(
            'baseurl' => $this->baseUrl,
            'baseurl_international' => $this->baseUrlInternational,
            'debug_mode' => GeneralSettings::get()->debugMode,
            'content' => $content,
            'vars' => $vars,
        );
        if ($blockType->editable)
            $data['salic_name'] = $salicName; // only add salic name if editable

        return $this->twig->render('blocks/' . $block['type'] . self::templateExtension, $data);
    }

    /**
     * Render the error page, and exit (not return, exit)
     *
     * @param \Exception $e - the exception that occured
     * @param $during - exception happened during XY
     */
    function renderError(\Exception $e, $during)
    {
        http_response_code(500);
        echo $this->twig->render(self::errorTemplate, array(
            'during' => $during,
            'exception' => $e,
        ));
        exit;
    }

    protected function doRenderPage($templatefile, $vars)
    {
        $vars['debug_mode'] = GeneralSettings::get()->debugMode;
        $vars['baseurl'] = $this->baseUrl;
        $vars['baseurl_international'] = $this->baseUrlInternational;
        $vars['nav_pages'] = Utils::getNavPageList($this->baseUrl, $this->current_lang);
        $vars['language'] = $this->current_lang;
        $vars['languages'] = Settings\LangSettings::get()->available;
        $vars['default_page'] = Settings\NavSettings::get()->homepage;
        echo $this->twig->render($templatefile, $vars);
    }
}