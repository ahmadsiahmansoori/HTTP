<?php

class HttpLog
{

    private static string $base_path = __DIR__ . '/httpClient'; // مسیر ذخیره لاگ‌ها


    public static function write(HttpForm $form, HttpResult $result, string $name = '')
    {
        $curl      = self::stringCurl($form);
        $response  = self::mapperResult($result);

        return self::writeFile($form->url, $curl, $response, $name);
    }


    private static function writeFile(string $url, string $curl, array $result, string $name = ''): bool
    {

        $host = parse_url($url, PHP_URL_HOST) ?? 'unknown';
        $date = date('Y-m-d H:i:s');
        $path = rtrim(self::$base_path, '/') . '/' . $host;

        if (!is_dir($path) && !mkdir($path, 0777, true) && !is_dir($path)) {
            return false;
        }


        if (empty($name)) {
            $file = $path . '/' . preg_replace('/[^a-zA-Z0-9_-]/', '_', $name) . '.json';
        } else {
            $file = $path . '/' . $date . '.json';
        }

        $data = [
            'datetime' => date('Y-m-d H:i:s'),
            'curl' => $curl,
            'result' => $result
        ];

        $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        $json .= PHP_EOL . str_repeat('-', 80) . PHP_EOL;

        return file_put_contents($file, $json, FILE_APPEND | LOCK_EX) !== false;
    }


    public static function mapperResult(HttpResult $result): array
    {
        return [
            'status' => $result->status,
            'message' => $result->message,
            'ok' => $result->ok,
            'ip' => $result->ip,
            'time' => $result->time,
            'size' => $result->size,
            'timings' => $result->timings,
            'headers' => $result->getHeaders(),
            'cookies' => $result->getCookies(),
            'body' => $result->body
        ];
    }


    public static function stringCurl(HttpForm $form): string
    {
        $url = $form->url;
        if (!empty($form->queryParams)) {
            $query = http_build_query($form->queryParams);
            $url .= (str_contains($url, '?') ? '&' : '?') . $query;
        }

        $headerStr = '';
        foreach ($form->headers as $key => $value) {
            $headerStr .= " --header '" . addslashes($key) . ": " . addslashes($value) . "'";
        }

        $dataStr = '';
        if (!in_array(strtoupper($form->method), [HttpForm::METHOD_GET, HttpForm::METHOD_DELETE]) && !empty($form->payload)) {
            if ($form->format === HttpForm::FORMAT_JSON) {
                $jsonPayload = json_encode($form->payload, JSON_UNESCAPED_UNICODE);
                $dataStr = " --data '" . addslashes($jsonPayload) . "'";
            } else {
                $formPayload = http_build_query($form->payload);
                $dataStr = " --data '" . addslashes($formPayload) . "'";
            }
        }

        $curl = "curl --location --request " . strtoupper($form->method) . " '" . $url . "' \\" . PHP_EOL
            . $headerStr
            . $dataStr;

        return $curl;
    }
}
