/**
 * TYPO3 Documentation Image Zoom
 *
 * Provides multiple zoom modes for figures and images:
 * - lightbox: Click to open full-size in overlay (native dialog)
 * - gallery: Click to open with wheel zoom and gallery navigation
 * - inline: Wheel zoom directly on image
 * - lens: Magnifier lens follows cursor
 */
(function() {
    'use strict';

    // ==========================================================================
    // Configuration
    // ==========================================================================
    var CONFIG = {
        maxZoom: 4,
        minZoom: 1,
        zoomStep: 0.25,
        inlineMaxZoom: 3,
        lensZoomFactor: 2
    };

    // ==========================================================================
    // Helper: Create Element
    // ==========================================================================
    function createElement(tag, className, attrs) {
        var el = document.createElement(tag);
        if (className) el.className = className;
        if (attrs) {
            Object.keys(attrs).forEach(function(key) {
                if (key === 'text') {
                    el.textContent = attrs[key];
                } else {
                    el.setAttribute(key, attrs[key]);
                }
            });
        }
        return el;
    }

    // ==========================================================================
    // Helper: Get Pinch Distance Between Two Touches
    // ==========================================================================
    function getPinchDistance(touches) {
        if (touches.length < 2) return 0;
        var dx = touches[0].clientX - touches[1].clientX;
        var dy = touches[0].clientY - touches[1].clientY;
        return Math.sqrt(dx * dx + dy * dy);
    }

    // ==========================================================================
    // Helper: Get Pinch Center Between Two Touches
    // ==========================================================================
    function getPinchCenter(touches) {
        if (touches.length < 2) return { x: 0, y: 0 };
        return {
            x: (touches[0].clientX + touches[1].clientX) / 2,
            y: (touches[0].clientY + touches[1].clientY) / 2
        };
    }

    // ==========================================================================
    // Helper: Add Zoom Indicator Icon
    // ==========================================================================
    function addZoomIndicator(container, img, iconClass, tooltip, trigger, cursor) {
        if (!img) return null;

        // Always set image to block to prevent baseline spacing issues
        img.style.display = 'block';

        // Check if indicator is disabled via data-zoom-indicator="false"
        var zoomIndicator = trigger ? trigger.getAttribute('data-zoom-indicator') : null;
        if (zoomIndicator === 'false' || zoomIndicator === 'no' || zoomIndicator === '0') {
            return null;
        }

        var indicator = createElement('span', 'zoom-indicator');
        indicator.setAttribute('aria-hidden', 'true');
        indicator.innerHTML = '<i class="fa ' + (iconClass || 'fa-search-plus') + '"></i>';
        // Keep pointer-events enabled for hover/tooltip, but forward clicks/scrolls to image
        var cursorStyle = cursor || 'zoom-in';
        indicator.style.cssText = 'position:absolute;font-size:14px;opacity:0.7;z-index:10;color:#333;text-shadow:0 0 3px #fff, 0 0 5px #fff, 0 0 7px #fff;cursor:' + cursorStyle + ';';
        if (tooltip) {
            indicator.setAttribute('title', tooltip);
        }

        // Forward click events to the image
        indicator.addEventListener('click', function(e) {
            e.stopPropagation();
            img.click();
        });

        // Forward wheel events to the image for inline zoom
        indicator.addEventListener('wheel', function(e) {
            e.preventDefault();
            e.stopPropagation();
            // Create and dispatch a new wheel event on the image
            var wheelEvent = new WheelEvent('wheel', {
                deltaY: e.deltaY,
                deltaX: e.deltaX,
                clientX: e.clientX,
                clientY: e.clientY,
                bubbles: true,
                cancelable: true
            });
            img.dispatchEvent(wheelEvent);
        }, { passive: false });

        container.style.position = 'relative';
        container.appendChild(indicator);

        // Position indicator at bottom-right of actual image content (inside border)
        function positionIndicator() {
            if (img.offsetWidth > 0 && img.offsetHeight > 0) {
                var imgStyle = window.getComputedStyle(img);
                var borderRight = parseFloat(imgStyle.borderRightWidth) || 0;
                var borderBottom = parseFloat(imgStyle.borderBottomWidth) || 0;
                var paddingRight = parseFloat(imgStyle.paddingRight) || 0;
                var paddingBottom = parseFloat(imgStyle.paddingBottom) || 0;

                // Position from the image's offset within container, accounting for border/padding
                var spacing = 6;
                indicator.style.top = (img.offsetTop + img.offsetHeight - borderBottom - paddingBottom - 20 - spacing) + 'px';
                indicator.style.left = (img.offsetLeft + img.offsetWidth - borderRight - paddingRight - 20 - spacing) + 'px';
            }
        }

        if (img.complete) {
            positionIndicator();
        } else {
            img.addEventListener('load', positionIndicator);
        }
        window.addEventListener('resize', positionIndicator);

        return indicator;
    }

    // ==========================================================================
    // Native Dialog Lightbox
    // ==========================================================================
    function initDialogLightbox() {
        document.querySelectorAll('[data-zoom="lightbox"]').forEach(function(trigger) {
            // Find the image inside the figure (or use trigger if it's an img)
            var img = trigger.tagName === 'IMG' ? trigger : trigger.querySelector('img');
            if (!img) return;

            var imgSrc = img.src;
            var caption = '';
            var figcaption = trigger.querySelector('figcaption');
            if (figcaption) {
                caption = figcaption.textContent;
            } else {
                caption = img.alt || '';
            }

            // Create dialog dynamically
            var dialog = createElement('dialog', 'image-lightbox-dialog');
            var dialogImg = createElement('img', '', { src: imgSrc, alt: caption });
            var closeBtn = createElement('button', 'lightbox-close', {
                'aria-label': 'Close',
                text: '×'
            });
            dialog.appendChild(closeBtn);
            dialog.appendChild(dialogImg);

            if (caption) {
                var captionEl = createElement('p', 'lightbox-caption', { text: caption });
                dialog.appendChild(captionEl);
            }

            document.body.appendChild(dialog);

            // Make trigger accessible
            img.setAttribute('tabindex', '0');
            img.setAttribute('role', 'button');
            img.setAttribute('aria-label', 'Click to enlarge: ' + (caption || 'image'));
            img.classList.add('lightbox-trigger');

            // Create wrapper for zoom indicator positioning
            var wrapper = createElement('span', 'zoom-trigger-wrapper');
            wrapper.style.cssText = 'display:inline-block;position:relative;line-height:0;';
            img.parentNode.insertBefore(wrapper, img);
            wrapper.appendChild(img);
            addZoomIndicator(wrapper, img, 'fa-search-plus', 'Click to enlarge', trigger);

            img.addEventListener('dragstart', function(e) { e.preventDefault(); });

            img.addEventListener('click', function(e) {
                e.preventDefault();
                dialog.showModal();
            });

            img.addEventListener('keydown', function(e) {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    dialog.showModal();
                }
            });

            // Close on any click (image or backdrop) - toggle behavior
            dialog.addEventListener('click', function(e) {
                // Don't close if clicking the close button (it handles itself)
                if (e.target.classList.contains('lightbox-close')) return;
                dialog.close();
            });

            closeBtn.addEventListener('click', function() {
                dialog.close();
            });

            // Close on Escape
            dialog.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') {
                    dialog.close();
                }
            });
        });
    }

    // ==========================================================================
    // Gallery with Wheel Zoom
    // ==========================================================================
    function initGallery() {
        var galleries = {};

        document.querySelectorAll('[data-zoom="gallery"]').forEach(function(trigger) {
            // Find the image inside the figure (or use trigger if it's an img)
            var img = trigger.tagName === 'IMG' ? trigger : trigger.querySelector('img');
            if (!img) return;

            var galleryId = trigger.getAttribute('data-gallery') || 'default';
            if (!galleries[galleryId]) {
                // Read configurable zoom limits from first element in gallery
                galleries[galleryId] = {
                    images: [],
                    currentIndex: 0,
                    zoom: 1,
                    panX: 0,
                    panY: 0,
                    dragging: false,
                    maxZoom: parseFloat(trigger.getAttribute('data-max-zoom')) || CONFIG.maxZoom,
                    minZoom: parseFloat(trigger.getAttribute('data-min-zoom')) || CONFIG.minZoom,
                    zoomStep: parseFloat(trigger.getAttribute('data-zoom-step')) || CONFIG.zoomStep
                };
            }

            // Get image source and caption from figure structure
            var imgSrc = trigger.getAttribute('data-zoom-src') || img.src;
            var caption = trigger.getAttribute('data-zoom-caption') || '';
            if (!caption) {
                var figcaption = trigger.querySelector('figcaption');
                if (figcaption) {
                    caption = figcaption.textContent;
                } else {
                    caption = img.alt || '';
                }
            }

            galleries[galleryId].images.push({
                src: imgSrc,
                caption: caption,
                trigger: trigger
            });

            var index = galleries[galleryId].images.length - 1;
            trigger.setAttribute('data-gallery-index', index);

            // Make the image keyboard accessible (not the figure)
            img.setAttribute('tabindex', '0');
            img.setAttribute('role', 'button');
            img.setAttribute('aria-label', 'Open image in gallery: ' + (caption || 'Image ' + (index + 1)));
            img.style.cursor = 'zoom-in';

            // Create wrapper for zoom indicator positioning
            var wrapper = createElement('span', 'zoom-trigger-wrapper');
            wrapper.style.cssText = 'display:inline-block;position:relative;line-height:0;';
            img.parentNode.insertBefore(wrapper, img);
            wrapper.appendChild(img);
            addZoomIndicator(wrapper, img, 'fa-search-plus', 'Click to enlarge', trigger);

            img.addEventListener('dragstart', function(e) { e.preventDefault(); });

            img.addEventListener('click', function(e) {
                e.preventDefault();
                openGallery(galleryId, index);
            });

            // Keyboard support for trigger
            img.addEventListener('keydown', function(e) {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    openGallery(galleryId, index);
                }
            });
        });

        if (Object.keys(galleries).length === 0) return;

        var overlay = createGalleryOverlay();
        document.body.appendChild(overlay);

        var img = overlay.querySelector('.gallery-img');
        var caption = overlay.querySelector('.gallery-caption');
        var counterCurrent = overlay.querySelector('.gallery-counter-current');
        var counterTotal = overlay.querySelector('.gallery-counter-total');
        var zoomLevel = overlay.querySelector('.gallery-zoom-level');
        var content = overlay.querySelector('.gallery-content');

        var currentGallery = null;
        var startX, startY;
        var lastFocusedElement = null;

        // Get all focusable elements in the overlay for focus trap
        function getFocusableElements() {
            return overlay.querySelectorAll('button:not([disabled])');
        }

        // Focus trap handler
        function handleFocusTrap(e) {
            if (e.key !== 'Tab') return;
            var focusable = getFocusableElements();
            if (focusable.length === 0) return;

            var firstEl = focusable[0];
            var lastEl = focusable[focusable.length - 1];

            if (e.shiftKey && document.activeElement === firstEl) {
                e.preventDefault();
                lastEl.focus();
            } else if (!e.shiftKey && document.activeElement === lastEl) {
                e.preventDefault();
                firstEl.focus();
            }
        }

        function openGallery(galleryId, index) {
            // Store the element that triggered the gallery for focus return
            lastFocusedElement = document.activeElement;

            currentGallery = galleries[galleryId];
            currentGallery.currentIndex = index;
            currentGallery.zoom = 1;
            currentGallery.panX = 0;
            currentGallery.panY = 0;
            showCurrentImage();
            overlay.classList.add('active');
            document.body.style.overflow = 'hidden';
            counterTotal.textContent = currentGallery.images.length;

            // Focus the close button for accessibility
            var closeBtn = overlay.querySelector('.gallery-close');
            if (closeBtn) {
                setTimeout(function() { closeBtn.focus(); }, 50);
            }

            // Enable focus trap
            overlay.addEventListener('keydown', handleFocusTrap);
        }

        function closeGallery() {
            overlay.classList.remove('active');
            document.body.style.overflow = '';

            // Remove focus trap
            overlay.removeEventListener('keydown', handleFocusTrap);

            // Return focus to the trigger element
            if (lastFocusedElement && lastFocusedElement.focus) {
                lastFocusedElement.focus();
            }

            currentGallery = null;
            lastFocusedElement = null;
        }

        function showCurrentImage() {
            if (!currentGallery) return;
            var current = currentGallery.images[currentGallery.currentIndex];
            img.onerror = function() {
                img.alt = 'Image failed to load: ' + current.src;
                img.style.opacity = '0.5';
            };
            img.onload = function() {
                img.style.opacity = '1';
            };
            img.src = current.src;
            img.alt = current.caption;
            caption.textContent = current.caption;
            counterCurrent.textContent = currentGallery.currentIndex + 1;
            resetZoom();
        }

        function navigate(delta) {
            if (!currentGallery) return;
            var len = currentGallery.images.length;
            currentGallery.currentIndex = (currentGallery.currentIndex + delta + len) % len;
            showCurrentImage();
        }

        function setZoom(newZoom) {
            if (!currentGallery) return;
            currentGallery.zoom = Math.max(currentGallery.minZoom, Math.min(currentGallery.maxZoom, newZoom));
            if (currentGallery.zoom === 1) {
                currentGallery.panX = 0;
                currentGallery.panY = 0;
            }
            updateTransform();
        }

        function resetZoom() {
            if (!currentGallery) return;
            currentGallery.zoom = 1;
            currentGallery.panX = 0;
            currentGallery.panY = 0;
            updateTransform();
        }

        function updateTransform() {
            if (!currentGallery) return;
            // translate then scale: scale from center, then offset
            img.style.transform = 'translate(' + currentGallery.panX + 'px, ' + currentGallery.panY + 'px) scale(' + currentGallery.zoom + ')';
            zoomLevel.textContent = Math.round(currentGallery.zoom * 100) + '%';
            img.style.cursor = currentGallery.zoom > 1 ? 'grab' : 'zoom-out';
        }

        overlay.querySelector('.gallery-close').addEventListener('click', closeGallery);
        overlay.querySelector('.gallery-prev').addEventListener('click', function() { navigate(-1); });
        overlay.querySelector('.gallery-next').addEventListener('click', function() { navigate(1); });
        overlay.querySelector('.gallery-zoom-in').addEventListener('click', function() { setZoom(currentGallery.zoom + 0.5); });
        overlay.querySelector('.gallery-zoom-out').addEventListener('click', function() { setZoom(currentGallery.zoom - 0.5); });
        overlay.querySelector('.gallery-zoom-reset').addEventListener('click', resetZoom);

        content.addEventListener('wheel', function(e) {
            if (!currentGallery) return;
            e.preventDefault();

            // Get mouse position relative to content center (transform origin)
            var contentRect = content.getBoundingClientRect();
            var mouseX = e.clientX - (contentRect.left + contentRect.width / 2);
            var mouseY = e.clientY - (contentRect.top + contentRect.height / 2);

            var oldZoom = currentGallery.zoom;
            var delta = e.deltaY > 0 ? -currentGallery.zoomStep : currentGallery.zoomStep;
            var newZoom = Math.max(currentGallery.minZoom, Math.min(currentGallery.maxZoom, oldZoom + delta));

            if (newZoom === 1) {
                currentGallery.panX = 0;
                currentGallery.panY = 0;
            } else if (oldZoom !== newZoom) {
                // Pin the point under cursor
                var ratio = newZoom / oldZoom;
                currentGallery.panX = mouseX * (1 - ratio) + currentGallery.panX * ratio;
                currentGallery.panY = mouseY * (1 - ratio) + currentGallery.panY * ratio;
            }

            currentGallery.zoom = newZoom;
            updateTransform();
        }, { passive: false });

        img.addEventListener('dragstart', function(e) { e.preventDefault(); });

        img.addEventListener('mousedown', function(e) {
            if (!currentGallery || currentGallery.zoom <= 1) return;
            e.preventDefault();
            currentGallery.dragging = true;
            startX = e.clientX - currentGallery.panX;
            startY = e.clientY - currentGallery.panY;
            img.style.cursor = 'grabbing';
        });

        document.addEventListener('mousemove', function(e) {
            if (!currentGallery || !currentGallery.dragging) return;
            e.preventDefault();
            currentGallery.panX = e.clientX - startX;
            currentGallery.panY = e.clientY - startY;
            updateTransform();
        });

        document.addEventListener('mouseup', function() {
            if (!currentGallery) return;
            currentGallery.dragging = false;
            if (currentGallery.zoom > 1) {
                img.style.cursor = 'grab';
            }
        });

        // Touch support for mobile
        var touchStartDistance = 0;
        var touchStartZoom = 1;
        var touchStartPanX = 0;
        var touchStartPanY = 0;
        var lastTouchX = 0;
        var lastTouchY = 0;

        img.addEventListener('touchstart', function(e) {
            if (!currentGallery) return;
            if (e.touches.length === 2) {
                // Pinch start
                e.preventDefault();
                touchStartDistance = getPinchDistance(e.touches);
                touchStartZoom = currentGallery.zoom;
                var center = getPinchCenter(e.touches);
                touchStartPanX = center.x;
                touchStartPanY = center.y;
            } else if (e.touches.length === 1 && currentGallery.zoom > 1) {
                // Pan start
                e.preventDefault();
                currentGallery.dragging = true;
                lastTouchX = e.touches[0].clientX;
                lastTouchY = e.touches[0].clientY;
            }
        }, { passive: false });

        img.addEventListener('touchmove', function(e) {
            if (!currentGallery) return;
            if (e.touches.length === 2) {
                // Pinch zoom
                e.preventDefault();
                var newDistance = getPinchDistance(e.touches);
                if (touchStartDistance > 0) {
                    var scale = newDistance / touchStartDistance;
                    var newZoom = Math.max(currentGallery.minZoom, Math.min(currentGallery.maxZoom, touchStartZoom * scale));
                    currentGallery.zoom = newZoom;
                    updateTransform();
                }
            } else if (e.touches.length === 1 && currentGallery.dragging) {
                // Pan
                e.preventDefault();
                var deltaX = e.touches[0].clientX - lastTouchX;
                var deltaY = e.touches[0].clientY - lastTouchY;
                currentGallery.panX += deltaX;
                currentGallery.panY += deltaY;
                lastTouchX = e.touches[0].clientX;
                lastTouchY = e.touches[0].clientY;
                updateTransform();
            }
        }, { passive: false });

        img.addEventListener('touchend', function(e) {
            if (!currentGallery) return;
            if (e.touches.length === 0) {
                currentGallery.dragging = false;
                touchStartDistance = 0;
            }
        });

        document.addEventListener('keydown', function(e) {
            if (!overlay.classList.contains('active')) return;
            switch(e.key) {
                case 'Escape': closeGallery(); break;
                case 'ArrowLeft': navigate(-1); break;
                case 'ArrowRight': navigate(1); break;
                case '+': case '=': setZoom(currentGallery.zoom + 0.5); break;
                case '-': setZoom(currentGallery.zoom - 0.5); break;
                case '0': resetZoom(); break;
            }
        });

        // Close on click or reset zoom - toggle behavior
        overlay.addEventListener('click', function(e) {
            // Don't close if clicking toolbar buttons or nav
            if (e.target.closest('.gallery-toolbar') || e.target.closest('.gallery-nav')) return;
            // Don't act on clicks on the image itself (allows drag)
            if (e.target === img) return;
            if (!currentGallery) return;

            // If zoomed, reset zoom first (clicking outside image acts as zoom-out)
            if (currentGallery.zoom > 1) {
                resetZoom();
                return;
            }
            // If not zoomed, close the gallery
            closeGallery();
        });
    }

    function createGalleryOverlay() {
        var overlay = createElement('div', 'image-gallery-overlay');
        // ARIA attributes for accessibility
        overlay.setAttribute('role', 'dialog');
        overlay.setAttribute('aria-modal', 'true');
        overlay.setAttribute('aria-label', 'Image gallery viewer');

        // Counter
        var counter = createElement('span', 'gallery-counter');
        counter.setAttribute('aria-live', 'polite');
        counter.setAttribute('aria-atomic', 'true');
        var counterCurrent = createElement('span', 'gallery-counter-current');
        counterCurrent.textContent = '1';
        var counterTotal = createElement('span', 'gallery-counter-total');
        counterTotal.textContent = '1';
        counter.appendChild(counterCurrent);
        counter.appendChild(document.createTextNode(' / '));
        counter.appendChild(counterTotal);
        overlay.appendChild(counter);

        // Zoom level - announce to screen readers
        var zoomLevel = createElement('div', 'gallery-zoom-level');
        zoomLevel.textContent = '100%';
        zoomLevel.setAttribute('aria-live', 'polite');
        zoomLevel.setAttribute('aria-atomic', 'true');
        overlay.appendChild(zoomLevel);

        // Toolbar with accessible buttons
        var toolbar = createElement('div', 'gallery-toolbar');
        toolbar.setAttribute('role', 'toolbar');
        toolbar.setAttribute('aria-label', 'Gallery controls');
        var btnZoomOut = createElement('button', 'gallery-zoom-out', {
            title: 'Zoom Out (scroll down)',
            text: '−',
            'aria-label': 'Zoom out'
        });
        var btnZoomIn = createElement('button', 'gallery-zoom-in', {
            title: 'Zoom In (scroll up)',
            text: '+',
            'aria-label': 'Zoom in'
        });
        var btnZoomReset = createElement('button', 'gallery-zoom-reset', {
            title: 'Reset Zoom',
            text: '1:1',
            'aria-label': 'Reset zoom to 100%'
        });
        var btnClose = createElement('button', 'gallery-close', {
            title: 'Close (ESC)',
            text: '×',
            'aria-label': 'Close gallery'
        });
        toolbar.appendChild(btnZoomOut);
        toolbar.appendChild(btnZoomIn);
        toolbar.appendChild(btnZoomReset);
        toolbar.appendChild(btnClose);
        overlay.appendChild(toolbar);

        // Content
        var content = createElement('div', 'gallery-content');
        var img = createElement('img', 'gallery-img', { src: '', alt: '' });
        content.appendChild(img);
        overlay.appendChild(content);

        // Caption
        var caption = createElement('p', 'gallery-caption');
        overlay.appendChild(caption);

        // Navigation with accessible buttons
        var prevBtn = createElement('button', 'gallery-nav prev gallery-prev', {
            title: 'Previous (←)',
            text: '❮',
            'aria-label': 'Previous image'
        });
        var nextBtn = createElement('button', 'gallery-nav next gallery-next', {
            title: 'Next (→)',
            text: '❯',
            'aria-label': 'Next image'
        });
        overlay.appendChild(prevBtn);
        overlay.appendChild(nextBtn);

        return overlay;
    }

    // ==========================================================================
    // Inline Zoom (Product-Style)
    // ==========================================================================
    function initInlineZoom() {
        document.querySelectorAll('[data-zoom="inline"]').forEach(function(container) {
            var img = container.querySelector('img');
            if (!img) return;

            // Read configurable zoom limits from data attributes
            var maxZoom = parseFloat(container.getAttribute('data-max-zoom')) || CONFIG.inlineMaxZoom;
            var minZoom = parseFloat(container.getAttribute('data-min-zoom')) || CONFIG.minZoom;
            var zoomStep = parseFloat(container.getAttribute('data-zoom-step')) || CONFIG.zoomStep;

            var zoom = 1;
            var panX = 0, panY = 0;
            var dragging = false;
            var startX, startY;

            // Add required class for CSS
            container.classList.add('inline-zoom-container');

            // Create wrapper for clipping - wrapper matches exact image size
            var wrapper = createElement('div', 'inline-zoom-wrapper');
            wrapper.style.cssText = 'display:inline-block;overflow:hidden;position:relative;line-height:0;';

            // For figures: border/shadow are on the figure (wrapping caption too), so don't move them
            // For span/img containers: border/shadow are on the img, so move them to wrapper using inline styles
            // (CSS classes like with-shadow only work on img/figure elements)
            var isFigure = container.tagName === 'FIGURE';
            if (!isFigure) {
                if (img.classList.contains('with-border')) {
                    img.classList.remove('with-border');
                    wrapper.style.border = 'var(--bs-border-width) var(--bs-border-style) var(--bs-border-color)';
                }
                if (img.classList.contains('with-shadow')) {
                    img.classList.remove('with-shadow');
                    wrapper.style.boxShadow = 'var(--bs-box-shadow)';
                    wrapper.style.padding = '1rem';
                    wrapper.style.marginBottom = '1.5rem';
                }
                img.style.border = 'none';
                img.style.boxShadow = 'none';
                img.style.padding = '0';
                img.style.margin = '0';
            }

            img.parentNode.insertBefore(wrapper, img);
            wrapper.appendChild(img);

            img.style.cursor = 'zoom-in';
            img.style.transformOrigin = 'center center';
            img.style.display = 'block';

            // Add zoom indicator to the wrapper (fa-expand for scroll zoom)
            addZoomIndicator(wrapper, img, 'fa-expand', 'Scroll to zoom', container);

            // Accessibility: make container focusable
            container.setAttribute('tabindex', '0');
            container.setAttribute('role', 'application');
            container.setAttribute('aria-label', 'Zoomable image. Use scroll wheel, plus/minus keys, or arrow keys to zoom. Double-click or press Escape to reset.');

            // Create screen reader announcement element
            var srAnnounce = createElement('span', 'sr-only');
            srAnnounce.setAttribute('aria-live', 'polite');
            srAnnounce.setAttribute('aria-atomic', 'true');
            srAnnounce.style.cssText = 'position:absolute;width:1px;height:1px;padding:0;margin:-1px;overflow:hidden;clip:rect(0,0,0,0);border:0;';
            container.appendChild(srAnnounce);

            function announceZoom() {
                srAnnounce.textContent = 'Zoom: ' + Math.round(zoom * 100) + '%';
            }

            img.addEventListener('dragstart', function(e) { e.preventDefault(); });
            img.style.userSelect = 'none';

            function updateTransform() {
                // translate then scale: scale happens from center, then we offset
                img.style.transform = 'translate(' + panX + 'px, ' + panY + 'px) scale(' + zoom + ')';
                container.classList.toggle('zoomed', zoom > 1);
            }

            function setZoom(newZoom, centerX, centerY) {
                var oldZoom = zoom;
                newZoom = Math.max(minZoom, Math.min(maxZoom, newZoom));

                if (newZoom === 1) {
                    panX = 0;
                    panY = 0;
                } else if (oldZoom !== newZoom && centerX !== undefined) {
                    var ratio = newZoom / oldZoom;
                    panX = centerX * (1 - ratio) + panX * ratio;
                    panY = centerY * (1 - ratio) + panY * ratio;
                }

                zoom = newZoom;
                updateTransform();
                announceZoom();
            }

            // Wheel zoom only on the image itself, not the whole container
            img.addEventListener('wheel', function(e) {
                e.preventDefault();

                // Get mouse position relative to wrapper center (transform origin)
                // Use wrapper rect, not container rect, because for figures the container
                // includes the figcaption and is taller than the image
                var wrapperRect = wrapper.getBoundingClientRect();
                var mouseX = e.clientX - (wrapperRect.left + wrapperRect.width / 2);
                var mouseY = e.clientY - (wrapperRect.top + wrapperRect.height / 2);

                var delta = e.deltaY > 0 ? -0.2 : 0.2;
                setZoom(zoom + delta, mouseX, mouseY);
            }, { passive: false });

            // Keyboard zoom support
            container.addEventListener('keydown', function(e) {
                var step = 0.25;
                var panStep = 20;

                switch(e.key) {
                    case '+':
                    case '=':
                        e.preventDefault();
                        setZoom(zoom + step, 0, 0);
                        break;
                    case '-':
                        e.preventDefault();
                        setZoom(zoom - step, 0, 0);
                        break;
                    case 'ArrowUp':
                        e.preventDefault();
                        if (zoom > 1) {
                            panY += panStep;
                            updateTransform();
                        } else {
                            setZoom(zoom + step, 0, 0);
                        }
                        break;
                    case 'ArrowDown':
                        e.preventDefault();
                        if (zoom > 1) {
                            panY -= panStep;
                            updateTransform();
                        } else {
                            setZoom(zoom - step, 0, 0);
                        }
                        break;
                    case 'ArrowLeft':
                        e.preventDefault();
                        if (zoom > 1) {
                            panX += panStep;
                            updateTransform();
                        }
                        break;
                    case 'ArrowRight':
                        e.preventDefault();
                        if (zoom > 1) {
                            panX -= panStep;
                            updateTransform();
                        }
                        break;
                    case 'Escape':
                    case '0':
                        e.preventDefault();
                        zoom = 1;
                        panX = 0;
                        panY = 0;
                        updateTransform();
                        announceZoom();
                        break;
                }
            });

            img.addEventListener('mousedown', function(e) {
                if (zoom <= 1) return;
                e.preventDefault();
                dragging = true;
                startX = e.clientX - panX;
                startY = e.clientY - panY;
                container.classList.add('dragging');
            });

            document.addEventListener('mousemove', function(e) {
                if (!dragging) return;
                e.preventDefault();
                panX = e.clientX - startX;
                panY = e.clientY - startY;
                updateTransform();
            });

            document.addEventListener('mouseup', function() {
                dragging = false;
                container.classList.remove('dragging');
            });

            img.addEventListener('dblclick', function() {
                zoom = 1;
                panX = 0;
                panY = 0;
                updateTransform();
                announceZoom();
            });

            // Touch support for mobile
            var touchStartDistance = 0;
            var touchStartZoom = 1;
            var lastTouchX = 0;
            var lastTouchY = 0;
            var touchDragging = false;

            img.addEventListener('touchstart', function(e) {
                if (e.touches.length === 2) {
                    // Pinch start
                    e.preventDefault();
                    touchStartDistance = getPinchDistance(e.touches);
                    touchStartZoom = zoom;
                } else if (e.touches.length === 1) {
                    if (zoom > 1) {
                        // Pan start when zoomed
                        e.preventDefault();
                        touchDragging = true;
                        lastTouchX = e.touches[0].clientX;
                        lastTouchY = e.touches[0].clientY;
                        container.classList.add('dragging');
                    }
                }
            }, { passive: false });

            img.addEventListener('touchmove', function(e) {
                if (e.touches.length === 2) {
                    // Pinch zoom
                    e.preventDefault();
                    var newDistance = getPinchDistance(e.touches);
                    if (touchStartDistance > 0) {
                        var scale = newDistance / touchStartDistance;
                        var newZoom = Math.max(minZoom, Math.min(maxZoom, touchStartZoom * scale));
                        zoom = newZoom;
                        updateTransform();
                        announceZoom();
                    }
                } else if (e.touches.length === 1 && touchDragging) {
                    // Pan
                    e.preventDefault();
                    var deltaX = e.touches[0].clientX - lastTouchX;
                    var deltaY = e.touches[0].clientY - lastTouchY;
                    panX += deltaX;
                    panY += deltaY;
                    lastTouchX = e.touches[0].clientX;
                    lastTouchY = e.touches[0].clientY;
                    updateTransform();
                }
            }, { passive: false });

            img.addEventListener('touchend', function(e) {
                if (e.touches.length === 0) {
                    touchDragging = false;
                    touchStartDistance = 0;
                    container.classList.remove('dragging');
                }
            });
        });
    }

    // ==========================================================================
    // Magnifier Lens
    // ==========================================================================
    function initLensZoom() {
        document.querySelectorAll('[data-zoom="lens"]').forEach(function(container) {
            var img = container.querySelector('img');
            if (!img) return;

            // Add required class for CSS styling
            container.classList.add('lens-zoom-container');

            // Ensure container has position relative for lens positioning
            container.style.position = 'relative';
            container.style.display = 'inline-block';
            img.style.cursor = 'crosshair';

            // Create wrapper around img for zoom indicator
            var imgWrapper = createElement('span', 'zoom-trigger-wrapper');
            imgWrapper.style.cssText = 'display:inline-block;position:relative;line-height:0;';
            img.parentNode.insertBefore(imgWrapper, img);
            imgWrapper.appendChild(img);
            addZoomIndicator(imgWrapper, img, 'fa-search', null, container, 'crosshair');  // No tooltip - lens activates on hover

            // Lens and result panel are appended to the imgWrapper (not container)
            // so they position relative to the image
            var lens = createElement('div', 'zoom-lens');
            imgWrapper.appendChild(lens);

            var result = createElement('div', 'zoom-result-panel');
            result.style.zIndex = '9999';  // Ensure result panel is above all other content
            document.body.appendChild(result);  // Append to body for fixed positioning

            var zoomFactor = parseFloat(container.getAttribute('data-zoom-factor')) || CONFIG.lensZoomFactor;
            var lensActive = false;
            var lensX = 50, lensY = 50; // Default position (center percentage)

            // Accessibility: make container focusable
            container.setAttribute('tabindex', '0');
            container.setAttribute('role', 'application');
            container.setAttribute('aria-label', 'Magnifier lens. Press Enter or Space to toggle lens on/off. Use arrow keys to move the lens when active.');

            // Create screen reader announcement element
            var srAnnounce = createElement('span', 'sr-only');
            srAnnounce.setAttribute('aria-live', 'polite');
            srAnnounce.setAttribute('aria-atomic', 'true');
            srAnnounce.style.cssText = 'position:absolute;width:1px;height:1px;padding:0;margin:-1px;overflow:hidden;clip:rect(0,0,0,0);border:0;';
            container.appendChild(srAnnounce);

            function updateLensPosition(x, y) {
                var rect = img.getBoundingClientRect();
                var style = window.getComputedStyle(img);
                var borderLeft = parseFloat(style.borderLeftWidth) || 0;
                var borderTop = parseFloat(style.borderTopWidth) || 0;
                var paddingLeft = parseFloat(style.paddingLeft) || 0;
                var paddingTop = parseFloat(style.paddingTop) || 0;

                // Position lens centered on cursor (x,y are relative to image content area)
                // Add border+padding offset since lens is positioned within imgWrapper
                var lensXPos = x + borderLeft + paddingLeft - lens.offsetWidth / 2;
                var lensYPos = y + borderTop + paddingTop - lens.offsetHeight / 2;

                lens.style.left = lensXPos + 'px';
                lens.style.top = lensYPos + 'px';

                // Background shows magnified view - pixel at (x,y) should appear at lens center
                // In magnified image, pixel (x,y) is at position (x*zoomFactor, y*zoomFactor)
                // To center it in lens, offset = -(x*zoomFactor) + lensCenter
                var lensBgX = -x * zoomFactor + lens.offsetWidth / 2;
                var lensBgY = -y * zoomFactor + lens.offsetHeight / 2;
                lens.style.backgroundImage = 'url(' + img.src + ')';
                lens.style.backgroundSize = (rect.width * zoomFactor) + 'px ' + (rect.height * zoomFactor) + 'px';
                lens.style.backgroundPosition = lensBgX + 'px ' + lensBgY + 'px';

                // Result panel - use fixed positioning to stay within viewport
                var resultWidth = 300;
                var resultHeight = 300;
                var margin = 16;
                var viewportWidth = window.innerWidth;
                var viewportHeight = window.innerHeight;

                // Default: position to the right of the image
                var resultX = rect.right + margin;
                var resultY = rect.top;

                // If no room on right, try left
                if (resultX + resultWidth > viewportWidth) {
                    resultX = rect.left - resultWidth - margin;
                }

                // If no room on left either, position below cursor
                if (resultX < 0) {
                    resultX = Math.max(margin, Math.min(rect.left, viewportWidth - resultWidth - margin));
                    resultY = rect.bottom + margin;
                }

                // Ensure result stays within vertical bounds
                if (resultY + resultHeight > viewportHeight) {
                    resultY = viewportHeight - resultHeight - margin;
                }
                if (resultY < margin) {
                    resultY = margin;
                }

                result.style.left = resultX + 'px';
                result.style.top = resultY + 'px';

                // Result panel shows magnified view centered on cursor
                var resultBgX = -x * zoomFactor + result.offsetWidth / 2;
                var resultBgY = -y * zoomFactor + result.offsetHeight / 2;
                result.style.backgroundImage = 'url(' + img.src + ')';
                result.style.backgroundSize = (rect.width * zoomFactor) + 'px ' + (rect.height * zoomFactor) + 'px';
                result.style.backgroundPosition = resultBgX + 'px ' + resultBgY + 'px';
            }

            function showLens() {
                lens.style.display = 'block';
                result.style.display = 'block';
                lensActive = true;
            }

            function hideLens() {
                lens.style.display = 'none';
                result.style.display = 'none';
                lensActive = false;
            }

            function toggleLens() {
                if (lensActive) {
                    hideLens();
                    srAnnounce.textContent = 'Lens deactivated';
                } else {
                    showLens();
                    // Position lens at center
                    var rect = img.getBoundingClientRect();
                    var x = rect.width * (lensX / 100);
                    var y = rect.height * (lensY / 100);
                    updateLensPosition(x, y);
                    srAnnounce.textContent = 'Lens activated. Use arrow keys to move.';
                }
            }

            // Get image content area (excluding border/padding)
            function getImageContentRect() {
                var rect = img.getBoundingClientRect();
                var style = window.getComputedStyle(img);
                var borderTop = parseFloat(style.borderTopWidth) || 0;
                var borderLeft = parseFloat(style.borderLeftWidth) || 0;
                var borderRight = parseFloat(style.borderRightWidth) || 0;
                var borderBottom = parseFloat(style.borderBottomWidth) || 0;
                var paddingTop = parseFloat(style.paddingTop) || 0;
                var paddingLeft = parseFloat(style.paddingLeft) || 0;
                var paddingRight = parseFloat(style.paddingRight) || 0;
                var paddingBottom = parseFloat(style.paddingBottom) || 0;
                return {
                    left: rect.left + borderLeft + paddingLeft,
                    top: rect.top + borderTop + paddingTop,
                    right: rect.right - borderRight - paddingRight,
                    bottom: rect.bottom - borderBottom - paddingBottom,
                    width: rect.width - borderLeft - borderRight - paddingLeft - paddingRight,
                    height: rect.height - borderTop - borderBottom - paddingTop - paddingBottom
                };
            }

            // Check if point is within image content (not border/padding)
            function isWithinImageContent(clientX, clientY) {
                var contentRect = getImageContentRect();
                return clientX >= contentRect.left && clientX <= contentRect.right &&
                       clientY >= contentRect.top && clientY <= contentRect.bottom;
            }

            // Listen on IMG element, only activate when within actual image content
            img.addEventListener('mousemove', function(e) {
                if (!isWithinImageContent(e.clientX, e.clientY)) {
                    if (lensActive) hideLens();
                    return;
                }

                var contentRect = getImageContentRect();
                var x = e.clientX - contentRect.left;
                var y = e.clientY - contentRect.top;

                // Store position as percentage for keyboard use
                lensX = (x / contentRect.width) * 100;
                lensY = (y / contentRect.height) * 100;

                if (!lensActive) showLens();
                updateLensPosition(x, y);
            });

            img.addEventListener('mouseleave', function() {
                hideLens();
            });

            // Keyboard support
            container.addEventListener('keydown', function(e) {
                var step = 5; // 5% movement

                switch(e.key) {
                    case 'Enter':
                    case ' ':
                        e.preventDefault();
                        toggleLens();
                        break;
                    case 'Escape':
                        e.preventDefault();
                        if (lensActive) {
                            hideLens();
                            srAnnounce.textContent = 'Lens deactivated';
                        }
                        break;
                    case 'ArrowUp':
                        if (lensActive) {
                            e.preventDefault();
                            lensY = Math.max(0, lensY - step);
                            var rect = img.getBoundingClientRect();
                            updateLensPosition(rect.width * (lensX / 100), rect.height * (lensY / 100));
                        }
                        break;
                    case 'ArrowDown':
                        if (lensActive) {
                            e.preventDefault();
                            lensY = Math.min(100, lensY + step);
                            var rect = img.getBoundingClientRect();
                            updateLensPosition(rect.width * (lensX / 100), rect.height * (lensY / 100));
                        }
                        break;
                    case 'ArrowLeft':
                        if (lensActive) {
                            e.preventDefault();
                            lensX = Math.max(0, lensX - step);
                            var rect = img.getBoundingClientRect();
                            updateLensPosition(rect.width * (lensX / 100), rect.height * (lensY / 100));
                        }
                        break;
                    case 'ArrowRight':
                        if (lensActive) {
                            e.preventDefault();
                            lensX = Math.min(100, lensX + step);
                            var rect = img.getBoundingClientRect();
                            updateLensPosition(rect.width * (lensX / 100), rect.height * (lensY / 100));
                        }
                        break;
                }
            });
        });
    }

    // ==========================================================================
    // Initialize
    // ==========================================================================
    function init() {
        initDialogLightbox();
        initGallery();
        initInlineZoom();
        initLensZoom();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    window.TYPO3DocsImageZoom = {
        init: init,
        initDialogLightbox: initDialogLightbox,
        initGallery: initGallery,
        initInlineZoom: initInlineZoom,
        initLensZoom: initLensZoom
    };
})();
