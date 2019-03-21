<?php

class Kraken
{
    protected $auth = [];
    public static $kraken_plugin_version = '2.6.3';

    public function __construct($key = '', $secret = '')
    {
        $this->auth = [
            'auth' => [
                'api_key' => $key,
                'api_secret' => $secret,
            ],
        ];
    }

    public function url($opts = [])
    {
        $data = json_encode(array_merge($this->auth, $opts));
        $response = self::request($data, 'https://api.kraken.io/v1/url', 'url');

        return $response;
    }

    public function upload($opts = [])
    {
        if (!isset($opts['file'])) {
            return [
                'success' => false,
                'error' => 'File parameter was not provided',
            ];
        }

        if (!file_exists($opts['file'])) {
            return [
                'success' => false,
                'error' => 'File `'.$opts['file'].'` does not exist',
            ];
        }

        if (class_exists('CURLFile')) {
            $file = new CURLFile($opts['file']);
        } else {
            $file = '@'.$opts['file'];
        }

        unset($opts['file']);

        $data = array_merge([
            'file' => $file,
            'data' => json_encode(array_merge($this->auth, $opts)),
        ]);
        $response = self::request($data, 'https://api.kraken.io/v1/upload', 'upload');

        return $response;
    }

    public function status()
    {
        $data = ['auth' => [
            'api_key' => $this->auth['auth']['api_key'],
            'api_secret' => $this->auth['auth']['api_secret'],
        ]];
        $response = self::request(json_encode($data), 'https://api.kraken.io/user_status', 'url');

        return $response;
    }

    private function request($data, $url, $type)
    {
        $curl = curl_init($url);

        if ('url' === $type) {
            curl_setopt($curl, \CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
            ]);
        }

        // Force continue-100 from server
        curl_setopt($curl, \CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 6.1; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/40.0.2214.85 Safari/537.36');
        curl_setopt($curl, \CURLOPT_HTTPHEADER, ['Expect:']);
        curl_setopt($curl, \CURLOPT_POST, 1);
        curl_setopt($curl, \CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, \CURLOPT_POSTFIELDS, $data);
        curl_setopt($curl, \CURLOPT_CONNECTTIMEOUT, 0);
        curl_setopt($curl, \CURLOPT_TIMEOUT, 400);
        curl_setopt($curl, \CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($curl, \CURLOPT_SSL_VERIFYHOST, 0);

        $response = json_decode(curl_exec($curl), true);
        $error = curl_errno($curl);

        if (null === $response) {
            $error = curl_error($curl);
            $error_code = curl_errno($curl);
            $response = [
                'success' => false,
                'error' => 'cURL Error: '.$error,
                'code' => $error_code,
            ];
        }
        curl_close($curl);

        return $response;
    }
}
