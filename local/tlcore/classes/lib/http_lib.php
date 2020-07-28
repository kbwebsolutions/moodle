<?php

namespace local_tlcore\lib;

use curl;

require_once($CFG->libdir.'/filelib.php');

class http_lib {

    /**
     * Make a GET request to a resource endpoint.
     *
     * @param string $endpoint Endpoint to call
     * @param array $params Optional params for the request
     * @param array $headers Optional headers for the request
     * @return array ['http_status_code' => int, 'original' => string, 'json' => array]
     */
    public static function get($endpoint, array $params = [], array $headers = []) {
        $curl = self::setup_curl($headers);
        return self::setup_response($curl, $curl->get($endpoint, $params));
    }

    /**
     * Make a POST request to a resource endpoint.
     *
     * @param string $endpoint Endpoint to call
     * @param array $params Optional params for the request
     * @param array $headers Optional headers for the request
     * @return array ['http_status_code' => int, 'original' => string, 'json' => array]
     */
    public static function post($endpoint, array $params = [], array $headers = []) {
        $curl = self::setup_curl($headers);
        return self::setup_response($curl, $curl->post($endpoint, $params));
    }

    /**
     * Sets up a curl instance with common functionality.
     *
     * @param array $headers
     * @return curl Curl instance
     */
    private static function setup_curl(array $headers = []) {
        $curl = new curl();

        foreach ($headers as $header) {
            $curl->setHeader($header);
        }

        $curl->setopt(['CURLOPT_CONNECTTIMEOUT' => 2]);

        return $curl;
    }

    /**
     * Creates a response array from curl data.
     *
     * @param curl $curl Curl object
     * @param string $original_response The response from the custom curl operation
     * @return array Response ['http_status_code' => int, 'original' => string, 'json' => array]
     */
    private static function setup_response(curl $curl, $original_response) {
        $info = $curl->get_info();

        $response = [
            'http_status_code' => $info['http_code'],
            'original' => $original_response
        ];

        return $response;
    }
}