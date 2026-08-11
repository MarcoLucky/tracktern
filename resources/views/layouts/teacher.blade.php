<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>@yield('title', 'Teacher Portal — TrackTern')</title>
  <link rel="stylesheet" href="{{ asset('css/tracktern.css') }}">
</head>
<body>
  <div class="portal-wrapper">
    <aside class="portal-sidebar">
      <div class="sidebar-header">
        <img src="{{ asset('images/logo.png') }}" alt="Logo">
        <span class="brand-title">TRACKTERN</span>
      </div>

      <nav class="sidebar-nav">
        <a href="/teacher/dashboard" class="nav-item @yield('nav_dashboard')">
          <svg viewBox="0 0 24 24"><path d="M3 13h8V3H3v10zm0 8h8v-6H3v6zm10 0h8V11h-8v10zm0-18v6h8V3h-8z"/></svg>
          Teacher Dashboard
        </a>
        <a href="/teacher/classrooms" class="nav-item @yield('nav_classrooms')">
          <svg viewBox="0 0 24 24"><path d="M4 6H2v14c0 1.1.9 2 2 2h14v-2H4V6zm16-4H8c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zm0 14H8V4h12v12z"/></svg>
          Classroom Management
        </a>
        <a href="/teacher/monitoring" class="nav-item @yield('nav_monitoring')">
          <svg viewBox="0 0 24 24"><path d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5c-1.66 0-3 1.34-3 3s1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5C6.34 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5c0-2.33-4.67-3.5-7-3.5zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.97 1.97 3.45V19h6v-2.5c0-2.33-4.67-3.5-7-3.5z"/></svg>
          Student Monitoring
        </a>
        <a href="/teacher/tasks/approvals" class="nav-item @yield('nav_tasks_approvals')">
          <svg viewBox="0 0 24 24"><path d="M19 3H5c-1.11 0-2 .9-2 2v14c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-9 14l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/></svg>
          Task Approvals Queue
        </a>
        <a href="/teacher/reports/export" class="nav-item @yield('nav_export')">
          <svg viewBox="0 0 24 24"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zM9 17H7v-7h2v7zm4 0h-2V7h2v10zm4 0h-2v-4h2v4z"/></svg>
          Reports Export
        </a>
      </nav>

      <div class="sidebar-footer">
        <button onclick="handleLogout()" class="btn-logout" style="width: 100%;">Logout</button>
      </div>
    </aside>

    <div class="portal-content-area">
      <header class="portal-topbar">
        <h1>@yield('page_heading', 'Teacher Portal')</h1>
        <div class="user-profile-badge">
          <div class="user-info">
            <div class="user-name" id="topbar-teacher-name">Loading...</div>
            <div class="user-role">Teacher / Academic Supervisor</div>
          </div>
        </div>
      </header>

      <main class="portal-main-view">
        @yield('content')
      </main>
    </div>
  </div>

  <div id="toast" class="alert-toast" style="display: none;"></div>

  <script>
    const API_BASE = '/api/v1';
    let authToken = localStorage.getItem('tracktern_token');
    let currentUser = JSON.parse(localStorage.getItem('tracktern_user') || 'null');

    function checkAuth() {
      if (!authToken || !currentUser || currentUser.role !== 'teacher') {
        window.location.href = '/login';
      } else {
        const nameEl = document.getElementById('topbar-teacher-name');
        if (nameEl) nameEl.textContent = currentUser.name;
      }
    }
    checkAuth();

    function showToast(message, isError = false) {
      const toast = document.getElementById('toast');
      if (!toast) return;
      toast.textContent = message;
      toast.style.borderLeftColor = isError ? '#DC2626' : '#004798';
      toast.style.display = 'block';
      setTimeout(() => { toast.style.display = 'none'; }, 4000);
    }

    async function apiRequest(endpoint, method = 'GET', body = null) {
      const headers = {};
      if (authToken) headers['Authorization'] = `Bearer ${authToken}`;
      if (body) headers['Content-Type'] = 'application/json';

      const config = { method, headers };
      if (body) config.body = JSON.stringify(body);

      try {
        const res = await fetch(`${API_BASE}${endpoint}`, config);
        const data = await res.json();
        if (!res.ok) throw new Error(data.message || 'Error occurred.');
        return data;
      } catch (err) {
        showToast(err.message, true);
        throw err;
      }
    }

    function handleLogout() {
      localStorage.removeItem('tracktern_token');
      localStorage.removeItem('tracktern_user');
      window.location.href = '/login';
    }
  </script>
  @yield('scripts')
</body>
</html>
