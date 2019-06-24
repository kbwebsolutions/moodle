<?php

use local_tlcore\testcase;
use local_tlcore\http\request;
use local_tlcore\http\response;
use local_tlcore\exceptions\http_exception;

class http_request_test extends testcase {
  const SKIP = false;

  /**
   * Test that new request creates and sets up correctly.
   * @return void
   */
  public function test_create_new_request() {
    $this->resetAfterTest(true);
    $request = new request();
    
    $this->assertEmpty($request->get_host(), 'Host should be empty by default');
    $this->assertEmpty($request->get_protocol(), 'Protocol should be empty by default');
    $this->assertEmpty($request->get_request_uri(), 'Request URI should be empty by default');
    $this->assertEmpty($request->get_path_info(), 'Path info should be empty by default');
    $this->assertEmpty($request->get_url(), 'The full URL should be empty by default');

    $expected_host = 'www.example.com';
    $expected_protocol = 'http';
    $expected_request_uri = '/somepage';
    $expected_path_info = '/somepath';
    $expected_url = 'http://www.example.com/somepage'; // NOT including the pathinfo; this is after the script name

    $request->set_host($expected_host);
    $request->set_protocol($expected_protocol);
    $request->set_request_uri($expected_request_uri);
    $request->set_path_info($expected_path_info);

    $this->assertEquals($expected_host, $request->get_host(), 'Host should match the request');
    $this->assertEquals($expected_protocol, $request->get_protocol(), 'Protocol should match the request');
    $this->assertEquals($expected_request_uri, $request->get_request_uri(), 'Request URI should match the request');
    $this->assertEquals($expected_path_info, $request->get_path_info(), 'Path info should match the request');
    $this->assertEquals($expected_url, $request->get_url(), 'The full URL is expected - the request should assemble it');

    $expected_url = 'http://new.host/somepage';
    $request->set_host('new.host');
    $this->assertEquals($expected_url, $request->get_url(), 'Changing the hostname should change the full URL');

    // by default a request should be 'GET'
    $expected_method = request::DEFAULT_REQUEST_METHOD;
    $this->assertEquals($expected_method, $request->get_request_method());
  }

  /**
   * Test getting input from a request.
   * @return void
   */
  public function test_get_input() {
    $this->resetAfterTest(true);
    $request = new request();

    $expected_input = (object)[
      'some_key' => 'some_val',
      'some_array' => (object)[
        'thing1',
        'thing2',
        'thing3'
      ]
    ];

    $request->set_input($expected_input);
    $this->assertEquals($expected_input, $request->get_input());
  }

  /**
   * Test getting parameters from the request.
   * @return void
   */
  public function test_get_params() {
    $this->resetAfterTest(true);
    $request = new request();

    $expected_input = (object)[
      'some_key' => 'some_val',
      'some_array' => [
        'thing1',
        'thing2',
        'thing3'
      ]
    ];

    $request->set_input($expected_input);
    $this->assertEquals('some_val', $request->get_param('some_key'));
    $this->assertArray($request->get_param('some_array'));
    $this->assertCount(3, $request->get_param('some_array'));

    // try and fetch empty
    $this->assertNull($request->get_param('does_not_exist'));

  }

  /**
   * Test requiring parameters.
   * @return void
   */
  public function test_require_params() {
    $this->resetAfterTest(true);
    $request = new request();

    $expected_input = (object)[
      'some_key' => 'some_val'
    ];

    // create new key and require it
    $request->set_input( (object)['new_key' => 'new_val'] );
    $this->assertEquals('new_val', $request->require_param('new_key'));

    $this->setExpectedException(http_exception::class);
    $this->assertNull($request->require_param('does_not_exist'));
  }

  /**
   * Test setting parameters.
   * @return void
   */
  public function test_set_params() {
    $this->resetAfterTest(true);
    $request = new request();

    // assert the value is not there
    $this->assertNull($request->get_param('some_key'), 'Value should be null by default');

    $request->set_param('some_key', 'some_val');
    $this->assertEquals('some_val', $request->get_param('some_key'), 'Value should be set now');
  }

  /**
   * Test getting and setting headers
   * @return void
   */
  public function test_get_headers() {
    $this->resetAfterTest(true);
    $request = new request();

    $headers = $request->get_headers();
    $this->assertArray($headers, 'headers should be an array');
    $this->assertEmpty($headers, 'headers should be an empty array by default');

    $header = $request->get_header('Content-Type');
    $this->assertNull($header, 'Should be not set');

    $expected_value = 'application/json';

    $request->set_header('Content-Type', $expected_value);
    $this->assertEquals($expected_value, $request->get_header('Content-Type'));
    $this->assertEquals($expected_value, $request->get_header('content-type'), 'case should not matter');

    $headers = $request->get_headers();
    $this->assertCount(1, $headers);
    $this->assertEquals($expected_value, $headers['content-type']);
  }

