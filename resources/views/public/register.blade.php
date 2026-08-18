@extends('layouts.public')

@section('title', 'Register — TrackTern')

@section('content')
<div class="auth-split-layout">
  <div class="auth-left-pane">
    <div class="auth-nav-row">
      <button type="button" class="menu-toggle" aria-label="Open navigation" aria-expanded="false" onclick="togglePublicMenu()">
        <svg viewBox="0 0 24 24"><path d="M4 6h16M4 12h16M4 18h16"/></svg>
      </button>
      <nav class="auth-nav" aria-label="Public navigation">
        <a href="/qdtr" class="nav-link">QDTR</a>
        <a href="/login" class="nav-link">Login</a>
        <a href="/register" class="nav-link active">Register</a>
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
    <div class="auth-form-card" id="register-card-container">
      <div class="auth-title-row">
        <img src="{{ asset('images/logo.png') }}" alt="TrackTern Logo" class="auth-title-logo">
        <h2>Welcome to TrackTern!</h2>
      </div>
      <p class="subtitle">Register your account</p>

      <form id="register-form" onsubmit="handleRegister(event)">
        <div class="form-group">
          <label for="name">Full Name:</label>
          <input type="text" id="name" placeholder="Enter your full name..." required>
        </div>

        <div class="form-group">
          <label for="email">Email Address:</label>
          <input type="email" id="email" placeholder="Enter your email address..." required>
        </div>

        <div class="form-group">
          <label for="role">User Type:</label>
          <select id="role" required>
            <option value="student">Student Intern</option>
            <option value="teacher">Instructor</option>
          </select>
        </div>

        <div class="form-group">
          <label for="password">Password:</label>
          <div class="password-input-wrap">
            <input type="password" id="password" placeholder="Min. 8 characters" required>
            <button type="button" class="password-toggle" aria-label="Show password" aria-pressed="false" onclick="togglePasswordVisibility('password', this)">
              <svg class="eye-icon" viewBox="0 0 24 24"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/></svg>
              <svg class="eye-off-icon" viewBox="0 0 24 24"><path d="M3 3l18 18M10.6 10.6A3 3 0 0 0 12 15a3 3 0 0 0 2.4-4.8M9.9 4.2A10.7 10.7 0 0 1 12 4c6.5 0 10 8 10 8a18.8 18.8 0 0 1-3.1 4.1M6.5 6.5C3.7 8.4 2 12 2 12s3.5 8 10 8a10.8 10.8 0 0 0 5.5-1.5"/></svg>
            </button>
          </div>
        </div>

        <div class="form-group">
          <label for="password_confirmation">Confirm Password:</label>
          <div class="password-input-wrap">
            <input type="password" id="password_confirmation" placeholder="Confirm your password" required>
            <button type="button" class="password-toggle" aria-label="Show password" aria-pressed="false" onclick="togglePasswordVisibility('password_confirmation', this)">
              <svg class="eye-icon" viewBox="0 0 24 24"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/></svg>
              <svg class="eye-off-icon" viewBox="0 0 24 24"><path d="M3 3l18 18M10.6 10.6A3 3 0 0 0 12 15a3 3 0 0 0 2.4-4.8M9.9 4.2A10.7 10.7 0 0 1 12 4c6.5 0 10 8 10 8a18.8 18.8 0 0 1-3.1 4.1M6.5 6.5C3.7 8.4 2 12 2 12s3.5 8 10 8a10.8 10.8 0 0 0 5.5-1.5"/></svg>
            </button>
          </div>
        </div>

        <div class="links-row">
          <a href="/login">Already registered? Click here</a>
        </div>

        <button type="submit" class="btn-green-cta">Register</button>
      </form>
    </div>
  </div>
</div>
@endsection

@section('scripts')
<script>
  function toggleRoleFields() {
    const role = document.getElementById('role').value;
    document.getElementById('teacher-extra-fields').style.display = role === 'teacher' ? 'block' : 'none';
  }

  async function handleRegister(e) {
    e.preventDefault();
    const payload = {
      name: document.getElementById('name').value,
      email: document.getElementById('email').value,
      role: document.getElementById('role').value,
      password: document.getElementById('password').value,
      password_confirmation: document.getElementById('password_confirmation').value,
    };

    try {
      const res = await apiRequest('/auth/register', 'POST', payload);
      showToast('Registration successful! Please login to continue.');
      window.location.href = '/login';
    } catch (err) {}
  }
</script>
@endsection
