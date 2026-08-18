@extends('layouts.student')

@section('title', 'Daily Time Record (DTR) — TrackTern')
@section('page_heading', 'Daily Time Record (DTR)')
@section('nav_dtr', 'active')

@section('content')
<div class="table-container" style="padding: 24px; margin-bottom: 30px; text-align: center;">
  <h3>Live DTR Time Recording Station</h3>
  <p style="font-size: 14px; color: #4B5563; margin-bottom: 24px;">Record your attendance session with automated server timestamps and email notifications.</p>

  <div id="dtr-clock-box" style="margin: 0 auto 24px; max-width: 420px; background: #F8FAFC; border: 1px solid #E2E8F0; border-radius: 12px; padding: 22px;">
    <div style="font-size: 14px; color: #6B7280; letter-spacing: 1px; text-transform: uppercase; margin-bottom: 10px;">Current Time</div>
    <div id="dtr-live-time" style="font-size: 44px; font-weight: 800; color: #111827; line-height: 1;">00:00 AM</div>
    <div id="dtr-live-date" style="font-size: 16px; color: #475569; margin-top: 8px;">01/01/2026</div>
  </div>

  <div style="display: flex; justify-content: center; gap: 20px; flex-wrap: wrap;">
    <button onclick="handleStudentTimeIn()" id="btn-timein" class="btn-time-in">TIME IN</button>
    <button onclick="handleStudentTimeOut()" id="btn-timeout" class="btn-time-out" disabled style="opacity:0.4; cursor:not-allowed;">TIME OUT</button>
  </div>
  <div id="open-session-status" style="margin-top: 16px; font-size: 15px; font-weight: 700; color: #004798; display: none;"></div>
</div>

<div class="table-container">
  <div class="table-header">
    <h3>Attendance Log History</h3>
  </div>
  <table class="solid-table">
    <thead>
      <tr>
        <th>Date</th>
        <th>Exact Time In</th>
        <th>Exact Time Out</th>
        <th>Rendered Hours</th>
        <th>Status</th>
      </tr>
    </thead>
    <tbody id="dtr-history-tbody">
      <tr><td colspan="5" style="text-align:center;">Loading DTR history...</td></tr>
    </tbody>
  </table>
</div>
@endsection

@section('scripts')
<script>
  function updateDtrClock() {
    const timeEl = document.getElementById('dtr-live-time');
    const dateEl = document.getElementById('dtr-live-date');
    const now = new Date();
    if (timeEl) {
      timeEl.textContent = formatAppTime(now);
    }
    if (dateEl) {
      dateEl.textContent = formatAppDate(now);
    }
  }
  updateDtrClock();
  setInterval(updateDtrClock, 1000);

  function formatDateOnly(value) {
    return formatAppDate(value);
  }

  function formatTimeOnly(value) {
    return formatAppTime(value);
  }

  async function loadDtrData() {
    const tbody = document.getElementById('dtr-history-tbody');
    const statusMsg = document.getElementById('open-session-status');
    const btnIn = document.getElementById('btn-timein');
    const btnOut = document.getElementById('btn-timeout');

    try {
      const res = await apiRequest('/student/dtr');
      const hasOpen = res.active_open_session;

      if (hasOpen) {
        // Active open session exists -> Only Time Out is clickable!
        btnIn.disabled = true;
        btnIn.style.opacity = '0.4';
        btnIn.style.cursor = 'not-allowed';

        btnOut.disabled = false;
        btnOut.style.opacity = '1';
        btnOut.style.cursor = 'pointer';

        statusMsg.style.display = 'block';
        statusMsg.innerHTML = `Active Session Open since: <strong>${formatTimeOnly(hasOpen.time_in)}</strong>`;
      } else {
        // No active session -> Only Time In is clickable!
        btnIn.disabled = false;
        btnIn.style.opacity = '1';
        btnIn.style.cursor = 'pointer';

        btnOut.disabled = true;
        btnOut.style.opacity = '0.4';
        btnOut.style.cursor = 'not-allowed';

        statusMsg.style.display = 'none';
      }

      tbody.innerHTML = res.attendance_records.data.length > 0 ? res.attendance_records.data.map(a => `
        <tr>
          <td><strong>${formatDateOnly(a.date)}</strong></td>
          <td>${formatTimeOnly(a.time_in)}</td>
          <td>${a.time_out ? formatTimeOnly(a.time_out) : '<span style="color:#004798; font-weight:700;">Session Open</span>'}</td>
          <td>${Number(a.rendered_hours || 0).toFixed(2)} hrs</td>
          <td><span class="badge ${a.status === 'completed' ? 'badge-approved' : 'badge-pending'}">${a.status}</span></td>
        </tr>
      `).join('') : '<tr><td colspan="5" style="text-align:center;">No DTR records found.</td></tr>';
    } catch(err) {}
  }
  loadDtrData();

  async function handleStudentTimeIn() {
    try {
      const res = await apiRequest('/student/dtr/time-in', 'POST');
      showToast(res.message);
      loadDtrData();
    } catch(err) {}
  }

  async function handleStudentTimeOut() {
    try {
      const res = await apiRequest('/student/dtr/time-out', 'POST');
      showToast(res.message);
      loadDtrData();
    } catch(err) {}
  }
</script>
@endsection
