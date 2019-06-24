<?php

namespace local_tlcore\http;

use stdClass;
use local_tlcore\exceptions\http_exception;

class request {
  const DEFAULT_REQUEST_METHOD = 'GET';
  const DEFAULT_CONTENT_TYPE = 'text/html';

  protected $body = "";
  protected $content_type = "";
  protected $headers = [];
  protected $host = "";
  protected $input;
  protected $path_info = "";
  protected $port;
  protected $protocol = "";
  protected $request_method = "";
  protected $request_uri = "";

  public function __construct() {
    $this->input = new stdClass();
    $this->set_request_method(self::DEFAULT_REQUEST_METHOD);
  }

  /**
   * Clears all the request headers.
   * @return local_tlcore\http\request 
   */
  public function delete_headers() {
    $this->headers = [];
    return $this;
  }

  /**
   * Shorthand for request->set_url(url)->send().
   * @param  string $url URL to get
   * @return local_tlcore\http\response    Response from curl request
   */
  public function get($url) {
    return $this
      ->set_request_method('GET')
      ->set_url($url)
      ->send();
  }

  /**
   * Gets the best Accept content type for the request.
   * @return string Accept content type, defaults to 'text/html'
   */
  public function get_best_accept() {
    $accept = $this->get_header('Accept');
    if (!$accept) return static::DEFAULT_CONTENT_TYPE;

    $accepts = explode(',', $accept);
    return array_shift($accepts);
  }

  /**
   * Gets the body set via set_body.
   * @return string Request body
   */
  public function get_body() {
    $body = $this->body;
    return $body;
  }

  public function get_content_type() {
    $content_type = $this->get_header('Content-Type');
    if (!$content_type) $content_type = static::DEFAULT_CONTENT_TYPE;
    return $content_type;
  }

  public function get_header($header) {
    $header = strtolower($header);
    if (isset($this->headers[$header])) {
      return $this->headers[$header];
    }
  }

  public function get_headers() {
    return $this->headers;
  }

  public function get_host() {
    return $this->host;
  }

  public function get_input() {
    return $this->input;
  }

  public function get_param($param) {
    if (is_object($this->input) && isset($this->input->$param)) {
      return $this->input->$param;
    }
  }

  public function get_path_info() {
    return $this->path_info;
  }

  public function get_port() {
    return $this->port;
  }

  public function get_protocol() {
    return $this->protocol;
  }

  /**
   * Gets the request method (GET, POST, PUT etc).
   * @return string Request method
   */
  public function get_request_method() {
    return $this->request_method ?: static::DEFAULT_REQUEST_METHOD;
  }

  public function get_request_uri() {
    return $this->request_uri;
  }

  public function get_url() {
    $url = "";

    // don't assemble URL if protocol or host missing
    if (!$this->protocol || !$this->host) return $url;

    $url = "{$this->protocol}://{$this->host}";

    if ($this->port) {
      $url = "{$url}:{$this->port}";
    }

    if ($this->request_uri) {
      $url = "{$url}{$this->request_uri}";
    }

    return $url;
  }

