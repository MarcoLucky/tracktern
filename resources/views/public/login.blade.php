@extends('layouts.public')

@section('title', 'Login — TrackTern')

@section('content')
<div class="auth-split-layout">
  <div class="auth-left-pane">
    <div class="auth-nav-row">
      <button type="button" class="menu-toggle" aria-label="Open navigation" aria-expanded="false" onclick="togglePublicMenu()">
        <svg viewBox="0 0 24 24"><path d="M4 6h16M4 12h16M4 18h16"/></svg>
      </button>
      <nav class="auth-nav" aria-label="Public navigation">
        <a href="/qdtr" class="nav-link">QDTR</a>
        <a href="/login" class="nav-link active">Login</a>
        <a href="/register" class="nav-link">Register</a>
      </nav>
    </div>

    <div class="auth-hero-center">
      <img src="{{ asset('images/logo.png') }}" alt="TrackTern Logo">
    </div>

    <div class="auth-footer-help">
      Need Help? Contact us at<br>
      <strong>tracktern2026@gmail.com</strong>
    </div>
  </div>

  <div class="auth-right-pane">
    <div class="auth-form-card">
      <div class="auth-title-row">
        <img src="{{ asset('images/logo.png') }}" alt="TrackTern Logo" class="auth-title-logo">
        <h2>Welcome to TrackTern!</h2>
      </div>
      <p class="subtitle">Login to access your account</p>

      <form id="login-form" onsubmit="handleLogin(event)">
        <div class="form-group">
          <label for="email">Email Address:</label>
          <input type="email" id="email" placeholder="Enter your email address..." required>
        </div>

        <div class="form-group">
          <label for="password">Password:</label>
          <div class="password-input-wrap">
            <input type="password" id="password" placeholder="Enter your password..." required>
            <button type="button" class="password-toggle" aria-label="Show password" aria-pressed="false" onclick="togglePasswordVisibility('password', this)">
              <svg class="eye-icon" viewBox="0 0 24 24"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/></svg>
              <svg class="eye-off-icon" viewBox="0 0 24 24"><path d="M3 3l18 18M10.6 10.6A3 3 0 0 0 12 15a3 3 0 0 0 2.4-4.8M9.9 4.2A10.7 10.7 0 0 1 12 4.0c6.5 0 10 8 10 8a18.8 18.8 0 0 1-3.1 4.1M6.5 6.5C3.7 8.4 2 12 2 12s3.5 8 10 8a10.8 10.8 0 0 0 5.5-1.5"/></svg>
            </button>
          </div>
        </div>

        <div class="links-row">
          <a href="/register">Don't have an account yet? Register here</a>
          <a href="/forgot-password">Forgot your password? Click here</a>
        </div>

        <button type="submit" class="btn-green-cta">Login</button>
      </form>
    </div>
  </div>
</div>
@endsection

@section('scripts')
<script>
  async function handleLogin(e) {
    e.preventDefault();
    const email = document.getElementById('email').value;
    const password = document.getElementById('password').value;

    try {
      const res = await apiRequest('/auth/login', 'POST', { email, password });
      localStorage.setItem('tracktern_token', res.access_token);
      localStorage.setItem('tracktern_user', JSON.stringify(res.user));

      showToast('Login successful!');
      if (res.user.role === 'student') {
        window.location.href = '/student/dashboard';
      } else {
        window.location.href = '/teacher/dashboard';
      }
    } catch (err) {}
  }
</script>
@endsection
