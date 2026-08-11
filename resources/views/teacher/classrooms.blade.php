@extends('layouts.teacher')

@section('title', 'Classrooms — TrackTern')
@section('page_heading', 'Classroom Management')
@section('nav_classrooms', 'active')

@section('content')
<!-- Create Classroom Form Card -->
<div class="table-container" style="padding: 24px; margin-bottom: 30px;">
  <h3>Create New Internship Classroom</h3>
  <form onsubmit="handleCreateClassroom(event)" style="margin-top: 16px;">
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
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
    </div>
    <button type="submit" class="btn-primary">Generate Classroom & 5-Digit Code</button>
  </form>
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

<!-- Selected Classroom View Container (Nested Student Monitoring & Task Queue) -->
<div id="selected-classroom-detail" style="display: none;">
  <div class="table-container" style="padding: 24px; border-top: 4px solid #004798;">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 20px;">
      <div>
        <h2 id="view-cr-name" style="font-size: 24px; color: #004798;">Classroom Detail</h2>
        <div style="font-size: 14px; color: #4B5563;">Invitation Code: <code id="view-cr-code" style="font-size: 16px; font-weight:800; color:#004798;">CR902</code></div>
      </div>
      <button onclick="closeClassroomDetail()" class="btn-secondary">Close View</button>
    </div>

    <!-- Nested Tabs -->
    <div style="display: flex; gap: 12px; border-bottom: 2px solid #E5E7EB; margin-bottom: 20px;">
      <button onclick="switchNestedTab('monitoring')" id="tab-btn-monitoring" class="btn-primary" style="border-radius: 6px 6px 0 0;">Student Monitoring Roster</button>
      <button onclick="switchNestedTab('tasks')" id="tab-btn-tasks" class="btn-secondary" style="border-radius: 6px 6px 0 0;">Task Approval Queue</button>
    </div>

    <!-- Tab 1: Student Monitoring -->
    <div id="nested-monitoring-view">
      <table class="solid-table">
        <thead>
          <tr>
            <th>Student Name</th>
            <th>Intern ID</th>
            <th>Rendered Hours</th>
            <th>Target Hours</th>
            <th>Remaining</th>
            <th>Progress %</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody id="nested-monitoring-tbody">
          <tr><td colspan="7" style="text-align:center;">Loading classroom roster...</td></tr>
        </tbody>
      </table>
    </div>

    <!-- Tab 2: Task Approval Queue -->
    <div id="nested-tasks-view" style="display: none;">
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
        <tbody id="nested-tasks-tbody">
          <tr><td colspan="5" style="text-align:center;">Loading task approvals...</td></tr>
        </tbody>
      </table>
    </div>
  </div>
</div>
@endsection

