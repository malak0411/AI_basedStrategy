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

    public function get($endpoint, $token = null, $params = [])
    {
        return $this->request('GET', $endpoint, $token, $params);
    }

    public function post($endpoint, $data = [], $token = null)
    {
        return $this->request('POST', $endpoint, $token, [], $data);
    }

    public function put($endpoint, $data = [], $token = null)
    {
        return $this->request('PUT', $endpoint, $token, [], $data);
    }

    public function delete($endpoint, $token = null)
    {
        return $this->request('DELETE', $endpoint, $token);
    }

    public function patch($endpoint, $data = [], $token = null)
    {
        return $this->request('PATCH', $endpoint, $token, [], $data);
    }

    private function request($method, $endpoint, $token = null, $params = [], $data = [])
    {
        try {
            $options = [
                'headers' => [
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                ]
            ];

            if ($token) {
                $options['headers']['Authorization'] = 'Bearer ' . $token;
            }

            if (!empty($params)) {
                $options['query'] = $params;
            }

            if (!empty($data) && in_array($method, ['POST', 'PUT', 'PATCH'])) {
                $options['json'] = $data;
            }

            $response = $this->client->request($method, $endpoint, $options);

            $statusCode = $response->getStatusCode();
            $body = json_decode($response->getBody()->getContents(), true);

            if (!is_array($body)) {
                $body = ['success' => ($statusCode >= 200 && $statusCode < 300), 'data' => []];
            }

            $body['status'] = $statusCode;

            return $body;

        } catch (RequestException $e) {
            \Log::error('API Request Error', [
                'method' => $method,
                'endpoint' => $endpoint,
                'error' => $e->getMessage()
            ]);

            if ($e->hasResponse()) {
                $statusCode = $e->getResponse()->getStatusCode();
                $body = json_decode($e->getResponse()->getBody()->getContents(), true);

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

    public function healthCheck()
    {
        try {
            $response = $this->get('/health');
            return ($response['status'] ?? 500) === 200;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function getPaginated($endpoint, $token = null, $page = 1, $perPage = 10)
    {
        return $this->get($endpoint, $token, [
            'page' => $page,
            'per_page' => $perPage
        ]);
    }

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

    public function upload($endpoint, $data, $token = null)
    {
        try {
            $options = [
                'headers' => [
                    'Authorization' => 'Bearer ' . $token,
                ],
                'multipart' => []
            ];

            foreach ($data as $key => $value) {
                if ($value instanceof \Illuminate\Http\UploadedFile) {
                    $options['multipart'][] = [
                        'name' => $key,
                        'contents' => fopen($value->getRealPath(), 'r'),
                        'filename' => $value->getClientOriginalName()
                    ];
                } else {
                    $options['multipart'][] = [
                        'name' => $key,
                        'contents' => $value
                    ];
                }
            }

            $response = $this->client->post($endpoint, $options);
            $body = json_decode($response->getBody()->getContents(), true);

            if (!is_array($body)) {
                return ['success' => false, 'message' => 'استجابة غير صالحة من الخادم'];
            }

            return $body;

        } catch (\Exception $e) {
            \Log::error('Upload Error: ' . $e->getMessage());
            return ['success' => false, 'message' => 'فشل رفع الملف: ' . $e->getMessage()];
        }
    }

    public function download($endpoint, $token = null)
    {
        try {
            $response = Http::withToken($token)->get($this->baseUrl . $endpoint);
            if ($response->successful()) {
                return $response->body();
            }
            return null;
        } catch (\Exception $e) {
            return null;
        }
    }

    public function uploadFile($endpoint, $file, $token = null, $extraData = [])
{
    try {
        $options = [
            'headers' => [
                'Authorization' => 'Bearer ' . $token,
            ],
            'multipart' => []
        ];

        if ($file instanceof \Illuminate\Http\UploadedFile) {
            $options['multipart'][] = [
                'name' => 'file',
                'contents' => fopen($file->getRealPath(), 'r'),
                'filename' => $file->getClientOriginalName()
            ];
        }

        foreach ($extraData as $key => $value) {
            $options['multipart'][] = [
                'name' => $key,
                'contents' => $value
            ];
        }

        $response = $this->client->post($endpoint, $options);
        $body = json_decode($response->getBody()->getContents(), true);

        if (!is_array($body)) {
            return ['success' => false, 'message' => 'استجابة غير صالحة من الخادم'];
        }

        return $body;

    } catch (\Exception $e) {
        \Log::error('Upload Error: ' . $e->getMessage());
        return ['success' => false, 'message' => 'فشل رفع الملف: ' . $e->getMessage()];
    }
}

public function getAttachmentData($attachmentId, $token = null)
{
    return $this->get("/api/tasks/attachments/{$attachmentId}/data", $token);
}


}
