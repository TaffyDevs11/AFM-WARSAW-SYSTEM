  </main><!-- /admin-content -->
</div><!-- /admin-layout -->

<!-- Confirm Delete Dialog -->
<div class="confirm-overlay" id="confirm-overlay">
  <div class="confirm-box">
    <div class="confirm-icon">🗑️</div>
    <div class="confirm-title">Confirm Delete</div>
    <div class="confirm-text" id="confirm-text">This action cannot be undone.</div>
    <div class="confirm-actions">
      <button class="btn btn-ghost" onclick="confirmNo()">Cancel</button>
      <button class="btn btn-danger" onclick="confirmYes()">Yes, Delete</button>
    </div>
  </div>
</div>

<div id="toast-container"></div>

</body>
</html>
