<?php
class HttpResult
{

    public $body;
    public $status;
    public $time;
    public $size;
    public $message;
    public $ip;
    public $timings = [];


    private $headers;
    private $cookies;


    public $ok = false;



    /**
     * مقداردهی اولیه از طریق handle cURL
     *
     * @param CurlHandle $ch
     * @param string|false $res
     * @return self
     */
    public static function init(CurlHandle $ch, string|bool $res, HttpForm $form = null)
    {
        $init = new self();
        $init->parse($ch, $res);

        if ($form &&  ($form->write_log || (!$init->isOk() && $form->write_log_error))) {
            HttpLog::write($form, $init, $form->log_name_file);
        }
            
        return $init;
    }


    /**
     * تجزیه و ذخیره اطلاعات و پاسخ
     *
     * @param CurlHandle $ch
     * @param string|false $res
     * @return void
     */
    private function parse(CurlHandle $ch, string|bool $res)
    {

        $this->status  =  (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $this->time    =  (float) curl_getinfo($ch, CURLINFO_TOTAL_TIME);
        $this->ip      =  (string) curl_getinfo($ch, CURLINFO_PRIMARY_IP);
        $this->size    =  (int) curl_getinfo($ch, CURLINFO_SIZE_DOWNLOAD);

        $this->timings = [
            'namelookup'   => (float) curl_getinfo($ch, CURLINFO_NAMELOOKUP_TIME),
            'connect'      => (float) curl_getinfo($ch, CURLINFO_CONNECT_TIME),
            'starttransfer' => (float) curl_getinfo($ch, CURLINFO_STARTTRANSFER_TIME),
        ];


        $headerSize = (int) curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $rawHeader  = substr((string) $res, 0, $headerSize);
        $bodyPart   = substr((string) $res, $headerSize);
        $this->headers = $this->parseHeader($rawHeader);
        $this->cookies = $this->parseCookies();
        $this->ok = $this->status >= 200 && $this->status < 300;

        if ($res == false) {
            $this->message = $this->parseErrorMessage($ch);
            $this->body    = null;
        } else {
            $this->body    = $this->parseBody($bodyPart);
            $this->message = self::statusText($this->status);
        }
    }



    /**
     * @param int $code
     */
    private static function statusText(int $code): string
    {
        static $texts = [
            100 => 'Continue',
            101 => 'Switching Protocols',
            200 => 'OK',
            201 => 'Created',
            202 => 'Accepted',
            203 => 'Non-Authoritative Information',
            204 => 'No Content',
            205 => 'Reset Content',
            206 => 'Partial Content',
            300 => 'Multiple Choices',
            301 => 'Moved Permanently',
            302 => 'Found',
            303 => 'See Other',
            304 => 'Not Modified',
            305 => 'Use Proxy',
            307 => 'Temporary Redirect',
            400 => 'Bad Request',
            401 => 'Unauthorized',
            402 => 'Payment Required',
            403 => 'Forbidden',
            404 => 'Not Found',
            405 => 'Method Not Allowed',
            406 => 'Not Acceptable',
            407 => 'Proxy Authentication Required',
            408 => 'Request Timeout',
            409 => 'Conflict',
            410 => 'Gone',
            411 => 'Length Required',
            412 => 'Precondition Failed',
            413 => 'Payload Too Large',
            414 => 'URI Too Long',
            415 => 'Unsupported Media Type',
            416 => 'Range Not Satisfiable',
            417 => 'Expectation Failed',
            500 => 'Internal Server Error',
            501 => 'Not Implemented',
            502 => 'Bad Gateway',
            503 => 'Service Unavailable',
            504 => 'Gateway Timeout',
            505 => 'HTTP Version Not Supported'
        ];
        return $texts[$code] ?? 'Unknown Status';
    }


    /**
     * نگاشت خطای cURL به پیام فارسی
     *
     * @param CurlHandle $ch
     * @return string
     */
    private function parseErrorMessage(CurlHandle $ch)
    {
        $message = curl_error($ch);
        $code    = curl_errno($ch);
        $errorMap = [
            6 => 'مشکل در یافتن آدرس سرور (DNS)',
            7 => 'اتصال به سرور برقرار نشد',
            28 => 'زمان انتظار برای پاسخ به پایان رسید',
            35 => 'خطای اتصال امنیتی (SSL)',
            47 => 'تعداد تغییر مسیرها بیش از حد مجاز',
            51 => 'گواهی امنیتی سرور نامعتبر است',
            52 => 'سرور پاسخ خالی ارسال کرد',
            56 => 'اتصال شبکه قطع شد',
            60 => 'مشکل در گواهی ریشه SSL (CA Certificate)',
            77 => 'خطا در خواندن فایل گواهی SSL'
        ];


        if ($message === '' && isset($errorMap[$code])) {
            return $errorMap[$code];
        }

        return $message !== '' ? $message : "خطای ناشناخته cURL (کد: {$code})";
    }


    /**
     * تجزیه هدرها و نگهداری چند مقداری
     *
     * @param string $header
     * @return array<string,string[]>
     */
    private function parseHeader(string $header): array
    {

        $lines = preg_split("/\r?\n/", trim($header));
        $result = [];
        foreach ($lines as $line) {
            if (strpos($line, ':') === false) continue;
            [$key, $value] = explode(':', $line, 2);
            $key = strtolower(trim($key));
            $value = trim($value);
            $result[$key][] = $value;
        }
        return $result;
    }


    /**
     * استخراج کوکی‌ها از هدرها
     *
     * @return array<string,string>
     */
    private function parseCookies(): array
    {
        $cookies = [];
        $setCookies = $this->headers['set-cookie'] ?? [];
        foreach ($setCookies as $cookieHeader) {
            $parts = explode(';', $cookieHeader);
            foreach ($parts as $part) {
                if (strpos($part, '=') !== false) {
                    [$name, $val] = explode('=', trim($part), 2);
                    $cookies[$name] = $val;
                }
            }
        }
        return $cookies;
    }


    /**
     * دیکد بدنه بر اساس Content-Type
     *
     * @param string $result
     * @return mixed|string
     */
    private function parseBody(string $result)
    {

        $contentTypes = $this->headers['content-type'] ?? [];
        foreach ($contentTypes as $ct) {
            if (stripos($ct, 'application/json') !== false) {
                return json_decode($result, true);
            }
        }
        return $result;
    }


    public function getBody()
    {
        return $this->body;
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function getCookies(): array
    {
        return $this->cookies;
    }

    public function getStatus(): int
    {
        return $this->status;
    }

    public function getTime(): float
    {
        return $this->time;
    }

    public function getSize(): int
    {
        return $this->size;
    }

    public function getIp(): string
    {
        return $this->ip;
    }

    public function isOk(): bool
    {
        return $this->ok;
    }

    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function getTimings(): array
    {
        return $this->timings;
    }
}
