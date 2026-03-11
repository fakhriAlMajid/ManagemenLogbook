<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login - Logbook Management</title>

    @vite(['resources/css/login.css', 'resources/js/Auth/login.js', 'resources/js/app.js'])

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
</head>
<body>

    <div class="main-card">

        {{-- ════════════ LEFT: Form ════════════ --}}
        <div class="left-panel">

            <div class="header-section">
                <div class="brand-logo">
                    <img class="logo-placeholder"
                         src="{{ asset('images/LogoBiruOnly.png') }}"
                         alt="Logbook Management Logo">
                    <div>
                        <h1 class="brand-title">LOGBOOK MANAGEMENT</h1>
                        <p class="brand-subtitle">Project Monitoring and Management</p>
                    </div>
                </div>
                <div class="divider"></div>
            </div>

            <form id="loginForm">

                <div id="errorMsg" class="alert-error" style="display:none;">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                    <span id="errorText"></span>
                </div>

                {{-- Username / Email --}}
                <div class="form-group">
                    <div class="input-wrapper">
                        <span class="input-icon">
                            <i class="bi bi-person"></i>
                        </span>
                        <input type="text"
                               id="usr_email"
                               class="form-input"
                               placeholder="Username or Email"
                               autocomplete="username"
                               required>
                    </div>
                </div>

                {{-- Password --}}
                <div class="form-group">
                    <div class="input-wrapper">
                        <span class="input-icon">
                            <i class="bi bi-lock"></i>
                        </span>
                        <input type="password"
                               id="password"
                               class="form-input"
                               placeholder="Password"
                               autocomplete="current-password"
                               required>
                    </div>
                </div>

                <button type="button" id="loginBtn" class="btn-login">
                    <i class="bi bi-box-arrow-in-right"></i>
                    Log In
                </button>

                <a href="/signup" class="btn-signup">
                    <i class="bi bi-person-plus me-1"></i>
                    Create an Account
                </a>

            </form>
        </div>

        {{-- ════════════ RIGHT: Illustration ════════════ --}}
        <div class="right-panel">
            <div class="illustration-container">
                <img class="illustration-img"
                     src="{{ asset('images/IlustrationLogin.png') }}"
                     alt="Login Illustration">
            </div>
        </div>

    </div>

</body>
</html>