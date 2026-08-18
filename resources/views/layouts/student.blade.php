<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>@yield('title', 'Student Portal — TrackTern')</title>
  <link rel="stylesheet" href="{{ asset('css/tracktern.css') }}">
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
  <div class="portal-backdrop" data-mobile-menu-close></div>
  <div class="portal-wrapper">
    <aside class="portal-sidebar" aria-label="Student navigation" aria-hidden="false">
      <div class="sidebar-header">
        <img src="{{ asset('images/logo.png') }}" alt="Logo">
        <span class="brand-title">TRACKTERN</span>
        <button type="button" class="sidebar-close" aria-label="Close navigation" onclick="togglePortalMenu(false)">
          <svg viewBox="0 0 24 24"><path d="M18 6 6 18M6 6l12 12"/></svg>
        </button>
      </div>

      <nav class="sidebar-nav">
        <a href="/student/dashboard" class="nav-item @yield('nav_dashboard')">
          <svg viewBox="0 0 24 24"><path d="M3 13h8V3H3v10zm0 8h8v-6H3v6zm10 0h8V11h-8v10zm0-18v6h8V3h-8z"/></svg>
          Dashboard
        </a>

        <a href="/student/classroom" class="nav-item @yield('nav_classroom')">
          <svg viewBox="0 0 24 24"><path d="M5 13.18v4L12 21l7-3.82v-4L12 17l-7-3.82zM12 3L1 9l11 6 9-4.91V17h2V9L12 3z"/></svg>
          My Classroom
        </a>
        <a href="/student/dtr" class="nav-item @yield('nav_dtr')">
          <svg viewBox="0 0 24 24"><path d="M11.99 2C6.47 2 2 6.48 2 12s4.47 10 9.99 10C17.52 22 22 17.52 22 12S17.52 2 11.99 2zM12 20c-4.42 0-8-3.58-8-8s3.58-8 8-8 8 3.58 8 8-3.58 8-8 8zm.5-13H11v6l5.25 3.15.75-1.23-4.5-2.67z"/></svg>
          Daily Time Record (DTR)
        </a>
        <a href="/student/tasks" class="nav-item @yield('nav_tasks')">
          <svg viewBox="0 0 24 24"><path d="M19 3H5c-1.11 0-2 .9-2 2v14c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-9 14l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/></svg>
          Accomplishment Report
        </a>
        <a href="/student/progress" class="nav-item @yield('nav_progress')">
          <svg viewBox="0 0 24 24"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zM9 17H7v-7h2v7zm4 0h-2V7h2v10zm4 0h-2v-4h2v4z"/></svg>
          Progress & Reports
        </a>
        <a href="/student/profile" class="nav-item @yield('nav_profile')">
          <svg viewBox="0 0 24 24"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>
          My Profile
        </a>
      </nav>

      <div class="sidebar-footer">
        <button onclick="handleLogout()" class="btn-logout" style="width: 100%;">Logout</button>
      </div>
    </aside>

    <div class="portal-content-area">
      <header class="portal-topbar">
        <div class="topbar-title-row">
          <button type="button" class="menu-toggle portal-menu-toggle" aria-label="Open navigation" aria-expanded="false" onclick="togglePortalMenu()">
            <svg viewBox="0 0 24 24"><path d="M4 6h16M4 12h16M4 18h16"/></svg>
          </button>
          <h1>@yield('page_heading', 'Student Portal')</h1>
        </div>
        <div class="user-profile-badge">
          <div class="user-info">
            <div class="user-name" id="topbar-user-name">Loading...</div>
            <div class="user-role">Intern ID: <span id="topbar-student-id" style="font-weight: 800; color: #004798;">---</span></div>
          </div>
        </div>
      </header>

      <main class="portal-main-view">
        @yield('content')
      </main>
    </div>
  </div>

  @include('partials.message-dialog')
  @include('partials.app-ui-scripts')

  <script>
    const API_BASE = '/api/v1';
    let authToken = localStorage.getItem('tracktern_token');
    let currentUser = JSON.parse(localStorage.getItem('tracktern_user') || 'null');

    function checkAuth() {
      if (!authToken || !currentUser || currentUser.role !== 'student') {
        window.location.href = '/login';
      } else {
        const nameEl = document.getElementById('topbar-user-name');
        const codeEl = document.getElementById('topbar-student-id');
        if (nameEl) nameEl.textContent = currentUser.name;
        if (codeEl) codeEl.textContent = currentUser.student ? currentUser.student.intern_id : '---';
      }
    }
    checkAuth();

    async function apiRequest(endpoint, method = 'GET', body = null) {
      const headers = {};
      if (authToken) headers['Authorization'] = `Bearer ${authToken}`;
      const isFormData = body instanceof FormData;
      if (body && !isFormData) headers['Content-Type'] = 'application/json';

      const config = { method, headers };
      if (body) config.body = isFormData ? body : JSON.stringify(body);

      try {
        const res = await fetch(`${API_BASE}${endpoint}`, config);
        const data = await res.json();
        if (!res.ok) throw new Error(extractApiMessage(data));
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
