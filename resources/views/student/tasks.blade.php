@extends('layouts.student')

@section('title', 'Accomplishment Report — TrackTern')
@section('page_heading', 'Accomplishment Report')
@section('nav_tasks', 'active')

@section('content')
<!-- Accomplishment Report Calendar -->
<div class="table-container" style="padding: 24px; margin-bottom: 30px;">
  <div style="display:flex; justify-content:space-between; align-items:flex-start; gap: 20px; flex-wrap: wrap; margin-bottom: 16px;">
    <div style="flex:1; min-width:280px;">
      <h3>Accomplishment Report Calendar</h3>
      <p style="font-size: 14px; color: #4B5563; margin-top: 6px; max-width: 640px;">The calendar reflects your recorded DTR time in and time out entries. Click a date to view the attendance details for that day and submit a task log for that DTR.</p>
    </div>
    <div style="display:flex; gap:12px; flex-wrap: wrap; align-items:center;">
      <button type="button" onclick="changeCalendarMonth(-1)" class="btn-secondary" style="padding: 10px 14px;">← Prev</button>
      <div id="calendar-month-label" style="font-size: 16px; font-weight: 700; min-width: 180px; text-align: center;"></div>
      <button type="button" onclick="changeCalendarMonth(1)" class="btn-secondary" style="padding: 10px 14px;">Next →</button>
      <button type="button" onclick="downloadMonthlyDtr()" class="btn-primary" style="padding: 10px 18px;">Download Monthly DTR</button>
    </div>
  </div>

  <div class="calendar-legend">
    <span class="calendar-legend-item">
      <span class="calendar-legend-swatch" style="border:2px solid #F59E0B;"></span>
      Time in and time out recorded
    </span>
    <span class="calendar-legend-item">
      <span class="calendar-legend-swatch" style="border:2px solid #10B981;"></span>
      Time in/out with submitted report
    </span>
  </div>

  <div style="display:grid; grid-template-columns: 1.25fr 0.75fr; gap: 20px;">
    <div>
      <div id="calendar-day-names" style="display:grid; grid-template-columns: repeat(7, 1fr); gap: 8px; margin-bottom: 10px; text-align:center; font-size: 12px; font-weight: 700; color:#475569;"></div>
      <div id="calendar-day-cells" style="display:grid; grid-template-columns: repeat(7, 1fr); gap: 10px;"></div>
    </div>

    <div id="calendar-detail-panel" style="background:#FFFFFF; border:1px solid #E5E7EB; border-radius:16px; padding:24px; min-height:280px;">
      <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
        <div>
          <div style="font-size: 13px; color:#6B7280; text-transform:uppercase; letter-spacing: 0.12em;">Selected Date</div>
          <div id="selected-date-label" style="font-size: 22px; font-weight: 800; margin-top: 6px; color:#111827;">Please click on a calendar date</div>
        </div>
      </div>
      <div id="selected-date-details" style="color:#374151; font-size: 14px; line-height: 1.7;">
        <p style="margin-top: 0;">Click a date that has a DTR record to view time in, time out, rendered hours, and status. You can also click the date to open the task submission popup prefilled with the selected DTR.</p>
      </div>
    </div>
  </div>
</div>

