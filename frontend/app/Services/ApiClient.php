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
     * طلب DELETE
     */
    public function delete($endpoint, $token = null)
    {
        return $this->request('DELETE', $endpoint, $token);
    }

    /**
     * طلب PATCH
     */
    public function patch($endpoint, $data = [], $token = null)
    {
        return $this->request('PATCH', $endpoint, $token, [], $data);
    }

    /**
     * تنفيذ الطلب مع معالجة الأخطاء
     */
    private function request($method, $endpoint, $token = null, $params = [], $data = [])
    {
        try {
            $options = [
                'headers' => [
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                ]
            ];

            // إضافة التوكن إذا وجد
            if ($token) {
                $options['headers']['Authorization'] = 'Bearer ' . $token;
            }

            // إضافة المعاملات إذا وجدت
            if (!empty($params)) {
                $options['query'] = $params;
            }

            // إضافة البيانات للطرق التي تدعم body
            if (!empty($data) && in_array($method, ['POST', 'PUT', 'PATCH'])) {
                $options['json'] = $data;
            }

            // تنفيذ الطلب
            $response = $this->client->request($method, $endpoint, $options);

            // معالجة الاستجابة
            $statusCode = $response->getStatusCode();
            $body = json_decode($response->getBody()->getContents(), true);

            // التأكد من أن الاستجابة مصفوفة
            if (!is_array($body)) {
                $body = ['success' => ($statusCode >= 200 && $statusCode < 300), 'data' => []];
            }

            // إضافة status code للاستجابة
            $body['status'] = $statusCode;

            return $body;

        } catch (RequestException $e) {
            \Log::error('API Request Error', [
                'method' => $method,
                'endpoint' => $endpoint,
                'error' => $e->getMessage()
            ]);

            // معالجة استجابة الخطأ
            if ($e->hasResponse()) {
                $statusCode = $e->getResponse()->getStatusCode();
                $body = json_decode($e->getResponse()->getBody()->getContents(), true);

                // إذا كان التوكن منتهي الصلاحية
                if ($statusCode === 401) {
                    session()->forget(['jwt_token', 'user_role', 'user_name']);
                    return [
                        'success' => false,
                        'status' => 401,
                        'detail' => 'انتهت صلاحية الجلسة، يرجى تسجيل الدخول مرة أخرى',
                        'data' => []
                    ];
                }

                return [
                    'success' => false,
                    'status' => $statusCode,
                    'detail' => $body['detail'] ?? $body['message'] ?? 'حدث خطأ في الطلب',
                    'data' => $body['data'] ?? []
                ];
            }

            // خطأ في الاتصال
            return [
                'success' => false,
                'status' => 500,
                'detail' => 'خطأ في الاتصال بالخادم. تأكد من أن الخادم يعمل.',
                'data' => []
            ];
        } catch (\Exception $e) {
            \Log::error('API Unexpected Error', [
                'method' => $method,
                'endpoint' => $endpoint,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'status' => 500,
                'detail' => 'حدث خطأ غير متوقع: ' . $e->getMessage(),
                'data' => []
            ];
        }
    }

    /**
     * التحقق من حالة الاتصال بالـ API
     */
    public function healthCheck()
    {
        try {
            $response = $this->get('/health');
            return ($response['status'] ?? 500) === 200;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * جلب البيانات مع pagination
     */
    public function getPaginated($endpoint, $token = null, $page = 1, $perPage = 10)
    {
        return $this->get($endpoint, $token, [
            'page' => $page,
            'per_page' => $perPage
        ]);
    }

    /**
     * رفع ملف
     */
    public function uploadFile($endpoint, $filePath, $token = null, $extraData = [])
    {
        try {
            $options = [
                'headers' => [
                    'Authorization' => 'Bearer ' . $token,
                ],
                'multipart' => [
                    [
                        'name' => 'file',
                        'contents' => fopen($filePath, 'r'),
                        'filename' => basename($filePath),
                    ],
                ],
            ];

            // إضافة بيانات إضافية
            foreach ($extraData as $key => $value) {
                $options['multipart'][] = [
                    'name' => $key,
                    'contents' => $value,
                ];
            }

            $response = $this->client->post($endpoint, $options);
            return json_decode($response->getBody()->getContents(), true);

        } catch (\Exception $e) {
            \Log::error('File Upload Error: ' . $e->getMessage());
            return [
                'success' => false,
                'detail' => 'فشل رفع الملف: ' . $e->getMessage()
            ];
        }
    }

    /**
     * جلب البيانات مع معالجة آمنة للأخطاء
     */
    public function safeGet($endpoint, $token = null, $params = [], $default = [])
    {
        try {
            $response = $this->get($endpoint, $token, $params);
            if ($response['success'] ?? false) {
                return $response['data'] ?? $default;
            }
            return $default;
        } catch (\Exception $e) {
            return $default;
        }
    }
}
