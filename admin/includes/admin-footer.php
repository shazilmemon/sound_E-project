  </div><!-- /admin-content -->
</div><!-- /admin-main -->
</div><!-- /admin-layout -->

<!-- Admin Global Audio Preview Bar -->
<div id="adminAudioPreview" class="admin-audio-bar" style="display:none;">
  <div class="audio-bar-inner">
    <div class="audio-bar-info">
      <div class="audio-bar-icon"><i class="fas fa-play"></i></div>
      <div class="audio-bar-text">
        <span class="audio-bar-tag">AUDIO PREVIEW</span>
        <strong id="adminAudioTitle">Track Title</strong>
      </div>
    </div>
    <audio id="adminAudioElement" controls preload="none"></audio>
    <button type="button" class="audio-bar-close" onclick="closeAdminAudio()" title="Close Player">&times;</button>
  </div>
</div>

<!-- Admin Video Preview Modal -->
<div id="adminVideoModal" class="admin-modal" style="display:none;">
  <div class="admin-modal-overlay" onclick="closeAdminVideo()"></div>
  <div class="admin-modal-content">
    <div class="admin-modal-head">
      <h3 id="adminVideoTitle"><i class="fas fa-video" style="color:var(--primary); margin-right:8px;"></i> Video Preview</h3>
      <button type="button" class="admin-modal-close" onclick="closeAdminVideo()">&times;</button>
    </div>
    <div class="admin-modal-body">
      <video id="adminVideoElement" controls style="width:100%; max-height:460px; border-radius:8px; background:#000;"></video>
    </div>
  </div>
</div>

<script src="<?= SITE_URL ?>/assets/js/admin.js?v=<?= time() ?>"></script>
</body>
</html>

