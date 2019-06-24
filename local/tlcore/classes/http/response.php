<?php

namespace local_tlcore\http;

use local_tlcore\http\request;
use local_tlcore\exceptions\http_exception;

/**
 * Wraps HTTP response.
 */
class response {
  /**
   * @var string Response content (encoded string)
   */
  protected $content = "";
  
  /**
   * @var int File size of download
   */
  protected $content_length = 0;
  
  /**
   * @var string Location on disk to download file from.
   */
  protected $filepath = "";
  
  /**
   * @var string Name to give the downloaded file.
   */
  protected $filename = "";

  /**
   * @var array Headers to output in response.
   */
  protected $headers = [];

  /**
   * @var local_tlcore\http\request The request that made this response.
   */
  protected $request;

  public function __construct(request $request = null) {
    if (!$request) {
      $request = new request();
    }
    $this->set_request($request);
  }

  /**
   * Gets the content to be rendered.
   * @return mixed
   */
  public function get_content() {
    return $this->content;
  }

  /**
   * Gets the content as parsed JSON.
   * @param bool $assoc - if true, returns json as an associative array
   * @return mixed 
   */
  public function get_json($assoc = false) {
    return json_decode($this->content, $assoc);
  }

  /**
   * Gets content length (file size of download).
   * Note: this is set after calling set_file_to_download().
   * Calling before this will return default (0).
   * @return int Size of download
   */
  public function get_content_length() {
    return $this->content_length;
  }

  /**
   * Gets the content type of the response.
   * @return string
   */
  public function get_content_type() {
    return $this->get_header('Content-Type');
  }
  
  /**
   * Gets the download filename.
   * Only set by calling set_file_to_download.
   * If set, and file set, then assume download with attachment.
   * @return string Download filename.
   */
  public function get_filename() {
    return $this->filename;
  }

  /**
   * Returns path on server of file to download.
   * @return string
   */
  public function get_filepath() {
    return $this->filepath;
  }

  /**
   * Gets a header value case-insensitively.
   * Note: this only fetches headers that have been
   * manually set on this response object using set_header().
   * If you want request headers (i.e. those passed by the client)
   * then you need request->get_header().
   * @param  string $key Header key to fetch
   * @return string      Matching header value
   */
  public function get_header($key) {
    $out = '';
    $key = strtolower($key);
    $headers = $this->get_headers();
    if (isset($headers[$key])) {
      $out = (string)$headers[$key];
    }
    return $out;
  }

  /**
   * Gets all headers.  Use set_header() to set one.
   * @return array Headers.
   */
  public function get_headers() {
    return array_change_key_case($this->headers, CASE_LOWER);
  }

  /**
   * Gets the request associated with this response.
   * @return local_tlcore\http\request 
   */
  public function get_request() {
    if (!$this->request instanceof request) {
      throw new http_exception('Not a valid request');
    }
    return $this->request;
  }

  /**
   * Gets the status code.
   * @return int|null
   */
  public function get_status_code() {
    $array = array_values(preg_grep('/HTTP\/1\.1/', $this->headers));
    if (empty($array) || !isset($array[0])) return;

    if (preg_match('/(\d{3})/', $array[0], $match)) {
      return isset($match[1]) ? $match[1] : null;
    }
  }

  /**
   * Return true if the response has a 2xx status code.
   * @return bool True if response is 2xx
   */
  public function is_successful() {
    $code = $this->get_status_code();
    return ($code >= 200 && $code < 300);
  }

  /**
   * Sets the content to be rendered.
   * @param mixed|null $content 
   * @return local_tlcore\http\response;
   */
  public function set_content($content = null) {
    $this->content = $content;
    return $this;
  }

  /**
   * Sets the content type of the response.
   * @param string $content_type E.g. application/json
   * @return local_tlcore\http\response;
   */
  public function set_content_type($content_type) {
    $this->set_header('Content-Type', $content_type);
    return $this;
  }

