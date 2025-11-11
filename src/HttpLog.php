<?php
class HttpLog
{


    private static string $base_path = '../httpClientLog';


    /**
     * @var int number of log files used for rotation. Defaults to 10.
     */
    private static int $maxLogFiles = 10;



    /**
     * @var int the permission to be set for newly created log files.
     * This value will be used by PHP chmod() function. No umask will be applied.
     * If not set, the permission will be determined by the current environment.
     */
    private static bool $enableRotation = true;




    /**
     * @var int the permission to be set for newly created directories.
     * This value will be used by PHP chmod() function. No umask will be applied.
     * Defaults to 0775, meaning the directory is read-writable by owner and group,
     * but read-only for other users.
     */
    private static int $dirMode = 0775;



    public static function export(string $message, string $path) {


        if(!is_dir(dirname($path))) {
            self::createDirectory(dirname($path));
        }
        

        if (($fp = @fopen($path, 'a')) === false) {
            return false;
        }

        @flock($fp, LOCK_EX);
        if (self::$enableRotation) {
            clearstatcache();

            if (is_file($path)) {
                self::rotateFiles($path);
            }
        }


        $written = @fwrite($fp, $message . "\n");

        @flock($fp, LOCK_UN);
        @fclose($fp);

        return $written !== false;

    }

    public static function write(HttpForm $form, HttpResult $result, string $name = '')
    {
        $curl      = self::stringCurl($form);
        $response  = self::mapperResult($result);

        return self::writeFile($form->url, $curl, $response, $name);
    }


    private static function writeFile(string $url, string $curl, array $result, string $name = ''): bool
    {

        $host = parse_url($url, PHP_URL_HOST) ?? 'unknown';
        $path = rtrim(self::$base_path, '/') . '/' . $host;
        
        self::createDirectory($path, self::$dirMode);
        $fileName = 'httpClientLog.log';

        $file = $path . '/' . $fileName;


        $data = [
            'datetime' => date('Y-m-d H:i:s'),
            'curl' => $curl,
            'result' => $result
        ];

        $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        return self::export($json, $file);

    }


    private static function createDirectory(string $path, int $mode = 0775): bool {

        $normalizedPath = rtrim($path, '/');

        if (is_dir($normalizedPath)) {
            return true;
        }

        if (!mkdir($normalizedPath, $mode, true) && !is_dir($normalizedPath)) {
            return false;
        }

        return chmod($normalizedPath, $mode);
    }



   private static function rotateFiles(string $file): void
    {
        for ($i = self::$maxLogFiles - 1; $i >= 0; --$i) {
            $source = $i === 0 ? $file : $file . '.' . $i;
                $dest   = $file . '.' . ($i + 1);

            if (is_file($source)) {
                if ($i + 1 >= self::$maxLogFiles) {
                    @unlink($dest);
                }
                @rename($source, $dest);
            }
        }
        if (is_file($file)) {
            @ftruncate(fopen($file, 'c'), 0);
        }
    }


    private static function clearLogFile(string $file): void
    {
        if ($fp = @fopen($file, 'a')) {
            @ftruncate($fp, 0);
            @fclose($fp);
        }
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
