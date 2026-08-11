@extends('layouts.student')

@section('title', 'My Profile — TrackTern')
@section('page_heading', 'My Student Profile')
@section('nav_profile', 'active')

@section('content')
<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px; max-width: 1000px;">
  
  <!-- Profile Info Card -->
  <div class="table-container" style="padding: 24px;">
    <div style="background-color: #F4F6F9; border: 2px solid #004798; border-radius: 8px; padding: 20px; margin-bottom: 24px; display: flex; align-items: center; justify-content: space-between;">
      <div>
        <div style="font-size: 13px; font-weight: 700; color: #4B5563; text-transform: uppercase;">Official Intern ID (5-Digit Numeric)</div>
        <div style="font-size: 32px; font-weight: 800; color: #004798; letter-spacing: 2px;" id="profile-intern-id-display">-----</div>
      </div>
      <div style="text-align: right; font-size: 12px; color: #4B5563;">
        Key for <strong>QDTR Kiosk</strong><br>Time In & Time Out
      </div>
    </div>

    <h3>Edit Profile Details</h3>
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
  <div class="table-container" style="padding: 24px; height: fit-content;">
    <h3>Change Password</h3>
    <p style="font-size: 13px; color: #4B5563; margin-bottom: 20px;">Update your account password securely.</p>
    
    <form onsubmit="handleChangePassword(event)">
      <div class="form-group">
        <label for="curr-pass">Current Password:</label>
        <input type="password" id="curr-pass" placeholder="Enter current password..." required>
      </div>

      <div class="form-group">
        <label for="new-pass">New Password:</label>
        <input type="password" id="new-pass" placeholder="Min. 8 characters" required>
      </div>

      <div class="form-group">
        <label for="new-pass-confirm">Confirm New Password:</label>
        <input type="password" id="new-pass-confirm" placeholder="Confirm new password" required>
      </div>

      <button type="submit" class="btn-secondary" style="width: 100%; margin-top: 10px;">Update Password</button>
    </form>
  </div>

</div>
@endsection

@section('scripts')
<script>
  function populateProfile() {
    const st = currentUser.student || {};
    document.getElementById('profile-intern-id-display').textContent = st.intern_id || '-----';
    document.getElementById('prof-name').value = currentUser.name || '';
    document.getElementById('prof-email').value = currentUser.email || '';
    document.getElementById('prof-contact').value = st.contact_number || '';
    document.getElementById('prof-company').value = st.company_name || '';
    document.getElementById('prof-start').value = st.internship_start_date ? st.internship_start_date.substring(0,10) : '';
    document.getElementById('prof-end').value = st.internship_end_date ? st.internship_end_date.substring(0,10) : '';
  }
  populateProfile();

  async function handleProfileUpdate(e) {
    e.preventDefault();
    try {
      const res = await apiRequest('/auth/profile', 'PUT', {
        name: document.getElementById('prof-name').value,
        contact_number: document.getElementById('prof-contact').value,
        company_name: document.getElementById('prof-company').value,
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
