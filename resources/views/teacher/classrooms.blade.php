@extends('layouts.teacher')

@section('title', 'Classrooms — TrackTern')
@section('page_heading', 'Classroom Management')
@section('nav_classrooms', 'active')

@section('content')
<div id="classrooms-main-view">
  <div style="display:flex; justify-content:flex-end; margin-bottom: 18px;">
    <button type="button" class="btn-primary" onclick="openCreateClassroomModal()">Create Classroom</button>
  </div>

  <!-- Classroom List with CRUD -->
  <div class="table-container" style="margin-bottom: 30px;">
    <div class="table-header">
      <h3>Managed Classrooms</h3>
    </div>
    <table class="solid-table">
      <thead>
        <tr>
          <th>Classroom Name</th>
          <th>Invitation Code</th>
          <th>Required Hours</th>
          <th>Semester</th>
          <th>Academic Year</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody id="cr-list-tbody">
        <tr><td colspan="6" style="text-align:center;">Loading classrooms...</td></tr>
      </tbody>
    </table>
  </div>
</div>

<div id="create-classroom-modal" class="app-modal-backdrop" hidden aria-hidden="true">
  <div class="app-modal-card" role="dialog" aria-modal="true" aria-labelledby="create-classroom-title">
    <div class="app-modal-header">
      <div>
        <div style="font-size: 12px; color: #6B7280; text-transform: uppercase; font-weight: 800;">Classroom Management</div>
        <h3 id="create-classroom-title" style="margin-top: 4px;">Create New Internship Classroom</h3>
      </div>
      <button type="button" class="icon-action-button" aria-label="Close create classroom form" onclick="closeCreateClassroomModal()">
        <svg viewBox="0 0 24 24"><path d="M18 6 6 18M6 6l12 12"/></svg>
      </button>
    </div>

    <form id="classroom-form" onsubmit="handleClassroomFormSubmit(event)">
      <input type="hidden" id="cr-edit-id">
      <div class="form-grid-one">
        <div class="form-group">
          <label for="cr-name">Classroom Name:</label>
          <input type="text" id="cr-name" placeholder="e.g. BSIT 4-A Practicum" required>
        </div>
        <div class="form-group">
          <label for="cr-hours">Required Target Hours (Set by Teacher):</label>
          <input type="number" id="cr-hours" value="400" min="1" required>
        </div>
        <div class="form-group">
          <label for="cr-sem">Semester:</label>
          <input type="text" id="cr-sem" value="2nd Semester">
        </div>
        <div class="form-group">
          <label for="cr-ay">Academic Year:</label>
          <input type="text" id="cr-ay" value="2025-2026">
        </div>
        <div class="form-grid-two">
          <div class="form-group">
            <label for="cr-start">Start Date:</label>
            <input type="date" id="cr-start">
          </div>
          <div class="form-group">
            <label for="cr-end">End Date:</label>
            <input type="date" id="cr-end">
          </div>
        </div>
      </div>
      <div style="display:flex; justify-content:flex-end; gap:10px; margin-top: 10px;">
        <button type="button" class="btn-secondary" onclick="closeCreateClassroomModal()">Cancel</button>
        <button type="submit" id="classroom-form-submit" class="btn-primary">Generate Classroom & 5-Digit Code</button>
      </div>
    </form>
  </div>
</div>

<!-- Selected Classroom View Container (Nested Student Monitoring & Task Queue) -->
<div id="selected-classroom-detail" style="display: none;">
  <div class="table-container" style="padding: 24px;">
    <div style="display:flex; justify-content:space-between; align-items:center; gap:12px; margin-bottom: 16px;">
      <button type="button" onclick="closeClassroomDetail()" class="btn-secondary">Back</button>
      <button type="button" onclick="downloadClassroomSummaryPdf()" class="btn-primary">Report Summary PDF</button>
    </div>

    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 20px;">
      <div>
        <h2 id="view-cr-name" style="font-size: 24px; color: #004798;">Classroom Detail</h2>
        <div style="font-size: 14px; color: #4B5563;">Invitation Code: <code id="view-cr-code" style="font-size: 16px; font-weight:800; color:#004798;">CR902</code></div>
      </div>
    </div>

    <!-- Nested Tabs -->
    <div style="display: flex; gap: 12px; border-bottom: 2px solid #E5E7EB; margin-bottom: 20px;">
      <button onclick="switchNestedTab('monitoring')" id="tab-btn-monitoring" class="btn-primary" style="border-radius: 6px 6px 0 0;">Student Monitoring Roster</button>
      <button onclick="switchNestedTab('tasks')" id="tab-btn-tasks" class="btn-secondary" style="border-radius: 6px 6px 0 0;">Task Approval Queue</button>
    </div>

    <!-- Tab 1: Student Monitoring -->
    <div id="nested-monitoring-view">
      <div class="filter-toolbar">
        <input type="search" id="monitoring-search-filter" placeholder="Search student or intern ID" oninput="renderMonitoringRoster()">
        <select id="monitoring-rendered-sort" onchange="renderMonitoringRoster()">
          <option value="">Rendered hours: Default</option>
          <option value="highest">Rendered hours: Highest</option>
          <option value="lowest">Rendered hours: Lowest</option>
        </select>
      </div>
      <table class="solid-table">
        <thead>
          <tr>
            <th>Student Name</th>
            <th>Intern ID</th>
            <th>Rendered Hours</th>
            <th>Target Hours</th>
            <th>Remaining</th>
            <th>Progress %</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody id="nested-monitoring-tbody">
          <tr><td colspan="7" style="text-align:center;">Loading classroom roster...</td></tr>
        </tbody>
      </table>
    </div>

    <!-- Tab 2: Task Approval Queue -->
    <div id="nested-tasks-view" style="display: none;">
      <div id="task-queue-bulk-actions" style="display:flex; justify-content:flex-end; gap:10px; flex-wrap:wrap; margin-bottom:14px;">
        <button type="button" onclick="handleApproveAllClassroomTasks()" class="btn-primary" style="padding:8px 12px; font-size:13px;">Approve All Pending in Classroom</button>
      </div>
      <div id="tasks-filter-note" style="display:none; align-items:center; justify-content:space-between; gap:12px; flex-wrap:wrap; margin-bottom:14px; padding:12px; border:1px solid #E5E7EB; border-radius:8px; background:#F9FAFB;">
        <span id="tasks-filter-label" style="font-weight:700; color:#111827;"></span>
        <div style="display:flex; gap:8px; flex-wrap:wrap;">
          <button type="button" id="approve-filtered-student-button" onclick="handleApproveFilteredStudentTasks()" class="btn-primary" style="padding:6px 12px; font-size:12px;">Approve Student Pending</button>
          <button type="button" onclick="renderPendingTaskQueue()" class="btn-secondary" style="padding:6px 12px; font-size:12px;">Show Pending Queue</button>
        </div>
      </div>
      <div class="filter-toolbar">
        <span id="task-search-wrapper">
          <input type="search" id="task-student-search-filter" placeholder="Search student" oninput="renderTaskQueue()">
        </span>
        <input type="date" id="task-date-filter" onchange="renderTaskQueue()">
        <select id="task-status-filter" onchange="renderTaskQueue()">
          <option value="pending">Pending</option>
          <option value="approved">Approved</option>
          <option value="rejected">Rejected</option>
          <option value="">All Statuses</option>
        </select>
        <button type="button" onclick="clearTaskFilters()" class="btn-secondary" style="padding:8px 12px; font-size:13px;">Clear Filters</button>
      </div>
      <table class="solid-table">
        <thead>
          <tr>
            <th>Student</th>
            <th>Task Title & Details</th>
            <th>Category</th>
            <th>Status</th>
            <th>DTR Date</th>
            <th>Submitted At</th>
            <th>Attachments</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody id="nested-tasks-tbody">
          <tr><td colspan="8" style="text-align:center;">Loading task approvals...</td></tr>
        </tbody>
      </table>
    </div>
  </div>
