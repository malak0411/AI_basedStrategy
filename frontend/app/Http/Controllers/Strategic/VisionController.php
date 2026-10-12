<?php


namespace App\Http\Controllers\Strategic;


use App\Http\Controllers\Controller;
use App\Services\ApiClient;
use Illuminate\Http\Request;


class VisionController extends Controller
{
    protected $apiClient;


    public function __construct()
    {
        $this->apiClient = new ApiClient();
    }


    public function index()
    {
        $token = session('jwt_token');
        $response = $this->apiClient->get('/api/strategic/vision', $token);
        $vision = $response['data'] ?? [];
        return view('strategic.vision.index', compact('vision'));
    }


    public function edit()
    {
        $token = session('jwt_token');
        $response = $this->apiClient->get('/api/strategic/vision', $token);
        $vision = $response['data'] ?? [];
        return view('strategic.vision.edit', compact('vision'));
    }


    public function update(Request $request)
    {
        $token = session('jwt_token');
        $data = [
            'text' => $request->text,
            'description' => $request->description ?? '',
        ];
        $response = $this->apiClient->put('/api/strategic/vision', $data, $token);
        if ($response['success'] ?? false) {
            return redirect()->route('strategic.vision.index')
                ->with('success', 'تم تحديث الرؤية الاستراتيجية');
        }
        return back()->with('error', $response['detail'] ?? 'فشل تحديث الرؤية')->withInput();
    }
}
