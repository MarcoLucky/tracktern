@extends('layouts.student')

@section('title', 'My Profile — TrackTern')
@section('page_heading', 'My Student Profile')
@section('nav_profile', 'active')

@section('content')
<div class="profile-grid">

  <!-- Profile Info Card -->
  <div class="table-container profile-card">
    <div style=" border-radius: 8px; padding: 20px; margin-bottom: -40px; display: flex; align-items: center; justify-content: space-between;">

    </div>

    <h3>Edit Profile Details</h3>
    <div class="profile-photo-panel">
      <button type="button" class="profile-photo-preview-button" onclick="previewProfilePhoto()" title="Preview profile photo">
        <img src="{{ asset('images/logo.png') }}" alt="Student profile photo" id="profile-photo-preview" class="profile-photo-preview">
      </button>
      <div>
        <label class="file-upload-button" for="profile-photo-input">
          Upload Photo
          <input type="file" id="profile-photo-input" accept="image/png,image/jpeg,image/webp" onchange="handleProfilePhotoUpload(event)">
        </label>
        <div style="font-size:12px; color:#4B5563; margin-top:8px;">PNG, JPG, or WEBP up to 5 MB.</div>
      </div>
    </div>

    <form onsubmit="handleProfileUpdate(event)" style="margin-top: 16px;">
      <div class="form-group">
        <label for="prof-name">Full Name:</label>
        <input type="text" id="prof-name" required>
      </div>

      <div class="form-group">
        <label for="prof-email">Email Address:</label>
        <input type="email" id="prof-email" disabled style="background:#EEEEEE;">
      </div>

      <div class="form-group">
        <label for="prof-contact">Contact Number:</label>
        <input type="text" id="prof-contact" placeholder="e.g. 09171234567">
      </div>

      <div class="form-group">
        <label for="prof-company">Host Training Establishment (Company/Organization):</label>
        <input type="text" id="prof-company" placeholder="Enter company or organization name...">
      </div>

      <div class="form-group">
        <label for="prof-location">Organization Location:</label>
        <input type="text" id="prof-location" placeholder="Enter the location of your organization...">
      </div>

      <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
        <div class="form-group">
          <label for="prof-start">Internship Start Date:</label>
          <input type="date" id="prof-start">
        </div>
        <div class="form-group">
          <label for="prof-end">Internship End Date:</label>
          <input type="date" id="prof-end">
        </div>
      </div>

      <button type="submit" class="btn-primary" style="margin-top: 10px; width: 100%;">Save Profile Changes</button>
    </form>
  </div>

  <!-- Change Password Card -->
  <div class="table-container profile-card">
    <h3>Change Password</h3>
    <p style="font-size: 13px; color: #4B5563; margin-bottom: 20px;">Update your account password securely.</p>
    
    <form onsubmit="handleChangePassword(event)">
      <div class="form-group">
        <label for="curr-pass">Current Password:</label>
        <div class="password-input-wrap">
          <input type="password" id="curr-pass" placeholder="Enter current password..." required>
          <button type="button" class="password-toggle" aria-label="Show password" aria-pressed="false" onclick="togglePasswordVisibility('curr-pass', this)">
            <svg class="eye-icon" viewBox="0 0 24 24"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/></svg>
            <svg class="eye-off-icon" viewBox="0 0 24 24"><path d="M3 3l18 18M10.6 10.6A3 3 0 0 0 12 15a3 3 0 0 0 2.4-4.8M9.9 4.2A10.7 10.7 0 0 1 12 4c6.5 0 10 8 10 8a18.8 18.8 0 0 1-3.1 4.1M6.5 6.5C3.7 8.4 2 12 2 12s3.5 8 10 8a10.8 10.8 0 0 0 5.5-1.5"/></svg>
          </button>
        </div>
      </div>

      <div class="form-group">
        <label for="new-pass">New Password:</label>
        <div class="password-input-wrap">
          <input type="password" id="new-pass" placeholder="Min. 8 characters" required>
          <button type="button" class="password-toggle" aria-label="Show password" aria-pressed="false" onclick="togglePasswordVisibility('new-pass', this)">
            <svg class="eye-icon" viewBox="0 0 24 24"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/></svg>
            <svg class="eye-off-icon" viewBox="0 0 24 24"><path d="M3 3l18 18M10.6 10.6A3 3 0 0 0 12 15a3 3 0 0 0 2.4-4.8M9.9 4.2A10.7 10.7 0 0 1 12 4c6.5 0 10 8 10 8a18.8 18.8 0 0 1-3.1 4.1M6.5 6.5C3.7 8.4 2 12 2 12s3.5 8 10 8a10.8 10.8 0 0 0 5.5-1.5"/></svg>
          </button>
        </div>
      </div>

      <div class="form-group">
        <label for="new-pass-confirm">Confirm New Password:</label>
        <div class="password-input-wrap">
          <input type="password" id="new-pass-confirm" placeholder="Confirm new password" required>
          <button type="button" class="password-toggle" aria-label="Show password" aria-pressed="false" onclick="togglePasswordVisibility('new-pass-confirm', this)">
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
  function profilePhotoSrc(url, bustCache = false) {
    const fallback = '{{ asset('images/logo.png') }}';
    if (!url) return fallback;

    let normalized = url;
    try {
      const parsed = new URL(url, window.location.origin);
      normalized = parsed.pathname.startsWith('/storage/') ? parsed.pathname : url;
    } catch (e) {}

    if (!bustCache) return normalized;
    return `${normalized}${normalized.includes('?') ? '&' : '?'}v=${Date.now()}`;
  }

  function populateProfile() {
    const st = currentUser.student || {};
    document.getElementById('prof-name').value = currentUser.name || '';
    document.getElementById('prof-email').value = currentUser.email || '';
    document.getElementById('prof-contact').value = st.contact_number || '';
    document.getElementById('prof-company').value = st.company_name || '';
    document.getElementById('prof-location').value = st.organization_location || '';
    document.getElementById('prof-start').value = st.internship_start_date ? st.internship_start_date.substring(0,10) : '';
    document.getElementById('prof-end').value = st.internship_end_date ? st.internship_end_date.substring(0,10) : '';
    document.getElementById('profile-photo-preview').src = profilePhotoSrc(st.profile_photo_url);
  }
  populateProfile();

  function previewProfilePhoto() {
    const image = document.getElementById('profile-photo-preview');
    if (!image || !image.src) return;
    previewAttachment(image.src, 'Profile photo', 'image');
  }

  async function handleProfilePhotoUpload(e) {
    const file = e.target.files && e.target.files[0];
    if (!file) return;

    const formData = new FormData();
    formData.append('profile_photo', file);

    try {
      const res = await apiRequest('/auth/profile/photo', 'POST', formData);
      currentUser = res.user;
      localStorage.setItem('tracktern_user', JSON.stringify(currentUser));
      showToast(res.message || 'Profile photo updated successfully.');
      populateProfile();
      document.getElementById('profile-photo-preview').src = profilePhotoSrc(currentUser.student.profile_photo_url, true);
    } catch (err) {
      e.target.value = '';
    }
  }

  async function handleProfileUpdate(e) {
    e.preventDefault();
    try {
      const res = await apiRequest('/auth/profile', 'PUT', {
        name: document.getElementById('prof-name').value,
        contact_number: document.getElementById('prof-contact').value,
        company_name: document.getElementById('prof-company').value,
        organization_location: document.getElementById('prof-location').value,
        internship_start_date: document.getElementById('prof-start').value || undefined,
        internship_end_date: document.getElementById('prof-end').value || undefined,
      });
      currentUser = res.user;
      localStorage.setItem('tracktern_user', JSON.stringify(currentUser));
      showToast('Profile updated successfully!');
      populateProfile();
    } catch(err) {}
  }

  async function handleChangePassword(e) {
    e.preventDefault();
    const currPass = document.getElementById('curr-pass').value;
    const newPass = document.getElementById('new-pass').value;
    const confirmPass = document.getElementById('new-pass-confirm').value;

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
      document.getElementById('curr-pass').value = '';
      document.getElementById('new-pass').value = '';
      document.getElementById('new-pass-confirm').value = '';
    } catch(err) {}
  }
</script>
@endsection
