@extends('layouts.teacher')

@section('title', 'Report Approvals — TrackTern')
@section('page_heading', 'Weekly Reports Review Queue')
@section('nav_reports_approvals', 'active')

@section('content')
<div class="table-container">
  <div class="table-header">
    <h3>Pending Weekly Reports</h3>
  </div>
  <table class="solid-table">
    <thead>
      <tr>
        <th>Student</th>
        <th>Week #</th>
        <th>Coverage Dates</th>
        <th>Activities</th>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody id="reports-approval-tbody">
      <tr><td colspan="5" style="text-align:center;">Loading weekly report review queue...</td></tr>
    </tbody>
  </table>
</div>
@endsection

@section('scripts')
<script>
  async function loadReportApprovals() {
    const tbody = document.getElementById('reports-approval-tbody');
    try {
      const res = await apiRequest('/teacher/reports/approvals');
      tbody.innerHTML = res.data.length > 0 ? res.data.map(r => `
        <tr>
          <td><strong>${r.student && r.student.user ? r.student.user.name : 'Student'}</strong></td>
          <td>Week ${r.week_number}</td>
          <td>${r.coverage_start_date} to ${r.coverage_end_date}</td>
          <td>${r.activities}</td>
          <td>
            <div style="display:flex; gap:8px;">
              <button onclick="handleApproveReport(${r.id})" class="btn-primary" style="padding:6px 12px; font-size:12px;">Approve</button>
              <button onclick="handleRevisionReport(${r.id})" class="btn-danger" style="padding:6px 12px; font-size:12px;">Request Revision</button>
            </div>
          </td>
        </tr>
      `).join('') : '<tr><td colspan="5" style="text-align:center;">No pending weekly reports to review.</td></tr>';
    } catch(err) {}
  }
  loadReportApprovals();

  async function handleApproveReport(reportId) {
    try {
      const res = await apiRequest(`/teacher/reports/${reportId}/approve`, 'POST');
      showToast(res.message);
      loadReportApprovals();
    } catch(err) {}
  }

  async function handleRevisionReport(reportId) {
    const feedback = prompt('Enter actionable feedback for weekly report revision:');
    if (!feedback) return;
    try {
      const res = await apiRequest(`/teacher/reports/${reportId}/revision`, 'POST', { feedback });
      showToast(res.message);
      loadReportApprovals();
    } catch(err) {}
  }
</script>
@endsection
