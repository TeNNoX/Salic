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
    public $pages;
    protected $twig;

    protected $defaultTemplate = 'default.html.twig';
    protected $baseUrl = '/';

    /**
     * Salic constructor.
     */
    public function __construct()
    {
        $this->loadPages();
    }

    public function loadTemplates()
    {
        $this->templates = json_decode(file_get_contents('data/templates.json'), true);
    }

    public function loadPages()
    {
        $this->pages = json_decode(file_get_contents('data/pages.json'), true);
        Utils::normalizePageArray($this->pages, $this->baseUrl, $this->defaultTemplate); // generates the href values
    }

    public function initTwig()
    {
        $loader = new \Twig_Loader_Filesystem(__DIR__ . '/../templates');    // look into main templates first
        $loader->addPath(__DIR__ . '/templates', 'salic');

        $this->twig = new \Twig_Environment($loader, array(
            /*'cache' => __DIR__ . '/compilation_cache', */ //TODO: enable twig caching
            'auto_reload' => true,
            'strict_variables' => true,
            'autoescape' => false,
        ));
    }

    /*public function savePages() {
        file_put_contents('pages.json', json_encode($this->pages, JSON_PRETTY_PRINT)); //TODO: disable prettyprint ?
    }*/

    public function renderPage($pagekey)
    {
        if (!array_key_exists($pagekey, $this->pages)) { // when querying an invalid page, go back to home TODO: 404 page
            $this->render404();
            return;
        }

        $page = $this->pages[$pagekey];
        $template = @$page['template'] ? $page['template'] . '.html.twig' : $this->defaultTemplate;
        $content = $this->loadContent($pagekey); // loads the content variables for the page

        $this->doRenderPage($template, array_merge(array(
            'pages' => $this->pages,
            'title' => 'SALiC Test page', //TODO: adapt page titles
            'pagekey' => $pagekey,
            'pagename' => $page['name'],
        ), $content));
    }

    public function loadContent($pagekey)
    {
        if (!is_dir("data/$pagekey")) {
            throw new \Exception("No data for page '$pagekey'");
        }

        $data = array();
        // read all XXX.txt files in the page's directory to the array as data[XXX] = <content>
        if ($handle = opendir("data/$pagekey")) {
            /* This is the correct way to loop over the directory. (says phpdoc) */
            while (false !== ($entry = readdir($handle))) {
                $fileinfo = pathinfo($entry);

                if ($fileinfo['extension'] == "txt") { // if this is a .txt file
                    $val = file_get_contents("data/$pagekey/$entry");
                    $fieldname = $fileinfo['filename'];
                    $data[$fieldname] = $val;
                }
            }

            closedir($handle);
        } else {
            throw new \Exception("Failed to read directory 'data/$pagekey'");
        }

        return $data;
    }

    private function render404()
    {
        http_response_code(404);
        $this->doRenderPage($this->defaultTemplate, array(
            'pages' => $this->pages,
            'title' => 'Error 404',
            'pagename' => 'Error 404', //TODO: handle this when saving from editor
            'pagekey' => '404',
            'content' => "<h1>Error 404 - Page not Found</h1><p>Sorry, but the page you are looking for doesn't exist!</p><br><a href='index.php'>Go to Homepage</a>", //TODO: customizable 404
        ));
    }

    protected function doRenderPage($templatefile, $vars)
    {
        echo $this->twig->render($templatefile, $vars);
    }
}