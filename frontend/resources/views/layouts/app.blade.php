<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'وزارة النفط والمعادن')</title>


    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">


    <style>
        :root {
            --primary-dark: #0a2e5c;
            --primary: #1a4a8a;
            --primary-light: #2a6ab0;
            --primary-soft: #ebf2fa;
            --gold: #d4af37;
            --gold-light: #e8c84a;
            --gold-dark: #b8932a;
            --gold-soft: #fdf8e7;
            --gray-light: #f4f6fa;
            --gray-border: #e5eaf1;
            --text-dark: #1a2332;
            --text-gray: #64748b;
            --danger: #e53e3e;
            --success: #38a169;
            --warning: #dd6b20;
            --info: #3182ce;
            --purple: #805ad5;
        }


        * { margin: 0; padding: 0; box-sizing: border-box; }


        body {
            font-family: 'Cairo', sans-serif;
            background: var(--gray-light);
            color: var(--text-dark);
            overflow-x: hidden;
            font-size: 14px;
        }


        /* ================= Sidebar ================= */
        .sidebar {
            position: fixed;
            top: 0;
            right: 0;
            width: 280px;
            height: 100vh;
            background: linear-gradient(180deg, var(--primary-dark) 0%, #061a36 100%);
            color: #fff;
            z-index: 1040;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            overflow-y: auto;
            overflow-x: hidden;
            padding: 0;
            box-shadow: -4px 0 20px rgba(0,0,0,0.08);
        }
        .sidebar::-webkit-scrollbar { width: 4px; }
        .sidebar::-webkit-scrollbar-track { background: transparent; }
        .sidebar::-webkit-scrollbar-thumb { background: rgba(212, 175, 55, 0.3); border-radius: 10px; }
        .sidebar::-webkit-scrollbar-thumb:hover { background: var(--gold); }


        .sidebar.collapsed { width: 82px; }


        .sidebar .brand {
            padding: 22px 18px;
            text-align: center;
            border-bottom: 1px solid rgba(212, 175, 55, 0.15);
            background: rgba(0,0,0,0.15);
            position: relative;
        }
        .sidebar .brand::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 20px;
            right: 20px;
            height: 2px;
            background: linear-gradient(90deg, transparent, var(--gold), transparent);
            opacity: 0.5;
        }
        .sidebar .brand-logo {
            width: 50px; height: 50px; border-radius: 14px;
            background: linear-gradient(135deg, var(--gold) 0%, var(--gold-dark) 100%);
            color: var(--primary-dark);
            display: flex; align-items: center; justify-content: center;
            font-weight: 800; font-size: 22px;
            margin: 0 auto 10px;
            box-shadow: 0 4px 12px rgba(212, 175, 55, 0.3);
            transition: all 0.3s ease;
        }
        .sidebar.collapsed .brand-logo { width: 42px; height: 42px; font-size: 18px; }
        .sidebar .brand h4 {
            color: #fff;
            font-weight: 700;
            margin: 0;
            font-size: 16px;
            letter-spacing: 0.3px;
            transition: all 0.3s ease;
        }
        .sidebar.collapsed .brand h4 {
            opacity: 0;
            height: 0;
            overflow: hidden;
        }
        .sidebar .brand small {
            color: var(--gold);
            font-size: 11px;
            display: block;
            margin-top: 3px;
            font-weight: 500;
            letter-spacing: 0.5px;
            transition: all 0.3s ease;
        }
        .sidebar.collapsed .brand small {
            opacity: 0;
            height: 0;
            overflow: hidden;
        }


        .sidebar .nav { padding: 12px 12px 100px 12px; }


        .sidebar .nav-section {
            color: rgba(212, 175, 55, 0.75);
            font-size: 10px;
            font-weight: 700;
            padding: 18px 16px 8px 16px;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            position: relative;
            transition: all 0.3s ease;
        }
        .sidebar .nav-section::before {
            content: '';
            display: inline-block;
            width: 3px; height: 3px;
            background: var(--gold);
            border-radius: 50%;
            margin-left: 6px;
            vertical-align: middle;
            opacity: 0.6;
        }
        .sidebar.collapsed .nav-section {
            text-align: center;
            padding: 18px 0 8px 0;
            font-size: 0;
        }
        .sidebar.collapsed .nav-section::before {
            width: 6px; height: 6px;
            margin: 0;
        }


        .sidebar .nav-link {
            color: rgba(255,255,255,0.72);
            padding: 11px 14px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            gap: 12px;
            transition: all 0.2s ease;
            text-decoration: none;
            font-weight: 500;
            font-size: 13.5px;
            cursor: pointer;
            border: none;
            background: none;
            width: 100%;
            text-align: right;
            margin-bottom: 2px;
            position: relative;
        }
        .sidebar .nav-link:hover {
            background: rgba(212, 175, 55, 0.12);
            color: #fff;
            transform: translateX(-3px);
        }
        .sidebar .nav-link.active {
            background: linear-gradient(90deg, rgba(212, 175, 55, 0.25) 0%, rgba(212, 175, 55, 0.08) 100%);
            color: var(--gold);
            font-weight: 600;
        }
        .sidebar .nav-link.active::before {
            content: '';
            position: absolute;
            right: 0; top: 20%;
            width: 3px; height: 60%;
            background: var(--gold);
            border-radius: 3px 0 0 3px;
        }
        .sidebar .nav-link i {
            width: 22px;
            text-align: center;
            font-size: 15px;
            color: rgba(255,255,255,0.55);
            transition: all 0.2s ease;
            flex-shrink: 0;
        }
        .sidebar .nav-link:hover i { color: var(--gold); }
        .sidebar .nav-link.active i { color: var(--gold); }


        .sidebar .nav-text {
            transition: opacity 0.2s ease, width 0.2s ease;
            white-space: nowrap;
            overflow: hidden;
        }
        .sidebar.collapsed .nav-text {
            opacity: 0;
            width: 0;
            display: none;
        }
        .sidebar.collapsed .nav-link {
            justify-content: center;
            padding: 12px 0;
            gap: 0;
        }
        .sidebar.collapsed .nav-link:hover { transform: none; }
        .sidebar.collapsed .nav-link.active::before {
            right: 2px;
        }


        .sidebar .nav-link.logout-btn {
            color: rgba(255, 130, 130, 0.9);
            border-top: 1px solid rgba(255,255,255,0.08);
            margin-top: 15px;
            padding-top: 15px;
            border-radius: 0;
        }
        .sidebar .nav-link.logout-btn:hover {
            background: rgba(229, 62, 62, 0.15);
            color: #fc8181;
            transform: none;
        }
        .sidebar .nav-link.logout-btn i { color: rgba(255, 130, 130, 0.7); }
        .sidebar .nav-link.logout-btn:hover i { color: #fc8181; }


        .sidebar .collapse-btn {
            position: fixed;
            bottom: 24px;
            right: 24px;
            background: linear-gradient(135deg, var(--gold) 0%, var(--gold-dark) 100%);
            border: none;
            color: var(--primary-dark);
            width: 42px;
            height: 42px;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 14px rgba(212, 175, 55, 0.4);
            z-index: 1050;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
        }
        .sidebar .collapse-btn:hover {
            transform: scale(1.08);
            box-shadow: 0 6px 20px rgba(212, 175, 55, 0.5);
        }
        .sidebar.collapsed .collapse-btn {
            right: 20px;
        }


        /* ================= Main Content ================= */
        .main-content {
            margin-right: 280px;
            padding: 22px 28px;
            min-height: 100vh;
            transition: margin-right 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .main-content.expanded { margin-right: 82px; }


        /* ================= Topbar ================= */
        .topbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #fff;
            padding: 16px 26px;
            border-radius: 16px;
            box-shadow: 0 2px 12px rgba(10, 46, 92, 0.05);
            margin-bottom: 24px;
            border: 1px solid var(--gray-border);
            position: relative;
            overflow: hidden;
        }
        .topbar::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 6px;
            height: 100%;
            background: linear-gradient(180deg, var(--gold) 0%, var(--gold-dark) 100%);
        }
        .topbar .page-title {
            font-weight: 700;
            font-size: 20px;
            color: var(--primary-dark);
            margin: 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .topbar .page-title::before {
            content: '';
            width: 4px; height: 22px;
            background: var(--gold);
            border-radius: 2px;
            display: inline-block;
        }
        .topbar .user-info {
            display: flex;
            align-items: center;
            gap: 14px;
        }
        .topbar .user-info .user-details {
            text-align: left;
        }
        .topbar .user-info .user-name {
            font-weight: 700;
            color: var(--text-dark);
            font-size: 14px;
            line-height: 1.3;
        }
        .topbar .user-info .user-role {
            font-size: 11px;
            color: var(--gold-dark);
            font-weight: 600;
            padding: 2px 8px;
            background: var(--gold-soft);
            border-radius: 10px;
            display: inline-block;
            margin-top: 2px;
        }
        .topbar .user-info .avatar {
            width: 44px; height: 44px; border-radius: 12px;
            background: linear-gradient(135deg, var(--primary-dark) 0%, var(--primary-light) 100%);
            color: var(--gold);
            display: flex; align-items: center; justify-content: center;
            font-weight: 700; font-size: 18px;
            box-shadow: 0 4px 10px rgba(10, 46, 92, 0.15);
            border: 2px solid var(--gold);
        }
        .topbar .toggle-sidebar {
            display: none; background: none; border: none;
            font-size: 22px; color: var(--primary-dark); cursor: pointer;
            padding: 8px; border-radius: 10px; transition: all 0.2s;
        }
        .topbar .toggle-sidebar:hover { background: var(--primary-soft); color: var(--primary); }


        /* ================= Responsive ================= */
        @media (max-width: 992px) {
            .sidebar { width: 280px !important; right: -100%; transition: right 0.3s ease; }
            .sidebar.mobile-open { right: 0; }
            .sidebar.mobile-open .nav-text,
            .sidebar.mobile-open .brand h4,
            .sidebar.mobile-open .brand small { opacity: 1; width: auto; display: block; }
            .main-content { margin-right: 0; }
            .topbar .toggle-sidebar { display: block; }
            .sidebar .collapse-btn { display: none; }
        }
        @media (max-width: 768px) {
            .topbar { padding: 14px 18px; }
            .topbar .page-title { font-size: 16px; }
            .topbar .user-info .user-details { display: none; }
            .main-content { padding: 16px; }
        }


        /* ================= Utilities ================= */
        .gold-text { color: var(--gold); }
        .bg-gold { background: var(--gold); color: var(--primary-dark); }


        .btn-gold {
            background: linear-gradient(135deg, var(--gold) 0%, var(--gold-dark) 100%);
            color: var(--primary-dark);
            font-weight: 700;
            border: none;
            padding: 10px 24px;
            border-radius: 10px;
            transition: all 0.3s ease;
            box-shadow: 0 3px 10px rgba(212, 175, 55, 0.25);
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 14px;
        }
        .btn-gold:hover {
            color: #fff;
            transform: translateY(-1px);
            box-shadow: 0 5px 16px rgba(212, 175, 55, 0.4);
            background: linear-gradient(135deg, var(--gold-light) 0%, var(--gold) 100%);
        }


        .card-custom {
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 2px 12px rgba(10, 46, 92, 0.06);
            padding: 22px;
            border: 1px solid var(--gray-border);
            transition: all 0.3s ease;
        }
        .card-custom:hover {
            box-shadow: 0 6px 22px rgba(10, 46, 92, 0.09);
        }


        .stat-card {
            padding: 20px;
            border-radius: 16px;
            background: #fff;
            border-right: 4px solid var(--gold);
            box-shadow: 0 2px 10px rgba(10, 46, 92, 0.05);
            transition: all 0.3s ease;
        }
        .stat-card:hover { transform: translateY(-2px); box-shadow: 0 6px 18px rgba(10, 46, 92, 0.08); }
        .stat-card .number {
            font-size: 28px;
            font-weight: 800;
            color: var(--primary-dark);
            line-height: 1.2;
        }
        .stat-card .label {
            font-size: 13px;
            color: var(--text-gray);
            margin-top: 4px;
            font-weight: 500;
        }
        .stat-card .icon {
            font-size: 30px;
            color: var(--gold);
            opacity: 0.35;
        }


        /* Scrollbar */
        ::-webkit-scrollbar { width: 8px; height: 8px; }
        ::-webkit-scrollbar-track { background: var(--gray-light); }
        ::-webkit-scrollbar-thumb {
            background: linear-gradient(180deg, var(--primary-light) 0%, var(--primary) 100%);
            border-radius: 10px;
        }
        ::-webkit-scrollbar-thumb:hover { background: var(--primary-dark); }


        /* Overlay for mobile */
        .sidebar-overlay {
            display: none;
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(10, 46, 92, 0.5);
            z-index: 1039;
            backdrop-filter: blur(2px);
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        .sidebar-overlay.show { display: block; opacity: 1; }
    </style>


    @stack('styles')
</head>
<body>


    <div class="sidebar-overlay" id="sidebarOverlay"></div>


    <aside class="sidebar" id="sidebar">


        <div class="brand">
            <div class="brand-logo">ن</div>
            <h4>وزارة النفط والمعادن</h4>
            <small>نظام إدارة الاستراتيجية</small>
        </div>


        <nav class="nav flex-column">
            @php
                $userRole = session('user_role', 'employee');
                $isMinister = in_array($userRole, ['minister', 'deputy', 'general_manager']);
                $isManager = ($userRole === 'manager');
                $isSuperAdmin = ($userRole === 'super_admin');
            @endphp


            {{-- الرئيسية --}}
            <div class="nav-section">الرئيسية</div>
            <a class="nav-link {{ request()->routeIs('dashboard.employee') ? 'active' : '' }}" href="{{ route('dashboard.employee') }}">
                <i class="fas fa-user"></i><span class="nav-text">لوحة الموظف</span>
            </a>
            @if($isManager || $isMinister || $isSuperAdmin)
            <a class="nav-link {{ request()->routeIs('dashboard.manager') ? 'active' : '' }}" href="{{ route('dashboard.manager') }}">
                <i class="fas fa-user-tie"></i><span class="nav-text">لوحة المدير</span>
            </a>
            @endif
            @if($isMinister || $isSuperAdmin)
            <a class="nav-link {{ request()->routeIs('dashboard.minister') ? 'active' : '' }}" href="{{ route('dashboard.minister') }}">
                <i class="fas fa-crown"></i><span class="nav-text">لوحة الوزير</span>
            </a>
            @endif


            {{-- العمليات --}}
            <div class="nav-section">العمليات</div>
            <a class="nav-link {{ request()->routeIs('tasks.*') ? 'active' : '' }}" href="{{ route('tasks.index') }}">
                <i class="fas fa-tasks"></i><span class="nav-text">المهام</span>
            </a>


            {{-- الاستراتيجية --}}
            @if($isMinister || $isManager || $isSuperAdmin)
            <div class="nav-section">الاستراتيجية</div>
            <a class="nav-link {{ request()->routeIs('strategic.vision.*') ? 'active' : '' }}" href="{{ route('strategic.vision.index') }}">
                <i class="fas fa-eye"></i><span class="nav-text">الرؤية</span>
            </a>
            <a class="nav-link {{ request()->routeIs('strategic.pillars.*') ? 'active' : '' }}" href="{{ route('strategic.pillars.index') }}">
                <i class="fas fa-chess-queen"></i><span class="nav-text">الركائز</span>
            </a>
            <a class="nav-link {{ request()->routeIs('strategic.goals.*') ? 'active' : '' }}" href="{{ route('strategic.goals.index') }}">
                <i class="fas fa-bullseye"></i><span class="nav-text">الأهداف</span>
            </a>
            <a class="nav-link {{ request()->routeIs('strategic.programs.*') ? 'active' : '' }}" href="{{ route('strategic.programs.index') }}">
                <i class="fas fa-project-diagram"></i><span class="nav-text">البرامج</span>
            </a>
            <a class="nav-link {{ request()->routeIs('strategic.initiatives.*') ? 'active' : '' }}" href="{{ route('strategic.initiatives.index') }}">
                <i class="fas fa-lightbulb"></i><span class="nav-text">المبادرات</span>
            </a>
            <a class="nav-link {{ request()->routeIs('strategic.swot.*') ? 'active' : '' }}" href="{{ route('strategic.swot.index') }}">
                <i class="fas fa-chess-board"></i><span class="nav-text">تحليل SWOT</span>
            </a>
            <a class="nav-link {{ request()->routeIs('strategic.pestel.*') ? 'active' : '' }}" href="{{ route('strategic.pestel.index') }}">
                <i class="fas fa-globe"></i><span class="nav-text">تحليل PESTEL</span>
            </a>
            @endif


            {{-- المتابعة --}}
            @if($isMinister || $isManager || $isSuperAdmin)
            <div class="nav-section">المتابعة</div>
            <a class="nav-link {{ request()->routeIs('kpis.*') ? 'active' : '' }}" href="{{ route('kpis.index') }}">
                <i class="fas fa-chart-line"></i><span class="nav-text">مؤشرات الأداء</span>
            </a>
            <a class="nav-link {{ request()->routeIs('budget.*') ? 'active' : '' }}" href="{{ route('budget.index') }}">
                <i class="fas fa-money-bill-wave"></i><span class="nav-text">الميزانية</span>
            </a>
            <a class="nav-link {{ request()->routeIs('risks.*') ? 'active' : '' }}" href="{{ route('risks.index') }}">
                <i class="fas fa-exclamation-triangle"></i><span class="nav-text">المخاطر</span>
            </a>
            <a class="nav-link" href="{{ route('operational.kanban') }}">
                <i class="fas fa-columns"></i><span class="nav-text">لوحة المهام</span>
            </a>
            @endif


            {{-- الذكاء الاصطناعي --}}
            @if($isMinister || $isManager || $isSuperAdmin)
            <div class="nav-section">ذكاء اصطناعي</div>
            <a class="nav-link {{ request()->routeIs('ai.dashboard') ? 'active' : '' }}" href="{{ route('ai.dashboard') }}">
                <i class="fas fa-brain"></i><span class="nav-text">لوحة AI</span>
            </a>
            <a class="nav-link {{ request()->routeIs('ai.strategic.*') ? 'active' : '' }}" href="{{ route('ai.strategic.index') }}">
                <i class="fas fa-robot"></i><span class="nav-text">توليد المهام الرئيسية</span>
            </a>
            <a class="nav-link {{ request()->routeIs('ai.models.*') ? 'active' : '' }}" href="{{ route('ai.models') }}">
                <i class="fas fa-microchip"></i><span class="nav-text">النماذج</span>
            </a>
            <a class="nav-link {{ request()->routeIs('ai.recommendations.*') ? 'active' : '' }}" href="{{ route('ai.recommendations') }}">
                <i class="fas fa-lightbulb"></i><span class="nav-text">التوصيات</span>
            </a>
            <a class="nav-link" href="{{ route('operational.major-tasks') }}">
                <i class="fas fa-tasks"></i><span class="nav-text">المهام التشغيلية</span>
            </a>
            @endif


            {{-- التقارير --}}
            <div class="nav-section">تقارير</div>
            <a class="nav-link {{ request()->routeIs('reports.*') ? 'active' : '' }}" href="{{ route('reports.index') }}">
                <i class="fas fa-file-alt"></i><span class="nav-text">التقارير</span>
            </a>


            {{-- حسابي --}}
            <div class="nav-section">حسابي</div>
            <a class="nav-link {{ request()->routeIs('profile.*') ? 'active' : '' }}" href="{{ route('profile.index') }}">
                <i class="fas fa-user-circle"></i><span class="nav-text">الملف الشخصي</span>
            </a>
            <a class="nav-link {{ request()->routeIs('password.change') ? 'active' : '' }}" href="{{ route('password.change') }}">
                <i class="fas fa-key"></i><span class="nav-text">تغيير كلمة المرور</span>
            </a>


            {{-- تتبع المواقع --}}
            @if($isManager || $isMinister || $isSuperAdmin)
            <div class="nav-section">متابعة</div>
            <a class="nav-link {{ request()->routeIs('location.*') ? 'active' : '' }}" href="{{ route('location.index') }}">
                <i class="fas fa-map-marker-alt"></i><span class="nav-text">تتبع المواقع</span>
            </a>
            @endif


            {{-- إدارة النظام --}}
            @if($isSuperAdmin)
            <div class="nav-section">إدارة النظام</div>
            <a class="nav-link {{ request()->routeIs('admin.employees.*') ? 'active' : '' }}" href="{{ route('admin.employees.index') }}">
                <i class="fas fa-users"></i><span class="nav-text">الموظفين</span>
            </a>
            <a class="nav-link {{ request()->routeIs('admin.departments.*') ? 'active' : '' }}" href="{{ route('admin.departments.index') }}">
                <i class="fas fa-building"></i><span class="nav-text">الإدارات</span>
            </a>
            <a class="nav-link {{ request()->routeIs('admin.roles.*') ? 'active' : '' }}" href="{{ route('admin.roles.index') }}">
                <i class="fas fa-user-tag"></i><span class="nav-text">الأدوار</span>
            </a>
            <a class="nav-link {{ request()->routeIs('admin.permissions.*') ? 'active' : '' }}" href="{{ route('admin.permissions.index') }}">
                <i class="fas fa-shield-alt"></i><span class="nav-text">الصلاحيات</span>
            </a>
            <a class="nav-link {{ request()->routeIs('admin.employee-roles.*') ? 'active' : '' }}" href="{{ route('admin.employee-roles.index') }}">
                <i class="fas fa-user-check"></i><span class="nav-text">ربط الموظفين</span>
            </a>
            <a class="nav-link {{ request()->routeIs('admin.role-permissions.*') ? 'active' : '' }}" href="{{ route('admin.role-permissions.index') }}">
                <i class="fas fa-link"></i><span class="nav-text">ربط الصلاحيات</span>
            </a>
            <a class="nav-link {{ request()->routeIs('admin.audit-logs.*') ? 'active' : '' }}" href="{{ route('admin.audit-logs.index') }}">
                <i class="fas fa-history"></i><span class="nav-text">سجل التدقيق</span>
            </a>
            <a class="nav-link {{ request()->routeIs('admin.settings.*') ? 'active' : '' }}" href="{{ route('admin.settings.index') }}">
                <i class="fas fa-cogs"></i><span class="nav-text">تهيئة النظام</span>
            </a>


            <div class="nav-section">القواميس</div>
            <a class="nav-link" href="{{ route('admin.dictionaries.statuses') }}">
                <i class="fas fa-tag"></i><span class="nav-text">الحالات</span>
            </a>
            <a class="nav-link" href="{{ route('admin.dictionaries.priorities') }}">
                <i class="fas fa-sort-amount-up"></i><span class="nav-text">الأولويات</span>
            </a>
            <a class="nav-link" href="{{ route('admin.dictionaries.risk-levels') }}">
                <i class="fas fa-exclamation-circle"></i><span class="nav-text">مستويات المخاطر</span>
            </a>
            <a class="nav-link" href="{{ route('admin.dictionaries.role-types') }}">
                <i class="fas fa-user-tag"></i><span class="nav-text">أنواع الأدوار</span>
            </a>
            <a class="nav-link" href="{{ route('admin.dictionaries.transaction-types') }}">
                <i class="fas fa-exchange-alt"></i><span class="nav-text">أنواع المعاملات</span>
            </a>
            @endif


            {{-- تسجيل الخروج --}}
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="nav-link logout-btn">
                    <i class="fas fa-sign-out-alt"></i><span class="nav-text">تسجيل الخروج</span>
                </button>
            </form>
        </nav>
    </aside>


    <button class="collapse-btn" id="collapseBtn" title="تصغير القائمة">
        <i class="fas fa-chevron-left" id="collapseIcon"></i>
    </button>


    <div class="main-content" id="mainContent">
        <div class="topbar">
            <button class="toggle-sidebar" id="mobileToggle">
                <i class="fas fa-bars"></i>
            </button>
            <h5 class="page-title">@yield('title', 'لوحة التحكم')</h5>
            <div class="user-info">
                <div class="user-details">
                    <div class="user-name">{{ session('user_name', 'مستخدم') }}</div>
                    <div class="user-role">{{ session('user_role', 'employee') }}</div>
                </div>
                <div class="avatar">{{ mb_substr(session('user_name', 'م'), 0, 1) }}</div>
            </div>
        </div>


        @yield('content')
    </div>


    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const sidebar = document.getElementById('sidebar');
        const mainContent = document.getElementById('mainContent');
        const collapseBtn = document.getElementById('collapseBtn');
        const collapseIcon = document.getElementById('collapseIcon');
        const sidebarOverlay = document.getElementById('sidebarOverlay');
        let collapsed = localStorage.getItem('sidebar_collapsed') === 'true';


        function toggleSidebar() {
            collapsed = !collapsed;
            sidebar.classList.toggle('collapsed', collapsed);
            mainContent.classList.toggle('expanded', collapsed);
            collapseIcon.className = collapsed ? 'fas fa-chevron-right' : 'fas fa-chevron-left';
            localStorage.setItem('sidebar_collapsed', collapsed);
        }


        collapseBtn.addEventListener('click', toggleSidebar);


        if (collapsed) {
            sidebar.classList.add('collapsed');
            mainContent.classList.add('expanded');
            collapseIcon.className = 'fas fa-chevron-right';
        }


        document.getElementById('mobileToggle').addEventListener('click', function() {
            sidebar.classList.toggle('mobile-open');
            sidebarOverlay.classList.toggle('show');
        });


        sidebarOverlay.addEventListener('click', function() {
            sidebar.classList.remove('mobile-open');
            sidebarOverlay.classList.remove('show');
        });


        document.addEventListener('click', function(e) {
            if (window.innerWidth <= 992) {
                const inside = sidebar.contains(e.target) || e.target.closest('.toggle-sidebar');
                if (!inside && sidebar.classList.contains('mobile-open')) {
                    sidebar.classList.remove('mobile-open');
                    sidebarOverlay.classList.remove('show');
                }
            }
        });
    </script>


    @stack('scripts')
</body>
</html>
