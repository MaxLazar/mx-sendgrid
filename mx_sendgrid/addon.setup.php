<?php

$addonJson = json_decode(file_get_contents(__DIR__ . '/addon.json'));


if (!defined('MX_SENDGRID_NAME')) {
    define('MX_SENDGRID_NAME', $addonJson->name);
    define('MX_SENDGRID_VERSION', $addonJson->version);
    define('MX_SENDGRID_DOCS', '');
    define('MX_SENDGRID_DESCRIPTION', $addonJson->description);
    define('MX_SENDGRID_AUTHOR', 'Max Lazar');
    define('MX_SENDGRID_DEBUG', false);
}

//$config['MX_SENDGRID_tab_title'] = MX_SENDGRID_NAME;

return [
    'name'           => $addonJson->name,
    'description'    => $addonJson->description,
    'version'        => $addonJson->version,
    'namespace'      => $addonJson->namespace,
    'author'         => 'Max Lazar',
    'author_url'     => 'https://eecms.dev',
    'settings_exist' => true,
    // Advanced settings
    'services'       => [],
];
