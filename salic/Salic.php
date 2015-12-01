<?php

namespace salic;

require(__DIR__ . '/../vendor/autoload.php');
require_once('Exceptions.php');
require_once('Utils.php');


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
        Utils::generatePageHrefs($this->pages, $this->baseUrl); // generates the href values
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

class SalicMng extends Salic
{
    /**
     * SalicMng constructor.
     */
    public function __construct()
    {
        $this->baseUrl = "/edit/";
        parent::__construct();
    }

    protected function doRenderPage($templatefile, $vars)
    {
        $vars['parent_template'] = $templatefile;
        parent::doRenderPage('@salic/edit.html.twig', $vars);
    }

    public function savePage($pagekey) {
        if (!array_key_exists('regions', $_POST)) {
            Utils::returnHttpError(400, "Error: missing regions in POST data");
        }
        $regions = $_POST['regions'];

        if (!array_key_exists($pagekey, $this->pages)) {
            //TODO: error handling
            Utils::returnHttpError(400, "Error: Unknown pagekey '$pagekey'");
        }

        $this->doSavePage($pagekey, $regions);
    }

    public function doSavePage($pagekey, array $regions)
    {
        foreach ($regions as $key => $val) {
            if (!is_dir("data/$pagekey/")) {
                if (!mkdir("data/$pagekey/", 0750, true)) { // rwxr-x---, TODO: configurable directory permissions
                    throw new \Exception("Failed to create directory 'data/$pagekey/'");
                }
            }

            $flag = file_put_contents("data/$pagekey/$key.txt", $val, LOCK_EX); // lock the file exclusively while writing
            if ($flag === false) {
                throw new \Exception("Failed to write file 'data/$pagekey/$key'");
            }
            //TODO: set file permissions
        }
    }

}