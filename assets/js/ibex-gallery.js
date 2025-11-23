// IBEX Gallery Lightbox Script
// DEBUG: Uncomment below for debugging
// try {
//   console.log('[IBEX Gallery] Script file loaded and executing');
// } catch(e) {
//   alert('IBEX Gallery script error: ' + e.message);
// }

(function() {
  'use strict';
  
  // DEBUG: Uncomment below for debugging
  // try {
  //   console.log('[IBEX Gallery] IIFE executing...', {
  //     documentReady: document.readyState,
  //     bodyExists: !!document.body
  //   });
  // } catch(e) {
  //   console.error('[IBEX Gallery] Error in IIFE start:', e);
  // }
  
  // Wait for DOM to be ready if needed
  const initGallery = function() {
    // DEBUG: console.log('[IBEX Gallery] Initializing gallery...');
  
    // Get all gallery links - refresh on each open to catch dynamically added images
    const getGalleryLinks = () => {
      const links = Array.from(document.querySelectorAll('[data-ibex-gallery]'));
      // DEBUG: console.log('[IBEX Gallery] Found', links.length, 'gallery links');
      // DEBUG: if (links.length > 0) {
      // DEBUG:   console.log('[IBEX Gallery] First link:', links[0]);
      // DEBUG: }
      return links;
    };

    let galleryLinks = getGalleryLinks();
    if (!galleryLinks.length) {
      // DEBUG: console.warn('[IBEX Gallery] No gallery links found, exiting');
      return;
    }
    
    // DEBUG: console.log('[IBEX Gallery] Initializing lightbox with', galleryLinks.length, 'images');

    const lightbox = document.createElement('div');
    lightbox.className = 'ibex-lightbox';
    lightbox.setAttribute('role', 'dialog');
    lightbox.setAttribute('aria-modal', 'true');
    lightbox.innerHTML = `
      <div class="ibex-lightbox__frame">
        <button type="button" class="ibex-lightbox__close" aria-label="Close"></button>
        <button type="button" class="ibex-lightbox__control ibex-lightbox__control--prev" aria-label="Previous image">&#10094;</button>
        <img class="ibex-lightbox__image" alt="" />
        <button type="button" class="ibex-lightbox__control ibex-lightbox__control--next" aria-label="Next image">&#10095;</button>
      </div>
    `;

    document.body.appendChild(lightbox);

    const lightboxImage = lightbox.querySelector('.ibex-lightbox__image');
    const closeButton = lightbox.querySelector('.ibex-lightbox__close');
    const prevButton = lightbox.querySelector('.ibex-lightbox__control--prev');
    const nextButton = lightbox.querySelector('.ibex-lightbox__control--next');
    
    // DEBUG: console.log('[IBEX Gallery] Lightbox elements:', {
    // DEBUG:   lightboxImage: !!lightboxImage,
    // DEBUG:   closeButton: !!closeButton,
    // DEBUG:   prevButton: !!prevButton,
    // DEBUG:   nextButton: !!nextButton
    // DEBUG: });
    
    if (!prevButton || !nextButton) {
      // DEBUG: console.error('[IBEX Gallery] Navigation buttons not found!');
      return;
    }

    let previousFocus = null;
    let currentIndex = 0;
    let isZoomed = false;
    let isPanning = false;
    let panStartMouseX = 0; // Initial mouse X position when pan starts
    let panStartMouseY = 0; // Initial mouse Y position when pan starts
    let panStartPanX = 0; // Initial pan X offset when pan starts
    let panStartPanY = 0; // Initial pan Y offset when pan starts
    let panX = 0;
    let panY = 0;
    let hasPanned = false; // Track if user actually panned (moved mouse)
    let panStartTime = 0;

    const toggleZoom = () => {
      isZoomed = !isZoomed;
      if (isZoomed) {
        lightbox.classList.add('ibex-lightbox--zoomed');
        // Reset pan position when zooming in
        panX = 0;
        panY = 0;
        updateImageTransform();
        lightboxImage.style.cursor = 'zoom-out';
      } else {
        lightbox.classList.remove('ibex-lightbox--zoomed');
        // Reset pan position when zooming out
        panX = 0;
        panY = 0;
        updateImageTransform();
        lightboxImage.style.cursor = 'zoom-in';
      }
    };

    const updateImageTransform = () => {
      if (isZoomed) {
        lightboxImage.style.transform = `translate(${panX}px, ${panY}px)`;
      } else {
        lightboxImage.style.transform = 'translate(0, 0)';
      }
    };

    const startPan = (clientX, clientY) => {
      if (!isZoomed) {
        return;
      }
      isPanning = true;
      hasPanned = false;
      // Store initial mouse position and current pan offset
      panStartMouseX = clientX;
      panStartMouseY = clientY;
      panStartPanX = panX;
      panStartPanY = panY;
      panStartTime = Date.now();
      lightboxImage.style.cursor = 'grabbing';
    };

    const doPan = (clientX, clientY) => {
      if (!isPanning || !isZoomed) {
        return;
      }
      
      // Calculate mouse movement
      const deltaX = clientX - panStartMouseX;
      const deltaY = clientY - panStartMouseY;
      
      // Check if user has moved the mouse significantly (more than 5px)
      if (Math.abs(deltaX) > 5 || Math.abs(deltaY) > 5) {
        hasPanned = true;
      }
      
      // Calculate new pan position: initial pan + mouse movement
      panX = panStartPanX + deltaX;
      panY = panStartPanY + deltaY;
      
      // Constrain panning to image bounds
      const imageRect = lightboxImage.getBoundingClientRect();
      const frameRect = lightbox.querySelector('.ibex-lightbox__frame').getBoundingClientRect();
      
      const maxX = Math.max(0, (imageRect.width - frameRect.width) / 2);
      const maxY = Math.max(0, (imageRect.height - frameRect.height) / 2);
      
      panX = Math.max(-maxX, Math.min(maxX, panX));
      panY = Math.max(-maxY, Math.min(maxY, panY));
      
      updateImageTransform();
    };

    const stopPan = () => {
      if (isPanning) {
        isPanning = false;
        lightboxImage.style.cursor = 'zoom-out';
        
        // Reset hasPanned flag after a short delay to allow click detection
        // Only reset if it was a quick click (less than 200ms) and no movement
        const panDuration = Date.now() - panStartTime;
        if (panDuration < 200 && !hasPanned) {
          // Quick click without panning - allow zoom toggle
          setTimeout(() => {
            hasPanned = false;
          }, 50);
        } else {
          // User panned - prevent click from toggling zoom
          setTimeout(() => {
            hasPanned = false;
          }, 300);
        }
      }
    };

    const updateNavigationVisibility = () => {
      // Always show buttons - hide only if there's truly only one image
      // Use style.display instead of hidden attribute to override CSS
      // DEBUG: const shouldShow = galleryLinks.length > 1;
      // DEBUG: console.log('[IBEX Gallery] Updating navigation visibility:', {
      // DEBUG:   imageCount: galleryLinks.length,
      // DEBUG:   shouldShow: shouldShow,
      // DEBUG:   prevButtonDisplay: prevButton.style.display,
      // DEBUG:   nextButtonDisplay: nextButton.style.display
      // DEBUG: });
      
      if (galleryLinks.length <= 1) {
        prevButton.style.display = 'none';
        nextButton.style.display = 'none';
        // DEBUG: console.log('[IBEX Gallery] Hiding navigation buttons (only 1 image)');
      } else {
        prevButton.style.display = 'flex';
        nextButton.style.display = 'flex';
        // DEBUG: console.log('[IBEX Gallery] Showing navigation buttons');
      }
      
      // DEBUG: Double-check computed styles
      // DEBUG: const prevComputed = window.getComputedStyle(prevButton);
      // DEBUG: const nextComputed = window.getComputedStyle(nextButton);
      // DEBUG: console.log('[IBEX Gallery] Computed styles:', {
      // DEBUG:   prevDisplay: prevComputed.display,
      // DEBUG:   prevVisibility: prevComputed.visibility,
      // DEBUG:   prevOpacity: prevComputed.opacity,
      // DEBUG:   nextDisplay: nextComputed.display,
      // DEBUG:   nextVisibility: nextComputed.visibility,
      // DEBUG:   nextOpacity: nextComputed.opacity
      // DEBUG: });
    };

    const updateImage = () => {
      const targetLink = galleryLinks[currentIndex];
      if (!targetLink) {
        return;
      }

      const targetImage = targetLink.querySelector('img');
      lightboxImage.src = targetLink.getAttribute('href');
      lightboxImage.alt = targetImage ? targetImage.alt : '';
      
      // Reset zoom and pan when changing images
      if (isZoomed) {
        isZoomed = false;
        lightbox.classList.remove('ibex-lightbox--zoomed');
      }
      panX = 0;
      panY = 0;
      updateImageTransform();
    };

    const openLightbox = (index, trigger) => {
      // DEBUG: console.log('[IBEX Gallery] Opening lightbox, index:', index);
      // Refresh gallery links in case new images were added
      galleryLinks = getGalleryLinks();
      
      // Find the index of the clicked link in the refreshed array
      const linkIndex = galleryLinks.indexOf(trigger);
      currentIndex = linkIndex >= 0 ? linkIndex : index;
      
      // DEBUG: console.log('[IBEX Gallery] Current index:', currentIndex, 'Total images:', galleryLinks.length);
      
      previousFocus = trigger;
      updateNavigationVisibility();
      updateImage();
      lightbox.classList.add('ibex-lightbox--active');
      document.body.classList.add('ibex-lightbox-open');
      closeButton.focus();
      
      // DEBUG: console.log('[IBEX Gallery] Lightbox opened, buttons should be visible');
    };

    const closeLightbox = () => {
      lightbox.classList.remove('ibex-lightbox--active');
      lightbox.classList.remove('ibex-lightbox--zoomed');
      document.body.classList.remove('ibex-lightbox-open');
      lightboxImage.src = '';
      isZoomed = false;
      isPanning = false;
      panX = 0;
      panY = 0;
      updateImageTransform();
      if (previousFocus) {
        previousFocus.focus();
      }
    };

    const showNext = () => {
      if (galleryLinks.length <= 1) {
        return;
      }
      currentIndex = (currentIndex + 1) % galleryLinks.length;
      updateImage();
    };

    const showPrev = () => {
      if (galleryLinks.length <= 1) {
        return;
      }
      currentIndex = (currentIndex - 1 + galleryLinks.length) % galleryLinks.length;
      updateImage();
    };

    // Buttons are visible by default via CSS
    // We'll update visibility when lightbox opens

    galleryLinks.forEach((link, index) => {
      link.addEventListener('click', (event) => {
        event.preventDefault();
        openLightbox(index, link);
      });
    });

    // Always attach event listeners - they'll work when buttons are visible
    prevButton.addEventListener('click', (event) => {
      event.preventDefault();
      event.stopPropagation();
      showPrev();
    });
    
    nextButton.addEventListener('click', (event) => {
      event.preventDefault();
      event.stopPropagation();
      showNext();
    });

    closeButton.addEventListener('click', (event) => {
      event.preventDefault();
      event.stopPropagation();
      closeLightbox();
    });

    // Click on image to toggle zoom (only if not panning or just panned)
    lightboxImage.addEventListener('click', (event) => {
      // Don't toggle zoom if we're currently panning or just finished panning
      if (isPanning || hasPanned) {
        event.preventDefault();
        event.stopPropagation();
        return;
      }
      event.preventDefault();
      event.stopPropagation();
      toggleZoom();
    });

    // Mouse events for panning
    lightboxImage.addEventListener('mousedown', (event) => {
      if (!isZoomed) {
        return;
      }
      event.preventDefault();
      event.stopPropagation();
      startPan(event.clientX, event.clientY);
    });

    document.addEventListener('mousemove', (event) => {
      if (isPanning && isZoomed) {
        event.preventDefault();
        doPan(event.clientX, event.clientY);
      }
    });

    document.addEventListener('mouseup', () => {
      stopPan();
    });

    // Touch events for mobile panning
    lightboxImage.addEventListener('touchstart', (event) => {
      if (!isZoomed || event.touches.length !== 1) {
        return;
      }
      event.preventDefault();
      event.stopPropagation();
      const touch = event.touches[0];
      startPan(touch.clientX, touch.clientY);
    });

    document.addEventListener('touchmove', (event) => {
      if (isPanning && isZoomed && event.touches.length === 1) {
        event.preventDefault();
        const touch = event.touches[0];
        doPan(touch.clientX, touch.clientY);
      }
    });

    document.addEventListener('touchend', () => {
      stopPan();
    });

    lightbox.addEventListener('click', (event) => {
      // Only close if clicking directly on the lightbox background, not on buttons or frame
      if (event.target === lightbox) {
        closeLightbox();
      }
    });

    document.addEventListener('keydown', (event) => {
      if (!lightbox.classList.contains('ibex-lightbox--active')) {
        return;
      }

      if (event.key === 'Escape') {
        closeLightbox();
      }

      if (event.key === 'ArrowRight') {
        showNext();
      }

      if (event.key === 'ArrowLeft') {
        showPrev();
      }
    });
    
    // DEBUG: console.log('[IBEX Gallery] Gallery initialized successfully');
  };
  
  // Run when DOM is ready
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initGallery);
  } else {
    initGallery();
  }
})();

// DEBUG: console.log('[IBEX Gallery] Script initialization complete');
