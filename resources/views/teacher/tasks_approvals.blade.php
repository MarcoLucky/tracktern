@extends('layouts.teacher')

@section('title', 'Task Approvals - TrackTern')
@section('page_heading', 'Task Submissions Review Queue')
@section('nav_tasks_approvals', 'active')

@section('content')
<div class="table-container">
  <div class="table-header">
    <h3>Pending Student Accomplishment Reports</h3>
    <button type="button" class="btn-primary" onclick="handleApproveAllTasks()" style="padding:8px 12px; font-size:13px;">Approve All Pending</button>
  </div>
  <table class="solid-table">
    <thead>
      <tr>
        <th>Student</th>
        <th>Task Title & Details</th>
        <th>Category</th>
        <th>DTR Date</th>
        <th>Submitted At</th>
        <th>Attachments</th>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody id="tasks-approval-tbody">
      <tr><td colspan="7" style="text-align:center;">Loading task approval queue...</td></tr>
    </tbody>
  </table>
</div>

<div id="task-detail-modal" class="app-modal-backdrop" hidden aria-hidden="true">
  <div class="app-modal-card" role="dialog" aria-modal="true" aria-labelledby="task-detail-title">
    <div class="app-modal-header">
      <div>
        <div style="font-size: 12px; color: #6B7280; text-transform: uppercase; font-weight: 800;">Accomplishment Report</div>
        <h3 id="task-detail-title" style="margin-top: 4px;">Task Details</h3>
      </div>
      <button type="button" class="icon-action-button" aria-label="Close task details" onclick="closeTaskDetailModal()">
        <svg viewBox="0 0 24 24"><path d="M18 6 6 18M6 6l12 12"/></svg>
      </button>
    </div>
    <div id="task-detail-body" class="info-grid"></div>
  </div>
</div>

<div id="task-reject-modal" class="app-modal-backdrop" hidden aria-hidden="true">
  <div class="app-modal-card" role="dialog" aria-modal="true" aria-labelledby="task-reject-title">
    <div class="app-modal-header">
      <div>
        <div style="font-size: 12px; color: #6B7280; text-transform: uppercase; font-weight: 800;">Send Rejection Reason</div>
        <h3 id="task-reject-title" style="margin-top: 4px;">Reject Accomplishment Report</h3>
      </div>
      <button type="button" class="icon-action-button" aria-label="Close reject task form" onclick="closeRejectTaskModal()">
        <svg viewBox="0 0 24 24"><path d="M18 6 6 18M6 6l12 12"/></svg>
      </button>
    </div>

    <form id="task-reject-form" onsubmit="submitRejectTask(event)">
      <div class="form-group">
        <label for="task-reject-reason">Reason for Rejection:</label>
        <textarea id="task-reject-reason" rows="5" placeholder="Tell the student what needs to be corrected..." required></textarea>
      </div>
      <div style="display:flex; justify-content:flex-end; gap:10px; margin-top: 10px;">
        <button type="button" class="btn-secondary" onclick="closeRejectTaskModal()">Cancel</button>
        <button type="submit" class="btn-danger">Reject Task</button>
      </div>
    </form>
  </div>
</div>
@endsection

