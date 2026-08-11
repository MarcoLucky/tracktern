@extends('layouts.public')

@section('title', 'Login — TrackTern')

@section('content')
<div class="auth-split-layout">
  <div class="auth-left-pane">
    <nav class="auth-nav">
      
      <a href="/qdtr" class="nav-link">QDTR</a>
      <a href="/login" class="nav-link active">Login</a>
      <a href="/register" class="nav-link">Register</a>
    </nav>

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
      <h2>Welcome to TrackTern!</h2>
      <p class="subtitle">Login to access your account</p>

      <form id="login-form" onsubmit="handleLogin(event)">
        <div class="form-group">
          <label for="email">Email Address:</label>
          <input type="email" id="email" placeholder="Enter your email address..." required>
        </div>

        <div class="form-group">
          <label for="password">Password:</label>
          <input type="password" id="password" placeholder="Enter your password..." required>
        </div>

        <div class="links-row">
          <a href="/register">Don't have an account yet? Register here</a>
          <a href="#" onclick="event.preventDefault(); alert('Please contact your administrator to reset password.');">Forgot your password? Click here</a>
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
