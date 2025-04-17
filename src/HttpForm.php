<?php

class HttpForm {
    const METHOD_GET    = 'GET';
    const METHOD_POST   = 'POST';
    const METHOD_DELETE = 'DELETE';
    const METHOD_PUT    = 'PUT';
    const METHOD_PATCH   = 'PATCH';

    const FORMAT_JSON = 'json';
    const FORMAT_FORM_DATA = 'form-data';


    public $url;
    public $time = 60;
    public $method = 'GET';

    public $headers = [];
    public $payload = [];
    public $queryParams = [];
    public $format = 'form-data';

    public $options = [
        CURLOPT_RETURNTRANSFER => 1,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_HEADER => 1,
        CURLOPT_FOLLOWLOCATION => 1,
        CURLOPT_FRESH_CONNECT => 1,
        CURLOPT_FORBID_REUSE => 1
    ];


    public function url(string $path) {
        if(!filter_var($path, FILTER_VALIDATE_URL)) {
            throw new InvalidArgumentException(" URL معتبر نیست.");
        }

        $this->url = $path;
        return $this;
    }


    public function method(string $method) {
        $method = strtoupper($method);
        if(!in_array($method, [self::METHOD_GET, self::METHOD_POST, self::METHOD_PUT, self::METHOD_DELETE, self::METHOD_PATCH])) {
            throw new InvalidArgumentException(" METHOD معتبر نیست.");
        }

        $this->method = $method;
        return $this;
    }


    public function header(string $name, string $value) {
        $this->headers[$name] = $value;
        return $this;
    }

    public function headers(array $headers): self {
        foreach ($headers as $name => $value) {
            $this->headers[$name] = $value;
        }
        return $this;
    }

    public function payload(array $payload): self {
        $this->payload = $payload;
        return $this;
    }

    
    public function addPayload(string $key, $value): self {
        $this->payload[$key] = $value;
        return $this;
    }
   
    public function queryParams(array $params): self {
        $this->queryParams = $params;
        return $this;
    }

    public function addQueryParam(string $key, $value): self {
        $this->queryParams[$key] = $value;
        return $this;
    }


    public function getHeaders() {
        return $this->headers;
    }

    public function getPayload() {
        return $this->payload;
    }

    public function getParams() {
        return $this->queryParams;
    }


    
    public function option(int $option, $value): self
    {
        $this->options[$option] = $value;
        return $this;
    }

    public function format(string $format) {
        $this->format = $format;
        return $this;
    }


    public function setOtpAuthBearer(string $token): self
    {
        $this->option(CURLOPT_HTTPAUTH, CURLAUTH_BEARER);
        $this->option(CURLOPT_XOAUTH2_BEARER, $token);
        return $this;
    }

    public function setOtpAuthBasic($username, $password): self
    {
        $this->option(CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        $this->option(CURLOPT_USERPWD, $username . ':' . $password);
        return $this;
    }


    public function time(int $time) {
        $this->time = $time;
        return $this;
    }

    public function curl() {
        return Http::new()->add($this)->run();
    }

    

    public static function init(string|null $url = null): self {
        $model = new self();

        if(!empty($url)) $model->url($url);

        return $model;
    }

}