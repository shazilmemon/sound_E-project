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

