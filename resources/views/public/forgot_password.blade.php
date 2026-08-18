@extends('layouts.public')

@section('title', 'Forgot Password - TrackTern')

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
        <h2>Reset Password</h2>
      </div>
      <p class="subtitle">Verify your email to set a new password</p>

      <form id="forgot-password-form" onsubmit="handleForgotPassword(event)">
        <div class="form-group">
          <label for="reset-email">Email Address:</label>
          <input type="email" id="reset-email" placeholder="Enter your email address..." required>
        </div>

        <button type="submit" class="btn-green-cta" id="send-otp-button">Send OTP Code</button>
      </form>

      <form id="reset-password-form" onsubmit="handleResetPassword(event)" style="display:none; margin-top: 22px;">
        <div class="form-group">
          <label for="reset-otp">OTP Code:</label>
          <input type="text" id="reset-otp" inputmode="numeric" maxlength="6" pattern="\d{6}" placeholder="Enter 6-digit OTP..." required>
        </div>

        <div class="form-group">
          <label for="reset-password">New Password:</label>
          <div class="password-input-wrap">
            <input type="password" id="reset-password" placeholder="Min. 8 characters" required>
            <button type="button" class="password-toggle" aria-label="Show password" aria-pressed="false" onclick="togglePasswordVisibility('reset-password', this)">
              <svg class="eye-icon" viewBox="0 0 24 24"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/></svg>
              <svg class="eye-off-icon" viewBox="0 0 24 24"><path d="M3 3l18 18M10.6 10.6A3 3 0 0 0 12 15a3 3 0 0 0 2.4-4.8M9.9 4.2A10.7 10.7 0 0 1 12 4c6.5 0 10 8 10 8a18.8 18.8 0 0 1-3.1 4.1M6.5 6.5C3.7 8.4 2 12 2 12s3.5 8 10 8a10.8 10.8 0 0 0 5.5-1.5"/></svg>
            </button>
          </div>
        </div>

        <div class="form-group">
          <label for="reset-password-confirmation">Confirm New Password:</label>
          <div class="password-input-wrap">
            <input type="password" id="reset-password-confirmation" placeholder="Confirm your password" required>
            <button type="button" class="password-toggle" aria-label="Show password" aria-pressed="false" onclick="togglePasswordVisibility('reset-password-confirmation', this)">
              <svg class="eye-icon" viewBox="0 0 24 24"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/></svg>
              <svg class="eye-off-icon" viewBox="0 0 24 24"><path d="M3 3l18 18M10.6 10.6A3 3 0 0 0 12 15a3 3 0 0 0 2.4-4.8M9.9 4.2A10.7 10.7 0 0 1 12 4c6.5 0 10 8 10 8a18.8 18.8 0 0 1-3.1 4.1M6.5 6.5C3.7 8.4 2 12 2 12s3.5 8 10 8a10.8 10.8 0 0 0 5.5-1.5"/></svg>
            </button>
          </div>
        </div>

        <div class="links-row">
          <a href="/login">Back to login</a>
        </div>

        <button type="submit" class="btn-green-cta">Reset Password</button>
      </form>
    </div>
  </div>
</div>
@endsection

@section('scripts')
<script>
  async function handleForgotPassword(e) {
    e.preventDefault();
    const email = document.getElementById('reset-email').value.trim();
    const button = document.getElementById('send-otp-button');

    try {
      button.disabled = true;
      const res = await apiRequest('/auth/forgot-password', 'POST', { email });
      showToast(res.message);
      document.getElementById('reset-password-form').style.display = 'block';
      button.textContent = 'Resend OTP Code';
      button.disabled = false;
      document.getElementById('reset-otp').focus();
    } catch (err) {
      button.disabled = false;
    }
  }

  async function handleResetPassword(e) {
    e.preventDefault();
    const payload = {
      email: document.getElementById('reset-email').value.trim(),
      otp: document.getElementById('reset-otp').value.trim(),
      password: document.getElementById('reset-password').value,
      password_confirmation: document.getElementById('reset-password-confirmation').value,
    };

    try {
      const res = await apiRequest('/auth/reset-password', 'POST', payload);
      showToast(res.message);
      window.location.href = '/login';
    } catch (err) {}
  }
</script>
@endsection
