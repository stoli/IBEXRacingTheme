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
      document.body.classList.remove('ibex-lightbox-open');
      lightboxImage.src = '';
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
