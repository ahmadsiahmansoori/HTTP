<?php

/**
 * کلاس Http برای ارسال درخواست‌های HTTP با استفاده از cURL
 *
 * این کلاس امکان ارسال درخواست‌های تکی و چندگانه را فراهم کرده و
 * خطاهای احتمالی cURL را ثبت می‌کند.
 */
class Http {

    /**
     * آرایه‌ای برای نگهداری handleهای cURL به همراه فرم‌های مربوطه.
     *
     * @var array
     */
    private $handles = [];

    /**
     * آرایه‌ای برای ثبت خطاهای رخ داده در فرآیند اجرای cURL.
     *
     * @var array
     */
    private $_errors = [];

    /**
     * ایجاد نمونه جدید از کلاس Http
     *
     * @return Http
     */
    public static function new() {
        return new self();
    }

    /**
     * اضافه کردن یک فرم به لیست handleهای cURL.
     *
     * @param HttpForm $form شیء HttpForm که شامل اطلاعات درخواست است.
     * @return self
     */
    public function add(HttpForm $form) {

        $ch = curl_init();

        // تنظیم هدرها
        $headers = [];
        foreach ($form->headers as $header => $value) {
            $headers[] = "{$header}: {$value}";
        }

        // تنظیم URL با Query Parameters
        curl_setopt($ch, CURLOPT_URL, $this->parseUrl($form->url, $form->queryParams));
        curl_setopt($ch, CURLOPT_TIMEOUT, $form->time);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $form->method);
        
        // تنظیم ارسال داده در صورت درخواست غیر GET
        if ($form->method != HttpForm::METHOD_GET && $form->method != HttpForm::METHOD_DELETE) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $this->parseBody($form->format, $form->payload));
        }

        // اعمال سایر گزینه‌های cURL از آرایه options
        foreach ($form->options as $option => $value) {
            curl_setopt($ch, $option, $value);
        }

        // اگر فرمت داده‌ها فرم دیتا باشد، به صورت POST ارسال می‌شود
        if ($form->format == HttpForm::FORMAT_FORM_DATA) {
            curl_setopt($ch, CURLOPT_POST, true);
        }

        // ثبت handle و فرم مرتبط
        $this->addHandel($ch, $form);
        return $this;
    }

    /**
     * اجرای درخواست‌های ثبت شده.
     *
     * اگر تنها یک handle وجود داشته باشد از exec() و در غیر این صورت از multiExec() استفاده می‌شود.
     *
     * @return array|bool آرایه نتیجه یا false در صورت عدم وجود handle.
     */
    public function run(): array|bool {
        if (count($this->handles) == 0) return false;

        if (count($this->handles) > 1) {
            return $this->multiExec();
        } else {
            return $this->exec();
        }
    }

    /**
     * اجرای درخواست‌های چندگانه با استفاده از curl_multi.
     *
     * در این متد ابتدا handleهای cURL به curl_multi اضافه می‌شوند،
     * سپس با استفاده از حلقه‌های exec و select، درخواست‌ها اجرا می‌شوند.
     *
     * @return array آرایه نتایج همراه با فرم‌های مربوطه.
     */
    private function multiExec(): array {
        // ایجاد multi handle
        $mh = curl_multi_init();
        foreach ($this->handles as $handle) {
            curl_multi_add_handle($mh, $handle['curl']);
        }

        $active = null;
        // حلقه‌ی اجرای چندگانه
        do {
            // اجرای درخواست‌ها
            $exec_status = curl_multi_exec($mh, $active);
            if ($exec_status !== CURLM_OK) {
                $this->addError('curl_exec_status', 'multi curl: status code: ' . $exec_status);
                break;
            }

            // استفاده از حلقه داخلی برای بررسی خروجی select در صورت بروز -1
            do {
                $select_status = curl_multi_select($mh);
                if ($select_status === -1) {
                    $this->addError('curl_select_status', 'curl_multi_select result -1');
                    usleep(100); // تاخیر کوتاه برای جلوگیری از busy loop
                }
            } while ($select_status === -1);

        } while ($active > 0);

        // خواندن نتیجه‌ی هر handle و بستن handleها
        $result = [];
        $i = 0;
        while ($info = curl_multi_info_read($mh)) {
            $ch = $info['handle'];
            $result[] = [
                'form'   => $this->handles[$i]['form'],
                'result' => HttpResult::init($ch, curl_multi_getcontent($ch)),
            ];
            curl_multi_remove_handle($mh, $ch);
            curl_close($ch);
            $i++;
        }

        // پاکسازی آرایه handleها
        $this->handles = [];
        return $result;
    }

    /**
     * اجرای درخواست تکی با استفاده از curl_exec.
     *
     * @return array آرایه شامل فرم و نتیجه‌ی درخواست.
     */
    private function exec() {
        $ch   = $this->handles[0]['curl'];
        $form = $this->handles[0]['form'];
        $res  = curl_exec($ch);
        
        // در صورت نیاز می‌توانید خطاهای curl_exec را نیز ثبت کنید.

        // بستن handle
        curl_close($ch);
        // پاکسازی آرایه handleها
        $this->handles = [];

        return [
            'form'   => $form,
            'result' => HttpResult::init($ch, $res)
        ];
    }

    /**
     * ساخت URL کامل با ادغام Query Parameters.
     *
     * @param string $url آدرس اصلی
     * @param array  $queryParams آرایه پارامترهای کوئری
     * @return string URL نهایی
     */
    private function parseUrl(string $url, array $queryParams = []) {
        $urlParts = parse_url($url);
        $query_params = http_build_query($queryParams);
        if (isset($urlParts['query'])) {
            // در صورتی که URL از قبل query داشته باشد، پارامترهای جدید به آن اضافه می‌شود.
            $query_params = $urlParts['query'] . '&' . $query_params;
        }
        // اگر بخش path وجود نداشته باشد، مقدار پیشفرض "/" در نظر گرفته می‌شود.
        $path = $urlParts['path'] ?? '/';
        // در صورت وجود query_params، اضافه کردن ? به URL.
        return $urlParts['scheme'] . '://' . $urlParts['host'] . $path . ($query_params ? '?' . $query_params : '');
    }

    /**
     * ساخت محتوای بدنه (body) بر اساس فرمت مشخص.
     *
     * @param string $format فرمت داده (مثال: FORMAT_JSON)
     * @param array  $data آرایه داده‌ها
     * @return string محتوای بدنه
     */
    private function parseBody(string $format, array $data) {
        if (count($data) == 0) return '';

        switch ($format) {
            case HttpForm::FORMAT_JSON:
                return json_encode($data);
            default:
                return http_build_query($data);
        }
    }

    /**
     * ثبت یک خطا در آرایه خطاها.
     *
     * @param string $att کلید دسته‌بندی خطا (attribute)
     * @param string $message پیام خطا
     */
    private function addError(string $att, string $message) {
        $this->_errors[$att][] = $message;
    }

    /**
     * دریافت آرایه خطاهای ثبت شده.
     *
     * @return array
     */
    public function errrors() {
        return $this->_errors;
    }

    /**
     * اضافه کردن یک handle به آرایه‌ی handles همراه با فرم مربوطه.
     *
     * @param CurlHandle $ch handle cURL
     * @param HttpForm   $form شیء فرم مربوطه
     */
    private function addHandel(CurlHandle $ch, HttpForm $form) {
        $this->handles[] = [
            'form' => $form,
            'curl' => $ch,
        ];
    }
}
