<?php

class HttpClient
{
    /**
     * ارسال درخواست GET
     *
     * @param string $url
     * @param array  $queryParams
     * @param array  $headers
     * @param int    $timeout
     * @return array|bool
     */
    public static function get(string $url, array $queryParams = [], array $headers = [], bool|array $write_log = false, int $timeout = 60)
    {
        $curl =  HttpForm::init($url)
            ->method(HttpForm::METHOD_GET)
            ->queryParams($queryParams)
            ->headers($headers)
            ->time($timeout);

        if (is_array($write_log)) {
            $name_file = '';
            $only_error = true;

            if (isset($write_log['log_name_file']) && is_string($write_log['log_name_file'])) {
                $name_file = $write_log['log_name_file'];
            }
            if (isset($write_log['only_error']) && is_bool($write_log['only_error'])) {
                $only_error = $write_log['only_error'];
            }
            $curl->activeLog($only_error, $name_file);
        }

        if (is_bool($write_log) && $write_log == true) {
            $curl->activeLog();
        }

        return $curl->curl();
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
    public static function post(string $url, array $queryParams = [], array $payload = [], array $headers = [], string $format = HttpForm::FORMAT_FORM_DATA, bool|array $write_log = false, int $timeout = 60)
    {

        $curl =  HttpForm::init($url)
            ->method(HttpForm::METHOD_POST)
            ->payload($payload)
            ->queryParams($queryParams)
            ->headers($headers)
            ->format($format)
            ->time($timeout);

        if (is_array($write_log)) {
            $name_file = '';
            $only_error = true;

            if (isset($write_log['log_name_file']) && is_string($write_log['log_name_file'])) {
                $name_file = $write_log['log_name_file'];
            }
            if (isset($write_log['only_error']) && is_bool($write_log['only_error'])) {
                $only_error = $write_log['only_error'];
            }
            $curl->activeLog($only_error, $name_file);
        }

        if (is_bool($write_log) && $write_log == true) {
            $curl->activeLog();
        }

        return $curl->curl();
    }


    /**
     * اجرای همزمان چند درخواست
     *
     * @param HttpForm ...$forms
     * @return array|bool
     */
    public static function exec(HttpForm ...$form)
    {
        $http = Http::new();
        foreach ($form as $item) {
            $http->add($item);
        }
        return $http->run();
    }
}
