<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>نسيان كلمة المرور - وزارة النفط والمعادن</title>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css">
    <style>
        :root {
            --primary-dark: #0a2e5c;
            --primary: #1a4a8a;
            --gold: #d4af37;
            --gold-dark: #c8a23b;
        }
        body {
            font-family: 'Cairo', sans-serif;
            background: linear-gradient(135deg, var(--primary-dark) 0%, var(--primary) 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
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
        }
        .card .logo .gold-text { color: var(--gold); }
        .btn-gold {
            background: var(--gold);
            color: var(--primary-dark);
            font-weight: 700;
            padding: 12px;
            border: none;
            border-radius: 10px;
            width: 100%;
            transition: all 0.3s ease;
        }
        .btn-gold:hover { background: var(--gold-dark); color: #fff; }
        .form-control {
            border-radius: 10px;
            padding: 12px 16px;
            border: 1px solid #e2e8f0;
        }
        .form-control:focus {
            border-color: var(--gold);
            box-shadow: 0 0 0 3px rgba(212, 175, 55, 0.25);
        }
        .form-label {
            font-weight: 600;
            color: var(--primary-dark);
        }
    </style>
</head>
<body>
    <div class="card">
        <div class="logo">
            <h1>وزارة <span class="gold-text">النفط والمعادن</span></h1>
            <small>نظام إدارة الاستراتيجية الذكي</small>
            <hr>
            <p class="text-muted small">أدخل بريدك الإلكتروني ورقم هاتفك لاستعادة كلمة المرور</p>
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
