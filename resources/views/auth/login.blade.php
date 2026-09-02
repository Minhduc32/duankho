<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng nhập | Hệ thống Quản lý Kho</title>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- FontAwesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root {
            --primary: #6366f1;
            --primary-hover: #4f46e5;
            --background: #0f172a;
            --card-bg: rgba(30, 41, 59, 0.7);
            --text-main: #f8fafc;
            --text-muted: #94a3b8;
            --border-color: rgba(255, 255, 255, 0.08);
            --gradient: linear-gradient(135deg, #1e1b4b 0%, #0f172a 100%);
            --accent: #06b6d4;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Outfit', sans-serif;
        }

        body {
            background: var(--gradient);
            color: var(--text-main);
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            position: relative;
        }

        /* Background blur circles */
        .circle {
            position: absolute;
            border-radius: 50%;
            filter: blur(80px);
            z-index: 0;
            opacity: 0.4;
        }

        .circle-1 {
            width: 300px;
            height: 300px;
            background: var(--primary);
            top: -50px;
            left: -50px;
            animation: float-1 8s infinite alternate ease-in-out;
        }

        .circle-2 {
            width: 250px;
            height: 250px;
            background: var(--accent);
            bottom: -50px;
            right: -50px;
            animation: float-2 8s infinite alternate ease-in-out;
        }

        @keyframes float-1 {
            0% { transform: translate(0, 0); }
            100% { transform: translate(40px, 40px); }
        }

        @keyframes float-2 {
            0% { transform: translate(0, 0); }
            100% { transform: translate(-30px, -30px); }
        }

        .login-container {
            width: 100%;
            max-width: 440px;
            padding: 2.5rem;
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 24px;
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
            z-index: 10;
            animation: fadeIn 0.6s cubic-bezier(0.16, 1, 0.3, 1);
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .brand {
            text-align: center;
            margin-bottom: 2rem;
        }

        .brand-icon {
            font-size: 2.5rem;
            background: linear-gradient(135deg, var(--accent) 0%, var(--primary) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 0.5rem;
        }

        .brand-title {
            font-size: 1.75rem;
            font-weight: 700;
            letter-spacing: -0.5px;
            background: linear-gradient(to right, #ffffff, #cbd5e1);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .brand-subtitle {
            font-size: 0.9rem;
            color: var(--text-muted);
            margin-top: 0.25rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
            position: relative;
        }

        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-size: 0.85rem;
            font-weight: 500;
            color: #cbd5e1;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }

        .input-wrapper {
            position: relative;
        }

        .input-icon {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted);
            font-size: 1.1rem;
            transition: color 0.3s;
        }

        .form-control {
            width: 100%;
            padding: 0.85rem 1rem 0.85rem 2.8rem;
            background: rgba(15, 23, 42, 0.6);
            border: 1px solid rgba(255, 255, 255, 0.05);
            border-radius: 12px;
            color: var(--text-main);
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.2);
            background: rgba(15, 23, 42, 0.8);
        }

        .form-control:focus + .input-icon {
            color: var(--primary);
        }

        .btn-submit {
            width: 100%;
            padding: 0.85rem;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-hover) 100%);
            border: none;
            border-radius: 12px;
            color: white;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 12px rgba(99, 102, 241, 0.3);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            margin-top: 2rem;
        }

        .btn-submit:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 20px rgba(99, 102, 241, 0.4);
        }

        .alert-error {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.2);
            color: #f87171;
            padding: 0.75rem 1rem;
            border-radius: 12px;
            font-size: 0.9rem;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            animation: shake 0.4s ease;
        }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            75% { transform: translateX(5px); }
        }

        .demo-accounts {
            margin-top: 1.5rem;
            background: rgba(255, 255, 255, 0.02);
            border-radius: 12px;
            padding: 1rem;
            border: 1px dashed rgba(255, 255, 255, 0.05);
        }

        .demo-title {
            font-size: 0.8rem;
            font-weight: 600;
            color: var(--text-muted);
            margin-bottom: 0.5rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .demo-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 0.5rem;
        }

        .demo-btn {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.03);
            color: var(--text-main);
            padding: 0.4rem;
            font-size: 0.75rem;
            border-radius: 6px;
            cursor: pointer;
            text-align: left;
            transition: all 0.2s;
        }

        .demo-btn:hover {
            background: rgba(255, 255, 255, 0.1);
            border-color: var(--primary);
        }

        .demo-btn span {
            display: block;
            color: var(--text-muted);
            font-size: 0.65rem;
        }
    </style>
</head>
<body>
    <div class="circle circle-1"></div>
    <div class="circle circle-2"></div>

    <div class="login-container">
        <div class="brand">
            <div class="brand-icon"><i class="fa-solid fa-boxes-stacked"></i></div>
            <h1 class="brand-title">Hệ thống Quản lý Kho</h1>
            <p class="brand-subtitle">Số hóa quy trình Xuất - Nhập - Tồn</p>
        </div>

        @if ($errors->has('error'))
            <div class="alert-error">
                <i class="fa-solid fa-circle-exclamation"></i>
                <span>{{ $errors->first('error') }}</span>
            </div>
        @endif

        <form action="{{ route('login.submit') }}" method="POST">
            @csrf
            <div class="form-group">
                <label for="username" class="form-label">Tên đăng nhập</label>
                <div class="input-wrapper">
                    <input type="text" id="username" name="username" class="form-control" value="{{ old('username') }}" placeholder="Nhập tên đăng nhập" required autocomplete="off">
                    <i class="fa-solid fa-user input-icon"></i>
                </div>
            </div>

            <div class="form-group">
                <label for="password" class="form-label">Mật khẩu</label>
                <div class="input-wrapper">
                    <input type="password" id="password" name="password" class="form-control" placeholder="••••••••" required>
                    <i class="fa-solid fa-lock input-icon"></i>
                </div>
            </div>

            <button type="submit" class="btn-submit">
                Đăng nhập <i class="fa-solid fa-arrow-right-to-bracket"></i>
            </button>
        </form>

        <div style="text-align: center; margin-top: 1.25rem; font-size: 0.9rem; color: var(--text-muted);">
            Chưa có tài khoản? <a href="{{ route('register') }}" style="color: var(--primary); text-decoration: none; font-weight: 600; transition: color 0.2s;" onmouseover="this.style.color='var(--accent)'" onmouseout="this.style.color='var(--primary)'">Đăng ký ngay</a>
        </div>

        <div class="demo-accounts">
            <h3 class="demo-title">Tài khoản trải nghiệm nhanh</h3>
            <div class="demo-grid">
                <button class="demo-btn" onclick="fillAccount('staff', 'staff123')">
                    <strong>Thủ kho</strong>
                    <span>user: staff / pass: staff123</span>
                </button>
                <button class="demo-btn" onclick="fillAccount('manager', 'manager123')">
                    <strong>Quản lý kho</strong>
                    <span>user: manager / pass: manager123</span>
                </button>
                <button class="demo-btn" onclick="fillAccount('accountant', 'accountant123')">
                    <strong>Kế toán kho</strong>
                    <span>user: accountant / pass: accountant123</span>
                </button>
                <button class="demo-btn" onclick="fillAccount('admin', 'admin123')">
                    <strong>Admin</strong>
                    <span>user: admin / pass: admin123</span>
                </button>
            </div>
        </div>
    </div>

    <script>
        function fillAccount(username, password) {
            document.getElementById('username').value = username;
            document.getElementById('password').value = password;
        }
    </script>
</body>
</html>