  /**
   * Populates this request from $_SERVER environment.
   * @param  array  $server Optional; modified $_SERVER or mock server
   * @return local_tlcore\http\request
   */
  public function populate_from_server(array $server = []) {
    $map = [
      'CONTENT_TYPE'    => 'set_content_type',
      'HTTP_HOST'       => 'set_host',
      'REQUEST_METHOD'  => 'set_request_method',
      'REQUEST_URI'     => 'set_request_uri'
    ];

    foreach ($map as $header => $method) {
      if (isset($server[$header])) {
        $this->$method($server[$header]);
      }
    }

    /** @see http://stackoverflow.com/questions/41427359/phpunit-getallheaders-not-work */
    foreach ($server as $name => $value) {
      if (substr($name, 0, 5) == 'HTTP_') {
        $header = str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))));
        $this->set_header($header, $value);
      }
    }

    return $this;
  }

  /**
   * Throws exception is header is not defined, otherwise return.
   * @param  string $header Header to fetch
   * @return string         Header value
   */
  public function require_header($header) {
    $header = strtolower($header);
    if (!isset($this->headers[$header])) {
      throw new http_exception("Missing required header `{$header}`");
    }
    return $this->headers[$header];
  }

  /**
   * Throws exception if param is not defined, otherwise return.
   * @param  string $param Param key to fetch
   * @return mixed         Param value
   * @throws local_tlcore\exceptions\http_exception 
   */
  public function require_param($param) {
    if (!isset($this->input->$param)) {
      throw new http_exception("Missing required parameter `{$param}`");
    }
    return $this->input->$param;
  }

  /**
   * Make an HTTP request.
   * @return local_tlcore\http\response 
   */
  public function send() {
    $url = $this->get_url();

    if (strtoupper($this->request_method) === 'GET' && $this->input) {
      // encode query string for GET requests
      $url .= '?'.http_build_query($this->input, '', '&');
    }

    $curl = new curl();
    $curl_response = $curl
      ->set_url( $url )
      ->set_body( $this->get_body() )
      ->set_headers( $this->get_headers() )
      ->exec();

    // wrap in a response
    $response = new response($this);
    $response
      ->set_content($curl_response->body)
      ->set_headers($curl_response->headers)
      ->set_request($this);

    return $response;
  }

  public function set_accept($accept) {
    $this->set_header('Accept', $accept);
    return $this;
  }

  /**
   * Sets request body (raw string).
   * Generally this is used for making auth requests.
   * @param string $body Raw request body
   */
  public function set_body($body) {
    $this->body = $body;
    return $this;
  }

  public function set_content_type($content_type) {
    $this->set_header('Content-Type', $content_type);
    return $this;
  }

  public function set_header($header, $value) {
    $header = strtolower($header);
    $this->headers[$header] = $value;
    return $this;
  }

  public function set_headers(array $headers) {
    foreach ($headers as $header => $value) {
      $this->set_header($header, $value);
    }
    return $this;
  }

  /**
   * Sets the request host, e.g. 'www.example.com'.
   * @param string $host 
   * @return local_tlcore\http\request 
   */
  public function set_host($host) {
    $this->host = trim($host, '/');
    return $this;
  }

  /**
   * Sets the input (parameters) for this request.
   * @param stdClass $input Object of key => value params
   * @return local_tlcore\http\request 
   */
  public function set_input(stdClass $input) {
    $this->input = $input;
    return $this;
  }

  /**
   * Sets a parameter in the request's input parameters.
   * @param string $key   Parameter name
   * @param string $value Parameter value
   * @return local_tlcore\http\request 
   */
  public function set_param($key, $value) {
    $this->input->$key = $value;
    return $this;
  }

  /**
   * Sets the path info, the bit after the script name.
   * @param string $path_info 
   * @return local_tlcore\http\request 
   */
  public function set_path_info($path_info) {
    $this->path_info = $path_info;
    return $this;
  }

  /**
   * Sets port.
   * @param int $port The port
   */
  public function set_port($port) {
    $this->port = $port;
    return $this;
  }

  /**
   * Sets the protocol, e.g. 'http'.
   * @param string $protocol
   * @return local_tlcore\http\request 
   */
  public function set_protocol($protocol) {
    $this->protocol = $protocol;
    return $this;
  }

  /**
   * Sets the request method, e.g. 'PUT'.
   * @param string $method Method verb
   * @return local_tlcore\http\request
   */
  public function set_request_method($method) {
    $this->request_method = $method;
    return $this;
  }

  /**
   * Sets the request_uri, the bit after the hostname and before the script.
   * @param string $request_uri 
   * @return local_tlcore\http\request 
   */
  public function set_request_uri($request_uri) {
    $this->request_uri = $request_uri;
    return $this;
  }

  /**
   * Sets the URL of this request, breaking apart into components.
   * @param string $url URL to parse
   * @return local_tlcore\http\request
   */
  public function set_url($url) {
    $parsed = parse_url($url);

    // for each component, call the corresponding set method
    $map = [
      'scheme'  => 'set_protocol',
      'host'    => 'set_host',
      'path'    => 'set_request_uri',
      'port'    => 'set_port'
    ];
    foreach ($map as $component => $method) {
      if (isset($parsed[$component])) {
        $this->$method($parsed[$component]);
      }    
    }

    // break apart the query string and set input
    if (isset($parsed['query'])) {
      $params = [];
      parse_str($parsed['query'], $params);
      $this->set_input( (object)$params );
    }

    return $this;
  }
}