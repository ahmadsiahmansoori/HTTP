# 🧰 PHP HTTP Client - CURL Wrapper
## کتابخانه‌ای سبک و قابل توسعه برای ارسال درخواست‌های HTTP با استفاده از cURL در PHP. این ابزار با هدف ساده‌سازی مدیریت درخواست‌ها و پاسخ‌ها


```php
    HttpClient::post($url, $payload, [], ['Content-Type' => 'application/json'], HttpForm::FORMAT_JSON);
```



```php
    HttpForm::init($url)
    ->method(HttpForm::METHOD_PATCH)
    ->format(HttpForm::FORMAT_JSON)
    ->header('Content-Type', 'application/json')
    ->payload($payload)
    ->curl();
```