</div>

<div id="student-info-modal" class="app-modal-backdrop" hidden aria-hidden="true">
  <div class="app-modal-card" role="dialog" aria-modal="true" aria-labelledby="student-info-title" style="width:min(1040px, 100%);">
    <div class="app-modal-header">
      <div>
        <div id="student-info-eyebrow" style="font-size: 12px; color: #6B7280; text-transform: uppercase; font-weight: 800;">Student Details</div>
        <h3 id="student-info-title" style="margin-top: 4px;">Student Details</h3>
      </div>
      <button type="button" class="icon-action-button" aria-label="Close student details" onclick="closeStudentInfoModal()">
        <svg viewBox="0 0 24 24"><path d="M18 6 6 18M6 6l12 12"/></svg>
      </button>
    </div>
    <div class="detail-tabs" role="tablist" aria-label="Student detail tabs">
      <button type="button" id="student-tab-profile" class="detail-tab-button active" onclick="switchStudentInfoTab('profile')">Profile</button>
      <button type="button" id="student-tab-tasks" class="detail-tab-button" onclick="switchStudentInfoTab('tasks')">Tasks</button>
      <button type="button" id="student-tab-dtr" class="detail-tab-button" onclick="switchStudentInfoTab('dtr')">DTR</button>
    </div>
    <div id="student-info-body"></div>
  </div>
</div>

<div id="classroom-task-detail-modal" class="app-modal-backdrop" hidden aria-hidden="true">
  <div class="app-modal-card" role="dialog" aria-modal="true" aria-labelledby="classroom-task-detail-title">
    <div class="app-modal-header">
      <div>
        <div style="font-size: 12px; color: #6B7280; text-transform: uppercase; font-weight: 800;">Accomplishment Report</div>
        <h3 id="classroom-task-detail-title" style="margin-top: 4px;">Task Details</h3>
      </div>
      <button type="button" class="icon-action-button" aria-label="Close task details" onclick="closeClassroomTaskDetailModal()">
        <svg viewBox="0 0 24 24"><path d="M18 6 6 18M6 6l12 12"/></svg>
      </button>
    </div>
    <div id="classroom-task-detail-body" class="info-grid"></div>
  </div>
</div>

<div id="classroom-task-reject-modal" class="app-modal-backdrop" hidden aria-hidden="true">
  <div class="app-modal-card" role="dialog" aria-modal="true" aria-labelledby="classroom-task-reject-title">
    <div class="app-modal-header">
      <div>
        <div style="font-size: 12px; color: #6B7280; text-transform: uppercase; font-weight: 800;">Send Rejection Reason</div>
        <h3 id="classroom-task-reject-title" style="margin-top: 4px;">Reject Accomplishment Report</h3>
      </div>
      <button type="button" class="icon-action-button" aria-label="Close reject task form" onclick="closeClassroomRejectTaskModal()">
        <svg viewBox="0 0 24 24"><path d="M18 6 6 18M6 6l12 12"/></svg>
      </button>
    </div>

    <form id="classroom-task-reject-form" onsubmit="submitClassroomRejectTask(event)">
      <div class="form-group">
        <label for="classroom-task-reject-reason">Reason for Rejection:</label>
        <textarea id="classroom-task-reject-reason" rows="5" placeholder="Tell the student what needs to be corrected..." required></textarea>
      </div>
      <div style="display:flex; justify-content:flex-end; gap:10px; margin-top: 10px;">
        <button type="button" class="btn-secondary" onclick="closeClassroomRejectTaskModal()">Cancel</button>
        <button type="submit" class="btn-danger">Reject Task</button>
      </div>
    </form>
  </div>
</div>
@endsection

