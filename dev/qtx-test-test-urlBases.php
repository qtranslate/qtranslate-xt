<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * @param array $urls
 * @param array $exampleAdmin
 * @param array $exampleAdminSolves
 *
 * @return void
 */
/**
 * @param array $urls
 * @param array $example
 * @param array $exampleSolves
 *
 * @return void
 */
function qtranxf_check_url_replacement(array $urls, array $example, array $exampleSolves)
{
    for ($i = 0; $i < count($example); $i++) {
        $replace = str_replace([$urls[$i], '/', '.php'], '', $example[$i]);
        if (strlen($replace) == 0 || $replace != $exampleSolves[$i]) {
            qtranxf_tst_log(__FUNCTION__ . ': exit on url: ' . $example[$i] . '. Returned: "' . $replace
                . '" but it should be "' . $exampleSolves[$i]. '"');
            exit();
        }
    }
}

function qtranxf_run_test_urlBases() {
    $urls = [
        'https://localhost/wordpress/',
        'https://www.localhost.de/',
        'https://www.kl12354.com/234kdsfgk4534o5/wordpress/',
        'https://www.dog.io/',
        'https://127.0.0.1/',
        'https://www.wordpress.com/'
    ];

    $exampleAdmin = [
        'https://localhost/wordpress/admin-page/',
        'https://www.localhost.de/testo_ad#1min/',
        'https://www.kl12354.com/234kdsfgk4534o5/wordpress/testo_admin/',
        'https://www.dog.io/testo-cutsom_url/',
        'https://127.0.0.1/wp-admin/',          //default
        'https://www.wordpress.com/wp-admin/'   //default
    ];

    $exampleAdminSolves = [
        'admin-page',
        'testo_ad#1min',
        'testo_admin',
        'testo-cutsom_url',
        'wp-admin',
        'wp-admin'
    ];

    $exampleLogin = [
        'https://localhost/wordpress/secret-login/',
        'https://www.localhost.de/testo_login/',
        'https://www.kl12354.com/234kdsfgk4534o5/wordpress/testo_login/',
        'https://www.dog.io/testo-cutsom_url/',
        'https://127.0.0.1/wp-login.php',           //default
        'https://www.wordpress.com/wp-login.php'    //default
    ];

    $exampleLoginSolves = [
        'secret-login',
        'testo_login',
        'testo_login',
        'testo-cutsom_url',
        'wp-login',
        'wp-login'
    ];

    qtranxf_check_url_replacement($urls, $exampleAdmin, $exampleAdminSolves);

    qtranxf_check_url_replacement($urls, $exampleLogin, $exampleLoginSolves);
}

qtranxf_run_test_urlBases();