<?php

namespace salic;

require(__DIR__ . '/../vendor/autoload.php');
require_once('Exceptions.php');
require_once('Utils.php');
require_once('SalicMng.php');


/**
 * SaLiC = Sassy Little CMS
 */
class Salic
{
    public $pages, $templates;
    protected $twig;

    protected $defaultTemplate = 'default';
    protected $template404 = '404.html.twig';
    protected $errorTemplate = '@salic/error.html.twig';
    protected $baseUrl = '/';
    protected $dataFileExtension = '.html';

    /**
     * Salic constructor.
     */
    public function __construct()
    {
        $this->loadPages();
    }

    public function loadTemplates()
    {
        if (!isset($this->templates)) // only if not already loaded
            $this->templates = json_decode(file_get_contents('site/templates.json'), true);
    }

    public function loadPages()
    {
        $this->pages = json_decode(file_get_contents('site/pages.json'), true);
        Utils::normalizePageArray($this->pages, $this->baseUrl, $this->defaultTemplate); // generates the href values
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
            if (!array_key_exists($pagekey, $this->pages)) { // when querying an invalid page, go back to home TODO: 404 page
                $this->render404();
                return;
            }

            // load template and field data
            $this->loadTemplates();
            $page = $this->pages[$pagekey];
            $template_key = $page['template'];
            if (!array_key_exists($template_key, $this->templates)) {
                throw new SalicException("Template '$template_key' not found in templates.json");
            }
            $template = $this->templates[$template_key];
            $fields = array();
            foreach ($template['fields'] as $field) {
                $data = $this->loadField($field, $pagekey); // loads the data for the field
                $fields[$field] = $data;
            }

            $this->doRenderPage($template['file'], array(
                'nav_pages' => Utils::removeHiddenPages($this->pages),
                'title' => 'SALiC Test page', //TODO: adapt page titles
                'pagekey' => $pagekey,
                'pagename' => $page['name'],
                'fields' => $fields
            ));
        } catch (\Exception $e) {
            $this->renderError($e);
        }
    }

    public function loadField($field, $pagekey)
    {
        if (!is_dir("site/data/$pagekey")) {
            //throw new SalicException("No data for page '$pagekey'");
            return null;
        }

        $file = "site/data/$pagekey/$field" . $this->dataFileExtension;
        if (!is_file($file)) {
            //throw new SalicException("No data for field '$field' on page '$pagekey'"); TODO: notify webmaster on missing variable
            return null;
        }
        return file_get_contents($file);
    }

    private function render404()
    {
        http_response_code(404);

        $content = $this->loadField('main', '404');
        if($content == null)
            $content = "<p>The page you are looking for couldn't be found.<br><a href='/'>Go back to the homepage</a></p>"; // default

        $this->doRenderPage($this->template404, array(
            'nav_pages' => Utils::removeHiddenPages($this->pages),
            'title' => 'Page not found',
            'pagename' => 'Page not found', //TODO: handle this when saving from editor
            'pagekey' => '404',
            'headline' => "Error 404 - Page not Found",
            'main' => $content,
        ));
    }

    private function renderError(\Exception $e)
    {
        http_response_code(500);
        $this->doRenderPage($this->errorTemplate, array(
            'exception' => $e
        ));
    }

    protected function doRenderPage($templatefile, $vars)
    {
        echo $this->twig->render($templatefile, $vars);
    }
}