<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>@yield('title', 'Teacher Portal — TrackTern')</title>
  <link rel="stylesheet" href="{{ asset('css/tracktern.css') }}">
</head>
<body>
  <div class="portal-backdrop" data-mobile-menu-close></div>
  <div class="portal-wrapper">
    <aside class="portal-sidebar" aria-label="Teacher navigation" aria-hidden="false">
      <div class="sidebar-header">
        <img src="{{ asset('images/logo.png') }}" alt="Logo">
        <span class="brand-title">TRACKTERN</span>
        <button type="button" class="sidebar-close" aria-label="Close navigation" onclick="togglePortalMenu(false)">
          <svg viewBox="0 0 24 24"><path d="M18 6 6 18M6 6l12 12"/></svg>
        </button>
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
        <a href="/teacher/profile" class="nav-item @yield('nav_profile')">
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
          <h1>@yield('page_heading', 'Teacher Portal')</h1>
        </div>
        <div class="user-profile-badge">
          <div class="user-info">
            <div class="user-name" id="topbar-teacher-name">Loading...</div>
            <div class="user-role">Instructor</div>
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
      if (!authToken || !currentUser || currentUser.role !== 'teacher') {
        window.location.href = '/login';
      } else {
        const nameEl = document.getElementById('topbar-teacher-name');
        if (nameEl) nameEl.textContent = currentUser.name;
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
