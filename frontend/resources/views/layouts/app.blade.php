<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'وزارة النفط والمعادن')</title>

    <!-- Google Fonts - Cairo -->
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Bootstrap 5 RTL -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css">
    
    <!-- Font Awesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Custom CSS -->
    <style>
        :root {
            --primary-dark: #0a2e5c;
            --primary: #1a4a8a;
            --primary-light: #2a6ab0;
            --gold: #d4af37;
            --gold-light: #e8c84a;
            --gold-dark: #c8a23b;
            --gray-light: #f5f7fa;
            --gray-border: #e2e8f0;
            --text-dark: #1a1a2e;
            --text-gray: #4a5568;
            --danger: #e53e3e;
            --success: #38a169;
            --warning: #ed8936;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Cairo', sans-serif;
            background: var(--gray-light);
            color: var(--text-dark);
            overflow-x: hidden;
        }

        /* ================================================================
        SIDEBAR (Right Side)
        ================================================================ */
        .sidebar {
            position: fixed;
            top: 0;
            right: 0;
            width: 280px;
            height: 100vh;
            background: var(--primary-dark);
            color: #fff;
            z-index: 1040;
            transition: all 0.3s ease;
            overflow-y: auto;
            padding: 0;
        }

        .sidebar.collapsed { width: 80px; }

        .sidebar .brand {
            padding: 20px 16px;
            text-align: center;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }

        .sidebar .brand h4 {
            color: var(--gold);
            font-weight: 800;
            margin: 0;
            font-size: 18px;
        }

        .sidebar .brand small {
            color: rgba(255,255,255,0.6);
            font-size: 12px;
            display: block;
            margin-top: 4px;
        }

        .sidebar .nav { padding: 16px 12px; }

        .sidebar .nav-link {
            color: rgba(255,255,255,0.7);
            padding: 12px 16px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            gap: 14px;
            transition: all 0.2s ease;
            text-decoration: none;
            font-weight: 500;
            font-size: 15px;
            cursor: pointer;
            border: none;
            background: none;
            width: 100%;
            text-align: right;
        }

        .sidebar .nav-link:hover {
            background: rgba(212, 175, 55, 0.15);
            color: #fff;
        }

        .sidebar .nav-link.active {
            background: rgba(212, 175, 55, 0.2);
            color: var(--gold);
        }

        .sidebar .nav-link i {
            width: 24px;
            text-align: center;
            font-size: 18px;
            color: rgba(255,255,255,0.6);
        }

        .sidebar .nav-link.active i { color: var(--gold); }

        .sidebar .nav-text { transition: opacity 0.2s ease; }

        .sidebar.collapsed .nav-text {
            opacity: 0;
            width: 0;
            overflow: hidden;
            display: none;
        }

        /* Section Title */
        .sidebar .nav-section {
            color: rgba(255,255,255,0.4);
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            padding: 16px 16px 8px 16px;
            letter-spacing: 1px;
        }

        .sidebar .collapse-btn {
            position: absolute;
            bottom: 80px;
            left: 50%;
            transform: translateX(-50%);
            background: rgba(255,255,255,0.1);
            border: none;
            color: #fff;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .sidebar .collapse-btn:hover {
            background: rgba(212, 175, 55, 0.3);
        }

        /* ================================================================
        MAIN CONTENT
        ================================================================ */
        .main-content {
            margin-right: 280px;
            padding: 20px 30px;
            min-height: 100vh;
            transition: margin-right 0.3s ease;
        }

        .main-content.expanded { margin-right: 80px; }

        /* ================================================================
        TOP BAR
        ================================================================ */
        .topbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #fff;
            padding: 16px 24px;
            border-radius: 16px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.04);
            margin-bottom: 24px;
        }

        .topbar .page-title {
            font-weight: 700;
            font-size: 22px;
            color: var(--primary-dark);
            margin: 0;
        }

        .topbar .user-info {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .topbar .user-info .avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--gold);
            color: var(--primary-dark);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 18px;
        }

        .topbar .user-info .user-name { font-weight: 600; color: var(--text-dark); }
        .topbar .user-info .user-role { font-size: 12px; color: var(--text-gray); }

        .topbar .toggle-sidebar {
            display: none;
            background: none;
            border: none;
            font-size: 24px;
            color: var(--primary-dark);
            cursor: pointer;
        }

        /* ================================================================
        RESPONSIVE
        ================================================================ */
        @media (max-width: 992px) {
            .sidebar {
                width: 280px !important;
                right: -100%;
                transition: right 0.3s ease;
            }
            .sidebar.mobile-open { right: 0; }
            .main-content { margin-right: 0; }
            .topbar .toggle-sidebar { display: block; }
        }

        @media (max-width: 768px) {
            .topbar {
                flex-direction: column;
                align-items: flex-start;
                gap: 12px;
            }
            .topbar .user-info {
                width: 100%;
                justify-content: flex-start;
            }
        }

        /* ================================================================
        UTILITIES
        ================================================================ */
        .gold-text { color: var(--gold); }
        .bg-gold { background: var(--gold); color: var(--primary-dark); }
        .btn-gold {
            background: var(--gold);
            color: var(--primary-dark);
            font-weight: 600;
            border: none;
            padding: 10px 24px;
            border-radius: 10px;
            transition: all 0.3s ease;
        }
        .btn-gold:hover { background: var(--gold-dark); color: #fff; }

        .card-custom {
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.06);
            padding: 20px;
            border: 1px solid var(--gray-border);
            transition: all 0.3s ease;
        }
        .card-custom:hover { box-shadow: 0 4px 20px rgba(0,0,0,0.08); }

        .stat-card {
            padding: 20px;
            border-radius: 16px;
            background: #fff;
            border-right: 4px solid var(--gold);
            box-shadow: 0 2px 10px rgba(0,0,0,0.04);
        }
        .stat-card .number {
            font-size: 28px;
            font-weight: 800;
            color: var(--primary-dark);
        }
        .stat-card .label {
            font-size: 14px;
            color: var(--text-gray);
            margin-top: 4px;
        }
        .stat-card .icon {
            font-size: 32px;
            color: var(--gold);
            opacity: 0.6;
        }

        ::-webkit-scrollbar { width: 6px; }
        ::-webkit-scrollbar-track { background: var(--gray-light); }
        ::-webkit-scrollbar-thumb { background: var(--primary-light); border-radius: 10px; }
    </style>

    @stack('styles')
