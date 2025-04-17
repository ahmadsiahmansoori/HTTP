<?php

class HttpClient {
    /**
     * ارسال درخواست GET
     *
     * @param string $url
     * @param array  $queryParams
     * @param array  $headers
     * @param int    $timeout
     * @return array|bool
     */
    public static function get(string $url, array $queryParams = [], array $headers = [], int $timeout = 60) {
        return HttpForm::init($url)
            ->method(HttpForm::METHOD_GET)
            ->queryParams($queryParams)
            ->headers($headers)
            ->time($timeout)
            ->curl();
    }


    /** 
     * ارسال درخواست POST
     *
     * @param string $url
     * @param array  $queryParams
     * @param array  $payload
     * @param array  $headers
     * @param string $format
     * @param int    $timeout
     * @return array|bool
     */
    public static function post(string $url, array $queryParams = [], array $payload = [], array $headers = [], string $format = HttpForm::FORMAT_FORM_DATA, int $timeout = 60) {
        return HttpForm::init($url)
            ->method(HttpForm::METHOD_POST)
            ->payload($payload)
            ->queryParams($queryParams)
            ->headers($headers)
            ->format($format)
            ->time($timeout)
            ->curl();
    }


    /**
     * اجرای همزمان چند درخواست
     *
     * @param HttpForm ...$forms
     * @return array|bool
     */
    public static function exec(HttpForm ...$form) {
        $http = Http::new();
        foreach ($form as $item) {
            $http->add($item);
        }
        return $http->run();
    }


}
