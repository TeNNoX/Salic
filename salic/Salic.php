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

    /**
     * Salic constructor.
     */
    public function __construct()
    {
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
            'home' => array('name' => "Home"),
            'page2' => array('name' => "Page 2"),
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

        echo $this->twig->render('index.html.twig', array(
            'pages' => $this->pages,
            'title' => 'SALiC Test page',
            'headline' => $this->pages[$pagekey]['name'],
            'content' => $this->contents[$pagekey],
        ));
    }

    private function render404()
    {
        echo $this->twig->render('index.html.twig', array(
            'pages' => $this->pages,
            'title' => 'Error 404',
            'headline' => "Error 404 - Page not Found",
            'content' => "Sorry, but the page you are looking for doesn't exist!<br><a href='index.php'>Go to Homepage</a>", //TODO: customizable 404
        ));
    }
}