</head>
<body>

    <!-- ================================================================
    SIDEBAR
    ================================================================ -->
    <aside class="sidebar" id="sidebar">
        <div class="brand">
            <h4>وزارة النفط</h4>
            <small>نظام إدارة الاستراتيجية</small>
        </div>

        <nav class="nav flex-column">
            @php
                $userRole = session('user_role', 'employee');
                $userName = session('user_name', 'مستخدم');
                $isMinister = in_array($userRole, ['minister', 'deputy', 'general_manager']);
                $isManager = ($userRole === 'manager');
                $isSuperAdmin = ($userRole === 'super_admin');
            @endphp

            {{-- ========== الرئيسية ========== --}}
            <div class="nav-section">الرئيسية</div>
            
            <a class="nav-link {{ request()->routeIs('dashboard.employee') ? 'active' : '' }}" href="{{ route('dashboard.employee') }}">
                <i class="fas fa-user"></i>
                <span class="nav-text">لوحة الموظف</span>
            </a>

            @if($isManager || $isMinister || $isSuperAdmin)
            <a class="nav-link {{ request()->routeIs('dashboard.manager') ? 'active' : '' }}" href="{{ route('dashboard.manager') }}">
                <i class="fas fa-user-tie"></i>
                <span class="nav-text">لوحة المدير</span>
            </a>
            @endif

            @if($isMinister || $isSuperAdmin)
            <a class="nav-link {{ request()->routeIs('dashboard.minister') ? 'active' : '' }}" href="{{ route('dashboard.minister') }}">
                <i class="fas fa-crown"></i>
                <span class="nav-text">لوحة الوزير</span>
            </a>
            @endif

            {{-- ========== المهام ========== --}}
            <div class="nav-section">العمليات</div>

            <a class="nav-link {{ request()->routeIs('tasks.*') ? 'active' : '' }}" href="{{ route('tasks.index') }}">
                <i class="fas fa-tasks"></i>
                <span class="nav-text">المهام</span>
            </a>

            {{-- ========== التخطيط الاستراتيجي ========== --}}
            @if($isMinister || $isManager || $isSuperAdmin)
            <div class="nav-section">الاستراتيجية</div>

            <a class="nav-link {{ request()->routeIs('strategic.pillars.*') ? 'active' : '' }}" href="{{ route('strategic.pillars.index') }}">
                <i class="fas fa-chess-queen"></i>
                <span class="nav-text">الركائز</span>
            </a>
            <a class="nav-link {{ request()->routeIs('strategic.goals.*') ? 'active' : '' }}" href="{{ route('strategic.goals.index') }}">
                <i class="fas fa-bullseye"></i>
                <span class="nav-text">الأهداف</span>
            </a>
            <a class="nav-link {{ request()->routeIs('strategic.programs.*') ? 'active' : '' }}" href="{{ route('strategic.programs.index') }}">
                <i class="fas fa-project-diagram"></i>
                <span class="nav-text">البرامج</span>
            </a>
            <a class="nav-link {{ request()->routeIs('strategic.initiatives.*') ? 'active' : '' }}" href="{{ route('strategic.initiatives.index') }}">
                <i class="fas fa-lightbulb"></i>
                <span class="nav-text">المبادرات</span>
            </a>
            @endif

            {{-- ========== الأداء والمتابعة ========== --}}
            @if($isMinister || $isManager || $isSuperAdmin)
            <div class="nav-section">المتابعة</div>

            <a class="nav-link {{ request()->routeIs('kpis.*') ? 'active' : '' }}" href="{{ route('kpis.index') }}">
                <i class="fas fa-chart-line"></i>
                <span class="nav-text">مؤشرات الأداء</span>
            </a>
            <a class="nav-link {{ request()->routeIs('budget.*') ? 'active' : '' }}" href="{{ route('budget.index') }}">
                <i class="fas fa-money-bill-wave"></i>
                <span class="nav-text">الميزانية</span>
            </a>
            <a class="nav-link {{ request()->routeIs('risks.*') ? 'active' : '' }}" href="{{ route('risks.index') }}">
                <i class="fas fa-exclamation-triangle"></i>
                <span class="nav-text">المخاطر</span>
            </a>
            @endif

            {{-- ========== الإعدادات ========== --}}
            <div class="nav-section">الإعدادات</div>

            <a class="nav-link {{ request()->routeIs('password.change') ? 'active' : '' }}" href="{{ route('password.change') }}">
                <i class="fas fa-key"></i>
                <span class="nav-text">تغيير كلمة المرور</span>
            </a>

            @if($isSuperAdmin)
            <div class="nav-section">الإدارة</div>
            <a class="nav-link {{ request()->routeIs('admin.employees.*') ? 'active' : '' }}" href="{{ route('admin.employees.index') }}">
                <i class="fas fa-users"></i>
                <span class="nav-text">الموظفين</span>
            </a>
            <a class="nav-link {{ request()->routeIs('admin.departments.*') ? 'active' : '' }}" href="{{ route('admin.departments.index') }}">
                <i class="fas fa-building"></i>
                <span class="nav-text">الإدارات</span>
            </a>
            @endif

            {{-- ========== تسجيل الخروج ========== --}}
            <form method="POST" action="{{ route('logout') }}" style="margin-top: 20px;">
                @csrf
                <button type="submit" class="nav-link" style="color: var(--danger);">
                    <i class="fas fa-sign-out-alt"></i>
                    <span class="nav-text">تسجيل الخروج</span>
                </button>
            </form>
        </nav>

        <button class="collapse-btn" id="collapseBtn" title="تصغير القائمة">
            <i class="fas fa-chevron-left" id="collapseIcon"></i>
        </button>
    </aside>

    <!-- ================================================================
    MAIN CONTENT
    ================================================================ -->
    <div class="main-content" id="mainContent">

        <!-- Top Bar -->
        <div class="topbar">
            <button class="toggle-sidebar" id="mobileToggle">
                <i class="fas fa-bars"></i>
            </button>

            <h5 class="page-title">@yield('title', 'لوحة التحكم')</h5>

            <div class="user-info">
                <div>
                    <div class="user-name">{{ session('user_name', 'مستخدم') }}</div>
                    <div class="user-role">{{ session('user_role', 'employee') }}</div>
                </div>
                <div class="avatar">{{ mb_substr(session('user_name', 'م'), 0, 1) }}</div>
            </div>
        </div>

        <!-- Page Content -->
        @yield('content')

    </div>

    <!-- ================================================================
    SCRIPTS
    ================================================================ -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const sidebar = document.getElementById('sidebar');
        const mainContent = document.getElementById('mainContent');
        const collapseBtn = document.getElementById('collapseBtn');
        const collapseIcon = document.getElementById('collapseIcon');
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
        });

        document.addEventListener('click', function(e) {
            if (window.innerWidth <= 992) {
                const isClickInside = sidebar.contains(e.target) || e.target.closest('.toggle-sidebar');
                if (!isClickInside) sidebar.classList.remove('mobile-open');
            }
        });
    </script>

    @stack('scripts')
</body>
</html>
