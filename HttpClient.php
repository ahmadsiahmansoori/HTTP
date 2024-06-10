<?php

namespace app;

use Exception;

class HttpClient
{
    const GET = 'GET';
    const POST = 'POST';
    const DELETE = 'DELETE';
    const PUT = 'PUT';


    public $request_headers = [];
    public $request_body = [];

    public $response = null;
    public $response_header = null;
    public $response_body = null;

    public $options = [
        CURLOPT_RETURNTRANSFER => 1,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_HEADER => 1,
        CURLOPT_FOLLOWLOCATION => 1,
        CURLOPT_FRESH_CONNECT => 1,
        CURLOPT_FORBID_REUSE => 1
    ];

    public $url = null;
    public $timeOut;

    public $method = null;

    public $http_status_code = 0;
    public $http_ip = null;

    private $verbose = false;
    private $std_err = null;

    public $message;

    public function __construct($timeout = 60)
    {
        if (!extension_loaded('curl')) {
            throw new Exception('The cURL extensions is not loaded, make sure you have installed the cURL extension: https://php.net/manual/curl.setup.php', 500);
        }

        $this->timeOut = $timeout;

    }



    public function setOtp(int $option, $value): void
    {
        $this->options[$option] = $value;
    }

    public function setOtpAuthBearer(string $token): void
    {
        $this->setOtp(CURLOPT_HTTPAUTH, CURLAUTH_BEARER);
        $this->setOtp(CURLOPT_XOAUTH2_BEARER, $token);
    }

    public function setOtpAuthBasic($username, $password): void
    {
        $this->setOtp(CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        $this->setOtp(CURLOPT_USERPWD, $username . ':' . $password);
    }

    public function setHeader($key , $value): void {
        $this->request_headers[$key] = $value;
    }

    public function setHeaders(array $items) {
        foreach($items as $item => $value) {
            $this->request_headers[$item] = $value;
        }
    }

    public function setVerbose(string $pathStdErr)
    {
        $this->verbose = true;
        $this->std_err = $pathStdErr;
        $this->setOtp(CURLOPT_VERBOSE, true);
    }

    public function exec($url, $method, array $queryParams = [] , array $body = [], $asJsonPayload = true) {
        $ch = curl_init();
        if(!empty($queryParams)) {
            $url .= '?' . http_build_query($queryParams);
        }

        if($method == self::POST) {
            if($asJsonPayload) {
                $this->prepareJsonPayload($body);
            }
            else
            {
                $this->preparePayload($body);
            }
        }

        $this->method = $method;
        $this->request_body = $body;

        $this->setOtp(CURLOPT_URL , $url);
        $this->setOtp(CURLOPT_TIMEOUT , $this->timeOut);



        foreach ($this->options as $option => $value) {
            curl_setopt($ch, $option, $value);
        }

        $headers = [];
        foreach($this->request_headers as $header => $value) {
            $headers[] = "{$header}: {$value}";
        }

        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);

        $this->response = curl_exec($ch);
        $this->url =  curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
        $this->http_status_code = intval(curl_getinfo($ch, CURLINFO_HTTP_CODE));
        $this->http_ip = curl_getinfo($ch,CURLINFO_PRIMARY_IP);
        $this->message = curl_error($ch);
        $sizeHeader = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $this->response_body = substr($this->response, $sizeHeader);
        $this->response_header = trim(substr($this->response, 0, $sizeHeader));

        if($this->verbose === true) {
            $curl_log = fopen($this->std_err, 'w');
            curl_setopt($ch, CURLOPT_STDERR, $curl_log);
        }

        curl_close($ch);
    }

    protected function prepareJsonPayload(array $data)
    {
        $this->setOtp(CURLOPT_POST, true);
        $this->setOtp(CURLOPT_POSTFIELDS, json_encode($data));
    }

    protected function preparePayload(array $data) {
        $data = http_build_query($data);
        $this->setOtp(CURLOPT_POSTFIELDS, $data);
    }

    public function get($url, $queryParams = []) {
        $this->exec($url, self::GET, $queryParams);
    }

    public function post($url, $queryParams = [], $data = [], $asJsonPayload = true) {
        $this->exec($url, self::POST, $queryParams, $data, $asJsonPayload);
    }

    public function toJsonPayload() {
        $this->response_body = json_decode($this->response_body);
        return $this->response_body;
    }

    public function toJsonHeaders() {
        $response_header = [];
        foreach (explode("\r\n", $this->response_header) as $row) {
            if (preg_match('/(.*?): (.*)/', $row, $matches)) {
                $response_header[$matches[1]] = $matches[2];
            }
        }

        $this->response_header = $response_header;
        return $this->response_header;
    }

    public function isOk(): bool {
        return $this->http_status_code >= 200 && $this->http_status_code < 300;
    }

}