<!-- Modal: Submit New Task Log (pops up when clicking calendar date) -->
<div id="task-modal" class="app-modal-backdrop" hidden aria-hidden="true">
  <div class="app-modal-card" style="width:min(720px, 100%);">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px;">
      <div>
        <div id="modal-task-eyebrow" style="font-size:12px; color:#6B7280; text-transform:uppercase; letter-spacing:0.12em;">Submit New Task Log</div>
        <div id="modal-selected-date" style="font-weight:800; font-size:18px; margin-top:6px; color:#111827;">Selected DTR date</div>
      </div>
      <div>
        <button type="button" onclick="closeTaskModal()" class="btn-secondary">Close</button>
      </div>
    </div>

    <form id="modal-task-form" onsubmit="handleModalSubmit(event)">
      <input type="hidden" id="modal-task-dtr" />

      <div class="form-group" style="margin-bottom:10px;">
        <label for="modal-task-title">Task Title:</label>
        <input type="text" id="modal-task-title" placeholder="Summary of task performed..." required />
      </div>

      <div class="form-group" style="margin-bottom:10px; display:flex; gap:12px;">
        <div style="flex:1;">
          <label for="modal-task-cat">Category:</label>
          <input type="text" id="modal-task-cat" placeholder="Documentation, Development, Design..." />
        </div>
        <div style="flex:1;">
          <label for="modal-task-date">DTR Entry:</label>
          <select id="modal-task-date" disabled style="width:100%; padding:8px; border-radius:6px; border:1px solid #E5E7EB; background:#F9FAFB;"></select>
        </div>
      </div>

      <div class="form-group" style="margin-bottom:12px;">
        <label for="modal-task-desc">Task Description & Activities:</label>
        <textarea id="modal-task-desc" rows="4" placeholder="Provide detailed description of internship work..." required style="width:100%; padding:8px; border-radius:6px; border:1px solid #E5E7EB;"></textarea>
      </div>

      <div class="form-group" id="modal-task-attachment-group" style="margin-bottom:12px;">
        <label for="modal-task-attachments">Upload Attachments:</label>
        <input type="file" id="modal-task-attachments" multiple accept=".pdf,.doc,.docx,image/*,video/*" />
        <small style="display:block; color:#6B7280; margin-top:6px;">Attach PDFs, images, documents, or videos of your task output. Maximum 5 files, 50 MB each.</small>
      </div>

      <div id="modal-existing-attachments" style="margin-bottom:12px;"></div>

      <div style="display:flex; gap:10px; justify-content:flex-end;">
        <button type="button" id="modal-task-cancel-button" onclick="closeTaskModal()" class="btn-secondary">Cancel</button>
        <button type="submit" id="modal-task-submit-button" class="btn-primary">Submit Task Log</button>
      </div>
    </form>
  </div>
</div>
@endsection

