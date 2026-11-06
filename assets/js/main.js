// =====================================================
// SOUND GROUP - Main Frontend Script
// assets/js/main.js
// =====================================================
document.addEventListener('DOMContentLoaded', () => {
  // Mobile Hamburger Toggle
  const hamburger = document.getElementById('hamburger');
  const navLinks = document.querySelector('.nav-links');
  if (hamburger && navLinks) {
    hamburger.addEventListener('click', () => {
      navLinks.style.display = navLinks.style.display === 'flex' ? 'none' : 'flex';
      navLinks.style.flexDirection = 'column';
      navLinks.style.position = 'absolute';
      navLinks.style.top = '72px';
      navLinks.style.left = '0';
      navLinks.style.right = '0';
      navLinks.style.background = '#141414';
      navLinks.style.padding = '20px';
      navLinks.style.borderBottom = '1px solid #2e2e2e';
    });
  }

  // Smooth scroll
  document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
      const target = document.querySelector(this.getAttribute('href'));
      if (target) {
        e.preventDefault();
        target.scrollIntoView({ behavior: 'smooth' });
      }
    });
  });

  // Auto-dismiss Flash message after 5 seconds
  const flash = document.querySelector('.flash-msg');
  if (flash) {
    setTimeout(() => {
      flash.style.opacity = '0';
      flash.style.transition = 'opacity 0.5s ease';
      setTimeout(() => flash.remove(), 500);
    }, 5000);
  }

  // Interactive Scroll Reveal Animation for Cards & Sections
  const revealElements = document.querySelectorAll('.media-card, .album-card, .artist-card, .trending-item, .cat-card, .section-header');
  revealElements.forEach(el => {
    el.classList.add('reveal-on-scroll');
  });

  const observer = new IntersectionObserver((entries) => {
    entries.forEach((entry, idx) => {
      if (entry.isIntersecting) {
        setTimeout(() => {
          entry.target.classList.add('is-visible');
        }, (idx % 5) * 80); // Staggered ripple effect
        observer.unobserve(entry.target);
      }
    });
  }, {
    threshold: 0.1,
    rootMargin: '0px 0px -40px 0px'
  });

  // ==========================================
  // HERO CAROUSEL SLIDER AUTO & MANUAL CONTROLS
  // ==========================================
  const slides = document.querySelectorAll('.slide');
  const dots = document.querySelectorAll('.slider-dots .dot');
  const prevBtn = document.getElementById('slidePrev');
  const nextBtn = document.getElementById('slideNext');
  let currentSlide = 0;
  let slideTimer = null;

  function showSlide(index) {
    if (!slides.length) return;
    if (index >= slides.length) index = 0;
    if (index < 0) index = slides.length - 1;
    currentSlide = index;

    slides.forEach((s, i) => {
      s.classList.toggle('active', i === currentSlide);
    });
    dots.forEach((d, i) => {
      d.classList.toggle('active', i === currentSlide);
    });
  }

  function startSlideTimer() {
    stopSlideTimer();
    slideTimer = setInterval(() => {
      showSlide(currentSlide + 1);
    }, 5500); // changes every 5.5s
  }

  function stopSlideTimer() {
    if (slideTimer) clearInterval(slideTimer);
  }

  if (slides.length > 0) {
    if (nextBtn) {
      nextBtn.addEventListener('click', () => {
        showSlide(currentSlide + 1);
        startSlideTimer();
      });
    }
    if (prevBtn) {
      prevBtn.addEventListener('click', () => {
        showSlide(currentSlide - 1);
        startSlideTimer();
      });
    }
    dots.forEach((dot, idx) => {
      dot.addEventListener('click', () => {
        showSlide(idx);
        startSlideTimer();
      });
    });

    const sliderContainer = document.getElementById('heroSlider');
    if (sliderContainer) {
      sliderContainer.addEventListener('mouseenter', stopSlideTimer);
      sliderContainer.addEventListener('mouseleave', startSlideTimer);
    }

    startSlideTimer();
  }

  // Fallback: If IntersectionObserver is not triggered immediately, make them visible
  setTimeout(() => {
    document.querySelectorAll('.reveal-on-scroll:not(.is-visible)').forEach(el => {
      el.classList.add('is-visible');
    });
  }, 1200);
});

