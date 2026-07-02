<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

class ApiClient
{
    protected $client;
    protected $baseUrl;

    public function __construct()
    {
        $this->baseUrl = env('API_BASE_URL', 'http://localhost:8000');
        $this->client = new Client([
            'base_uri' => $this->baseUrl,
            'timeout' => 30.0,
            'http_errors' => false,
        ]);
    }

    /**
     * طلب GET
     */
    public function get($endpoint, $token = null, $params = [])
    {
        return $this->request('GET', $endpoint, $token, $params);
    }

    /**
     * طلب POST
     */
    public function post($endpoint, $data = [], $token = null)
    {
        return $this->request('POST', $endpoint, $token, [], $data);
    }

    /**
     * طلب PUT
     */
    public function put($endpoint, $data = [], $token = null)
    {
        return $this->request('PUT', $endpoint, $token, [], $data);
    }

    /**
     * تنفيذ الطلب
     */
    private function request($method, $endpoint, $token = null, $params = [], $data = [])
    {
        try {
            $options = [
                'headers' => [
                    'Accept' => 'application/json',
                ]
            ];

            // إضافة التوكن
            if ($token) {
                $options['headers']['Authorization'] = 'Bearer ' . $token;
            }

            // إضافة المعاملات
            if (!empty($params)) {
                $options['query'] = $params;
            }

            // إضافة البيانات
            if (!empty($data)) {
                $options['json'] = $data;
            }

            $response = $this->client->request($method, $endpoint, $options);
            $statusCode = $response->getStatusCode();
            $body = json_decode($response->getBody()->getContents(), true);

            // تأكد من أن النتيجة دائماً مصفوفة
            if (!is_array($body)) {
                $body = ['success' => true, 'data' => []];
            }

            return $body;

        } catch (RequestException $e) {
            \Log::error('API Error: ' . $e->getMessage());
            
            if ($e->hasResponse()) {
                $body = json_decode($e->getResponse()->getBody(), true);
                if (!is_array($body)) {
                    $body = ['detail' => 'خطأ غير معروف'];
                }
                return [
                    'success' => false,
                    'status' => $e->getResponse()->getStatusCode(),
                    'detail' => $body['detail'] ?? 'خطأ في الطلب',
                    'data' => []
                ];
            }

            return [
                'success' => false,
                'status' => 500,
                'detail' => 'خطأ في الاتصال بالخادم',
                'data' => []
            ];
        }
    }
}
