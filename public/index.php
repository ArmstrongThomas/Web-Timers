<?php
require_once __DIR__ . '/../vendor/autoload.php';
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

$url = $_GET['url'] ?? 'home';

// Scan the pages directory for PHP files
$pagesDir = __DIR__ . '/../pages';
$pages = array_diff(scandir($pagesDir), array('..', '.'));

// Create an associative array of page names and their corresponding file paths
$pageFiles = [];
foreach ($pages as $page) {
    if (pathinfo($page, PATHINFO_EXTENSION) === 'php') {
        $pageName = pathinfo($page, PATHINFO_FILENAME);
        $pageFiles[$pageName] = $pagesDir . '/' . $page;
    }
}

// Check if the requested URL matches any page name
if (array_key_exists($url, $pageFiles)) {
    require_once $pageFiles[$url];
} else {
    echo "Page not found";
}
?>