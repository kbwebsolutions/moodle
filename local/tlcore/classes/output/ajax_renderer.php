<?php

namespace local_tlcore\output;

use plugin_renderer_base;
use moodle_page;

class ajax_renderer extends plugin_renderer_base {
    protected $debug;
    protected $json;

    public function debug($json) {
        global $CFG;
        if ($CFG->debugdeveloper) {
            $this->debug = $json;
        }
        return $this;
    }

    public function exit() {
        exit;
    }

    public function footer() {
        $this->exit();
    }

    public function get_body() {
        return json_decode(file_get_contents('php://input'), true);
    }

    public function get_token() {
        $auth = isset($_SERVER['HTTP_AUTHORIZATION']) ? $_SERVER['HTTP_AUTHORIZATION'] : '';
        $token = trim( str_ireplace('bearer', '', $auth) );
        return $token;
    }

    public function header() {
        $this->contenttype = 'application/json';

        set_exception_handler(function($e) {
            header('HTTP/1.1 500 Internal Server Error');
            echo json_encode(['error' => $e->getMessage(), 'exception' => (array)$e]);
            exit;
        });

        $this->page->set_state(moodle_page::STATE_PRINTING_HEADER);

        header('Content-Type: '.$this->contenttype);
        ob_start();
        
        $this->page->set_state(moodle_page::STATE_IN_BODY);
    }

    public function json($json) {
        $this->json = $json;
        return $this;
    }

    public function require_verify_token($check_token) {
        $token = $this->get_token();

        if (!$token || $token !== $check_token) {
            $this->send_header_401();
            $this->json(['error' => 'invalid token']);
            $this->exit();
        }

        return true;
    }

    public function send_header_401() {
        header('HTTP/1.1 401 Not Authorized');
        return $this;
    }

    public function send_header_403() {
        header('HTTP/1.1 403 Forbidden');
        return $this;
    }

    public function send_header_404() {
        header('HTTP/1.1 404 Not Found');
        return $this;
    }

    public function send_header_500() {
        header('HTTP/1.1 500 Internal Server Error');
        return $this;
    }

    public function __destruct() {
        if ($this->debug) {
            if ($this->json && is_array($this->json)) {
                $this->json['debug'] = $this->debug;
            } else {
                $this->json = ['debug' => $this->debug];
            }
        }
        echo json_encode($this->json);
    }
}