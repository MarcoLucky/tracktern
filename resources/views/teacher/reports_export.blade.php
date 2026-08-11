@extends('layouts.teacher')

@section('title', 'Reports Export — TrackTern')
@section('page_heading', 'Reports & Analytics Center')

@section('content')
<div class="table-container" style="padding: 24px; margin-bottom: 30px;">
  <h3>Classroom Performance PDF Export</h3>
  <p style="font-size: 14px; color: #4B5563; margin-bottom: 16px;">Export class summary report including roster statistics, target vs rendered hours, and completion badges.</p>
  <div style="display: flex; gap: 16px; max-width: 500px;">
    <input type="number" id="export-classroom-id" placeholder="Enter Classroom ID (e.g. 1)" value="1">
    <button onclick="handleExportClassroomReport()" class="btn-primary" style="white-space: nowrap;">Export Class Summary</button>
  </div>
</div>
@endsection

@section('scripts')
<script>
  async function handleExportClassroomReport() {
    const cid = document.getElementById('export-classroom-id').value;
    if (!cid) return;
    try {
      const res = await apiRequest(`/teacher/reports/classroom/${cid}/export`);
      alert(JSON.stringify(res.report, null, 2));
    } catch(err) {}
  }
</script>
@endsection