@section('scripts')
<script>
  let classroomsData = [];

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
              <button onclick="viewClassroomDetail(${c.id})" class="btn-primary" style="padding:6px 12px; font-size:12px;">View & Manage</button>
              <button onclick="editClassroomPrompt(${c.id})" class="btn-secondary" style="padding:6px 12px; font-size:12px;">Edit</button>
              <button onclick="deleteClassroom(${c.id})" class="btn-danger" style="padding:6px 12px; font-size:12px;">Delete</button>
            </div>
          </td>
        </tr>
      `).join('') : '<tr><td colspan="6" style="text-align:center;">No classrooms created yet.</td></tr>';
    } catch(err) {}
  }
  loadTeacherClassrooms();

  async function handleCreateClassroom(e) {
    e.preventDefault();
    try {
      const res = await apiRequest('/teacher/classrooms', 'POST', {
        classroom_name: document.getElementById('cr-name').value,
        required_hours: document.getElementById('cr-hours').value,
        semester: document.getElementById('cr-sem').value,
        academic_year: document.getElementById('cr-ay').value,
      });
      showToast(`Classroom created! Invitation Code: ${res.classroom.classroom_code}`);
      document.getElementById('cr-name').value = '';
      loadTeacherClassrooms();
    } catch(err) {}
  }

  async function editClassroomPrompt(cid) {
    const c = classroomsData.find(item => item.id === cid);
    if (!c) return;

    const newName = prompt('Update Classroom Name:', c.classroom_name);
    if (newName === null) return;
    const newHours = prompt('Update Required Target Hours:', c.required_hours);
    if (newHours === null) return;

    try {
      const res = await apiRequest(`/teacher/classrooms/${cid}`, 'PUT', {
        classroom_name: newName,
        required_hours: newHours,
      });
      showToast(res.message);
      loadTeacherClassrooms();
    } catch(err) {}
  }

  async function deleteClassroom(cid) {
    if (!confirm('Are you sure you want to delete this classroom?')) return;
    try {
      const res = await apiRequest(`/teacher/classrooms/${cid}`, 'DELETE');
      showToast(res.message);
      loadTeacherClassrooms();
    } catch(err) {}
  }

  async function viewClassroomDetail(cid) {
    const detailContainer = document.getElementById('selected-classroom-detail');
    detailContainer.style.display = 'block';

    try {
      const res = await apiRequest(`/teacher/classrooms/${cid}`);
      document.getElementById('view-cr-name').textContent = res.classroom.classroom_name;
      document.getElementById('view-cr-code').textContent = res.classroom.classroom_code;

      // Populate Monitoring Roster
      const mBody = document.getElementById('nested-monitoring-tbody');
      mBody.innerHTML = res.student_monitoring.length > 0 ? res.student_monitoring.map(s => {
        let badgeClass = 'badge-on-track';
        if (s.status_badge === 'Needs Attention') badgeClass = 'badge-needs-attention';
        if (s.status_badge === 'Behind') badgeClass = 'badge-behind';
        if (s.status_badge === 'Completed') badgeClass = 'badge-completed';
        return `
          <tr>
            <td><strong>${s.student_name}</strong></td>
            <td><code style="font-size:14px; font-weight:800; color:#004798;">${s.intern_id}</code></td>
            <td>${s.total_rendered_hours} hrs</td>
            <td>${s.required_target_hours} hrs</td>
            <td>${s.remaining_hours} hrs</td>
            <td>${s.progress_percentage}%</td>
            <td><span class="badge ${badgeClass}">${s.status_badge}</span></td>
          </tr>
        `;
      }).join('') : '<tr><td colspan="7" style="text-align:center;">No students enrolled in this classroom.</td></tr>';

      // Populate Pending Tasks Queue
      const tBody = document.getElementById('nested-tasks-tbody');
      tBody.innerHTML = res.pending_tasks.length > 0 ? res.pending_tasks.map(t => `
        <tr>
          <td><strong>${t.student && t.student.user ? t.student.user.name : 'Student'}</strong></td>
          <td><strong>${t.title}</strong><br><small style="color:#6B7280;">${t.description}</small></td>
          <td>${t.category}</td>
          <td>${new Date(t.submitted_at).toLocaleDateString()}</td>
          <td>
            <div style="display:flex; gap:8px;">
              <button onclick="handleApproveTask(${t.id}, ${cid})" class="btn-primary" style="padding:6px 12px; font-size:12px;">Approve</button>
              <button onclick="handleRevisionTask(${t.id}, ${cid})" class="btn-danger" style="padding:6px 12px; font-size:12px;">Request Revision</button>
            </div>
          </td>
        </tr>
      `).join('') : '<tr><td colspan="5" style="text-align:center;">No pending tasks to review for this classroom.</td></tr>';

      detailContainer.scrollIntoView({ behavior: 'smooth' });
    } catch(err) {}
  }

  function closeClassroomDetail() {
    document.getElementById('selected-classroom-detail').style.display = 'none';
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
    try {
      const res = await apiRequest(`/teacher/tasks/${taskId}/approve`, 'POST');
      showToast(res.message);
      viewClassroomDetail(cid);
    } catch(err) {}
  }

  async function handleRevisionTask(taskId, cid) {
    const feedback = prompt('Enter actionable revision feedback for student:');
    if (!feedback) return;
    try {
      const res = await apiRequest(`/teacher/tasks/${taskId}/revision`, 'POST', { feedback });
      showToast(res.message);
      viewClassroomDetail(cid);
    } catch(err) {}
  }
</script>
@endsection
