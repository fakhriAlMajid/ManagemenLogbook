<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sign Up - Logbook Management</title>

    @vite(['resources/css/login.css', 'resources/js/Auth/signup.js'])

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
                        <h1 class="brand-title">Create an Account</h1>
                        <p class="brand-subtitle">Join and start managing your projects</p>
                    </div>
                </div>
                <div class="divider"></div>
            </div>

            <form id="signupForm">

                <div id="signupError" class="alert-error" style="display:none;">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                    <span id="signupErrorText"></span>
                </div>

                {{-- Username --}}
                <div class="form-group">
                    <div class="input-wrapper">
                        <span class="input-icon">
                            <i class="bi bi-at"></i>
                        </span>
                        <input type="text"
                               id="username"
                               class="form-input"
                               placeholder="Username"
                               autocomplete="username"
                               required>
                    </div>
                </div>

                {{-- First Name --}}
                <div class="form-group">
                    <div class="input-wrapper">
                        <span class="input-icon">
                            <i class="bi bi-person"></i>
                        </span>
                        <input type="text"
                               id="first_name"
                               class="form-input"
                               placeholder="First Name"
                               autocomplete="given-name"
                               required>
                    </div>
                </div>

                {{-- Last Name --}}
                <div class="form-group">
                    <div class="input-wrapper">
                        <span class="input-icon">
                            <i class="bi bi-person"></i>
                        </span>
                        <input type="text"
                               id="last_name"
                               class="form-input"
                               placeholder="Last Name (optional)"
                               autocomplete="family-name">
                    </div>
                </div>

                {{-- Email --}}
                <div class="form-group">
                    <div class="input-wrapper">
                        <span class="input-icon">
                            <i class="bi bi-envelope"></i>
                        </span>
                        <input type="email"
                               id="email"
                               class="form-input"
                               placeholder="Email Address"
                               autocomplete="email"
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
                               autocomplete="new-password"
                               required>
                    </div>
                </div>

                <button type="button" id="signupBtn" class="btn-login">
                    <i class="bi bi-person-check"></i>
                    Sign Up
                </button>

                <a href="/" class="btn-signup">
                    <i class="bi bi-arrow-left me-1"></i>
                    Back to Login
                </a>

            </form>
        </div>

        {{-- ════════════ RIGHT: Illustration ════════════ --}}
        <div class="right-panel">
            <div class="illustration-container">
                <img class="illustration-img"
                     src="{{ asset('images/IlustrationLogin.png') }}"
                     alt="Sign Up Illustration">
            </div>
        </div>

    </div>

</body>
</html>