@extends('layouts.teacher')

@section('title', 'My Profile - TrackTern')
@section('page_heading', 'My Teacher Profile')
@section('nav_profile', 'active')

@section('content')
<div class="profile-grid">
  <div class="table-container profile-card">
    <h3>Edit Profile Details</h3>
    <form onsubmit="handleTeacherProfileUpdate(event)" style="margin-top: 16px;">
      <div class="form-group">
        <label for="teacher-name">Full Name:</label>
        <input type="text" id="teacher-name" required>
      </div>

      <div class="form-group">
        <label for="teacher-email">Email Address:</label>
        <input type="email" id="teacher-email" required>
      </div>

      <div class="form-group">
        <label for="teacher-contact">Contact Number:</label>
        <input type="text" id="teacher-contact" placeholder="e.g. 09171234567">
      </div>

      <button type="submit" class="btn-primary" style="margin-top: 10px; width: 100%;">Save Profile Changes</button>
    </form>
  </div>

  <div class="table-container profile-card">
    <h3>Change Password</h3>
    <p style="font-size: 13px; color: #4B5563; margin-bottom: 20px;">Update your account password securely.</p>

    <form onsubmit="handleTeacherChangePassword(event)">
      <div class="form-group">
        <label for="teacher-curr-pass">Current Password:</label>
        <div class="password-input-wrap">
          <input type="password" id="teacher-curr-pass" placeholder="Enter current password..." required>
          <button type="button" class="password-toggle" aria-label="Show password" aria-pressed="false" onclick="togglePasswordVisibility('teacher-curr-pass', this)">
            <svg class="eye-icon" viewBox="0 0 24 24"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/></svg>
            <svg class="eye-off-icon" viewBox="0 0 24 24"><path d="M3 3l18 18M10.6 10.6A3 3 0 0 0 12 15a3 3 0 0 0 2.4-4.8M9.9 4.2A10.7 10.7 0 0 1 12 4c6.5 0 10 8 10 8a18.8 18.8 0 0 1-3.1 4.1M6.5 6.5C3.7 8.4 2 12 2 12s3.5 8 10 8a10.8 10.8 0 0 0 5.5-1.5"/></svg>
          </button>
        </div>
      </div>

      <div class="form-group">
        <label for="teacher-new-pass">New Password:</label>
        <div class="password-input-wrap">
          <input type="password" id="teacher-new-pass" placeholder="Min. 8 characters" required>
          <button type="button" class="password-toggle" aria-label="Show password" aria-pressed="false" onclick="togglePasswordVisibility('teacher-new-pass', this)">
            <svg class="eye-icon" viewBox="0 0 24 24"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/></svg>
            <svg class="eye-off-icon" viewBox="0 0 24 24"><path d="M3 3l18 18M10.6 10.6A3 3 0 0 0 12 15a3 3 0 0 0 2.4-4.8M9.9 4.2A10.7 10.7 0 0 1 12 4c6.5 0 10 8 10 8a18.8 18.8 0 0 1-3.1 4.1M6.5 6.5C3.7 8.4 2 12 2 12s3.5 8 10 8a10.8 10.8 0 0 0 5.5-1.5"/></svg>
          </button>
        </div>
      </div>

      <div class="form-group">
        <label for="teacher-new-pass-confirm">Confirm New Password:</label>
        <div class="password-input-wrap">
          <input type="password" id="teacher-new-pass-confirm" placeholder="Confirm new password" required>
          <button type="button" class="password-toggle" aria-label="Show password" aria-pressed="false" onclick="togglePasswordVisibility('teacher-new-pass-confirm', this)">
            <svg class="eye-icon" viewBox="0 0 24 24"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/></svg>
            <svg class="eye-off-icon" viewBox="0 0 24 24"><path d="M3 3l18 18M10.6 10.6A3 3 0 0 0 12 15a3 3 0 0 0 2.4-4.8M9.9 4.2A10.7 10.7 0 0 1 12 4c6.5 0 10 8 10 8a18.8 18.8 0 0 1-3.1 4.1M6.5 6.5C3.7 8.4 2 12 2 12s3.5 8 10 8a10.8 10.8 0 0 0 5.5-1.5"/></svg>
          </button>
        </div>
      </div>

      <button type="submit" class="btn-secondary" style="width: 100%; margin-top: 10px;">Update Password</button>
    </form>
  </div>
</div>
@endsection

@section('scripts')
<script>
  function populateTeacherProfile() {
    const teacher = currentUser.teacher || {};
    document.getElementById('teacher-name').value = currentUser.name || '';
    document.getElementById('teacher-email').value = currentUser.email || '';
    document.getElementById('teacher-contact').value = teacher.contact_number || '';
  }
  populateTeacherProfile();

  async function handleTeacherProfileUpdate(e) {
    e.preventDefault();

    try {
      const res = await apiRequest('/auth/profile', 'PUT', {
        name: document.getElementById('teacher-name').value,
        email: document.getElementById('teacher-email').value,
        contact_number: document.getElementById('teacher-contact').value,
      });

      currentUser = res.user;
      localStorage.setItem('tracktern_user', JSON.stringify(currentUser));
      const nameEl = document.getElementById('topbar-teacher-name');
      if (nameEl) nameEl.textContent = currentUser.name;
      showToast('Profile updated successfully!');
      populateTeacherProfile();
    } catch (err) {}
  }

  async function handleTeacherChangePassword(e) {
    e.preventDefault();
    const currPass = document.getElementById('teacher-curr-pass').value;
    const newPass = document.getElementById('teacher-new-pass').value;
    const confirmPass = document.getElementById('teacher-new-pass-confirm').value;

    if (newPass !== confirmPass) {
      showToast('New password confirmation does not match.', true);
      return;
    }

    try {
      const res = await apiRequest('/auth/change-password', 'POST', {
        current_password: currPass,
        new_password: newPass,
        new_password_confirmation: confirmPass,
      });
      showToast(res.message);
      document.getElementById('teacher-curr-pass').value = '';
      document.getElementById('teacher-new-pass').value = '';
      document.getElementById('teacher-new-pass-confirm').value = '';
    } catch (err) {}
  }
</script>
@endsection
