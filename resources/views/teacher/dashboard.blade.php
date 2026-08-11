@extends('layouts.teacher')

@section('title', 'Teacher Dashboard — TrackTern')
@section('page_heading', 'Teacher Summary Dashboard')
@section('nav_dashboard', 'active')

@section('content')
<div id="teacher-dashboard-container">
  <div style="text-align: center; padding: 40px;">Loading teacher metrics...</div>
</div>
@endsection

@section('scripts')
<script>
  async function loadTeacherDashboard() {
    const container = document.getElementById('teacher-dashboard-container');
    try {
      const res = await apiRequest('/teacher/dashboard');
      const s = res.summary;

      container.innerHTML = `
        <div class="metrics-grid">
          <div class="metric-card">
            <div class="metric-label">Active Classrooms</div>
            <div class="metric-value">${s.total_active_classrooms}</div>
          </div>
          <div class="metric-card">
            <div class="metric-label">Enrolled Students</div>
            <div class="metric-value">${s.total_enrolled_students}</div>
          </div>
          <div class="metric-card">
            <div class="metric-label">Currently Rendering</div>
            <div class="metric-value">${s.students_currently_rendering}</div>
            <div class="metric-subtext">Active DTR Sessions</div>
          </div>
          <div class="metric-card">
            <div class="metric-label">Completed Internships</div>
            <div class="metric-value">${s.completed_internships}</div>
          </div>
          <div class="metric-card">
            <div class="metric-label">Pending Task Reviews</div>
            <div class="metric-value" style="color: #92400E;">${s.pending_task_approvals}</div>
          </div>
          <div class="metric-card">
            <div class="metric-label">Pending Report Reviews</div>
            <div class="metric-value" style="color: #92400E;">${s.pending_weekly_reports}</div>
          </div>
          <div class="metric-card">
            <div class="metric-label">Students Behind</div>
            <div class="metric-value" style="color: #991B1B;">${s.students_behind_schedule}</div>
          </div>
        </div>

        <div class="table-container">
          <div class="table-header">
            <h3>Managed Classrooms</h3>
            <a href="/teacher/classrooms" class="btn-primary">Create New Classroom</a>
          </div>
          <table class="solid-table">
            <thead>
              <tr>
                <th>Classroom Name</th>
                <th>Invitation Code</th>
                <th>Target Hours</th>
                <th>Academic Term</th>
                <th>Students Enrolled</th>
              </tr>
            </thead>
            <tbody>
              ${res.classrooms.length > 0 ? res.classrooms.map(c => `
                <tr>
                  <td><strong>${c.classroom_name}</strong></td>
                  <td><code style="font-size:16px; font-weight:800; color:#004798;">${c.classroom_code}</code></td>
                  <td>${c.required_hours} hrs</td>
                  <td>${c.semester} (${c.academic_year})</td>
                  <td>${c.students ? c.students.length : 0} students</td>
                </tr>
              `).join('') : '<tr><td colspan="5" style="text-align:center;">No classrooms created yet.</td></tr>'}
            </tbody>
          </table>
        </div>
      `;
    } catch(err) {}
  }
  loadTeacherDashboard();
</script>
@endsection
