<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function qtranxf_check_url( $url_converted, $url_expected ) {
    return qtranxf_check_test( $url_converted, $url_expected, 'qtx-test-convertURL' );
}

function qtranxf_test_convertURL( $url_mode_name, $urls, $lang, $showLanguage ) {
    foreach ( $urls as $url => $url_expected ) {
        $url_converted = qtranxf_get_url_for_language( $url, $lang, $showLanguage );
        if ( ! qtranxf_check_url( $url_converted, $url_expected ) ) {
            qtranxf_tst_log( 'qtranxf_test_convertURL(' . $url_mode_name . '): exit on the first error for url: ', $url );
            exit();
        }
    }
}

function qtranxf_run_test_convertURL( $url_mode, $lang ) {
    global $q_config;

    $homeinfo = qtranxf_get_home_info();
    $p        = $homeinfo['path'];
    $h        = $homeinfo['scheme'] . '://' . $homeinfo['host'] . $p;
    $b        = trailingslashit( $p );

    // common tests
    $urls = array(
        '#'                                      => '#',
        '#tag'                                   => '#tag',
        'https://external.domain.com'             => 'https://external.domain.com',
        'https://external.domain.com/'            => 'https://external.domain.com/',
        'https://external.domain.com?tr=123#tag'  => 'https://external.domain.com?tr=123#tag',
        'https://external.domain.com/?tr=123#tag' => 'https://external.domain.com/?tr=123#tag',
        'https://external.domain.com?tr=123'      => 'https://external.domain.com?tr=123',
        'https://external.domain.com/?tr=123'     => 'https://external.domain.com/?tr=123',
        'https://external.domain.com#tag'         => 'https://external.domain.com#tag',
        'https://external.domain.com/#tag'        => 'https://external.domain.com/#tag',
    );
    qtranxf_test_convertURL( 'Common', $urls, $lang, true );
    qtranxf_test_convertURL( 'Common', $urls, $lang, false );

    $q_config['url_mode'] = $url_mode;
    switch ( $url_mode ) {

        case QTX_URL_QUERY:
            //qtranxf_tst_log('qtx-test-convertURL: $url_mode=QTX_URL_QUERY: $p=',$p);
            $urls = array(
                $b . '?lang=fr'                          => $h . '/?lang=' . $lang,
                $b . '#'                                 => $h . '/?lang=' . $lang,
                $b . '#tag'                              => $h . '/?lang=' . $lang . '#tag',
                $h . '#tag'                              => $h . '?lang=' . $lang . '#tag',
                $h . '?lang=fr#tag'                      => $h . '?lang=' . $lang . '#tag',
                $b . '?lang=fr&page_id=123#tag'          => $h . '/?page_id=123&lang=' . $lang . '#tag',
                $h . '?page_id=123&lang=fr'              => $h . '?page_id=123&lang=' . $lang . '',
                $h . '?page_id=123&lang=fr#tag'          => $h . '?page_id=123&lang=' . $lang . '#tag',
                $b . '?page_id=123&lang=fr&tab=tab3'     => $h . '/?page_id=123&tab=tab3&lang=' . $lang . '',
                $h . '?page_id=123&lang=fr&tab=tab3#tag' => $h . '?page_id=123&tab=tab3&lang=' . $lang . '#tag',
            );
            qtranxf_test_convertURL( 'QTX_URL_QUERY', $urls, $lang, true );
            $urls = array(
                $b . '?lang=fr'                          => $h . '/',
                $b . '#'                                 => $h . '/',
                $b . '#tag'                              => $h . '/#tag',
                $h . '#tag'                              => $h . '#tag',
                $h . '?lang=fr#tag'                      => $h . '#tag',
                $b . '?lang=fr&page_id=123#tag'          => $h . '/?page_id=123#tag',
                $h . '?page_id=123&lang=fr'              => $h . '?page_id=123',
                $h . '?page_id=123&lang=fr#tag'          => $h . '?page_id=123#tag',
                $b . '?page_id=123&lang=fr&tab=tab3'     => $h . '/?page_id=123&tab=tab3',
                $h . '?page_id=123&lang=fr&tab=tab3#tag' => $h . '?page_id=123&tab=tab3#tag',
            );
            qtranxf_test_convertURL( 'QTX_URL_QUERY', $urls, $lang, false );
            break;

        case QTX_URL_PATH:
            //qtranxf_tst_log('qtx-test-convertURL: $url_mode=QTX_URL_PATH');
            $hp   = $h . '/' . $lang;
            $urls = array(
                $b . 'fr'                                            => $hp . '',
                $h . '/fr'                                           => $hp . '',
                $b . 'fr/'                                           => $hp . '/',
                $h . '/fr/'                                          => $hp . '/',
                $b . '#'                                             => $hp . '/',
                $b . '#tag'                                          => $hp . '/#tag',
                $h . '#tag'                                          => $hp . '#tag',
                $b . 'fr/?lang=fr'                                   => $hp . '/',
                $h . '/fr?lang=fr#tag'                               => $hp . '#tag',
                $b . 'fr/?lang=fr&page_id=123#tag'                   => $hp . '/?page_id=123#tag',
                $h . '?page_id=123&lang=fr'                          => $hp . '?page_id=123',
                $h . '/fr?page_id=123&lang=fr#tag'                   => $hp . '?page_id=123#tag',
                $b . '?page_id=123&lang=fr&tab=tab3'                 => $hp . '/?page_id=123&tab=tab3',
                $h . '/fr/?page_id=123&lang=fr&lang=xx&tab=tab3#tag' => $hp . '/?page_id=123&tab=tab3#tag',
            );
            qtranxf_test_convertURL( 'QTX_URL_PATH', $urls, $lang, true );
            $urls = array(
                $b . 'fr'                                    => $h . '',
                $h . '/fr'                                   => $h . '',
                $b . 'fr/'                                   => $h . '/',
                $h . '/fr/'                                  => $h . '/',
                $b . '#'                                     => $h . '/',
                $b . '#tag'                                  => $h . '/#tag',
                $h . '#tag'                                  => $h . '#tag',
                $b . '?lang=fr'                              => $h . '/',
                $h . '?lang=fr'                              => $h . '',
                $h . '/fr?lang=fr#tag'                       => $h . '#tag',
                $b . 'fr/?lang=fr&page_id=123#tag'           => $h . '/?page_id=123#tag',
                $h . '?page_id=123&lang=fr'                  => $h . '?page_id=123',
                $h . '?page_id=123&lang=fr#tag'              => $h . '?page_id=123#tag',
                $b . 'fr/?page_id=123&lang=fr&tab=tab3'      => $h . '/?page_id=123&tab=tab3',
                $h . '/fr/?page_id=123&lang=fr&tab=tab3#tag' => $h . '/?page_id=123&tab=tab3#tag',
            );
            qtranxf_test_convertURL( 'QTX_URL_PATH', $urls, $lang, false );
            break;
        case QTX_URL_DOMAIN:
            //qtranxf_tst_log('qtx-test-convertURL: $url_mode=QTX_URL_DOMAIN');
            $hp   = $homeinfo['scheme'] . '://' . $lang . '.' . $homeinfo['host'] . $p;
            $urls = array(
                $b . '?lang=fr'                          => $hp . '/',
                $b . '#'                                 => $hp . '/',
                $h . '?lang=fr#tag'                      => $hp . '#tag',
                $b . '?lang=fr&page_id=123#tag'          => $hp . '/?page_id=123#tag',
                $h . '?page_id=123&lang=fr'              => $hp . '?page_id=123',
                $h . '?page_id=123&lang=fr#tag'          => $hp . '?page_id=123#tag',
                $b . '?page_id=123&lang=fr&tab=tab3'     => $hp . '/?page_id=123&tab=tab3',
                $h . '?page_id=123&lang=fr&tab=tab3#tag' => $hp . '?page_id=123&tab=tab3#tag',
            );
            qtranxf_test_convertURL( 'QTX_URL_DOMAIN', $urls, $lang, true );
            $urls = array(
                $b . '?lang=fr'                          => $h . '/',
                $b . '#'                                 => $h . '/',
                $h . '?lang=fr#tag'                      => $h . '#tag',
                $b . '?lang=fr&page_id=123#tag'          => $h . '/?page_id=123#tag',
                $h . '?page_id=123&lang=fr'              => $h . '?page_id=123',
                $h . '?page_id=123&lang=fr#tag'          => $h . '?page_id=123#tag',
                $b . '?page_id=123&lang=fr&tab=tab3'     => $h . '/?page_id=123&tab=tab3',
                $h . '?page_id=123&lang=fr&tab=tab3#tag' => $h . '?page_id=123&tab=tab3#tag',
            );
            qtranxf_test_convertURL( 'QTX_URL_DOMAIN', $urls, $lang, false );
            break;
        case QTX_URL_DOMAINS:
            //qtranxf_tst_log('qtx-test-convertURL: $url_mode=QTX_URL_DOMAINS');
            $h    = $homeinfo['scheme'] . '://' . $q_config['domains'][ $q_config['default_language'] ] . $p;
            $hp   = $homeinfo['scheme'] . '://' . $q_config['domains'][ $lang ] . $p;
            $urls = array(
                $b . '?lang=fr'                          => $hp . '/',
                $b . '#'                                 => $hp . '/',
                $h . '?lang=fr#tag'                      => $hp . '#tag',
                $b . '?lang=fr&page_id=123#tag'          => $hp . '/?page_id=123#tag',
                $h . '?page_id=123&lang=fr'              => $hp . '?page_id=123',
                $h . '?page_id=123&lang=fr#tag'          => $hp . '?page_id=123#tag',
                $b . '?page_id=123&lang=fr&tab=tab3'     => $hp . '/?page_id=123&tab=tab3',
                $h . '?page_id=123&lang=fr&tab=tab3#tag' => $hp . '?page_id=123&tab=tab3#tag',
            );
            qtranxf_test_convertURL( 'QTX_URL_DOMAINS', $urls, $lang, true );
            $urls = array(
                $b . '?lang=fr'                          => $h . '/',
                $b . '#'                                 => $h . '/',
                $h . '?lang=fr#tag'                      => $h . '#tag',
                $b . '?lang=fr&page_id=123#tag'          => $h . '/?page_id=123#tag',
                $h . '?page_id=123&lang=fr'              => $h . '?page_id=123',
                $h . '?page_id=123&lang=fr#tag'          => $h . '?page_id=123#tag',
                $b . '?page_id=123&lang=fr&tab=tab3'     => $h . '/?page_id=123&tab=tab3',
                $h . '?page_id=123&lang=fr&tab=tab3#tag' => $h . '?page_id=123&tab=tab3#tag',
            );
            qtranxf_test_convertURL( 'QTX_URL_DOMAINS', $urls, $lang, false );
            break;
        default:
            qtranxf_tst_log( 'qtx-test-convertURL: unknown $url_mode=', $url_mode );
            break;
    }
}

function qtranxf_run_tests_convertURL() {
    global $q_config;
    $lang = qtranxf_getLanguage();
    foreach ( $q_config['enabled_languages'] as $lng ) {
        if ( $lng == $q_config['default_language'] ) {
            continue;
        }
        $lang = $lng;
        break;
    }
    $url_mode = $q_config['url_mode'];
    qtranxf_run_test_convertURL( $url_mode, $lang );
    // TODO clarify how to run these tests
    // cache breaks tests, need to run one at a time
    //qtranxf_run_test_convertURL(QTX_URL_QUERY, $lang);
    //qtranxf_run_test_convertURL(QTX_URL_PATH, $lang);
    //qtranxf_run_test_convertURL(QTX_URL_DOMAIN, $lang);
    //qtranxf_run_test_convertURL(QTX_URL_DOMAINS, $lang);
    $q_config['url_mode'] = $url_mode;
}

qtranxf_run_tests_convertURL();
