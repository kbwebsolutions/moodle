<?php 

namespace local_tlcore\http;

use local_tlcore\exceptions\http_exception;

/**
 * Wraps around PHP's curl functions.
 */
class curl {
  protected $resource;

  public function __construct() {
    $this->resource = curl_init();
    $this->setopt(CURLOPT_ENCODING, 'UTF-8');
    $this->setopt(CURLOPT_RETURNTRANSFER, 1);
    $this->setopt(CURLOPT_HEADER, 1);
  }

  /**
   * Call curl_setopt on the resource.
   * @param  int $opt     CURLOPT constant
   * @param  mixed $value Value to set
   * @return local_tlcore\http\curl
   */
  public function setopt($opt, $value) {
    curl_setopt($this->resource, $opt, $value);
    return $this;
  }

  /**
   * Sets the POSTFIELDS of the curl resource.
   * Body is only used when making a POST request.
   * Only sets if the body is not empty.
   * @param string $body Body string (raw)
   * @return local_tlcore\http\curl
   */
  public function set_body($body = '') {
    if ($body) {
      $this->setopt(CURLOPT_POSTFIELDS, $body);
    }
    return $this;
  }

  /**
   * Sets an array of headers on the curl resource.
   * If the header key is the same as the value, then only the single value is used.
   * Only sets if the headers are not empty.
   * @param array $headers Array of headers
   * @return local_tlcore\http\curl
   */
  public function set_headers(array $headers = []) {
    if ($headers) {
      $headers = array_map(function($key, $value) {
        return ($key == $value) ? $key : "{$key}: {$value}";
      }, array_keys($headers), $headers);
      $this->setopt(CURLOPT_HTTPHEADER, $headers);
    }
    return $this;
  }

  /**
   * Sets the URL for the curl resource.
   * @param string $url
   * @return local_tlcore\http\curl
   */
  public function set_url($url) {
    $this->setopt(CURLOPT_URL, $url);
    return $this;
  }

  /**
   * Call curl_exec, return headers and body of curl result.
   * @return stdClass Parsed response, { headers: [], body: '' }
   */
  public function exec() {
    $response = curl_exec($this->resource);
    if ($response === false) {
      throw new http_exception(curl_error($this->resource), curl_errno($this->resource));
    }
    $header_size = curl_getinfo($this->resource, CURLINFO_HEADER_SIZE);
    $header = substr($response, 0, $header_size);
    $headers = [];
    $body = substr($response, $header_size);

    // break apart the header line by line, split by colon
    foreach (explode("\n", $header) as $line) {
      $line = trim($line);
      if (!$line) continue;
      if ($pos = strpos($line, ':')) {
        $headers[substr($line, 0, $pos)] = trim(substr($line, $pos + 1));
      } else {
        $headers[$line] = $line;
      }
    }

    return (object)[
      'headers' => $headers,
      'body' => $body
    ];
  }

  /**
   * Free the curl resource.
   */
  public function __destruct() {
    curl_close($this->resource);
  }
}