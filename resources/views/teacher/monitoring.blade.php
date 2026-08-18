@extends('layouts.teacher')

@section('title', 'Student Monitoring — TrackTern')
@section('page_heading', 'Student Progress Monitoring')
@section('nav_monitoring', 'active')

@section('content')
<div class="table-container">
  <div class="table-header">
    <h3>Student Roster Performance</h3>
  </div>
  <table class="solid-table">
    <thead>
      <tr>
        <th>Student Name</th>
        <th>Student Code / ID</th>
        <th>Classroom</th>
        <th>Rendered Hours</th>
        <th>Target Hours</th>
        <th>Remaining</th>
        <th>Progress %</th>
        <th>Status</th>
      </tr>
    </thead>
    <tbody id="monitoring-tbody">
      <tr><td colspan="8" style="text-align:center;">Loading student roster monitoring...</td></tr>
    </tbody>
  </table>
</div>
@endsection

@section('scripts')
<script>
  async function loadMonitoring() {
    const tbody = document.getElementById('monitoring-tbody');
    try {
      const res = await apiRequest('/teacher/monitoring');
      tbody.innerHTML = res.students.length > 0 ? res.students.map(s => {
        let badgeClass = 'badge-on-track';
        if (s.status_badge === 'Needs Attention') badgeClass = 'badge-needs-attention';
        if (s.status_badge === 'Behind') badgeClass = 'badge-behind';
        if (s.status_badge === 'Completed') badgeClass = 'badge-completed';
        return `
          <tr>
            <td><strong>${s.student_name}</strong></td>
            <td><code style="font-size:14px; font-weight:800; color:#004798;">${s.intern_id}</code></td>
            <td>${s.classroom_name}</td>
            <td>${s.total_rendered_hours} hrs</td>
            <td>${s.required_target_hours} hrs</td>
            <td>${s.remaining_hours} hrs</td>
            <td>${s.progress_percentage}%</td>
            <td><span class="badge ${badgeClass}">${s.status_badge}</span></td>
          </tr>
        `;
      }).join('') : '<tr><td colspan="8" style="text-align:center;">No students enrolled yet.</td></tr>';
    } catch(err) {}
  }
  loadMonitoring();
</script>
@endsection
