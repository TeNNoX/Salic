<?php

namespace salic;

require(__DIR__ . '/../vendor/autoload.php');
require_once('Exceptions.php');
require_once('Utils.php');


/**
 * Simple And Light Cms
 */
class Salic
{
    public $pages, $contents;
    protected $twig;

    protected $baseTemplate;

    /**
     * Salic constructor.
     */
    public function __construct()
    {
        $this->baseTemplate = 'base.html.twig';
    }

    public function init()
    {
        $loader = new \Twig_Loader_Filesystem(__DIR__ . '/../templates');
        $this->twig = new \Twig_Environment($loader, array(
            /*'cache' => __DIR__ . '/compilation_cache', */ //TODO: enable caching
            'auto_reload' => true,
            'strict_variables' => true,
            'autoescape' => false,
        ));

        $this->loadPages();
    }

    private function loadPages()
    {
        $this->pages = array(
            'home' => array('name' => "Home", 'template' => "headline_with_text"),
            'page2' => array('name' => "Page 2", 'template' => "headline_with_text"),
        );
        Utils::generatePageHrefs($this->pages); // generates the href values

        $this->contents = array(
            'home' => "<p>This is some <i>Test Content</i> for the main page.</p>",
            'page2' => "<p>This is some <b>extra spicy</b> <i>Test Content</i> for the second page.</p>",
        );
    }

    public function renderPage($pagekey)
    {
        if (!array_key_exists($pagekey, $this->pages)) { // when querying an invalid page, go back to home TODO: 404 page
            $this->render404();
            return;
        }

        if (!array_key_exists($pagekey, $this->contents)) {
            throw new ShouldNotHappenException("No content defined for page '$pagekey'");
        }

        $page = $this->pages[$pagekey];
        $this->doRenderPage($page['template'] . '.html.twig', array(
            'pages' => $this->pages,
            'title' => 'SALiC Test page',
            'headline' => $page['name'],
            'content' => $this->contents[$pagekey],
        ));
    }

    private function render404()
    {
        echo $this->twig->render($this->baseTemplate, array(
            'pages' => $this->pages,
            'title' => 'Error 404',
            'content' => "<h1>Error 404 - Page not Found</h1>Sorry, but the page you are looking for doesn't exist!<br><a href='index.php'>Go to Homepage</a>", //TODO: customizable 404
        ));
    }

    protected function doRenderPage($templatefile, $vars)
    {
        echo $this->twig->render($templatefile, $vars);
    }
}

class SalicMng extends Salic
{

    protected function doRenderPage($templatefile, $vars)
    {
        $vars['parent_template'] = $templatefile;
        parent::doRenderPage('manage.html.twig', $vars);
    }

}