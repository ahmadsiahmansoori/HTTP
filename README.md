# 🧰 PHP HTTP Client - CURL Wrapper

### Features

- Send HTTP Requests Easily
- Multi-Request Support
- Smart Log with HttpLog
- Error Handling

```php
    HttpClient::get($url);
```

```php
    HttpClient::post($url,$queryParams, $payload, $headers, HttpForm::FORMAT_JSON);
```

```php
    HttpForm::init($url)
    ->method(HttpForm::METHOD_PATCH)
    ->format(HttpForm::FORMAT_JSON)
    ->header('Content-Type', 'application/json')
    ->payload($payload)
    ->curl();
```

```php
    // Set HttpForm log (HttpLog)
    // 'only error' logs only errors (default is true for all logs)
    // 'file_name' specifies the log file to save
    activeLog('only error log default true', 'file name create file')
```

```php
    // form (HttpForm)
    return HttpClient::exec(
        $form,
        $form,
        $form,
        $form
    );
```
