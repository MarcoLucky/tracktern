@extends('layouts.student')

@section('title', 'Weekly Reports — TrackTern')
@section('page_heading', 'Weekly Progress Reports')
@section('nav_weekly_reports', 'active')

@section('content')
<div class="table-container" style="padding: 24px; margin-bottom: 30px;">
  <h3>Submit Weekly Report</h3>
  <form onsubmit="handleWeeklyReportSubmit(event)" style="margin-top: 16px;">
    <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 16px;">
      <div class="form-group">
        <label for="wr-week">Week Number:</label>
        <input type="number" id="wr-week" min="1" value="1" required>
      </div>
      <div class="form-group">
        <label for="wr-start">Coverage Start Date:</label>
        <input type="date" id="wr-start" required>
      </div>
      <div class="form-group">
        <label for="wr-end">Coverage End Date:</label>
        <input type="date" id="wr-end" required>
      </div>
    </div>
    <div class="form-group">
      <label for="wr-activities">Activities & Accomplishments:</label>
      <textarea id="wr-activities" rows="2" placeholder="Summary of weekly tasks..." required></textarea>
    </div>
    <div class="form-group">
      <label for="wr-problems">Problems Encountered:</label>
      <textarea id="wr-problems" rows="2" placeholder="Any obstacles faced..."></textarea>
    </div>
    <div class="form-group">
      <label for="wr-skills">Skills Learned & Reflections:</label>
      <textarea id="wr-skills" rows="2" placeholder="Key takeaways and skills developed..."></textarea>
    </div>
    <button type="submit" class="btn-primary">Submit Weekly Report</button>
  </form>
</div>

<div class="table-container">
  <div class="table-header">
    <h3>Weekly Reports Log</h3>
  </div>
  <table class="solid-table">
    <thead>
      <tr>
        <th>Week #</th>
        <th>Coverage Dates</th>
        <th>Activities</th>
        <th>Status</th>
        <th>Teacher Feedback</th>
      </tr>
    </thead>
    <tbody id="wr-tbody">
      <tr><td colspan="5" style="text-align:center;">Loading weekly reports...</td></tr>
    </tbody>
  </table>
</div>
@endsection

@section('scripts')
<script>
  async function loadWeeklyReports() {
    const tbody = document.getElementById('wr-tbody');
    try {
      const res = await apiRequest('/student/weekly-reports');
      tbody.innerHTML = res.data.length > 0 ? res.data.map(r => `
        <tr>
          <td><strong>Week ${r.week_number}</strong></td>
          <td>${r.coverage_start_date} to ${r.coverage_end_date}</td>
          <td>${r.activities}</td>
          <td><span class="badge ${r.status === 'approved' ? 'badge-approved' : (r.status === 'pending' ? 'badge-pending' : 'badge-needs-revision')}">${r.status}</span></td>
          <td>${r.teacher_feedback || '<em>No feedback yet</em>'}</td>
        </tr>
      `).join('') : '<tr><td colspan="5" style="text-align:center;">No weekly reports submitted yet.</td></tr>';
    } catch(err) {}
  }
  loadWeeklyReports();

  async function handleWeeklyReportSubmit(e) {
    e.preventDefault();
    try {
      const res = await apiRequest('/student/weekly-reports', 'POST', {
        week_number: document.getElementById('wr-week').value,
        coverage_start_date: document.getElementById('wr-start').value,
        coverage_end_date: document.getElementById('wr-end').value,
        activities: document.getElementById('wr-activities').value,
        problems_encountered: document.getElementById('wr-problems').value,
        skills_learned: document.getElementById('wr-skills').value,
      });
      showToast(res.message);
      document.getElementById('wr-activities').value = '';
      document.getElementById('wr-problems').value = '';
      document.getElementById('wr-skills').value = '';
      loadWeeklyReports();
    } catch(err) {}
  }
</script>
@endsection