@section('scripts')
<script>
  let dtrRecords = [];
  let submittedTasks = [];
  let selectedMonth = new Date();
  let selectedDate = null;

  function escapeHtml(value) {
    return String(value ?? '').replace(/[&<>"']/g, char => ({
      '&': '&amp;',
      '<': '&lt;',
      '>': '&gt;',
      '"': '&quot;',
      "'": '&#039;',
    }[char]));
  }

  function formatTimeDisplay(value) {
    return formatAppTime(value);
  }

  function formatDateLabel(dateString) {
    return formatAppDate(dateString);
  }

  function buildLocalDateKey(year, month, day) {
    return `${year}-${String(month + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
  }

  function buildMonthLabel(date) {
    return `${String(date.getMonth() + 1).padStart(2, '0')}/${date.getFullYear()}`;
  }

  function getMonthKey(date) {
    return `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}`;
  }

  function getCalendarDays(date) {
    const year = date.getFullYear();
    const month = date.getMonth();
    const firstDay = new Date(year, month, 1);
    const lastDay = new Date(year, month + 1, 0);
    const startWeekday = firstDay.getDay();
    const totalDays = lastDay.getDate();

    const days = [];
    for (let i = 0; i < startWeekday; i += 1) days.push({ empty: true });
    for (let day = 1; day <= totalDays; day += 1) {
      const isoDate = buildLocalDateKey(year, month, day);
      const record = dtrRecords.find(r => r.date === isoDate);
      days.push({ day, isoDate, record });
    }
    while (days.length % 7 !== 0) days.push({ empty: true });
    return days;
  }

  function getSubmittedTaskForAttendance(attendanceId) {
    return submittedTasks.find(task => Number(task.attendance_id) === Number(attendanceId));
  }

  function formatTaskStatusBadge(status) {
    const badgeClass = status === 'approved'
      ? 'badge-approved'
      : (status === 'pending' ? 'badge-pending' : 'badge-rejected');

    return `<span class="badge ${badgeClass}" style="text-transform: capitalize;">${escapeHtml(String(status || 'pending').replace('_', ' '))}</span>`;
  }

  function renderCalendar() {
    const monthLabel = document.getElementById('calendar-month-label');
    const namesContainer = document.getElementById('calendar-day-names');
    const cellsContainer = document.getElementById('calendar-day-cells');
    if (!monthLabel || !namesContainer || !cellsContainer) return;

    monthLabel.textContent = buildMonthLabel(selectedMonth);
    const dayNames = ['SUN','MON','TUE','WED','THU','FRI','SAT'];
    namesContainer.innerHTML = dayNames.map(n => `<div>${n}</div>`).join('');

    const days = getCalendarDays(selectedMonth);
    cellsContainer.innerHTML = days.map(day => {
      if (day.empty) return '<div style="min-height:90px;border-radius:14px;background:transparent"></div>';
      const hasRecord = !!day.record;
      const isCompleted = hasRecord && day.record.time_in && day.record.time_out;
      const hasSubmittedTask = isCompleted && !!getSubmittedTaskForAttendance(day.record.id);
      const bg = '#FFFFFF';
      const border = hasRecord
        ? (hasSubmittedTask ? '2px solid #10B981' : (isCompleted ? '2px solid #F59E0B' : '1px solid #CBD5E1'))
        : '1px solid #E5E7EB';
      const color = '#111827';
      const cursor = hasRecord ? 'pointer' : 'default';
      const disabled = hasRecord ? '' : 'disabled';

      return `
        <button type="button" ${disabled} onclick="onCalendarDateClick('${day.isoDate}')" style="min-height:98px;border-radius:16px;background:${bg};border:${border};padding:14px;text-align:center;color:${color};cursor:${cursor};width:100%;${hasRecord ? '' : 'opacity:0.65;'}">
          <div style="font-weight:800;font-size:18px">${day.day}</div>
        </button>
      `;
    }).join('');

    if (selectedDate) updateSelectedDatePanel(selectedDate);
  }

  function updateSelectedDatePanel(dateString) {
    const panel = document.getElementById('selected-date-details');
    const label = document.getElementById('selected-date-label');
    const record = dtrRecords.find(r => r.date === dateString);
    if (!label || !panel) return;
    if (!record) { label.textContent = formatDateLabel(dateString); panel.innerHTML = '<p>No DTR record exists for this date yet.</p>'; return; }
    const renderedHours = Number(record.rendered_hours || 0).toFixed(2);
    const task = getSubmittedTaskForAttendance(record.id);
    label.textContent = formatDateLabel(dateString);
    panel.innerHTML = `
      <div style="display:grid;gap:14px;">
        <div><strong>Status:</strong> <span style="color:${record.status === 'completed' ? '#047857' : '#B45309'};">${record.status}</span></div>
        <div><strong>Time In:</strong> ${formatTimeDisplay(record.time_in)}</div>
        <div><strong>Time Out:</strong> ${record.time_out ? formatTimeDisplay(record.time_out) : '<span style="color:#EF4444;font-weight:700">Pending</span>'}</div>
        <div><strong>Total Hours:</strong> ${renderedHours} hrs</div>
        <div><strong>Classroom:</strong> ${record.classroom ? record.classroom.classroom_name : 'N/A'}</div>
        <div><strong>Task Status:</strong> ${task ? `${formatTaskStatusBadge(task.status)} <span style="margin-left:6px;">${escapeHtml(task.title)}</span>` : '<span style="color:#B45309;font-weight:700;">No task submitted yet</span>'}</div>
      </div>
    `;
  }

  function onCalendarDateClick(dateString) {
    selectedDate = dateString;
    updateSelectedDatePanel(dateString);
    const record = dtrRecords.find(r => r.date === dateString);
    if (record && record.time_in && record.time_out) {
      openTaskModal(record, getSubmittedTaskForAttendance(record.id));
    } else if (record) {
      showToast('Please time out before submitting an accomplishment report for this date.', true);
    }
  }

  function openTaskModal(record) {
    const modal = document.getElementById('task-modal');
    const modalDateLabel = document.getElementById('modal-selected-date');
    const modalDtr = document.getElementById('modal-task-dtr');
    const modalSelect = document.getElementById('modal-task-date');
    modal.hidden = false;
    modal.setAttribute('aria-hidden', 'false');
    modalDateLabel.textContent = `${formatAppDate(record.date)} - ${record.time_in ? formatTimeDisplay(record.time_in) : 'N/A'}`;
    modalDtr.value = record.id;
    modalSelect.innerHTML = `<option value="${record.id}">${formatAppDate(record.date)} - ${record.time_in ? formatTimeDisplay(record.time_in) : 'N/A'} to ${record.time_out ? formatTimeDisplay(record.time_out) : 'Open'}</option>`;
    setTimeout(() => document.getElementById('modal-task-title').focus(), 150);
  }

  function closeTaskModal() {
    const modal = document.getElementById('task-modal');
    modal.hidden = true;
    modal.setAttribute('aria-hidden', 'true');
    document.getElementById('modal-task-form').reset();
  }

  function setTaskModalReadonly(isReadonly) {
    document.getElementById('modal-task-title').readOnly = isReadonly;
    document.getElementById('modal-task-cat').readOnly = isReadonly;
    document.getElementById('modal-task-desc').readOnly = isReadonly;
    document.getElementById('modal-task-attachments').disabled = isReadonly;
    document.getElementById('modal-task-attachment-group').style.display = isReadonly ? 'none' : 'block';
    document.getElementById('modal-task-submit-button').style.display = isReadonly ? 'none' : 'inline-flex';
    document.getElementById('modal-task-cancel-button').textContent = isReadonly ? 'Close' : 'Cancel';
  }

  function openTaskModal(record, task = null) {
    const modal = document.getElementById('task-modal');
    const modalDateLabel = document.getElementById('modal-selected-date');
    const modalDtr = document.getElementById('modal-task-dtr');
    const modalSelect = document.getElementById('modal-task-date');
    const existingAttachments = document.getElementById('modal-existing-attachments');
    const isSubmitted = !!task;

    modal.hidden = false;
    modal.setAttribute('aria-hidden', 'false');
    document.getElementById('modal-task-eyebrow').textContent = isSubmitted ? 'Submitted Task Log' : 'Submit New Task Log';
    modalDateLabel.textContent = `${formatAppDate(record.date)} - ${record.time_in ? formatTimeDisplay(record.time_in) : 'N/A'}`;
    modalDtr.value = record.id;
    modalSelect.innerHTML = `<option value="${record.id}">${formatAppDate(record.date)} - ${record.time_in ? formatTimeDisplay(record.time_in) : 'N/A'} to ${record.time_out ? formatTimeDisplay(record.time_out) : 'Open'}</option>`;
    document.getElementById('modal-task-title').value = task ? task.title : '';
    document.getElementById('modal-task-cat').value = task ? task.category : '';
    document.getElementById('modal-task-desc').value = task ? task.description : '';
    document.getElementById('modal-task-attachments').value = '';
    existingAttachments.innerHTML = task ? `
      <div class="info-grid">
        <div class="info-row"><strong>Status</strong><span>${formatTaskStatusBadge(task.status)}</span></div>
        <div class="info-row"><strong>Submitted</strong><span>${formatAppDateTime(task.submitted_at)}</span></div>
        <div class="info-row"><strong>Teacher Feedback</strong><span>${task.teacher_feedback ? escapeHtml(task.teacher_feedback) : '<em>No feedback yet</em>'}</span></div>
        <div class="info-row"><strong>Attachments</strong><span>${renderAttachments(task.attachments)}</span></div>
      </div>
    ` : '';

    setTaskModalReadonly(isSubmitted);
    if (!isSubmitted) setTimeout(() => document.getElementById('modal-task-title').focus(), 150);
  }

  function closeTaskModal() {
    const modal = document.getElementById('task-modal');
    modal.hidden = true;
    modal.setAttribute('aria-hidden', 'true');
    document.getElementById('modal-task-form').reset();
    document.getElementById('modal-existing-attachments').innerHTML = '';
    setTaskModalReadonly(false);
  }

  function appendAttachments(formData, inputId) {
    const input = document.getElementById(inputId);
    if (!input || !input.files) return;

    Array.from(input.files).forEach(file => {
      formData.append('attachments[]', file);
    });
  }

  function buildModalTaskFormData() {
    const formData = new FormData();
    formData.append('attendance_id', document.getElementById('modal-task-dtr').value);
    formData.append('title', document.getElementById('modal-task-title').value);
    formData.append('category', document.getElementById('modal-task-cat').value || 'General');
    formData.append('description', document.getElementById('modal-task-desc').value);
    appendAttachments(formData, 'modal-task-attachments');
    return formData;
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

  async function loadData() {
    try {
      const monthKey = getMonthKey(selectedMonth);
      const [dtrRes, tasksRes] = await Promise.all([
        apiRequest(`/student/dtr/calendar?month=${monthKey}`),
        apiRequest(`/student/tasks?month=${monthKey}&per_page=100`),
      ]);
      dtrRecords = dtrRes.attendance_records || [];
      submittedTasks = tasksRes.data || [];
      renderCalendar();
      if (selectedDate) updateSelectedDatePanel(selectedDate);
    } catch(err) { console.error(err); }
  }

  function changeCalendarMonth(offset) {
    selectedMonth = new Date(selectedMonth.getFullYear(), selectedMonth.getMonth() + offset, 1);
    loadData();
  }

  function downloadMonthlyDtr() { showToast('Download Monthly DTR is not yet available in this demo.'); }

  loadData();

  async function handleModalSubmit(e) {
    e.preventDefault();
    const attendance_id = document.getElementById('modal-task-dtr').value;
    if (!attendance_id) { showToast('Missing DTR selection', true); return; }
    try {
      const res = await apiRequest('/student/tasks', 'POST', buildModalTaskFormData());
      showToast(res.message || 'Task submitted');
      await loadData();
      const record = dtrRecords.find(r => Number(r.id) === Number(attendance_id));
      if (record) openTaskModal(record, res.task || getSubmittedTaskForAttendance(attendance_id));
    } catch(err) { console.error(err); }
  }

  // Keep existing non-modal form handler if present on page (graceful fallback)
  async function handleTaskSubmit(e) {
    e.preventDefault();
    const dtrEl = document.getElementById('task-dtr');
    const attendance_id = dtrEl ? dtrEl.value : null;
    if (!attendance_id) { showToast('Please select the DTR entry you accomplished this task for.', true); return; }
    try {
      const formData = new FormData();
      formData.append('attendance_id', attendance_id);
      formData.append('title', document.getElementById('task-title').value);
      formData.append('category', document.getElementById('task-cat').value || 'General');
      formData.append('description', document.getElementById('task-desc').value);
      appendAttachments(formData, 'task-attachments');
      const res = await apiRequest('/student/tasks', 'POST', formData);
      showToast(res.message || 'Task submitted');
      if (document.getElementById('task-title')) document.getElementById('task-title').value = '';
      if (document.getElementById('task-desc')) document.getElementById('task-desc').value = '';
      if (document.getElementById('task-cat')) document.getElementById('task-cat').value = '';
      if (document.getElementById('task-attachments')) document.getElementById('task-attachments').value = '';
      loadData();
    } catch(err) { console.error(err); }
  }
</script>
@endsection
