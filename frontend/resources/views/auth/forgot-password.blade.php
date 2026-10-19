<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>نسيان كلمة المرور - وزارة النفط والمعادن</title>
    <style>
        :root {
            --primary-dark: #0a2e5c;
            --primary: #1a4a8a;
            --gold: #d4af37;
            --gold-dark: #c8a23b;
        }
        * { box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Arial, sans-serif;
            background: linear-gradient(135deg, var(--primary-dark) 0%, var(--primary) 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
            padding: 20px;
            direction: rtl;
        }
        .card {
            background: rgba(255,255,255,0.98);
            border-radius: 20px;
            padding: 40px;
            max-width: 480px;
            width: 100%;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }
        .card .logo { text-align: center; margin-bottom: 30px; }
        .card .logo h1 {
            color: var(--primary-dark);
            font-weight: 800;
            font-size: 24px;
            margin: 0;
        }
        .card .logo .gold-text { color: var(--gold); }
        .card .logo small { display: block; color: #6c757d; margin-top: 4px; font-size: 0.8rem; }
        .card .logo hr { margin: 16px 0; border: 0; border-top: 1px solid rgba(0,0,0,0.1); }
        .btn-gold {
            background: var(--gold);
            color: var(--primary-dark);
            font-weight: 700;
            padding: 12px;
            border: none;
            border-radius: 10px;
            width: 100%;
            cursor: pointer;
            font-family: inherit;
            font-size: 1rem;
            transition: all 0.3s ease;
        }
        .btn-gold:hover { background: var(--gold-dark); color: #fff; }
        .form-control {
            display: block;
            width: 100%;
            padding: 12px 16px;
            font-size: 1rem;
            line-height: 1.5;
            color: #212529;
            background-color: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            font-family: inherit;
            transition: border-color 0.15s, box-shadow 0.15s;
        }
        .form-control:focus {
            outline: none;
            border-color: var(--gold);
            box-shadow: 0 0 0 3px rgba(212, 175, 55, 0.25);
        }
        .form-label {
            display: inline-block;
            margin-bottom: 6px;
            font-weight: 600;
            color: var(--primary-dark);
        }
        .alert {
            padding: 12px 16px;
            margin-bottom: 16px;
            border: 1px solid transparent;
            border-radius: 8px;
            font-size: 0.95rem;
        }
        .alert p { margin: 0; }
        .alert p + p { margin-top: 4px; }
        .alert-success { color: #155724; background-color: #d4edda; border-color: #c3e6cb; }
        .alert-danger { color: #721c24; background-color: #f8d7da; border-color: #f5c6cb; }
        .alert-info { color: #0c5460; background-color: #d1ecf1; border-color: #bee5eb; }
        .text-center { text-align: center; }
        .text-muted { color: #6c757d; }
        .small { font-size: 0.875em; }
        .text-decoration-none { text-decoration: none; color: var(--primary); }
        .text-decoration-none:hover { color: var(--gold); }
        .mb-0 { margin-bottom: 0; }
        .mb-3 { margin-bottom: 16px; }
        .mt-2 { margin-top: 8px; }
        .mt-3 { margin-top: 16px; }
        .mt-4 { margin-top: 24px; }
        h4 { font-size: 1.5rem; margin: 8px 0; font-weight: 700; }
    </style>
</head>
<body>
    <div class="card">
        <div class="logo">
            <h1>وزارة <span class="gold-text">النفط والمعادن</span></h1>
            <small>نظام إدارة الاستراتيجية الذكي</small>
            <hr>
            <p class="text-muted small mb-0">أدخل بريدك الإلكتروني ورقم هاتفك لاستعادة كلمة المرور</p>
        </div>


        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
            @if (session('new_password'))
                <div class="alert alert-info text-center">
                    <strong>كلمة المرور الجديدة:</strong>
                    <h4 class="mt-2">{{ session('new_password') }}</h4>
                    <small>يرجى نسخها وتغييرها فوراً</small>
                </div>
            @endif
        @endif


        @if ($errors->any())
            <div class="alert alert-danger">
                @foreach ($errors->all() as $error)
                    <p class="mb-0">{{ $error }}</p>
                @endforeach
            </div>
        @endif


        <form method="POST" action="{{ route('password.email') }}">
            @csrf
            <div class="mb-3">
                <label class="form-label">البريد الإلكتروني</label>
                <input type="email" name="email" class="form-control" placeholder="example@mom-ye.org" value="{{ old('email') }}" required>
            </div>
            <div class="mb-3">
                <label class="form-label">رقم الهاتف</label>
                <input type="text" name="phone_number" class="form-control" placeholder="777123456" value="{{ old('phone_number') }}" required>
            </div>
            <button type="submit" class="btn-gold">إرسال كلمة مرور جديدة</button>
        </form>


        <div class="text-center mt-3">
            <a href="{{ route('login') }}" class="text-decoration-none">← العودة إلى تسجيل الدخول</a>
        </div>
    </div>
</body>
</html>
