(() => {
  const galleryLinks = Array.from(document.querySelectorAll('[data-ibex-gallery]'));
  if (!galleryLinks.length) {
    return;
  }

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

  let previousFocus = null;
  let currentIndex = 0;

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
    currentIndex = index;
    previousFocus = trigger;
    updateImage();
    lightbox.classList.add('ibex-lightbox--active');
    document.body.classList.add('ibex-lightbox-open');
    closeButton.focus();
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
    currentIndex = (currentIndex + 1) % galleryLinks.length;
    updateImage();
  };

  const showPrev = () => {
    currentIndex = (currentIndex - 1 + galleryLinks.length) % galleryLinks.length;
    updateImage();
  };

  galleryLinks.forEach((link, index) => {
    link.addEventListener('click', (event) => {
      event.preventDefault();
      openLightbox(index, link);
    });
  });

  if (galleryLinks.length <= 1) {
    prevButton.hidden = true;
    nextButton.hidden = true;
  } else {
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
  }

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
})();
