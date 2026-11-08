// =====================================================
// SOUND GROUP - Admin Panel JS
// assets/js/admin.js
// =====================================================
document.addEventListener('DOMContentLoaded', () => {
  // Auto-dismiss Flash message
  const flash = document.querySelector('.flash-msg');
  if (flash) {
    setTimeout(() => {
      flash.style.opacity = '0';
      flash.style.transition = 'opacity 0.5s ease';
      setTimeout(() => flash.remove(), 500);
    }, 4000);
  }
});

// Audio Preview Bar in Admin
window.previewAdminSong = function(audioUrl, title) {
  const bar = document.getElementById('adminAudioPreview');
  const player = document.getElementById('adminAudioElement');
  const titleEl = document.getElementById('adminAudioTitle');
  if (bar && player) {
    if (titleEl) titleEl.textContent = title || 'Playing Track';
    player.src = audioUrl;
    bar.style.display = 'block';
    player.play().catch(e => console.log('Autoplay deferred:', e));
  }
};

window.closeAdminAudio = function() {
  const bar = document.getElementById('adminAudioPreview');
  const player = document.getElementById('adminAudioElement');
  if (player) {
    player.pause();
    player.src = '';
  }
  if (bar) bar.style.display = 'none';
};

// Video Preview Modal in Admin
window.previewAdminVideo = function(videoUrl, title) {
  // Pause any audio first
  if (window.closeAdminAudio) window.closeAdminAudio();

  const modal = document.getElementById('adminVideoModal');
  const videoPlayer = document.getElementById('adminVideoElement');
  const titleEl = document.getElementById('adminVideoTitle');
  if (modal && videoPlayer) {
    if (titleEl) {
      titleEl.innerHTML = '<i class="fas fa-video" style="color:var(--primary); margin-right:8px;"></i> ' + (title || 'Video Preview');
    }
    videoPlayer.src = videoUrl;
    modal.style.display = 'flex';
    videoPlayer.play().catch(e => console.log('Autoplay deferred:', e));
  }
};

window.closeAdminVideo = function() {
  const modal = document.getElementById('adminVideoModal');
  const videoPlayer = document.getElementById('adminVideoElement');
  if (videoPlayer) {
    videoPlayer.pause();
    videoPlayer.src = '';
  }
  if (modal) modal.style.display = 'none';
};

// Global escape key listener
document.addEventListener('keydown', (e) => {
  if (e.key === 'Escape') {
    if (window.closeAdminVideo) window.closeAdminVideo();
  }
});

