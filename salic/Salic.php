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
    public $templates;
    protected $twig;

    protected $defaultTemplate = 'default';
    protected $template404 = '404.html.twig';
    protected $errorTemplate = '@salic/error.html.twig';
    protected $baseUrl;
    protected $baseUrlInternational = '/';
    protected $dataFileExtension = '.html';

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

    public function loadTemplates()
    {
        if (!isset($this->templates)) // only if not already loaded
            $this->templates = json_decode(file_get_contents('site/templates.json'), true);
    }

    public function getPageSettings()
    {
        return Settings::getPageSettings($this->baseUrl, $this->defaultTemplate); // for generation of href values
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
            $pages = $this->getPageSettings()['available'];
            if (!array_key_exists($pagekey, $pages)) { // when querying an invalid page, go back to home TODO: 404 page
                $this->render404();
                return;
            }

            // load template and field data
            $this->loadTemplates();
            $page = $pages[$pagekey];
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
                'pagekey' => $pagekey,
                'pagename' => $page['name'],
                'fields' => $fields
            ));
        } catch (\Exception $e) {
            $this->renderError($e);
        }
    }

    private function render404()
    {
        try {
            http_response_code(404);

            $fields = array();
            $fields['bannertext'] = $this->loadField('bannertext', '404', "<h1>Page not found</h1>"); // default

            $fields['main'] = $this->loadField('main', '404',
                "<p>The page you are looking for couldn't be found.<br><a href='/'>Go back to the homepage</a></p>");

            $this->doRenderPage($this->template404, array(
                'pagekey' => '404',
                'pagename' => '404',
                'fields' => $fields
            ));
        } catch (\Exception $e) {
            $this->renderError($e);
        }
    }

    public function loadField($field, $pagekey, $default = null)
    {
        if (!is_dir("site/data/$pagekey")) {
            //throw new SalicException("No data for page '$pagekey'");
            return $default;
        }

        $file = "site/data/$pagekey/$field" . "_" . $this->current_lang . $this->dataFileExtension;
        if (!is_file($file)) {
            //throw new SalicException("No data for field '$field' on page '$pagekey'"); TODO: notify webmaster on missing variable
            return $default;
        }
        return file_get_contents($file);
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
        $vars['baseurl'] = $this->baseUrl;
        $vars['baseurl_international'] = $this->baseUrlInternational;
        $vars['nav_pages'] = Utils::removeHiddenPages($this->getPageSettings()['available']);
        $vars['languages'] = Settings::getLangSettings()['available'];
        $vars['default_page'] = $this->getPageSettings()['default'];
        echo $this->twig->render($templatefile, $vars);
    }
}