@extends('layouts.public')

@section('title', 'Quick Daily Time Record (QDTR) - TrackTern')

@section('content')
<header class="public-header">
  <div class="header-brand">
    <img src="{{ asset('images/logo.png') }}" alt="TrackTern Logo">
  </div>
  <button type="button" class="menu-toggle" aria-label="Open navigation" aria-expanded="false" onclick="togglePublicMenu()">
    <svg viewBox="0 0 24 24"><path d="M4 6h16M4 12h16M4 18h16"/></svg>
  </button>
  <nav class="nav-links" aria-label="Public navigation">
    <a href="/qdtr" class="nav-link active">QDTR</a>
    <a href="/login" class="nav-link">Login</a>
    <a href="/register" class="nav-link">Register</a>
  </nav>
</header>

<div class="kiosk-wrapper">
  <div class="kiosk-card" id="kiosk-card-container">
    
    
    <div class="kiosk-header">
      <h2>Quick Daily Time Record</h2>
    </div>

    <div class="clock-display-box">
      <div class="digital-clock" id="kiosk-clock">00:00 AM</div>
      <div class="digital-date" id="kiosk-date">08/03/2026</div>
    </div>

    <form id="kiosk-form" onsubmit="handleKioskAttendance(event)">
      <div class="kiosk-form-group">
        <label for="intern_id">Enter Intern ID:</label>
        <input type="text" id="intern_id" placeholder="Eg: 58492" maxlength="5" pattern="\d{5}" required autocomplete="off">
      </div>

      <div class="kiosk-action-buttons single-action">
        <button type="submit" class="btn-time-in">Record Attendance</button>
      </div>
    </form>
  </div>
</div>
@endsection

@section('scripts')
<script>
  function updateClock() {
    const clockEl = document.getElementById('kiosk-clock');
    const dateEl = document.getElementById('kiosk-date');
    if (!clockEl) return;
    const now = new Date();
    clockEl.textContent = formatAppTime(now);
    dateEl.textContent = formatAppDate(now);
  }
  updateClock();
  setInterval(updateClock, 1000);

  async function handleKioskAttendance(event) {
    event.preventDefault();
    const input = document.getElementById('intern_id');
    const code = input.value.trim();

    if (!code || code.length !== 5) {
      showToast('Please enter your 5-digit numeric Intern ID.', true);
      return;
    }

    try {
      const res = await apiRequest('/attendance/quick/record', 'POST', { intern_id: code });

      showToast(res.message);
      input.value = '';

      const card = document.getElementById('kiosk-card-container');

      card.innerHTML = `
        <div style="text-align: center; padding: 20px;">
          <h2 style="color: #004798; font-size: 28px; margin-bottom: 12px;">Attendance Recorded!</h2>
          <p style="font-size: 20px; font-weight: 700; color: #1E1E1E; margin-bottom: 8px;">Intern: ${res.student_name_masked}</p>
          <p style="font-size: 18px; font-weight: 800; color: ${res.action === 'time-in' ? '#004798' : '#1E1E1E'}; margin-bottom: 8px;">${res.action === 'time-in' ? 'TIME IN' : 'TIME OUT'}</p>
          <p style="font-size: 16px; color: #4B5563; margin-bottom: 16px;">Timestamp: ${res.timestamp}</p>
          ${res.action === 'time-out' ? `<p style="font-size: 18px; font-weight: 700; color: #007A33;">Rendered Time: ${Number(res.rendered_hours || 0).toFixed(2)} Hours</p>` : ''}
          <div style="margin-top: 24px; font-size: 14px; color: #6B7280;">Kiosk will reset automatically in 5 seconds...</div>
        </div>
      `;

      setTimeout(() => {
        window.location.reload();
      }, 5000);
    } catch (err) {}
  }
</script>
@endsection
