<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\ApiClient;
use Illuminate\Http\Request;

class RiskController extends Controller
{
    protected $apiClient;

    public function __construct()
    {
        $this->apiClient = new ApiClient();
    }

    public function index()
    {
        $token = session('jwt_token');
        $response = $this->apiClient->get('/api/risks', $token);
        $risks = $response['data'] ?? [];

        return view('risks.index', compact('risks'));
    }

    public function create()
    {
        return view('risks.create');
    }

    public function store(Request $request)
    {
        $token = session('jwt_token');
        $response = $this->apiClient->post('/api/risks', $request->all(), $token);

        if ($response['success']) {
            return redirect()->route('risks.index')->with('success', 'تم تسجيل الخطر بنجاح');
        }
        return back()->with('error', $response['detail'] ?? 'فشل تسجيل الخطر')->withInput();
    }

    public function show($id)
    {
        $token = session('jwt_token');
        $response = $this->apiClient->get("/api/risks/{$id}", $token);
        $risk = $response['data'] ?? [];

        if (empty($risk)) {
            return redirect()->route('risks.index')->with('error', 'الخطر غير موجود');
        }

        return view('risks.show', compact('risk'));
    }

    public function edit($id)
    {
        $token = session('jwt_token');
        $response = $this->apiClient->get("/api/risks/{$id}", $token);
        $risk = $response['data'] ?? [];

        if (empty($risk)) {
            return redirect()->route('risks.index')->with('error', 'الخطر غير موجود');
        }

        return view('risks.edit', compact('risk'));
    }

    public function update(Request $request, $id)
    {
        $token = session('jwt_token');
        $response = $this->apiClient->put("/api/risks/{$id}", $request->all(), $token);

        if ($response['success']) {
            return redirect()->route('risks.show', $id)->with('success', 'تم تحديث الخطر بنجاح');
        }
        return back()->with('error', $response['detail'] ?? 'فشل تحديث الخطر');
    }

    public function mitigations($id)
    {
        $token = session('jwt_token');
        $riskResponse = $this->apiClient->get("/api/risks/{$id}", $token);
        $risk = $riskResponse['data'] ?? [];
        $response = $this->apiClient->get("/api/risks/{$id}/mitigations", $token);
        $mitigations = $response['data'] ?? [];

        return view('risks.mitigations', compact('risk', 'mitigations', 'id'));
    }

    public function createMitigation($id)
    {
        return view('risks.mitigations-create', compact('id'));
    }

    public function storeMitigation(Request $request, $id)
    {
        $token = session('jwt_token');
        $response = $this->apiClient->post("/api/risks/{$id}/mitigations", $request->all(), $token);

        if ($response['success']) {
            return redirect()->route('risks.mitigations', $id)->with('success', 'تم إضافة خطة التخفيف بنجاح');
        }
        return back()->with('error', $response['detail'] ?? 'فشل إضافة الخطة')->withInput();
    }
}
