<?php 

require_once './src/Http.php';
require_once './src/HttpForm.php';
require_once './src/HttpResult.php';
require_once './src/HttpClient.php';


class TestCase {

    const BASE_URL = 'https://jsonplaceholder.typicode.com';

    /**
     * result Get https://jsonplaceholder.typicode.com/post/1
     * {
        "userId": 1,
        "id": 1,
        "title": "sunt aut facere repellat provident occaecati excepturi optio reprehenderit",
        "body": "quia et suscipit\nsuscipit recusandae consequuntur expedita et cum\nreprehenderit molestiae ut ut quas totam\nnostrum rerum est autem sunt rem eveniet architecto"
       }
     */
    static function testGet() {
        $url = self::BASE_URL . '/posts/1';
        return HttpClient::get($url);

    }

    static function testPost() {
        $url = self::BASE_URL . '/posts';
        $payload = ['title' => 'foo', 'body' => 'bar', 'userId' => 1];
        return HttpClient::post($url, $payload, [], ['Content-Type' => 'application/json'], HttpForm::FORMAT_JSON);

    }


    static function testPatch() {
        $url = self::BASE_URL . '/posts/1';
        $payload = ['title' => 'patched title new'];
        return HttpForm::init($url)
            ->method(HttpForm::METHOD_PATCH)
            ->format(HttpForm::FORMAT_JSON)
            ->header('Content-Type', 'application/json')
            ->payload($payload)
            ->curl();
    }


    static function testPut() {
        $url = self::BASE_URL . '/posts/1';
        $payload = ['id' => 1, 'title' => 'replaced', 'body' => 'content', 'userId' => 1];
        return HttpForm::init($url)
            ->method(HttpForm::METHOD_PUT)
            ->format(HttpForm::FORMAT_JSON)
            ->header('Content-Type', 'application/json')
            ->payload($payload)
            ->curl();
    }


    static function testDelete() {
        $url = self::BASE_URL . '/posts/1';
    
        return HttpForm::init($url)
            ->method(HttpForm::METHOD_DELETE)
            ->curl();
    }


    static function testMultiCurl() {


        $form1 = HttpForm::init(self::BASE_URL . '/posts/1')->method(HttpForm::METHOD_GET);
        $form2 = HttpForm::init(self::BASE_URL . '/users/1')->method(HttpForm::METHOD_GET);
        $form3 = HttpForm::init(self::BASE_URL . '/todos/1')->method(HttpForm::METHOD_GET);

        $url = self::BASE_URL . '/posts';
        $payload = ['title' => 'foo', 'body' => 'bar', 'userId' => 1];
        $form4 = HttpForm::init($url)->method(HttpForm::METHOD_POST)->payload($payload)->format(HttpForm::FORMAT_JSON)->header('Content-Type', 'application/json');

        return HttpClient::exec(
            $form1,
            $form2,
            $form3,
            $form4
        );
    }
 
    
}