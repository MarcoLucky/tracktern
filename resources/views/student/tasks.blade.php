@extends('layouts.student')

@section('title', 'Task Submissions — TrackTern')
@section('page_heading', 'Task Log Submissions')
@section('nav_tasks', 'active')

@section('content')
<!-- Interactive DTR Calendar Overview -->
<div class="table-container" style="padding: 24px; margin-bottom: 30px;">
  <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 16px;">
    <h3>DTR Attendance Calendar Overview</h3>
    <div style="display:flex; gap:16px; font-size:12px; font-weight:700;">
      <span style="display:flex; align-items:center; gap:6px;"><span style="width:12px; height:12px; background-color:#10B981; display:inline-block; border-radius:2px;"></span> Completed DTR Day</span>
      <span style="display:flex; align-items:center; gap:6px;"><span style="width:12px; height:12px; background-color:#004798; display:inline-block; border-radius:2px;"></span> Open DTR Session</span>
    </div>
  </div>
  <div id="dtr-calendar-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(130px, 1fr)); gap: 12px;">
    <div style="text-align:center; padding: 20px;">Loading DTR calendar...</div>
  </div>
</div>

<!-- Task Submission Form -->
<div class="table-container" style="padding: 24px; margin-bottom: 30px;">
  <h3>Submit New Task Log</h3>
  <form onsubmit="handleTaskSubmit(event)" style="margin-top: 16px;">
    <div class="form-group">
      <label for="task-dtr">Select DTR Entry Accomplished For:</label>
      <select id="task-dtr" required>
        <option value="">-- Select DTR Attendance Date --</option>
      </select>
    </div>

    <div class="form-group">
      <label for="task-title">Task Title:</label>
      <input type="text" id="task-title" placeholder="Summary of task performed..." required>
    </div>

    <div class="form-group">
      <label for="task-cat">Category:</label>
      <input type="text" id="task-cat" placeholder="Documentation, Development, Design, Meeting, etc.">
    </div>

    <div class="form-group">
      <label for="task-desc">Task Description & Activities:</label>
      <textarea id="task-desc" rows="3" placeholder="Provide detailed description of internship work..." required></textarea>
    </div>

    <button type="submit" class="btn-primary">Submit Task Log</button>
  </form>
</div>

<!-- Submitted Tasks History -->
<div class="table-container">
  <div class="table-header">
    <h3>Submitted Tasks History</h3>
  </div>
  <table class="solid-table">
    <thead>
      <tr>
        <th>Accomplished DTR Date</th>
        <th>Title</th>
        <th>Category</th>
        <th>Submitted At</th>
        <th>Status</th>
        <th>Teacher Feedback</th>
      </tr>
    </thead>
    <tbody id="tasks-tbody">
      <tr><td colspan="6" style="text-align:center;">Loading task history...</td></tr>
    </tbody>
  </table>
</div>
@endsection

@section('scripts')
<script>
  let dtrRecords = [];

  async function loadData() {
    const calendarGrid = document.getElementById('dtr-calendar-grid');
    const dtrSelect = document.getElementById('task-dtr');
    const tasksTbody = document.getElementById('tasks-tbody');

    try {
      // 1. Fetch Student DTR log for Calendar & Dropdown
      const dtrRes = await apiRequest('/student/dtr');
      dtrRecords = dtrRes.attendance_records.data || [];

      if (dtrRecords.length > 0) {
        calendarGrid.innerHTML = dtrRecords.map(r => {
          const isCompleted = r.status === 'completed';
          const bgColor = isCompleted ? '#D1FAE5' : '#E0F2FE';
          const borderColor = isCompleted ? '#10B981' : '#004798';
          const textColor = isCompleted ? '#065F46' : '#0369A1';
          return `
            <div style="background-color: ${bgColor}; border: 2px solid ${borderColor}; padding: 10px; border-radius: 6px; text-align: center;">
              <div style="font-weight: 800; font-size: 13px; color: ${textColor};">${r.date}</div>
              <div style="font-size: 11px; margin-top: 4px; color: #1E1E1E;">${(r.rendered_minutes/60).toFixed(1)} hrs</div>
            </div>
          `;
        }).join('');

        dtrSelect.innerHTML = '<option value="">-- Select DTR Attendance Date --</option>' + dtrRecords.map(r => `
          <option value="${r.id}">DTR Entry: ${r.date} (${(r.rendered_minutes/60).toFixed(2)} hrs rendered)</option>
        `).join('');
      } else {
        calendarGrid.innerHTML = '<div style="grid-column: 1/-1; text-align:center; color:#6B7280;">No DTR records logged yet. Please record DTR Time In first.</div>';
        dtrSelect.innerHTML = '<option value="">No DTR records available. Time In first.</option>';
      }

      // 2. Fetch Submitted Tasks
      const tasksRes = await apiRequest('/student/tasks');
      tasksTbody.innerHTML = tasksRes.data.length > 0 ? tasksRes.data.map(t => `
        <tr>
          <td><strong>${t.attendance ? t.attendance.date : 'N/A'}</strong></td>
          <td><strong>${t.title}</strong><br><small style="color:#6B7280;">${t.description}</small></td>
          <td>${t.category}</td>
          <td>${new Date(t.submitted_at).toLocaleDateString()}</td>
          <td><span class="badge ${t.status === 'approved' ? 'badge-approved' : (t.status === 'pending' ? 'badge-pending' : 'badge-needs-revision')}">${t.status}</span></td>
          <td>${t.teacher_feedback || '<em>No feedback yet</em>'}</td>
        </tr>
      `).join('') : '<tr><td colspan="6" style="text-align:center;">No tasks submitted yet.</td></tr>';
    } catch(err) {}
  }
  loadData();

  async function handleTaskSubmit(e) {
    e.preventDefault();
    const dtrId = document.getElementById('task-dtr').value;
    if (!dtrId) {
      showToast('Please select the DTR entry you accomplished this task for.', true);
      return;
    }

    try {
      const res = await apiRequest('/student/tasks', 'POST', {
        attendance_id: dtrId,
        title: document.getElementById('task-title').value,
        category: document.getElementById('task-cat').value || 'General',
        description: document.getElementById('task-desc').value,
      });
      showToast(res.message);
      document.getElementById('task-title').value = '';
      document.getElementById('task-desc').value = '';
      loadData();
    } catch(err) {}
  }
</script>
@endsection
