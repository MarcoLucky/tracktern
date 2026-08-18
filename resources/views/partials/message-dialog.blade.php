<div id="message-dialog" class="message-dialog-backdrop" hidden aria-hidden="true">
  <div class="message-dialog-card" role="alertdialog" aria-modal="true" aria-labelledby="message-dialog-text">
    <div id="message-dialog-text" class="message-dialog-text"></div>
    <input id="message-dialog-input" class="message-dialog-input" type="text" hidden>
    <div class="message-dialog-actions">
      <button type="button" id="message-dialog-cancel" class="message-dialog-button message-dialog-button-secondary" hidden>Cancel</button>
      <button type="button" id="message-dialog-ok" class="message-dialog-button">Ok</button>
    </div>
  </div>
</div>

<div id="attachment-preview-modal" class="app-modal-backdrop" hidden aria-hidden="true">
  <div class="app-modal-card attachment-preview-card" role="dialog" aria-modal="true" aria-labelledby="attachment-preview-title">
    <div class="app-modal-header">
      <div>
        <div style="font-size: 12px; color: #6B7280; text-transform: uppercase; font-weight: 800;">Attachment Preview</div>
        <h3 id="attachment-preview-title" style="margin-top: 4px;">Attachment</h3>
      </div>
      <button type="button" class="icon-action-button" aria-label="Close attachment preview" onclick="closeAttachmentPreview()">
        <svg viewBox="0 0 24 24"><path d="M18 6 6 18M6 6l12 12"/></svg>
      </button>
    </div>
    <div id="attachment-preview-body" class="attachment-preview-body"></div>
  </div>
</div>
