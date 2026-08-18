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
      const progress = Math.max(0, Math.min(100, Number(s.progress_percentage || 0)));
      const renderedHours = Number(s.total_rendered_hours || 0).toFixed(2);
      const targetHours = Number(s.required_target_hours || 0).toFixed(2);
      const remainingHours = Number(s.remaining_hours || 0).toFixed(2);

      container.innerHTML = `
        <div class="hours-progress-panel">
          <div class="hours-progress-header">
            <div>
              <div class="hours-progress-title">Internship Hours Progress</div>
              <div class="hours-progress-value">${renderedHours} / ${targetHours} hrs</div>
              <div class="hours-progress-meta">${s.classroom_joined ? `Class: <strong>${escapeAppHtml(s.classroom_name)}</strong>` : '<em>Join a class to set your target hours</em>'}</div>
            </div>
            <div style="text-align:right;">
              <div class="hours-progress-title">Completed</div>
              <div class="hours-progress-value" style="color:#004798;">${progress}%</div>
            </div>
          </div>
          <div class="hours-progress-track" aria-label="Internship rendered hours progress">
            <div class="hours-progress-fill" style="width:${progress}%;"></div>
          </div>
          <div class="hours-progress-stats">
            <span><strong>Rendered:</strong> ${renderedHours} hrs</span>
            <span><strong>Needed:</strong> ${targetHours} hrs</span>
            <span><strong>Remaining:</strong> ${remainingHours} hrs</span>
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
                  <td>${formatAppDate(a.date)}</td>
                  <td>${formatAppTime(a.time_in)}</td>
                  <td>${a.time_out ? formatAppTime(a.time_out) : 'Pending'}</td>
                  <td>${Number(a.rendered_hours || 0).toFixed(2)} hrs</td>
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
