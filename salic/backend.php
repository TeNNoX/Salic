<?php
namespace Salic;

use Salic\Settings\LangSettings;
use Salic\Settings\NavSettings;
use Salic\Settings\PageSettings;

require_once('Salic.php');

if (!Utils::validAuthentication()) {
    exit; // Utils should call exit(), but just to be sure...
}

// TODO: ? backend language support
$salic = new SalicMng(LangSettings::get()->default);
$salic->initTwig();

try {
    $section = @$_GET['section'];
    if (empty($section)) { // no section selected
        $salic->renderBackendPage('@salic/backend.html.twig');
        exit;
    }

    if ($section == 'nav') {
        $salic->renderBackendPage('@salic/backend_nav.html.twig', array(
            'navSettings' => NavSettings::get(),
            'available_pages' => PageSettings::listAvailablePages(),
        ));
        exit;
    } else if ($section == 'pages') {
        if (empty($_GET['page'])) {
            $salic->renderBackendPage('@salic/backend_pages.html.twig', array(
            ));
        }
    } else {
        throw new \Exception("Invalid section: '$section'");
    }
} catch (\Exception $e) {
    Utils::dieWithError($e, 'Rendering backend', $salic);
    exit;
}
?>