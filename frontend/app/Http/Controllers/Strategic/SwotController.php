<?php

namespace App\Http\Controllers\Strategic;

use App\Http\Controllers\Controller;
use App\Services\ApiClient;
use Illuminate\Http\Request;

class SwotController extends Controller
{
    protected $apiClient;

    public function __construct()
    {
        $this->apiClient = new ApiClient();
    }

    public function index()
    {
        $token = session('jwt_token');
        $swot = $this->apiClient->safeGet('/api/strategic/swot', $token, []);
        return view('strategic.swot.index', compact('swot'));
    }

    public function edit()
    {
        $token = session('jwt_token');
        $swot = $this->apiClient->safeGet('/api/strategic/swot', $token, []);
        return view('strategic.swot.edit', compact('swot'));
    }

    public function update(Request $request)
    {
        $token = session('jwt_token');
        $data = [
            'strengths' => $request->strengths ?? '',
            'weaknesses' => $request->weaknesses ?? '',
            'opportunities' => $request->opportunities ?? '',
            'threats' => $request->threats ?? '',
        ];
        $response = $this->apiClient->put('/api/strategic/swot', $data, $token);
        if ($response['success'] ?? false) {
            return redirect()->route('strategic.swot.index')->with('success', 'تم تحديث تحليل SWOT');
        }
        return back()->with('error', $response['detail'] ?? 'فشل تحديث SWOT');
    }
}
