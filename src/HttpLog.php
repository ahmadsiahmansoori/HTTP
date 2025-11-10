<?php

class HttpLog
{

    private static string $base_path =  '../httpClientLog';


    public static function write(HttpForm $form, HttpResult $result, string $name = '')
    {
        $curl      = self::stringCurl($form);
        $response  = self::mapperResult($result);

        return self::writeFile($form->url, $curl, $response, $name);
    }


    private static function writeFile(string $url, string $curl, array $result, string $name = ''): bool
    {

        $host = parse_url($url, PHP_URL_HOST) ?? 'unknown';
        $date = date('Y-m-d_H-i-s');
        $path = rtrim(self::$base_path, '/') . '/' . $host;

        if (!is_dir($path) && !mkdir($path, 0777, true) && !is_dir($path)) {
            return false;
        }


        if (!empty($name)) {
            $file = $path . '/' . md5(uniqid()) . '&' . preg_replace('/[^a-zA-Z0-9_-]/', '_', $name) . '.json';
        } else {
            $file = $path . '/' . md5(uniqid()) . '&' . $date . '.json';
        }

        $data = [
            'datetime' => date('Y-m-d H:i:s'),
            'curl' => $curl,
            'result' => $result
        ];

        $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

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
            'body' => $result->body
        ];
    }


    public static function stringCurl(HttpForm $form): string
    {
        $url = $form->url;

        // ساخت QueryString
        if (!empty($form->queryParams)) {
            $query = http_build_query($form->queryParams);
            $url .= (str_contains($url, '?') ? '&' : '?') . $query;
        }

        // هدرها
        $headers = [];
        foreach ($form->headers as $key => $value) {
            $headers[] = "--header '" . str_replace("'", "'\"'\"'", "{$key}: {$value}") . "'";
        }

        // payload
        $dataStr = '';
        if (!in_array(strtoupper($form->method), [HttpForm::METHOD_GET, HttpForm::METHOD_DELETE]) && !empty($form->payload)) {
            if ($form->format === HttpForm::FORMAT_JSON) {
                $jsonPayload = json_encode($form->payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
                $dataStr = "--data '" . str_replace("'", "'\"'\"'", $jsonPayload) . "'";
            } else {
                $formPayload = http_build_query($form->payload);
                $dataStr = "--data '" . str_replace("'", "'\"'\"'", $formPayload) . "'";
            }
        }

        // مونتاژ نهایی با newline واقعی
        $parts = ["curl --location", "--request " . strtoupper($form->method), "'{$url}'"];
        if (!empty($headers)) {
            $parts = array_merge($parts, $headers);
        }
        if (!empty($dataStr)) {
            $parts[] = $dataStr;
        }

        return implode('  ', $parts);
    }
}
