<?php


namespace App\Http\Controllers\Strategic;


use App\Http\Controllers\Controller;
use App\Services\ApiClient;
use Illuminate\Http\Request;


class PillarController extends Controller
{
    protected $apiClient;


    public function __construct()
    {
        $this->apiClient = new ApiClient();
    }


    public function index()
    {
        $token = session('jwt_token');
        $response = $this->apiClient->get('/api/strategic/pillars', $token);
        $pillars = $response['data'] ?? [];
        return view('strategic.pillars.index', compact('pillars'));
    }


    public function create()
    {
        return view('strategic.pillars.create');
    }


    public function store(Request $request)
    {
        $token = session('jwt_token');
        $data = [
            'name' => $request->name,
            'description' => $request->description ?? '',
            'order_index' => (int) ($request->order_index ?? 0),
            'is_active' => true,
        ];
        $response = $this->apiClient->post('/api/strategic/pillars', $data, $token);
        if ($response['success'] ?? false) {
            return redirect()->route('strategic.pillars.index')
                ->with('success', 'تم إنشاء الركيزة');
        }
        return back()->with('error', $response['detail'] ?? 'فشل إنشاء الركيزة')->withInput();
    }


    public function show($id)
    {
        $token = session('jwt_token');


        $response = $this->apiClient->get("/api/strategic/pillars/{$id}", $token);
        $pillar = $response['data'] ?? [];


        if (empty($pillar)) {
            return redirect()->route('strategic.pillars.index')
                ->with('error', 'الركيزة غير موجودة');
        }


        $goals = $pillar['goals'] ?? [];


        return view('strategic.pillars.show', compact('pillar', 'goals'));
    }


    public function edit($id)
    {
        $token = session('jwt_token');
        $response = $this->apiClient->get("/api/strategic/pillars/{$id}", $token);
        $pillar = $response['data'] ?? [];


        if (empty($pillar)) {
            return redirect()->route('strategic.pillars.index')
                ->with('error', 'الركيزة غير موجودة');
        }


        return view('strategic.pillars.edit', compact('pillar'));
    }


    public function update(Request $request, $id)
    {
        $token = session('jwt_token');
        $data = [
            'name' => $request->name,
            'description' => $request->description ?? '',
            'order_index' => (int) ($request->order_index ?? 0),
            'is_active' => true,
        ];
        $response = $this->apiClient->put("/api/strategic/pillars/{$id}", $data, $token);
        if ($response['success'] ?? false) {
            return redirect()->route('strategic.pillars.show', $id)
                ->with('success', 'تم تحديث الركيزة');
        }
        return back()->with('error', $response['detail'] ?? 'فشل تحديث الركيزة');
    }
}
