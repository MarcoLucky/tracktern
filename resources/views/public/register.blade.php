@extends('layouts.public')

@section('title', 'Register — TrackTern')

@section('content')
<div class="auth-split-layout">
  <div class="auth-left-pane">
    <nav class="auth-nav">
      
      <a href="/qdtr" class="nav-link">QDTR</a>
      <a href="/login" class="nav-link">Login</a>
      <a href="/register" class="nav-link active">Register</a>
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
    <div class="auth-form-card" id="register-card-container">
      <h2>Welcome to TrackTern!</h2>
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
          <select id="role" onchange="toggleRoleFields()" required>
            <option value="student">Student Intern</option>
            <option value="teacher">Teacher / Instructor</option>
          </select>
        </div>

        <div id="teacher-extra-fields" style="display: none;">
          <div class="form-group">
            <label for="employee_number">Employee Number:</label>
            <input type="text" id="employee_number" placeholder="Eg: EMP-2026-001">
          </div>
        </div>

        <div class="form-group">
          <label for="password">Password:</label>
          <input type="password" id="password" placeholder="Min. 8 characters" required>
        </div>

        <div class="form-group">
          <label for="password_confirmation">Confirm Password:</label>
          <input type="password" id="password_confirmation" placeholder="Confirm your password" required>
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

    if (payload.role === 'teacher') {
      payload.employee_number = document.getElementById('employee_number').value || undefined;
    }

    try {
      const res = await apiRequest('/auth/register', 'POST', payload);
      localStorage.setItem('tracktern_token', res.access_token);
      localStorage.setItem('tracktern_user', JSON.stringify(res.user));

      if (res.user.role === 'student' && res.user.student) {
        const studentCode = res.user.student.student_code;
        const studentNumber = res.user.student.student_number;

        const container = document.getElementById('register-card-container');
        container.innerHTML = `
          <div style="text-align: center; padding: 24px; background-color: #FFFFFF; color: #1E1E1E; border-radius: 8px;">
            <h2 style="color: #004798; font-size: 26px; margin-bottom: 12px;">Registration Successful!</h2>
            <p style="font-size: 15px; margin-bottom: 20px;">Your account has been created. Below are your assigned credentials:</p>
            
            <div style="background-color: #F4F6F9; border: 2px dashed #004798; padding: 20px; border-radius: 8px; margin-bottom: 16px;">
              <div style="font-size: 13px; text-transform: uppercase; color: #6B7280; font-weight: 700;">Intern ID / Kiosk Code (Key for DTR)</div>
              <div style="font-size: 36px; font-weight: 800; color: #004798; letter-spacing: 2px;">${studentCode}</div>
            </div>

            <div style="background-color: #F4F6F9; border: 1px solid #D1D5DB; padding: 14px; border-radius: 8px; margin-bottom: 20px;">
              <div style="font-size: 12px; text-transform: uppercase; color: #6B7280; font-weight: 700;">Generated Student Number</div>
              <div style="font-size: 18px; font-weight: 700; color: #1E1E1E;">${studentNumber}</div>
            </div>

            <p style="font-size: 13px; color: #4B5563; margin-bottom: 24px;">Note: You can update your Host Training Establishment (Company Name) on your Student Profile page.</p>
            <button onclick="window.location.href='/student/dashboard'" class="btn-green-cta">Proceed to Student Dashboard</button>
          </div>
        `;
      } else {
        showToast('Registration successful!');
        window.location.href = '/teacher/dashboard';
      }
    } catch (err) {}
  }
</script>
@endsection
