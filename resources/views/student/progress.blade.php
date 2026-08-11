@extends('layouts.student')

@section('title', 'Progress & Reports — TrackTern')
@section('page_heading', 'Progress & Reports Center')
@section('nav_progress', 'active')

@section('content')
<div id="progress-metrics-container">
  <div style="text-align:center; padding:40px;">Loading progress statistics...</div>
</div>

<div class="table-container" style="padding: 24px; margin-bottom: 30px;">
  <h3>Official Documentation PDF Data Export</h3>
  <p style="font-size: 14px; color: #4B5563; margin-bottom: 16px;">Compile official DTR summary logs and student performance reports.</p>
  <div style="display: flex; gap: 16px;">
    <button onclick="handleExportDtrPDF()" class="btn-primary">Generate DTR Report Payload</button>
    <button onclick="handleExportProgressPDF()" class="btn-secondary">Generate Progress Report Payload</button>
  </div>
</div>
@endsection

@section('scripts')
<script>
  async function loadProgress() {
    const container = document.getElementById('progress-metrics-container');
    try {
      const res = await apiRequest('/student/progress');
      const p = res.progress;

      container.innerHTML = `
        <div class="metrics-grid">
          <div class="metric-card">
            <div class="metric-label">Target Internship Hours</div>
            <div class="metric-value">${p.required_target_hours} hrs</div>
          </div>
          <div class="metric-card">
            <div class="metric-label">Total Rendered Hours</div>
            <div class="metric-value">${p.total_rendered_hours} hrs</div>
          </div>
          <div class="metric-card">
            <div class="metric-label">Completion Percentage</div>
            <div class="metric-value">${p.progress_percentage}%</div>
          </div>
        </div>
      `;
    } catch(err) {}
  }
  loadProgress();

  async function handleExportDtrPDF() {
    try {
      const res = await apiRequest('/student/reports/dtr/export');
      alert(JSON.stringify(res.report, null, 2));
    } catch(err) {}
  }

  async function handleExportProgressPDF() {
    try {
      const res = await apiRequest('/student/reports/progress/export');
      alert(JSON.stringify(res.report, null, 2));
    } catch(err) {}
  }
</script>
@endsection