  /**
   * Sets the file to be downloaded.
   * @param string $filepath Path on server of file to download
   * @param string $filename    Optional filename.  If not set, doesn't force attachment
   * @return local_tlcore\http\response;
   */
  public function set_file_to_download($filepath, $filename = '') {
    if (!file_exists($filepath) || !is_readable($filepath)) {
      throw new http_exception('File cannot be read');
    }

    $this->filepath = $filepath;
    $this->filename = $filename;
    $this->content_length = filesize($filepath);
    $this->set_content_type(self::mime_from_extension($filename));

    return $this;
  }

  public function set_header($header, $value = "") {
    if (!$value) $value = $header;
    $this->headers[$header] = $value;
    return $this;
  }

  public function set_headers(array $headers) {
    foreach ($headers as $header => $value) {
      $this->set_header($header, $value);
    }
    return $this;
  }

  public function set_request(request $request) {
    $this->request = $request;
    return $this;
  }

  /**
   * Sets the status code.
   * @param int $code e.g. 200
   * @return local_tlcore\http\response;
   */
  public function set_status_code($code) {
    $description = self::get_http_status_description($code);
    $this->set_header("HTTP/1.1 {$code} {$description}");
    return $this;
  }

  /**
   * Render the response content to a string.
   * @return string
   */
  public function render() {
    // no
  }

  /**
   * Output the response to wherever.  Probably the browser.
   * @return void 
   */
  public function output() {
    // nope
  }

  /**
   * Kind of lame.
   * @param  int $code HTTP status code
   * @return string    HTTP status string
   */
  public static function get_http_status_description($code) {
    $map = [
      200 => 'OK',
      404 => 'Not Found',
      500 => 'Internal Server Error'
    ];
    return isset($map[$code]) ? $map[$code] : null;
  }

  /**
   * Embarrassing really.
   * @param  string $extension file extension to find mime for
   * @return string            mime type for setting content type
   */
  public static function mime_from_extension($extension) {
    $map = [
      'css' =>      'text/css',
      'doc' =>      'application/msword',
      'docm' =>     'application/vnd.ms-word.document.macroEnabled.12',
      'docx' =>     'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
      'dot' =>      'application/msword',
      'dotm' =>     'application/vnd.ms-word.template.macroEnabled.12',
      'dotx' =>     'application/vnd.openxmlformats-officedocument.wordprocessingml.template',
      'gif' =>      'image/gif',
      'jpeg' =>     'image/jpeg',
      'jpg' =>      'image/jpeg',
      'js' =>       'application/javascript',
      'pdf' =>      'application/pdf',
      'png' =>      'image/png',
      'pot' =>      'application/vnd.ms-powerpoint',
      'potm' =>     'application/vnd.ms-powerpoint.template.macroEnabled.12',
      'potx' =>     'application/vnd.openxmlformats-officedocument.presentationml.template',
      'ppa' =>      'application/vnd.ms-powerpoint',
      'ppam' =>     'application/vnd.ms-powerpoint.addin.macroEnabled.12',
      'pps' =>      'application/vnd.ms-powerpoint',
      'ppsm' =>     'application/vnd.ms-powerpoint.slideshow.macroEnabled.12',
      'ppsx' =>     'application/vnd.openxmlformats-officedocument.presentationml.slideshow',
      'ppt' =>      'application/vnd.ms-powerpoint',
      'pptm' =>     'application/vnd.ms-powerpoint.presentation.macroEnabled.12',
      'pptx' =>     'application/vnd.openxmlformats-officedocument.presentationml.presentation',
      'xla' =>      'application/vnd.ms-excel',
      'xlam' =>     'application/vnd.ms-excel.addin.macroEnabled.12',
      'xls' =>      'application/vnd.ms-excel',
      'xlsb' =>     'application/vnd.ms-excel.sheet.binary.macroEnabled.12',
      'xlsm' =>     'application/vnd.ms-excel.sheet.macroEnabled.12',
      'xlsx' =>     'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
      'xlt' =>      'application/vnd.ms-excel',
      'xltm' =>     'application/vnd.ms-excel.template.macroEnabled.12',
      'xltx' =>     'application/vnd.openxmlformats-officedocument.spreadsheetml.template'
    ];
    return isset($map[$extension]) ? $map[$extension] : '';
  }
}