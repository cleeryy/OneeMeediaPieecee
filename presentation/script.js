class PresentationController {
  constructor() {
    this.currentSlide = 1;
    this.totalSlides = document.querySelectorAll(".slide").length;
    this.init();
  }

  init() {
    this.updateSlideCounter();
    this.bindEvents();
    this.showSlide(1);
  }

  bindEvents() {
    // Navigation buttons
    document
      .getElementById("prevBtn")
      .addEventListener("click", () => this.previousSlide());
    document
      .getElementById("nextBtn")
      .addEventListener("click", () => this.nextSlide());
    document
      .getElementById("fullscreenBtn")
      .addEventListener("click", () => this.toggleFullscreen());

    // Keyboard navigation
    document.addEventListener("keydown", (e) => {
      switch (e.key) {
        case "ArrowLeft":
        case "ArrowUp":
          this.previousSlide();
          break;
        case "ArrowRight":
        case "ArrowDown":
        case " ":
          e.preventDefault();
          this.nextSlide();
          break;
        case "Home":
          this.goToSlide(1);
          break;
        case "End":
          this.goToSlide(this.totalSlides);
          break;
        case "Escape":
          if (document.fullscreenElement) {
            document.exitFullscreen();
          }
          break;
      }
    });

    // Touch/swipe support for mobile
    let startX = 0;
    let endX = 0;

    document.addEventListener("touchstart", (e) => {
      startX = e.changedTouches[0].screenX;
    });

    document.addEventListener("touchend", (e) => {
      endX = e.changedTouches[0].screenX;
      this.handleSwipe();
    });

    const handleSwipe = () => {
      const threshold = 50;
      const diff = startX - endX;

      if (Math.abs(diff) > threshold) {
        if (diff > 0) {
          this.nextSlide();
        } else {
          this.previousSlide();
        }
      }
    };

    this.handleSwipe = handleSwipe;
  }

  showSlide(n) {
    const slides = document.querySelectorAll(".slide");

    if (n > this.totalSlides) {
      this.currentSlide = this.totalSlides;
    } else if (n < 1) {
      this.currentSlide = 1;
    } else {
      this.currentSlide = n;
    }

    slides.forEach((slide, index) => {
      slide.classList.remove("active");
      if (index === this.currentSlide - 1) {
        slide.classList.add("active");
        this.animateSlideContent(slide);
      }
    });

    this.updateSlideCounter();
    this.updateNavigation();
  }

  animateSlideContent(slide) {
    const elements = slide.querySelectorAll(
      ".feature-card, .context-card, .challenge-card, .improvement-item, .api-endpoint",
    );
    elements.forEach((el, index) => {
      el.style.opacity = "0";
      el.style.transform = "translateY(20px)";

      setTimeout(() => {
        el.style.transition = "all 0.5s ease";
        el.style.opacity = "1";
        el.style.transform = "translateY(0)";
      }, index * 100);
    });
  }

  nextSlide() {
    this.showSlide(this.currentSlide + 1);
  }

  previousSlide() {
    this.showSlide(this.currentSlide - 1);
  }

  goToSlide(n) {
    this.showSlide(n);
  }

  updateSlideCounter() {
    document.getElementById("slideCounter").textContent =
      `${this.currentSlide} / ${this.totalSlides}`;
  }

  updateNavigation() {
    const prevBtn = document.getElementById("prevBtn");
    const nextBtn = document.getElementById("nextBtn");

    prevBtn.disabled = this.currentSlide === 1;
    nextBtn.disabled = this.currentSlide === this.totalSlides;

    prevBtn.style.opacity = this.currentSlide === 1 ? "0.5" : "1";
    nextBtn.style.opacity =
      this.currentSlide === this.totalSlides ? "0.5" : "1";
  }

  toggleFullscreen() {
    if (!document.fullscreenElement) {
      document.documentElement.requestFullscreen().catch((err) => {
        console.log(`Error attempting to enable fullscreen: ${err.message}`);
      });
      document.getElementById("fullscreenBtn").innerHTML =
        '<i class="fas fa-compress"></i>';
    } else {
      document.exitFullscreen();
      document.getElementById("fullscreenBtn").innerHTML =
        '<i class="fas fa-expand"></i>';
    }
  }

  // Progress tracking
  getProgress() {
    return Math.round((this.currentSlide / this.totalSlides) * 100);
  }

  // Auto-advance (optional)
  startAutoAdvance(interval = 30000) {
    // 30 seconds per slide
    this.autoAdvanceInterval = setInterval(() => {
      if (this.currentSlide < this.totalSlides) {
        this.nextSlide();
      } else {
        this.stopAutoAdvance();
      }
    }, interval);
  }

  stopAutoAdvance() {
    if (this.autoAdvanceInterval) {
      clearInterval(this.autoAdvanceInterval);
      this.autoAdvanceInterval = null;
    }
  }
}

// Initialize presentation when DOM is loaded
document.addEventListener("DOMContentLoaded", () => {
  window.presentation = new PresentationController();

  // Optional: Start auto-advance (uncomment if needed)
  // window.presentation.startAutoAdvance(45000); // 45 seconds per slide
});

// Utility functions for demo purposes
function highlightCode() {
  // Simple syntax highlighting for PHP code
  const codeBlocks = document.querySelectorAll("code.php");
  codeBlocks.forEach((block) => {
    let html = block.innerHTML;

    // Highlight PHP keywords
    html = html.replace(
      /\b(class|function|public|private|protected|return|if|else|foreach|while|for|new|extends|implements|interface|abstract|final|static|const|var|array|string|int|bool|float|void|null|true|false)\b/g,
      '<span style="color: #569cd6;">$1</span>',
    );

    // Highlight variables
    html = html.replace(/\$\w+/g, '<span style="color: #9cdcfe;">$&</span>');

    // Highlight strings
    html = html.replace(
      /'([^']*)'|"([^"]*)"/g,
      '<span style="color: #ce9178;">$&</span>',
    );

    // Highlight comments
    html = html.replace(/\/\/.*$/gm, '<span style="color: #6a9955;">$&</span>');

    block.innerHTML = html;
  });
}

// Call syntax highlighting after DOM loads
document.addEventListener("DOMContentLoaded", () => {
  setTimeout(highlightCode, 100);
});
