<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>@yield('title', 'TrackTern — Student Internship Time & Progress Monitoring System')</title>
  <link rel="icon" type="image/png" href="{{ asset('images/logo.png') }}">
  <link rel="shortcut icon" type="image/png" href="{{ asset('images/logo.png') }}">
  <meta property="og:image" content="{{ asset('images/logo.png') }}">
  <link rel="stylesheet" href="{{ asset('css/tracktern.css') }}">
</head>
<body>

  @yield('content')

  <!-- Toast Notification Alert -->
  <div id="toast" class="alert-toast" style="display: none;"></div>

  <script>
    const API_BASE = '/api/v1';

    function showToast(message, isError = false) {
      const toast = document.getElementById('toast');
      if (!toast) return;
      toast.textContent = message;
      toast.style.borderLeftColor = isError ? '#DC2626' : '#004798';
      toast.style.display = 'block';
      setTimeout(() => {
        toast.style.display = 'none';
      }, 4000);
    }

    async function apiRequest(endpoint, method = 'GET', body = null) {
      const token = localStorage.getItem('tracktern_token');
      const headers = {};
      if (token) headers['Authorization'] = `Bearer ${token}`;
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
  </script>
  @yield('scripts')
</body>
</html>
