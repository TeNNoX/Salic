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
            'page3' => array('name' => "Page 3"),
        );
        Utils::generatePageHrefs($this->pages); // generates the href values

        $this->contents = array(
            'home' => "<p>This is some <i>Test Content</i> for the main page.</p>",
            'page2' => "<p>This is some <b>extra spicy</b> <i>Test Content</i> for the second page.</p>",
            'page3' => "<b>No template used for this one</b><br><h2>So... here's a list for you</h2><ul><li>Test 1</li><li>Test 2</li></ul><i>we don't have ice anymore, so you have to be content with that. :/</i>",
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
        $template = @$page['template'] ? $page['template'] . '.html.twig' : $this->baseTemplate;
        $this->doRenderPage($template, array(
            'pages' => $this->pages,
            'title' => 'SALiC Test page',
            'headline' => $page['name'],
            'content' => $this->contents[$pagekey],
        ));
    }

    private function render404()
    {
        $this->doRenderPage($this->baseTemplate, array(
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