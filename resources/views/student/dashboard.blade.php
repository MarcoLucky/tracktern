@extends('layouts.student')

@section('title', 'Student Dashboard — TrackTern')
@section('page_heading', 'Student Performance Dashboard')
@section('nav_dashboard', 'active')

@section('content')
<div id="student-dashboard-content">
  <div style="text-align: center; padding: 40px;">Loading dashboard metrics...</div>
</div>
@endsection

@section('scripts')
<script>
  async function loadDashboard() {
    const container = document.getElementById('student-dashboard-content');
    try {
      const res = await apiRequest('/student/dashboard');
      const s = res.summary;

      let badgeClass = 'badge-on-track';
      if (s.status_badge === 'Needs Attention') badgeClass = 'badge-needs-attention';
      if (s.status_badge === 'Behind') badgeClass = 'badge-behind';
      if (s.status_badge === 'Completed') badgeClass = 'badge-completed';

      container.innerHTML = `
        <div class="metrics-grid">
          <div class="metric-card">
            <div class="metric-label">Rendered Hours</div>
            <div class="metric-value">${s.total_rendered_hours} hrs</div>
            <div class="metric-subtext">${s.total_rendered_minutes} total minutes</div>
          </div>

          <div class="metric-card">
            <div class="metric-label">Classroom Required Target</div>
            <div class="metric-value">${s.required_target_hours} hrs</div>
            <div class="metric-subtext">${s.classroom_joined ? `Class: <strong>${s.classroom_name}</strong>` : '<em>Join a class to set target</em>'}</div>
          </div>

          <div class="metric-card">
            <div class="metric-label">Remaining Hours</div>
            <div class="metric-value">${s.remaining_hours} hrs</div>
            <div class="metric-subtext">${s.progress_percentage}% completed</div>
          </div>

          <div class="metric-card">
            <div class="metric-label">Official Intern ID</div>
            <div class="metric-value" style="color: #004798;">${s.intern_id}</div>
            <div class="metric-subtext">Status: <span class="badge ${badgeClass}">${s.status_badge}</span></div>
          </div>
        </div>

        <div class="table-container">
          <div class="table-header">
            <h3>Recent DTR Records</h3>
            <a href="/student/dtr" class="btn-secondary">View Full DTR Log</a>
          </div>
          <table class="solid-table">
            <thead>
              <tr>
                <th>Date</th>
                <th>Time In</th>
                <th>Time Out</th>
                <th>Rendered Hours</th>
                <th>Status</th>
              </tr>
            </thead>
            <tbody>
              ${res.recent_attendance.length > 0 ? res.recent_attendance.map(a => `
                <tr>
                  <td>${a.date}</td>
                  <td>${new Date(a.time_in).toLocaleTimeString()}</td>
                  <td>${a.time_out ? new Date(a.time_out).toLocaleTimeString() : 'Pending'}</td>
                  <td>${(a.rendered_minutes / 60).toFixed(2)} hrs</td>
                  <td><span class="badge ${a.status === 'completed' ? 'badge-approved' : 'badge-pending'}">${a.status}</span></td>
                </tr>
              `).join('') : '<tr><td colspan="5" style="text-align:center;">No DTR records logged yet.</td></tr>'}
            </tbody>
          </table>
        </div>
      `;
    } catch (err) {}
  }
  loadDashboard();
</script>
@endsection
