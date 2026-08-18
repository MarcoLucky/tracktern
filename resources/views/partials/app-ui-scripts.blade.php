<script>
  let messageDialogResolver = null;
  let messageDialogTimer = null;

  function getReadableMessage(message) {
    if (!message) return 'Something went wrong. Please try again.';
    if (Array.isArray(message)) return getReadableMessage(message[0]);
    if (typeof message === 'object') {
      const firstValue = Object.values(message)[0];
      return getReadableMessage(firstValue);
    }
    return String(message).replace(/\s+/g, ' ').trim();
  }

  function extractApiMessage(data) {
    if (data && data.errors) {
      return getReadableMessage(data.errors);
    }

    return getReadableMessage(data && data.message ? data.message : 'Error occurred.');
  }

  function closeAppDialog(result = true) {
    const dialog = document.getElementById('message-dialog');
    if (!dialog) return;

    clearTimeout(messageDialogTimer);
    messageDialogTimer = null;
    dialog.hidden = true;
    dialog.setAttribute('aria-hidden', 'true');
    document.body.classList.remove('has-message-dialog');

    if (messageDialogResolver) {
      const resolver = messageDialogResolver;
      messageDialogResolver = null;
      resolver(result);
    }
  }

  function openAppDialog(message, options = {}) {
    const dialog = document.getElementById('message-dialog');
    const text = document.getElementById('message-dialog-text');
    const input = document.getElementById('message-dialog-input');
    const okButton = document.getElementById('message-dialog-ok');
    const cancelButton = document.getElementById('message-dialog-cancel');
    if (!dialog || !text || !input || !okButton || !cancelButton) {
      window.alert(getReadableMessage(message));
      return Promise.resolve(true);
    }

    const mode = options.mode || 'alert';

    return new Promise(resolve => {
      if (messageDialogResolver) closeAppDialog(false);

      messageDialogResolver = resolve;
      clearTimeout(messageDialogTimer);
      messageDialogTimer = null;

      text.textContent = getReadableMessage(message);
      input.hidden = mode !== 'prompt';
      input.value = options.defaultValue || '';
      cancelButton.hidden = mode !== 'confirm' && mode !== 'prompt';
      dialog.classList.toggle('is-error', !!options.isError);
      dialog.classList.toggle('is-details', mode === 'details');
      dialog.hidden = false;
      dialog.setAttribute('aria-hidden', 'false');
      document.body.classList.add('has-message-dialog');

      okButton.onclick = () => closeAppDialog(mode === 'prompt' ? input.value : true);
      cancelButton.onclick = () => closeAppDialog(mode === 'prompt' ? null : false);
      dialog.onclick = event => {
        if (event.target === dialog && mode === 'alert') closeAppDialog(true);
      };

      if (options.autoCloseMs) {
        messageDialogTimer = setTimeout(() => closeAppDialog(true), options.autoCloseMs);
      }

      setTimeout(() => (mode === 'prompt' ? input : okButton).focus(), 0);
    });
  }

  function showToast(message, isError = false) {
    return openAppDialog(message, {
      isError,
      autoCloseMs: isError ? 0 : 2500,
    });
  }

  function showAppConfirm(message) {
    return openAppDialog(message, { mode: 'confirm' });
  }

  function showAppPrompt(message, defaultValue = '') {
    return openAppDialog(message, { mode: 'prompt', defaultValue });
  }

  function showAppDetails(title, details) {
    const body = typeof details === 'string' ? details : JSON.stringify(details, null, 2);
    return openAppDialog(`${title}\n\n${body}`, { mode: 'details' });
  }

  function parseAppDate(value) {
    if (!value) return null;
    if (value instanceof Date) return Number.isNaN(value.getTime()) ? null : value;

    const text = String(value).trim();
    const dateMatch = text.match(/^(\d{4})-(\d{1,2})-(\d{1,2})/);
    if (dateMatch) {
      return new Date(Number(dateMatch[1]), Number(dateMatch[2]) - 1, Number(dateMatch[3]));
    }

    const parsed = new Date(text);
    return Number.isNaN(parsed.getTime()) ? null : parsed;
  }

  function formatAppDate(value) {
    const parsed = parseAppDate(value);
    if (!parsed) return value ? String(value) : 'N/A';

    const month = String(parsed.getMonth() + 1).padStart(2, '0');
    const day = String(parsed.getDate()).padStart(2, '0');
    return `${month}/${day}/${parsed.getFullYear()}`;
  }

  function parseAppTime(value) {
    if (!value) return null;
    if (value instanceof Date) return Number.isNaN(value.getTime()) ? null : value;

    const text = String(value).trim();
    const timeOnlyMatch = text.match(/^(\d{1,2}):(\d{2})(?::\d{2})?/);
    if (timeOnlyMatch) {
      return new Date(2000, 0, 1, Number(timeOnlyMatch[1]), Number(timeOnlyMatch[2]));
    }

    const normalized = text.includes(' ') && !text.includes('T')
      ? text.replace(' ', 'T')
      : text;
    const parsed = new Date(normalized);
    return Number.isNaN(parsed.getTime()) ? null : parsed;
  }

  function formatAppTime(value) {
    const parsed = parseAppTime(value);
    if (!parsed) return value ? String(value) : 'N/A';
    return parsed.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' });
  }

  function formatAppDateTime(value) {
    if (!value) return 'N/A';
    const date = formatAppDate(value);
    const time = formatAppTime(value);
    return date === 'N/A' ? time : `${date} ${time}`;
  }

  function escapeAppHtml(value) {
    return String(value ?? '').replace(/[&<>"']/g, char => ({
      '&': '&amp;',
      '<': '&lt;',
      '>': '&gt;',
      '"': '&quot;',
      "'": '&#039;',
    }[char]));
  }

  function escapeAppJsString(value) {
    return String(value ?? '')
      .replace(/\\/g, '\\\\')
      .replace(/'/g, "\\'")
      .replace(/\r/g, '\\r')
      .replace(/\n/g, '\\n');
  }

  function getAttachmentPreviewKind(url, name = '', fileType = '') {
    const source = `${fileType} ${name} ${url}`.toLowerCase().split('?')[0];
    if (source.includes('image/') || /\.(png|jpe?g|gif|webp|bmp|svg)$/i.test(source)) return 'image';
    if (source.includes('video/') || /\.(mp4|mov|avi|webm|mkv|m4v)$/i.test(source)) return 'video';
    if (source.includes('pdf') || /\.pdf$/i.test(source)) return 'pdf';
    return 'file';
  }

  function previewAttachment(url, name = 'Attachment', fileType = '') {
    const modal = document.getElementById('attachment-preview-modal');
    const title = document.getElementById('attachment-preview-title');
    const body = document.getElementById('attachment-preview-body');
    if (!modal || !title || !body || !url) return;

    const safeUrl = escapeAppHtml(url);
    const safeName = escapeAppHtml(name || 'Attachment');
    const kind = getAttachmentPreviewKind(url, name, fileType);

    title.textContent = name || 'Attachment';
    if (kind === 'image') {
      body.innerHTML = `<img class="attachment-preview-media" src="${safeUrl}" alt="${safeName}">`;
    } else if (kind === 'video') {
      body.innerHTML = `<video class="attachment-preview-video" src="${safeUrl}" controls playsinline></video>`;
    } else if (kind === 'pdf') {
      body.innerHTML = `<iframe class="attachment-preview-frame" src="${safeUrl}" title="${safeName}"></iframe>`;
    } else {
      body.innerHTML = `
        <div class="attachment-preview-fallback">
          <strong>${safeName}</strong>
          <p>This file type cannot be previewed directly on the page.</p>
          <a href="${safeUrl}" class="btn-secondary" download>Download File</a>
        </div>
      `;
    }

    modal.hidden = false;
    modal.setAttribute('aria-hidden', 'false');
  }

  function closeAttachmentPreview() {
    const modal = document.getElementById('attachment-preview-modal');
    const body = document.getElementById('attachment-preview-body');
    if (!modal) return;

    modal.hidden = true;
    modal.setAttribute('aria-hidden', 'true');
    if (body) body.innerHTML = '';
  }

  function renderAttachmentPreviewButton(attachment) {
    const url = attachment && (attachment.url || (attachment.file_path ? `/storage/${attachment.file_path}` : ''));
    if (!url) return '<em>Attachment unavailable</em>';

    const name = attachment.original_filename || attachment.name || 'Attachment';
    const fileType = attachment.file_type || attachment.mime_type || '';
    const jsUrl = escapeAppHtml(escapeAppJsString(url));
    const jsName = escapeAppHtml(escapeAppJsString(name));
    const jsFileType = escapeAppHtml(escapeAppJsString(fileType));
    return `<button type="button" class="attachment-link" title="${escapeAppHtml(name)}" onclick="previewAttachment('${jsUrl}', '${jsName}', '${jsFileType}')">${escapeAppHtml(name)}</button>`;
  }

  function togglePasswordVisibility(inputId, button) {
    const input = document.getElementById(inputId);
    if (!input) return;

    const isHidden = input.type === 'password';
    input.type = isHidden ? 'text' : 'password';
    if (button) {
      button.classList.toggle('is-visible', isHidden);
      button.setAttribute('aria-label', isHidden ? 'Hide password' : 'Show password');
      button.setAttribute('aria-pressed', String(isHidden));
    }
  }

  function togglePublicMenu(forceOpen) {
    const container = document.querySelector('.public-header, .auth-left-pane');
    if (!container) return;

    const shouldOpen = typeof forceOpen === 'boolean' ? forceOpen : !container.classList.contains('nav-open');
    container.classList.toggle('nav-open', shouldOpen);
    const button = container.querySelector('.menu-toggle');
    const nav = container.querySelector('.nav-links, .auth-nav');
    if (button) button.setAttribute('aria-expanded', String(shouldOpen));
    if (nav) nav.setAttribute('aria-hidden', String(!shouldOpen));
  }

  function togglePortalMenu(forceOpen) {
    const shouldOpen = typeof forceOpen === 'boolean' ? forceOpen : !document.body.classList.contains('portal-menu-open');
    document.body.classList.toggle('portal-menu-open', shouldOpen);

    const button = document.querySelector('.portal-menu-toggle');
    const sidebar = document.querySelector('.portal-sidebar');
    if (button) button.setAttribute('aria-expanded', String(shouldOpen));
    if (sidebar) sidebar.setAttribute('aria-hidden', String(!shouldOpen));
  }

  document.addEventListener('keydown', event => {
    if (event.key === 'Escape') {
      closeAttachmentPreview();
      closeAppDialog(false);
      togglePublicMenu(false);
      togglePortalMenu(false);
    }
  });

  document.addEventListener('click', event => {
    if (event.target.closest('[data-mobile-menu-close]')) {
      togglePortalMenu(false);
      return;
    }

    const attachmentPreview = document.getElementById('attachment-preview-modal');
    if (attachmentPreview && event.target === attachmentPreview) {
      closeAttachmentPreview();
      return;
    }

    if (
      document.body.classList.contains('portal-menu-open') &&
      !event.target.closest('.portal-sidebar') &&
      !event.target.closest('.portal-menu-toggle')
    ) {
      togglePortalMenu(false);
    }

    const publicContainer = document.querySelector('.public-header.nav-open, .auth-left-pane.nav-open');
    if (
      publicContainer &&
      !event.target.closest('.public-header') &&
      !event.target.closest('.auth-left-pane')
    ) {
      togglePublicMenu(false);
    }
  });
</script>