  /**
   * Test a required header.
   * @return void
   */
  public function test_require_header() {
    $this->resetAfterTest(true);
    $request = new request();

    $header_to_set = 'Authorization';
    $expected_value = 'some token';

    $request->set_header($header_to_set, $expected_value);
    $this->assertEquals($expected_value, $request->require_header($header_to_set));

    $request->delete_headers();
    $this->assertEmpty($request->get_headers());

    $this->setExpectedException(http_exception::class);
    $this->assertNull($request->require_header($header_to_set));
  }

  /**
   * Test setting all headers
   * @return void
   */
  public function test_set_all_headers() {
    $this->resetAfterTest(true);
    $request = new request();

    $headers = [
      'Content-Type' => 'text/javascript',
      'Authorization' => 'token',
      'HTTP/1.0 201 Created' => 'HTTP/1.0 201 Created'
    ];

    $request->set_headers($headers);
    $this->assertEquals('text/javascript', $request->get_header('Content-Type'));
    $this->assertEquals('token', $request->get_header('Authorization'));
    $this->assertEquals('HTTP/1.0 201 Created', $request->get_header('HTTP/1.0 201 Created'));
  }

  /**
   * Test request method set and set.
   * @return void
   */
  public function test_request_method() {
    $this->resetAfterTest(true);
    $request = new request();

    $this->assertEquals(request::DEFAULT_REQUEST_METHOD, $request->get_request_method());

    $request->set_request_method('PUT');
    $this->assertEquals('PUT', $request->get_request_method());
  }

  /**
   * Test Content Type; not set by default in PHP
   * @return void
   */
  public function test_content_type() {
    $this->resetAfterTest(true);
    $request = new request();

    $this->assertEquals(request::DEFAULT_CONTENT_TYPE, $request->get_content_type(), 'Empty by default');

    $request->set_content_type('application/json');
    $this->assertEquals('application/json', $request->get_content_type(), 'must be as set');
  }

  /**
   * Test that URL can be broken apart into fragments
   * @return void
   */
  public function test_parse_url() {
    $this->resetAfterTest(true);
    $request = new request();

    $expected_host = 'www.example.com';
    $expected_protocol = 'http';
    $expected_request_uri = '/some/page';
    $expected_key_value = 'value';

    $request->set_url('http://www.example.com/some/page?key=value');
    $this->assertEquals($expected_host, $request->get_host());
    $this->assertEquals($expected_protocol, $request->get_protocol());
    $this->assertEquals($expected_request_uri, $request->get_request_uri());
    $this->assertEquals($expected_key_value, $request->get_param('key'));
  }

  /**
   * Test making an HTTP call using the request component.
   * @return void
   */
  public function __test_send() {
    $this->markTestSkipped('Todo: adapt API path');
    $this->resetAfterTest(true);
    $request = new request();

    // don't use Moodle's shitty example.com
    $url = util::get_real_wwwroot().'/local/titus_core/index.php/api';

    $request->set_url($url);
    $request->set_content_type('application/json');
    
    // make an HTTP call and test the headers
    $response = $request->send();
    $this->assertInstanceOf(response::class, $response, 'must return a response');
    $this->assertEquals(200, $response->get_status_code(), 'must be 200 (no errors)');
    $this->assertContains('application/json', $response->get_content_type(), 'must be application/json because the api was queried');
    $this->assertNotEmpty($response->get_content(), 'the api index page should not return empty');

    // test the response data
    $expected_version = 2.0;

    $json = $response->get_json();
    $this->assertObject($json, 'must be a PHP object parsed from JSON');
    $this->assertEquals($expected_version, $json->version, 'must equal the API version');
  }

  /**
   * Test not found error when sending
   * @return void
   */
  public function __test_send_not_found() {
    $this->markTestSkipped('Todo: adapt API path');
    $this->resetAfterTest(true);
    $request = new request();

    $url = util::get_real_wwwroot().'/local/titus_core/index.php/api/does_not_exist';
    $response = $request
      ->set_accept('application/json')
      ->set_url($url)
      ->send();

    // must be JSON and 404 because it's not a valid endpoint
    $this->assertEquals('application/json', $response->get_content_type());
    $this->assertEquals(404, $response->get_status_code());
  }

  /**
   * Test shorthand methods for send operations: get(), post(), put(), delete().
   * @return void
   */
  public function __test_shorthand_send() {
    $this->markTestSkipped('Todo: adapt API path');
    $this->resetAfterTest(true);
    $request = new request();

    $url = util::get_real_wwwroot().'/local/titus_core/index.php/api';
    $response = $request->get($url);

    $this->assertEquals('application/json', $response->get_content_type());
    $this->assertEquals(200, $response->get_status_code());
    $this->assertEquals(2.0, $response->get_json()->version);

    /** @todo other verbs */
  }
}