@section('scripts')
<script>
  let approvalTasks = [];
  let taskIdPendingReject = null;

  function escapeHtml(value) {
    return String(value ?? '').replace(/[&<>"']/g, char => ({
      '&': '&amp;',
      '<': '&lt;',
      '>': '&gt;',
      '"': '&quot;',
      "'": '&#039;',
    }[char]));
  }

  function formatDate(value) {
    return formatAppDate(value);
  }

  function formatDateTime(value) {
    return formatAppDateTime(value);
  }

  function renderAttachments(attachments) {
    if (!attachments || attachments.length === 0) return '<em>No attachments</em>';

    return `
      <div class="attachment-list">
        ${attachments.map(attachment => {
          return renderAttachmentPreviewButton(attachment);
        }).join('')}
      </div>
    `;
  }

  function renderStatusBadge(status) {
    const badgeClass = status === 'approved'
      ? 'badge-approved'
      : (status === 'pending' ? 'badge-pending' : 'badge-rejected');

    return `<span class="badge ${badgeClass}">${escapeHtml(String(status || '').replace('_', ' '))}</span>`;
  }

  function getStudentPendingCount(studentId) {
    return approvalTasks.filter(task => Number(task.student_id) === Number(studentId) && task.status === 'pending').length;
  }

  async function loadTaskApprovals() {
    const tbody = document.getElementById('tasks-approval-tbody');
    try {
      const res = await apiRequest('/teacher/tasks/approvals');
      approvalTasks = res.data || [];
      tbody.innerHTML = approvalTasks.length > 0 ? approvalTasks.map(t => {
        const studentName = t.student && t.student.user ? t.student.user.name : 'Student';
        const dtrDate = t.attendance && t.attendance.date ? formatDate(t.attendance.date) : 'N/A';
        const studentPendingCount = getStudentPendingCount(t.student_id);
        return `
          <tr>
            <td>
              <strong>${escapeHtml(studentName)}</strong>
              <div style="margin-top:6px;">${studentPendingCount > 1 ? `<span class="badge badge-pending">${studentPendingCount} pending reports</span>` : ''}</div>
            </td>
            <td><strong>${escapeHtml(t.title)}</strong><br><small style="color:#6B7280;">${escapeHtml(t.description)}</small></td>
            <td>${escapeHtml(t.category)}</td>
            <td>${dtrDate}</td>
            <td>${formatDateTime(t.submitted_at)}</td>
            <td>${renderAttachments(t.attachments)}</td>
            <td>
              <div style="display:flex; gap:8px; flex-wrap:wrap;">
                <button type="button" onclick="openTaskDetail(${t.id})" class="btn-secondary" style="padding:6px 12px; font-size:12px;">View</button>
                <button type="button" onclick="handleApproveTask(${t.id})" class="btn-primary" style="padding:6px 12px; font-size:12px;">Approve</button>
                <button type="button" onclick="handleApproveStudentTasks(${t.student_id})" class="btn-secondary" style="padding:6px 12px; font-size:12px;">Approve Student (${studentPendingCount})</button>
                <button type="button" onclick="openRejectTaskModal(${t.id})" class="btn-danger" style="padding:6px 12px; font-size:12px;">Reject</button>
              </div>
            </td>
          </tr>
        `;
      }).join('') : '<tr><td colspan="7" style="text-align:center;">No pending tasks to review.</td></tr>';
    } catch(err) {}
  }
  loadTaskApprovals();

  async function openTaskDetail(taskId) {
    try {
      const task = await apiRequest(`/teacher/tasks/${taskId}`);
      const student = task.student || {};
      const user = student.user || {};
      const attendance = task.attendance || {};
      const classroom = task.classroom || {};
      const body = document.getElementById('task-detail-body');

      body.innerHTML = `
        <div class="info-row"><strong>Student</strong><span>${escapeHtml(user.name || 'N/A')}</span></div>
        <div class="info-row"><strong>Intern ID</strong><span>${escapeHtml(student.intern_id || 'N/A')}</span></div>
        <div class="info-row"><strong>Classroom</strong><span>${escapeHtml(classroom.classroom_name || 'N/A')}</span></div>
        <div class="info-row"><strong>Title</strong><span>${escapeHtml(task.title)}</span></div>
        <div class="info-row"><strong>Category</strong><span>${escapeHtml(task.category || 'General')}</span></div>
        <div class="info-row"><strong>DTR Date</strong><span>${attendance.date ? formatDate(attendance.date) : 'N/A'}</span></div>
        <div class="info-row"><strong>Time In</strong><span>${attendance.time_in ? formatAppTime(attendance.time_in) : 'N/A'}</span></div>
        <div class="info-row"><strong>Time Out</strong><span>${attendance.time_out ? formatAppTime(attendance.time_out) : 'N/A'}</span></div>
        <div class="info-row"><strong>Rendered Hours</strong><span>${Number(attendance.rendered_hours || 0).toFixed(2)} hrs</span></div>
        <div class="info-row"><strong>Status</strong><span>${renderStatusBadge(task.status)}</span></div>
        <div class="info-row"><strong>Description</strong><span>${escapeHtml(task.description)}</span></div>
        <div class="info-row"><strong>Attachments</strong><span>${renderAttachments(task.attachments)}</span></div>
      `;

      const modal = document.getElementById('task-detail-modal');
      modal.hidden = false;
      modal.setAttribute('aria-hidden', 'false');
    } catch (err) {}
  }

  function closeTaskDetailModal() {
    const modal = document.getElementById('task-detail-modal');
    modal.hidden = true;
    modal.setAttribute('aria-hidden', 'true');
  }

  async function handleApproveTask(taskId) {
    try {
      const res = await apiRequest(`/teacher/tasks/${taskId}/approve`, 'POST');
      showToast(res.message);
      loadTaskApprovals();
    } catch(err) {}
  }

  async function handleApproveStudentTasks(studentId) {
    const task = approvalTasks.find(item => Number(item.student_id) === Number(studentId));
    const studentName = task && task.student && task.student.user ? task.student.user.name : 'this student';
    const confirmed = await showAppConfirm(`Approve all pending tasks for ${studentName}?`);
    if (!confirmed) return;

    try {
      const res = await apiRequest(`/teacher/tasks/approvals/students/${studentId}/approve-all`, 'POST');
      showToast(res.message);
      loadTaskApprovals();
    } catch(err) {}
  }

  async function handleApproveAllTasks() {
    const confirmed = await showAppConfirm('Approve all pending task submissions from all students?');
    if (!confirmed) return;

    try {
      const res = await apiRequest('/teacher/tasks/approvals/approve-all', 'POST');
      showToast(res.message);
      loadTaskApprovals();
    } catch(err) {}
  }

  function openRejectTaskModal(taskId) {
    taskIdPendingReject = taskId;
    document.getElementById('task-reject-reason').value = '';
    const modal = document.getElementById('task-reject-modal');
    modal.hidden = false;
    modal.setAttribute('aria-hidden', 'false');
    setTimeout(() => document.getElementById('task-reject-reason').focus(), 100);
  }

  function closeRejectTaskModal() {
    taskIdPendingReject = null;
    const modal = document.getElementById('task-reject-modal');
    modal.hidden = true;
    modal.setAttribute('aria-hidden', 'true');
  }

  async function submitRejectTask(event) {
    event.preventDefault();
    if (!taskIdPendingReject) return;

    try {
      const res = await apiRequest(`/teacher/tasks/${taskIdPendingReject}/reject`, 'POST', {
        reason: document.getElementById('task-reject-reason').value,
      });
      showToast(res.message);
      closeRejectTaskModal();
      loadTaskApprovals();
    } catch(err) {}
  }
</script>
@endsection
