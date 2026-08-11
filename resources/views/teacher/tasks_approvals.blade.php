@extends('layouts.teacher')

@section('title', 'Task Approvals — TrackTern')
@section('page_heading', 'Task Submissions Review Queue')
@section('nav_tasks_approvals', 'active')

@section('content')
<div class="table-container">
  <div class="table-header">
    <h3>Pending Student Task Logs</h3>
  </div>
  <table class="solid-table">
    <thead>
      <tr>
        <th>Student</th>
        <th>Task Title & Details</th>
        <th>Category</th>
        <th>Submitted At</th>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody id="tasks-approval-tbody">
      <tr><td colspan="5" style="text-align:center;">Loading task approval queue...</td></tr>
    </tbody>
  </table>
</div>
@endsection

@section('scripts')
<script>
  async function loadTaskApprovals() {
    const tbody = document.getElementById('tasks-approval-tbody');
    try {
      const res = await apiRequest('/teacher/tasks/approvals');
      tbody.innerHTML = res.data.length > 0 ? res.data.map(t => `
        <tr>
          <td><strong>${t.student && t.student.user ? t.student.user.name : 'Student'}</strong></td>
          <td><strong>${t.title}</strong><br><small style="color:#6B7280;">${t.description}</small></td>
          <td>${t.category}</td>
          <td>${new Date(t.submitted_at).toLocaleDateString()}</td>
          <td>
            <div style="display:flex; gap:8px;">
              <button onclick="handleApproveTask(${t.id})" class="btn-primary" style="padding:6px 12px; font-size:12px;">Approve</button>
              <button onclick="handleRevisionTask(${t.id})" class="btn-danger" style="padding:6px 12px; font-size:12px;">Request Revision</button>
            </div>
          </td>
        </tr>
      `).join('') : '<tr><td colspan="5" style="text-align:center;">No pending tasks to review.</td></tr>';
    } catch(err) {}
  }
  loadTaskApprovals();

  async function handleApproveTask(taskId) {
    try {
      const res = await apiRequest(`/teacher/tasks/${taskId}/approve`, 'POST');
      showToast(res.message);
      loadTaskApprovals();
    } catch(err) {}
  }

  async function handleRevisionTask(taskId) {
    const feedback = prompt('Enter actionable revision feedback for student:');
    if (!feedback) return;
    try {
      const res = await apiRequest(`/teacher/tasks/${taskId}/revision`, 'POST', { feedback });
      showToast(res.message);
      loadTaskApprovals();
    } catch(err) {}
  }
</script>
@endsection