@section('scripts')
<script>
  let classroomsData = [];
  let currentClassroomId = null;
  let currentClassroomTasks = [];
  let currentStudentMonitoring = [];
  let currentClassroomAttendance = [];
  let currentTaskStudentFilter = null;
  let classroomTaskPendingReject = null;
  let selectedStudentId = null;
  let selectedStudentDtrMonth = new Date();

  function escapeHtml(value) {
    return String(value ?? '').replace(/[&<>"']/g, char => ({
      '&': '&amp;',
      '<': '&lt;',
      '>': '&gt;',
      '"': '&quot;',
      "'": '&#039;',
    }[char]));
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

  function formatDate(value) {
    return formatAppDate(value);
  }

  function formatDateTime(value) {
    return formatAppDateTime(value);
  }

  function iconSvg(name) {
    const icons = {
      view: '<path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/>',
      edit: '<path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4 12.5-12.5Z"/>',
      delete: '<path d="M3 6h18"/><path d="M8 6V4h8v2"/><path d="M19 6l-1 14H6L5 6"/><path d="M10 11v6M14 11v6"/>',
      info: '<circle cx="12" cy="12" r="10"/><path d="M12 16v-4M12 8h.01"/>',
      approve: '<path d="M20 6 9 17l-5-5"/>',
      reject: '<path d="M18 6 6 18M6 6l12 12"/>',
    };
    return `<svg viewBox="0 0 24 24">${icons[name] || icons.view}</svg>`;
  }

  function renderStatusBadge(status) {
    const normalized = String(status || '').toLowerCase();
    const badgeClass = ['approved', 'completed', 'active'].includes(normalized)
      ? 'badge-approved'
      : (['pending', 'open'].includes(normalized) ? 'badge-pending' : 'badge-rejected');

    return `<span class="badge ${badgeClass}">${escapeHtml(String(status || '').replace('_', ' '))}</span>`;
  }

  function getClassroomStudentPendingCount(studentId) {
    return currentClassroomTasks.filter(task => Number(task.student_id) === Number(studentId) && task.status === 'pending').length;
  }

  function getTaskStudentName(task) {
    return task.student && task.student.user ? task.student.user.name : 'Student';
  }

  function getTaskStudentInternId(task) {
    return task.student && task.student.intern_id ? task.student.intern_id : '';
  }

  function getTaskDate(task) {
    return task.attendance && task.attendance.date ? task.attendance.date : '';
  }

  function normalizeDateFilterValue(value) {
    const text = String(value || '').trim();
    const displayMatch = text.match(/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/);
    if (displayMatch) {
      return `${displayMatch[3]}-${String(displayMatch[1]).padStart(2, '0')}-${String(displayMatch[2]).padStart(2, '0')}`;
    }
    return text;
  }

  function setTaskStatusFilter(value) {
    const statusFilter = document.getElementById('task-status-filter');
    if (statusFilter) statusFilter.value = value;
  }

  function updateTaskQueueBulkActions() {
    const bulkActions = document.getElementById('task-queue-bulk-actions');
    const searchWrapper = document.getElementById('task-search-wrapper');
    if (bulkActions) bulkActions.style.display = currentTaskStudentFilter ? 'none' : 'flex';
    if (searchWrapper) searchWrapper.style.display = currentTaskStudentFilter ? 'none' : 'inline-flex';
  }

  function renderTaskActions(task, cid) {
    if (task.status !== 'pending') {
      return `
        <div style="display:flex; gap:8px; flex-wrap:wrap;">
          <button type="button" onclick="openClassroomTaskDetail(${task.id})" class="icon-action-button" title="View task details" aria-label="View task details">${iconSvg('view')}</button>
        </div>
      `;
    }

    return `
      <div style="display:flex; gap:8px; flex-wrap:wrap;">
        <button type="button" onclick="openClassroomTaskDetail(${task.id})" class="icon-action-button" title="View task details" aria-label="View task details">${iconSvg('view')}</button>
        <button type="button" onclick="handleApproveTask(${task.id}, ${cid})" class="icon-action-button" title="Approve task" aria-label="Approve task">${iconSvg('approve')}</button>
        <button type="button" onclick="openClassroomRejectTaskModal(${task.id})" class="icon-action-button" title="Reject task" aria-label="Reject task" style="color:#991B1B;">${iconSvg('reject')}</button>
      </div>
    `;
  }

  function renderTasksTable(tasks, emptyMessage = 'No pending tasks to review for this classroom.') {
    const tBody = document.getElementById('nested-tasks-tbody');
    tBody.innerHTML = tasks.length > 0 ? tasks.map(t => {
      const studentPendingCount = getClassroomStudentPendingCount(t.student_id);
      const studentName = getTaskStudentName(t);
      const internId = getTaskStudentInternId(t);
      return `
        <tr>
          <td>
            <strong>${escapeHtml(studentName)}</strong>
            ${internId ? `<br><code style="font-size:12px; font-weight:800; color:#004798;">${escapeHtml(internId)}</code>` : ''}
            <div style="margin-top:6px;">${studentPendingCount > 1 ? `<span class="badge badge-pending">${studentPendingCount} pending reports</span>` : ''}</div>
          </td>
          <td><strong>${escapeHtml(t.title)}</strong><br><small style="color:#6B7280;">${escapeHtml(t.description)}</small></td>
          <td>${escapeHtml(t.category)}</td>
          <td>${renderStatusBadge(t.status)}</td>
          <td>${t.attendance && t.attendance.date ? formatDate(t.attendance.date) : 'N/A'}</td>
          <td>${formatDateTime(t.submitted_at)}</td>
          <td>${renderAttachments(t.attachments)}</td>
          <td>${renderTaskActions(t, currentClassroomId)}</td>
        </tr>
      `;
    }).join('') : `<tr><td colspan="8" style="text-align:center;">${escapeHtml(emptyMessage)}</td></tr>`;
  }

  function renderTaskQueue(emptyMessage = null) {
    const note = document.getElementById('tasks-filter-note');
    const searchFilter = document.getElementById('task-student-search-filter');
    const dateFilter = document.getElementById('task-date-filter');
    const statusFilter = document.getElementById('task-status-filter');
    const search = searchFilter ? searchFilter.value.trim().toLowerCase() : '';
    const date = dateFilter ? normalizeDateFilterValue(dateFilter.value) : '';
    const status = statusFilter ? statusFilter.value : 'pending';

    if (note) note.style.display = currentTaskStudentFilter ? 'flex' : 'none';
    updateTaskQueueBulkActions();

    let tasks = currentClassroomTasks.slice();
    if (currentTaskStudentFilter) {
      tasks = tasks.filter(task => Number(task.student_id) === Number(currentTaskStudentFilter));
    }
    if (status) {
      tasks = tasks.filter(task => task.status === status);
    }
    if (date) {
      tasks = tasks.filter(task => getTaskDate(task) === date);
    }
    if (search) {
      tasks = tasks.filter(task => {
        const haystack = `${getTaskStudentName(task)} ${getTaskStudentInternId(task)}`.toLowerCase();
        return haystack.includes(search);
      });
    }

    const selectedStudent = currentStudentMonitoring.find(item => Number(item.student_id) === Number(currentTaskStudentFilter));
    const fallbackMessage = currentTaskStudentFilter
      ? `No ${status || 'matching'} tasks found for ${selectedStudent ? selectedStudent.student_name : 'this student'}.`
      : (status === 'pending' ? 'No pending tasks to review for this classroom.' : 'No tasks match the selected filters.');

    renderTasksTable(tasks, emptyMessage || fallbackMessage);
  }

  function renderPendingTaskQueue() {
    const note = document.getElementById('tasks-filter-note');
    const searchFilter = document.getElementById('task-student-search-filter');
    const dateFilter = document.getElementById('task-date-filter');
    currentTaskStudentFilter = null;
    if (searchFilter) searchFilter.value = '';
    if (dateFilter) dateFilter.value = '';
    setTaskStatusFilter('pending');
    if (note) note.style.display = 'none';
    updateTaskQueueBulkActions();
    renderTaskQueue('No pending tasks to review for this classroom.');
  }

  function clearTaskFilters() {
    const searchFilter = document.getElementById('task-student-search-filter');
    const dateFilter = document.getElementById('task-date-filter');
    if (searchFilter) searchFilter.value = '';
    if (dateFilter) dateFilter.value = '';
    setTaskStatusFilter('pending');
    renderTaskQueue();
  }

  function viewStudentTasks(studentId) {
    const student = currentStudentMonitoring.find(item => Number(item.student_id) === Number(studentId));
    const studentName = student ? student.student_name : 'student';
    const note = document.getElementById('tasks-filter-note');
    const searchFilter = document.getElementById('task-student-search-filter');
    const dateFilter = document.getElementById('task-date-filter');
    const pendingCount = getClassroomStudentPendingCount(studentId);
    currentTaskStudentFilter = studentId;
    if (searchFilter) searchFilter.value = '';
    if (dateFilter) dateFilter.value = '';
    setTaskStatusFilter('pending');
    document.getElementById('tasks-filter-label').textContent = `Pending tasks for ${studentName} (${pendingCount} pending)`;
    document.getElementById('approve-filtered-student-button').textContent = `Approve ${studentName} Pending (${pendingCount})`;
    if (note) note.style.display = 'flex';
    renderTaskQueue(`No pending tasks found for ${studentName}.`);
    switchNestedTab('tasks');
  }

  function renderMonitoringRoster() {
    const mBody = document.getElementById('nested-monitoring-tbody');
    const searchFilter = document.getElementById('monitoring-search-filter');
    const sortFilter = document.getElementById('monitoring-rendered-sort');
    if (!mBody) return;

    const search = searchFilter ? searchFilter.value.trim().toLowerCase() : '';
    const sort = sortFilter ? sortFilter.value : '';
    let students = currentStudentMonitoring.slice();

    if (search) {
      students = students.filter(student => {
        const haystack = `${student.student_name || ''} ${student.intern_id || ''}`.toLowerCase();
        return haystack.includes(search);
      });
    }

    if (sort === 'highest') {
      students.sort((a, b) => Number(b.total_rendered_hours || 0) - Number(a.total_rendered_hours || 0));
    } else if (sort === 'lowest') {
      students.sort((a, b) => Number(a.total_rendered_hours || 0) - Number(b.total_rendered_hours || 0));
    }

    mBody.innerHTML = students.length > 0 ? students.map(s => {
      const studentName = escapeHtml(s.student_name);
      const rawPhotoUrl = s.profile_photo_url || @json(asset('images/logo.png'));
      const photoUrl = escapeHtml(rawPhotoUrl);
      const photoPreviewUrl = escapeAppHtml(escapeAppJsString(rawPhotoUrl));
      const photoPreviewName = escapeAppHtml(escapeAppJsString(`${s.student_name || 'Student'} profile photo`));
      const pendingCount = getClassroomStudentPendingCount(s.student_id);
      return `
        <tr>
          <td>
            <span class="student-name-with-photo">
              <button type="button" class="student-photo-button" onclick="previewAttachment('${photoPreviewUrl}', '${photoPreviewName}', 'image')" title="Preview profile photo">
                <img src="${photoUrl}" alt="${studentName} profile photo" class="student-roster-photo">
              </button>
              <strong>${studentName}</strong>
            </span>
          </td>
          <td><code style="font-size:14px; font-weight:800; color:#004798;">${escapeHtml(s.intern_id)}</code></td>
          <td>${escapeHtml(s.total_rendered_hours)} hrs</td>
          <td>${escapeHtml(s.required_target_hours)} hrs</td>
          <td>${escapeHtml(s.remaining_hours)} hrs</td>
          <td>${escapeHtml(s.progress_percentage)}%</td>
          <td>
            <div style="display:flex; gap:8px; flex-wrap:wrap;">
              <button type="button" onclick="openStudentInfoModal(${s.student_id})" class="icon-action-button" title="View student information" aria-label="View student information">
                ${iconSvg('info')}
              </button>
              <button type="button" onclick="handleUnenrollStudent(${s.student_id})" class="icon-action-button" title="Unenroll student" aria-label="Unenroll student" style="color:#991B1B;">${iconSvg('delete')}</button>
            </div>
          </td>
        </tr>
      `;
    }).join('') : `<tr><td colspan="7" style="text-align:center;">${currentStudentMonitoring.length > 0 ? 'No students match the selected filters.' : 'No students enrolled in this classroom.'}</td></tr>`;
  }

  function resetClassroomDetailFilters() {
    const monitoringSearch = document.getElementById('monitoring-search-filter');
    const monitoringSort = document.getElementById('monitoring-rendered-sort');
    const taskSearch = document.getElementById('task-student-search-filter');
    const taskDate = document.getElementById('task-date-filter');

    if (monitoringSearch) monitoringSearch.value = '';
    if (monitoringSort) monitoringSort.value = '';
    if (taskSearch) taskSearch.value = '';
    if (taskDate) taskDate.value = '';
    setTaskStatusFilter('pending');
  }

  function getSelectedStudent() {
    return currentStudentMonitoring.find(item => Number(item.student_id) === Number(selectedStudentId));
  }

  function getStudentTasks(studentId) {
    return currentClassroomTasks.filter(task => Number(task.student_id) === Number(studentId));
  }

  function getStudentAttendance(studentId) {
    return currentClassroomAttendance.filter(record => Number(record.student_id) === Number(studentId));
  }

  function getAttendanceTask(attendanceId, studentId) {
    return currentClassroomTasks.find(task => Number(task.attendance_id) === Number(attendanceId) && Number(task.student_id) === Number(studentId));
  }

  function getLocalDateKey(date) {
    return `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')}`;
  }

  function getDateValue(value) {
    return value ? String(value).slice(0, 10) : '';
  }

  function renderStudentInfoProfile(student) {
    const rawPhotoUrl = student.profile_photo_url || @json(asset('images/logo.png'));
    const photoUrl = escapeHtml(rawPhotoUrl);
    const photoPreviewUrl = escapeAppHtml(escapeAppJsString(rawPhotoUrl));
    const photoPreviewName = escapeAppHtml(escapeAppJsString(`${student.student_name || 'Student'} profile photo`));

    return `
      <div class="student-profile-summary">
        <button type="button" class="student-photo-button" onclick="previewAttachment('${photoPreviewUrl}', '${photoPreviewName}', 'image')" title="Preview profile photo">
          <img src="${photoUrl}" alt="Student profile photo">
        </button>
        <div class="info-grid">
          <div class="info-row"><strong>Full Name</strong><span>${escapeHtml(student.student_name)}</span></div>
          <div class="info-row"><strong>Email</strong><span>${escapeHtml(student.email)}</span></div>
          <div class="info-row"><strong>Contact Number</strong><span>${escapeHtml(student.contact_number)}</span></div>
          <div class="info-row"><strong>Intern ID</strong><span>${escapeHtml(student.intern_id)}</span></div>
          <div class="info-row"><strong>Course</strong><span>${escapeHtml(student.course_name || 'N/A')}</span></div>
          <div class="info-row"><strong>Company</strong><span>${escapeHtml(student.company_name)}</span></div>
          <div class="info-row"><strong>Location</strong><span>${escapeHtml(student.organization_location)}</span></div>
          <div class="info-row"><strong>Internship Start</strong><span>${formatDate(student.internship_start_date)}</span></div>
          <div class="info-row"><strong>Internship End</strong><span>${formatDate(student.internship_end_date)}</span></div>
          <div class="info-row"><strong>Rendered Hours</strong><span>${escapeHtml(student.total_rendered_hours)} / ${escapeHtml(student.required_target_hours)} hrs</span></div>
          <div class="info-row"><strong>Progress</strong><span>${escapeHtml(student.progress_percentage)}% (${escapeHtml(student.status_badge)})</span></div>
        </div>
      </div>
    `;
  }

  function renderStudentInfoTasks(student) {
    const tasks = getStudentTasks(student.student_id);

    return `
      <div style="overflow-x:auto;">
        <table class="solid-table">
          <thead>
            <tr>
              <th>Task Title & Details</th>
              <th>Category</th>
              <th>Status</th>
              <th>DTR Date</th>
              <th>Submitted At</th>
              <th>Teacher Feedback</th>
              <th>Attachments</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            ${tasks.length > 0 ? tasks.map(task => `
              <tr>
                <td><strong>${escapeHtml(task.title)}</strong><br><small style="color:#6B7280;">${escapeHtml(task.description)}</small></td>
                <td>${escapeHtml(task.category || 'General')}</td>
                <td>${renderStatusBadge(task.status)}</td>
                <td>${task.attendance && task.attendance.date ? formatDate(task.attendance.date) : 'N/A'}</td>
                <td>${formatDateTime(task.submitted_at)}</td>
                <td>${task.teacher_feedback ? escapeHtml(task.teacher_feedback) : '<em>No feedback yet</em>'}</td>
                <td>${renderAttachments(task.attachments)}</td>
                <td>${renderTaskActions(task, currentClassroomId)}</td>
              </tr>
            `).join('') : '<tr><td colspan="8" style="text-align:center;">No accomplishment reports submitted by this student.</td></tr>'}
          </tbody>
        </table>
      </div>
    `;
  }

  function renderStudentDtrDetails(record) {
    if (!record) {
      return '<p style="margin-top:0;color:#4B5563;">Select a DTR date to view the exact time in, time out, rendered hours, and submitted task.</p>';
    }

    const task = getAttendanceTask(record.id, record.student_id);
    return `
      <div class="info-grid">
        <div class="info-row"><strong>Date</strong><span>${formatDate(record.date)}</span></div>
        <div class="info-row"><strong>Time In</strong><span>${formatAppTime(record.time_in)}</span></div>
        <div class="info-row"><strong>Time Out</strong><span>${record.time_out ? formatAppTime(record.time_out) : 'Session Open'}</span></div>
        <div class="info-row"><strong>Rendered Hours</strong><span>${Number(record.rendered_hours || 0).toFixed(2)} hrs</span></div>
        <div class="info-row"><strong>Status</strong><span>${renderStatusBadge(record.status)}</span></div>
        <div class="info-row"><strong>Task</strong><span>${task ? `${escapeHtml(task.title)} ${renderStatusBadge(task.status)}` : '<em>No submitted task</em>'}</span></div>
        ${task ? `<div class="info-row"><strong>Attachments</strong><span>${renderAttachments(task.attachments)}</span></div>` : ''}
      </div>
    `;
  }

  function renderStudentDtrCalendar(student) {
    const attendance = getStudentAttendance(student.student_id);
    const year = selectedStudentDtrMonth.getFullYear();
    const month = selectedStudentDtrMonth.getMonth();
    const firstDay = new Date(year, month, 1);
    const lastDay = new Date(year, month + 1, 0);
    const days = [];

    for (let index = 0; index < firstDay.getDay(); index += 1) days.push({ empty: true });
    for (let day = 1; day <= lastDay.getDate(); day += 1) {
      const isoDate = getLocalDateKey(new Date(year, month, day));
      days.push({
        day,
        isoDate,
        record: attendance.find(item => getDateValue(item.date) === isoDate),
      });
    }
    while (days.length % 7 !== 0) days.push({ empty: true });

    const dayNames = ['SUN', 'MON', 'TUE', 'WED', 'THU', 'FRI', 'SAT'];
    return `
      <div class="detail-panel-actions">
        <button type="button" class="btn-secondary" onclick="changeStudentDtrMonth(-1)">Prev</button>
        <strong>${String(month + 1).padStart(2, '0')}/${year}</strong>
        <button type="button" class="btn-secondary" onclick="changeStudentDtrMonth(1)">Next</button>
      </div>
      <div class="dtr-calendar-layout">
        <div>
          <div class="dtr-calendar-grid" style="margin-bottom:8px;">
            ${dayNames.map(name => `<div class="dtr-calendar-day-name">${name}</div>`).join('')}
          </div>
          <div class="dtr-calendar-grid">
            ${days.map(day => {
              if (day.empty) return '<div></div>';
              const record = day.record;
              const task = record ? getAttendanceTask(record.id, record.student_id) : null;
              const border = record ? (task ? '2px solid #10B981' : (record.time_out ? '2px solid #F59E0B' : '1px solid #CBD5E1')) : '1px solid #E5E7EB';
              return `<button type="button" class="dtr-calendar-day" ${record ? '' : 'disabled'} onclick="selectStudentDtrDate('${day.isoDate}')" style="border:${border};">${day.day}</button>`;
            }).join('')}
          </div>
        </div>
        <div id="student-dtr-date-details" style="border:1px solid #E5E7EB;border-radius:8px;padding:16px;background:#FFFFFF;">
          ${renderStudentDtrDetails(null)}
        </div>
      </div>
    `;
  }

  function renderStudentDtrTable(student) {
    const attendance = getStudentAttendance(student.student_id);
    return `
      <div style="overflow-x:auto;">
        <table class="solid-table">
          <thead>
            <tr>
              <th>Date</th>
              <th>Time In</th>
              <th>Time Out</th>
              <th>Rendered Hours</th>
              <th>Status</th>
              <th>Submitted Task</th>
            </tr>
          </thead>
          <tbody>
            ${attendance.length > 0 ? attendance.map(record => {
              const task = getAttendanceTask(record.id, record.student_id);
              return `
                <tr>
                  <td>${formatDate(record.date)}</td>
                  <td>${formatAppTime(record.time_in)}</td>
                  <td>${record.time_out ? formatAppTime(record.time_out) : 'Session Open'}</td>
                  <td>${Number(record.rendered_hours || 0).toFixed(2)} hrs</td>
                  <td>${renderStatusBadge(record.status)}</td>
                  <td>${task ? `${escapeHtml(task.title)} ${renderStatusBadge(task.status)}` : '<em>No submitted task</em>'}</td>
                </tr>
              `;
            }).join('') : '<tr><td colspan="6" style="text-align:center;">No DTR records found for this student.</td></tr>'}
          </tbody>
        </table>
      </div>
    `;
  }

  function renderStudentInfoDtr(student, view = 'date') {
    return `
      <div class="detail-panel-actions">
        <div class="calendar-legend">
          <span class="calendar-legend-item"><span class="calendar-legend-swatch" style="border:2px solid #F59E0B;"></span>Time in/out</span>
          <span class="calendar-legend-item"><span class="calendar-legend-swatch" style="border:2px solid #10B981;"></span>With submitted task</span>
        </div>
        <div class="dtr-view-toggle">
          <button type="button" class="${view === 'date' ? 'btn-primary' : 'btn-secondary'}" onclick="switchStudentDtrView('date')">By Date</button>
          <button type="button" class="${view === 'table' ? 'btn-primary' : 'btn-secondary'}" onclick="switchStudentDtrView('table')">By Table</button>
        </div>
      </div>
      ${view === 'date' ? renderStudentDtrCalendar(student) : renderStudentDtrTable(student)}
    `;
  }

  function switchStudentInfoTab(tab) {
    const student = getSelectedStudent();
    if (!student) return;

    ['profile', 'tasks', 'dtr'].forEach(item => {
      const button = document.getElementById(`student-tab-${item}`);
      if (button) button.classList.toggle('active', item === tab);
    });

    const body = document.getElementById('student-info-body');
    if (tab === 'tasks') {
      body.innerHTML = renderStudentInfoTasks(student);
    } else if (tab === 'dtr') {
      body.innerHTML = renderStudentInfoDtr(student, 'date');
    } else {
      body.innerHTML = renderStudentInfoProfile(student);
    }
  }

  function switchStudentDtrView(view) {
    const student = getSelectedStudent();
    if (!student) return;
    document.getElementById('student-info-body').innerHTML = renderStudentInfoDtr(student, view);
  }

  function changeStudentDtrMonth(offset) {
    selectedStudentDtrMonth = new Date(selectedStudentDtrMonth.getFullYear(), selectedStudentDtrMonth.getMonth() + offset, 1);
    switchStudentDtrView('date');
  }

  function selectStudentDtrDate(dateString) {
    const record = getStudentAttendance(selectedStudentId).find(item => getDateValue(item.date) === dateString);
    const details = document.getElementById('student-dtr-date-details');
    if (details) details.innerHTML = renderStudentDtrDetails(record);
  }

  function openStudentInfoModal(studentId, initialTab = 'profile') {
    const student = currentStudentMonitoring.find(item => Number(item.student_id) === Number(studentId));
    if (!student) return;

    selectedStudentId = studentId;
    selectedStudentDtrMonth = new Date();
    document.getElementById('student-info-title').textContent = 'Student Details';
    document.getElementById('student-info-eyebrow').textContent = student.intern_id ? `Intern ID ${student.intern_id}` : 'Student Details';

    const modal = document.getElementById('student-info-modal');
    modal.hidden = false;
    modal.setAttribute('aria-hidden', 'false');
    switchStudentInfoTab(initialTab);
  }

  function closeStudentInfoModal() {
    const modal = document.getElementById('student-info-modal');
    selectedStudentId = null;
    modal.hidden = true;
    modal.setAttribute('aria-hidden', 'true');
  }

  function openCreateClassroomModal() {
    document.getElementById('create-classroom-title').textContent = 'Create New Internship Classroom';
    document.getElementById('classroom-form-submit').textContent = 'Generate Classroom & 5-Digit Code';
    document.getElementById('cr-edit-id').value = '';
    document.getElementById('classroom-form').reset();
    document.getElementById('cr-hours').value = '400';
    document.getElementById('cr-sem').value = '2nd Semester';
    document.getElementById('cr-ay').value = '2025-2026';
    const modal = document.getElementById('create-classroom-modal');
    modal.hidden = false;
    modal.setAttribute('aria-hidden', 'false');
    setTimeout(() => document.getElementById('cr-name').focus(), 100);
  }

  function openEditClassroomModal(cid) {
    const classroom = classroomsData.find(item => Number(item.id) === Number(cid));
    if (!classroom) return;

    document.getElementById('create-classroom-title').textContent = 'Edit Internship Classroom';
    document.getElementById('classroom-form-submit').textContent = 'Save Classroom Changes';
    document.getElementById('cr-edit-id').value = classroom.id;
    document.getElementById('cr-name').value = classroom.classroom_name || '';
    document.getElementById('cr-hours').value = classroom.required_hours || 400;
    document.getElementById('cr-sem').value = classroom.semester || '';
    document.getElementById('cr-ay').value = classroom.academic_year || '';
    document.getElementById('cr-start').value = classroom.start_date ? String(classroom.start_date).slice(0, 10) : '';
    document.getElementById('cr-end').value = classroom.end_date ? String(classroom.end_date).slice(0, 10) : '';

    const modal = document.getElementById('create-classroom-modal');
    modal.hidden = false;
    modal.setAttribute('aria-hidden', 'false');
    setTimeout(() => document.getElementById('cr-name').focus(), 100);
  }

  function closeCreateClassroomModal() {
    const modal = document.getElementById('create-classroom-modal');
    modal.hidden = true;
    modal.setAttribute('aria-hidden', 'true');
    document.getElementById('classroom-form').reset();
    document.getElementById('cr-edit-id').value = '';
    document.getElementById('create-classroom-title').textContent = 'Create New Internship Classroom';
    document.getElementById('classroom-form-submit').textContent = 'Generate Classroom & 5-Digit Code';
    document.getElementById('cr-hours').value = '400';
    document.getElementById('cr-sem').value = '2nd Semester';
    document.getElementById('cr-ay').value = '2025-2026';
  }

  async function loadTeacherClassrooms() {
    const tbody = document.getElementById('cr-list-tbody');
    try {
      classroomsData = await apiRequest('/teacher/classrooms');
      tbody.innerHTML = classroomsData.length > 0 ? classroomsData.map(c => `
        <tr>
          <td><strong>${c.classroom_name}</strong></td>
          <td><code style="font-size:16px; font-weight:800; color:#004798;">${c.classroom_code}</code></td>
          <td>${c.required_hours} hrs</td>
          <td>${c.semester}</td>
          <td>${c.academic_year}</td>
          <td>
            <div style="display:flex; gap:8px;">
              <button type="button" onclick="viewClassroomDetail(${c.id})" class="icon-action-button" title="View and manage classroom" aria-label="View and manage classroom">${iconSvg('view')}</button>
              <button type="button" onclick="openEditClassroomModal(${c.id})" class="icon-action-button" title="Edit classroom" aria-label="Edit classroom">${iconSvg('edit')}</button>
              <button type="button" onclick="deleteClassroom(${c.id})" class="icon-action-button" title="Delete classroom" aria-label="Delete classroom" style="color:#991B1B;">${iconSvg('delete')}</button>
            </div>
          </td>
        </tr>
      `).join('') : '<tr><td colspan="6" style="text-align:center;">No classrooms created yet.</td></tr>';
      const params = new URLSearchParams(window.location.search);
      const viewId = Number(params.get('view'));
      if (viewId && classroomsData.some(classroom => Number(classroom.id) === viewId)) {
        viewClassroomDetail(viewId, params.get('tab') || 'monitoring', false);
      }
    } catch(err) {}
  }
  loadTeacherClassrooms();

  async function handleClassroomFormSubmit(e) {
    e.preventDefault();
    const editId = document.getElementById('cr-edit-id').value;
    const payload = {
      classroom_name: document.getElementById('cr-name').value,
      required_hours: document.getElementById('cr-hours').value,
      semester: document.getElementById('cr-sem').value,
      academic_year: document.getElementById('cr-ay').value,
      start_date: document.getElementById('cr-start').value || null,
      end_date: document.getElementById('cr-end').value || null,
    };

    try {
      const res = editId
        ? await apiRequest(`/teacher/classrooms/${editId}`, 'PUT', payload)
        : await apiRequest('/teacher/classrooms', 'POST', payload);
      showToast(editId ? res.message : `Classroom created! Invitation Code: ${res.classroom.classroom_code}`);
      closeCreateClassroomModal();
      loadTeacherClassrooms();
    } catch(err) {}
  }

  async function deleteClassroom(cid) {
    const confirmed = await showAppConfirm('Are you sure you want to delete this classroom?');
    if (!confirmed) return;
    try {
      const res = await apiRequest(`/teacher/classrooms/${cid}`, 'DELETE');
      showToast(res.message);
      loadTeacherClassrooms();
    } catch(err) {}
  }

  async function viewClassroomDetail(cid, initialTab = 'monitoring', updateUrl = true) {
    const mainView = document.getElementById('classrooms-main-view');
    const detailContainer = document.getElementById('selected-classroom-detail');
    currentClassroomId = cid;
    mainView.style.display = 'none';
    detailContainer.style.display = 'block';
    if (updateUrl) {
      window.history.replaceState(null, '', `/teacher/classrooms?view=${cid}`);
    }

    try {
      const res = await apiRequest(`/teacher/classrooms/${cid}`);
      currentStudentMonitoring = res.student_monitoring || [];
      currentClassroomTasks = res.tasks || res.pending_tasks || [];
      currentClassroomAttendance = res.attendance || [];
      document.getElementById('view-cr-name').textContent = res.classroom.classroom_name;
      document.getElementById('view-cr-code').textContent = res.classroom.classroom_code;

      resetClassroomDetailFilters();
      renderMonitoringRoster();

      // Populate Pending Tasks Queue
      renderPendingTaskQueue();

      switchNestedTab(initialTab);
      document.querySelector('.portal-main-view').scrollTo({ top: 0, behavior: 'smooth' });
    } catch(err) {
      currentClassroomId = null;
      mainView.style.display = 'block';
      detailContainer.style.display = 'none';
    }
  }

  function closeClassroomDetail() {
    currentClassroomId = null;
    currentClassroomTasks = [];
    currentStudentMonitoring = [];
    currentClassroomAttendance = [];
    currentTaskStudentFilter = null;
    classroomTaskPendingReject = null;
    resetClassroomDetailFilters();
    updateTaskQueueBulkActions();
    document.getElementById('classrooms-main-view').style.display = 'block';
    document.getElementById('selected-classroom-detail').style.display = 'none';
    window.history.replaceState(null, '', '/teacher/classrooms');
    document.querySelector('.portal-main-view').scrollTo({ top: 0, behavior: 'smooth' });
  }

  async function downloadClassroomSummaryPdf() {
    if (!currentClassroomId) return;

    try {
      const response = await fetch(`${API_BASE}/teacher/reports/classroom/${currentClassroomId}/export`, {
        headers: {
          'Authorization': `Bearer ${authToken}`,
        },
      });

      if (!response.ok) {
        let message = 'Unable to generate classroom summary PDF.';
        try {
          message = extractApiMessage(await response.json());
        } catch (e) {}
        throw new Error(message);
      }

      const blob = await response.blob();
      const disposition = response.headers.get('Content-Disposition') || '';
      const filenameMatch = disposition.match(/filename="?([^"]+)"?/);
      const filename = filenameMatch ? filenameMatch[1] : 'classroom-summary.pdf';
      const url = window.URL.createObjectURL(blob);
      const link = document.createElement('a');
      link.href = url;
      link.download = filename;
      document.body.appendChild(link);
      link.click();
      link.remove();
      window.URL.revokeObjectURL(url);
      showToast('Classroom summary PDF downloaded.');
    } catch (err) {
      showToast(err.message, true);
    }
  }

  function switchNestedTab(tab) {
    const mView = document.getElementById('nested-monitoring-view');
    const tView = document.getElementById('nested-tasks-view');
    const mBtn = document.getElementById('tab-btn-monitoring');
    const tBtn = document.getElementById('tab-btn-tasks');

    if (tab === 'monitoring') {
      mView.style.display = 'block';
      tView.style.display = 'none';
      mBtn.className = 'btn-primary';
      tBtn.className = 'btn-secondary';
    } else {
      mView.style.display = 'none';
      tView.style.display = 'block';
      mBtn.className = 'btn-secondary';
      tBtn.className = 'btn-primary';
    }
  }

  async function handleApproveTask(taskId, cid) {
    const studentFilter = currentTaskStudentFilter;
    const modalStudent = selectedStudentId;
    try {
      const res = await apiRequest(`/teacher/tasks/${taskId}/approve`, 'POST');
      showToast(res.message);
      await viewClassroomDetail(cid, 'tasks');
      if (modalStudent && currentStudentMonitoring.some(item => Number(item.student_id) === Number(modalStudent))) {
        openStudentInfoModal(modalStudent, 'tasks');
      } else if (studentFilter && currentStudentMonitoring.some(item => Number(item.student_id) === Number(studentFilter))) {
        viewStudentTasks(studentFilter);
      }
    } catch(err) {}
  }

  async function handleApproveAllClassroomTasks() {
    if (!currentClassroomId) return;

    const confirmed = await showAppConfirm('Approve all pending task submissions in this classroom?');
    if (!confirmed) return;

    try {
      const res = await apiRequest('/teacher/tasks/approvals/approve-all', 'POST', {
        classroom_id: currentClassroomId,
      });
      showToast(res.message);
      viewClassroomDetail(currentClassroomId, 'tasks');
    } catch(err) {}
  }

  async function handleApproveFilteredStudentTasks() {
    if (!currentClassroomId || !currentTaskStudentFilter) return;

    const studentFilter = currentTaskStudentFilter;
    const student = currentStudentMonitoring.find(item => Number(item.student_id) === Number(currentTaskStudentFilter));
    const studentName = student ? student.student_name : 'this student';
    const confirmed = await showAppConfirm(`Approve all pending task submissions for ${studentName}?`);
    if (!confirmed) return;

    try {
      const res = await apiRequest(`/teacher/tasks/approvals/students/${currentTaskStudentFilter}/approve-all`, 'POST', {
        classroom_id: currentClassroomId,
      });
      showToast(res.message);
      await viewClassroomDetail(currentClassroomId, 'tasks');
      if (studentFilter && currentStudentMonitoring.some(item => Number(item.student_id) === Number(studentFilter))) {
        viewStudentTasks(studentFilter);
      }
    } catch(err) {}
  }

  async function openClassroomTaskDetail(taskId) {
    try {
      const task = await apiRequest(`/teacher/tasks/${taskId}`);
      const student = task.student || {};
      const user = student.user || {};
      const attendance = task.attendance || {};
      const classroom = task.classroom || {};

      document.getElementById('classroom-task-detail-body').innerHTML = `
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
        <div class="info-row"><strong>Teacher Feedback</strong><span>${escapeHtml(task.teacher_feedback || 'No feedback yet')}</span></div>
        <div class="info-row"><strong>Attachments</strong><span>${renderAttachments(task.attachments)}</span></div>
      `;

      const modal = document.getElementById('classroom-task-detail-modal');
      modal.hidden = false;
      modal.setAttribute('aria-hidden', 'false');
    } catch(err) {}
  }

  function closeClassroomTaskDetailModal() {
    const modal = document.getElementById('classroom-task-detail-modal');
    modal.hidden = true;
    modal.setAttribute('aria-hidden', 'true');
  }

  function openClassroomRejectTaskModal(taskId) {
    classroomTaskPendingReject = taskId;
    document.getElementById('classroom-task-reject-reason').value = '';
    const modal = document.getElementById('classroom-task-reject-modal');
    modal.hidden = false;
    modal.setAttribute('aria-hidden', 'false');
    setTimeout(() => document.getElementById('classroom-task-reject-reason').focus(), 100);
  }

  function closeClassroomRejectTaskModal() {
    classroomTaskPendingReject = null;
    const modal = document.getElementById('classroom-task-reject-modal');
    modal.hidden = true;
    modal.setAttribute('aria-hidden', 'true');
  }

  async function submitClassroomRejectTask(event) {
    event.preventDefault();
    if (!classroomTaskPendingReject || !currentClassroomId) return;

    const studentFilter = currentTaskStudentFilter;
    const modalStudent = selectedStudentId;
    try {
      const res = await apiRequest(`/teacher/tasks/${classroomTaskPendingReject}/reject`, 'POST', {
        reason: document.getElementById('classroom-task-reject-reason').value,
      });
      showToast(res.message);
      closeClassroomRejectTaskModal();
      await viewClassroomDetail(currentClassroomId, 'tasks');
      if (modalStudent && currentStudentMonitoring.some(item => Number(item.student_id) === Number(modalStudent))) {
        openStudentInfoModal(modalStudent, 'tasks');
      } else if (studentFilter && currentStudentMonitoring.some(item => Number(item.student_id) === Number(studentFilter))) {
        viewStudentTasks(studentFilter);
      }
    } catch(err) {}
  }

  async function handleUnenrollStudent(studentId) {
    const student = currentStudentMonitoring.find(item => Number(item.student_id) === Number(studentId));
    const studentName = student ? student.student_name : 'this student';
    const confirmed = await showAppConfirm(`Unenroll ${studentName} from this classroom?`);
    if (!confirmed) return;

    try {
      const res = await apiRequest(`/teacher/classrooms/${currentClassroomId}/students/${studentId}`, 'DELETE');
      showToast(res.message);
      viewClassroomDetail(currentClassroomId, 'monitoring');
    } catch(err) {}
  }
</script>
@endsection
