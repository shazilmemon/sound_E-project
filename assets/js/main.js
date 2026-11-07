// =====================================================
// SOUND GROUP - Main Frontend Script
// assets/js/main.js
// =====================================================
document.addEventListener('DOMContentLoaded', () => {
  // ==========================================
  // THEME SWITCHER (BLACK & WHITE / DARK & LIGHT)
  // ==========================================
  const themeToggleBtn = document.getElementById('themeToggleBtn');

  function updateThemeUI(theme) {
    document.documentElement.setAttribute('data-theme', theme);
    localStorage.setItem('sound_theme', theme);
    if (themeToggleBtn) {
      themeToggleBtn.setAttribute('title', theme === 'light' ? 'Switch to Black Theme (Dark Mode)' : 'Switch to White Theme (Light Mode)');
      themeToggleBtn.setAttribute('aria-label', theme === 'light' ? 'Switch to Dark Mode' : 'Switch to Light Mode');
    }
  }

  // Sync with current theme
  const initialTheme = localStorage.getItem('sound_theme') || 'dark';
  updateThemeUI(initialTheme);

  if (themeToggleBtn) {
    themeToggleBtn.addEventListener('click', (e) => {
      e.preventDefault();
      const current = document.documentElement.getAttribute('data-theme') || 'dark';
      const nextTheme = current === 'light' ? 'dark' : 'light';
      updateThemeUI(nextTheme);
    });
  }

  // Navbar Scroll Dynamic Effect
  const navbar = document.getElementById('navbar');
  const handleNavScroll = () => {
    if (!navbar) return;
    if (window.scrollY > 20) {
      navbar.classList.add('scrolled');
    } else {
      navbar.classList.remove('scrolled');
    }
  };
  window.addEventListener('scroll', handleNavScroll, { passive: true });
  handleNavScroll();

  // Mobile Hamburger & Drawer Toggle
  const hamburger = document.getElementById('hamburger');
  const navLinks = document.getElementById('navLinks');
  if (hamburger && navLinks) {
    hamburger.addEventListener('click', (e) => {
      e.stopPropagation();
      const isActive = hamburger.classList.toggle('active');
      navLinks.classList.toggle('active', isActive);
      hamburger.setAttribute('aria-expanded', isActive ? 'true' : 'false');
    });

    // Close mobile menu when clicking outside
    document.addEventListener('click', (e) => {
      if (!hamburger.contains(e.target) && !navLinks.contains(e.target)) {
        hamburger.classList.remove('active');
        navLinks.classList.remove('active');
        hamburger.setAttribute('aria-expanded', 'false');
      }
    });

    // Close when a nav link is clicked
    navLinks.querySelectorAll('a').forEach(link => {
      link.addEventListener('click', () => {
        hamburger.classList.remove('active');
        navLinks.classList.remove('active');
        hamburger.setAttribute('aria-expanded', 'false');
      });
    });
  }

  // User menu dropdown toggle on click (supports touch and mobile)
  const userPillBtn = document.getElementById('userPillBtn');
  const userMenu = document.getElementById('userMenu');
  if (userPillBtn && userMenu) {
    userPillBtn.addEventListener('click', (e) => {
      e.stopPropagation();
      const isOpen = userMenu.classList.toggle('open');
      userPillBtn.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
    });

    document.addEventListener('click', (e) => {
      if (!userMenu.contains(e.target)) {
        userMenu.classList.remove('open');
        userPillBtn.setAttribute('aria-expanded', 'false');
      }
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

