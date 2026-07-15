<?php

namespace App\Http\Controllers\Strategic;

use App\Http\Controllers\Controller;
use App\Services\ApiClient;
use Illuminate\Http\Request;

class PestelController extends Controller
{
    protected $apiClient;

    public function __construct()
    {
        $this->apiClient = new ApiClient();
    }

    public function index()
    {
        $token = session('jwt_token');
        $pestel = $this->apiClient->safeGet('/api/strategic/pestel', $token, []);
        return view('strategic.pestel.index', compact('pestel'));
    }

    public function edit()
    {
        $token = session('jwt_token');
        $pestel = $this->apiClient->safeGet('/api/strategic/pestel', $token, []);
        return view('strategic.pestel.edit', compact('pestel'));
    }

    public function update(Request $request)
    {
        $token = session('jwt_token');
        $data = [
            'political' => $request->political ?? '',
            'economic' => $request->economic ?? '',
            'social' => $request->social ?? '',
            'technological' => $request->technological ?? '',
            'environmental' => $request->environmental ?? '',
            'legal' => $request->legal ?? '',
        ];
        $response = $this->apiClient->put('/api/strategic/pestel', $data, $token);
        if ($response['success'] ?? false) {
            return redirect()->route('strategic.pestel.index')->with('success', 'تم تحديث تحليل PESTEL');
        }
        return back()->with('error', $response['detail'] ?? 'فشل تحديث PESTEL');
    }
}
