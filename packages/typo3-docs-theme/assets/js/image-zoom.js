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
    // Native Dialog Lightbox
    // ==========================================================================
    function initDialogLightbox() {
        document.querySelectorAll('[data-zoom="lightbox"]').forEach(function(trigger) {
            var dialogId = trigger.getAttribute('data-zoom-dialog');
            var dialog = document.getElementById(dialogId);
            if (!dialog) return;

            trigger.addEventListener('dragstart', function(e) { e.preventDefault(); });

            trigger.addEventListener('click', function() {
                dialog.showModal();
            });

            trigger.addEventListener('keydown', function(e) {
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

            var closeBtn = dialog.querySelector('.lightbox-close');
            if (closeBtn) {
                closeBtn.addEventListener('click', function() {
                    dialog.close();
                });
            }
        });
    }

    // ==========================================================================
    // Gallery with Wheel Zoom
    // ==========================================================================
    function initGallery() {
        var galleries = {};

        document.querySelectorAll('[data-zoom="gallery"]').forEach(function(trigger) {
            var galleryId = trigger.getAttribute('data-gallery') || 'default';
            if (!galleries[galleryId]) {
                galleries[galleryId] = {
                    images: [],
                    currentIndex: 0,
                    zoom: 1,
                    panX: 0,
                    panY: 0,
                    dragging: false
                };
            }

            var imgSrc = trigger.getAttribute('data-zoom-src') || trigger.src;
            var caption = trigger.getAttribute('data-zoom-caption') || trigger.alt || '';

            galleries[galleryId].images.push({
                src: imgSrc,
                caption: caption,
                trigger: trigger
            });

            var index = galleries[galleryId].images.length - 1;
            trigger.setAttribute('data-gallery-index', index);

            // Make trigger keyboard accessible
            trigger.setAttribute('tabindex', '0');
            trigger.setAttribute('role', 'button');
            trigger.setAttribute('aria-label', 'Open image in gallery: ' + (caption || 'Image ' + (index + 1)));

            trigger.addEventListener('dragstart', function(e) { e.preventDefault(); });

            trigger.addEventListener('click', function(e) {
                e.preventDefault();
                openGallery(galleryId, index);
            });

            // Keyboard support for trigger
            trigger.addEventListener('keydown', function(e) {
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
            currentGallery.zoom = Math.max(CONFIG.minZoom, Math.min(CONFIG.maxZoom, newZoom));
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
            var delta = e.deltaY > 0 ? -CONFIG.zoomStep : CONFIG.zoomStep;
            var newZoom = Math.max(CONFIG.minZoom, Math.min(CONFIG.maxZoom, oldZoom + delta));

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

        // Close on click - toggle behavior (but not when zoomed/panning)
        overlay.addEventListener('click', function(e) {
            // Don't close if clicking toolbar buttons or nav
            if (e.target.closest('.gallery-toolbar') || e.target.closest('.gallery-nav')) return;
            // Close if not zoomed, or if clicking backdrop/content area
            if (!currentGallery || currentGallery.zoom <= 1) {
                closeGallery();
            }
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
            var img = container.querySelector('img') || container;
            var zoom = 1;
            var panX = 0, panY = 0;
            var dragging = false;
            var startX, startY;

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
                newZoom = Math.max(CONFIG.minZoom, Math.min(CONFIG.inlineMaxZoom, newZoom));

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

            container.addEventListener('wheel', function(e) {
                e.preventDefault();

                // Get mouse position relative to container center (transform origin)
                var containerRect = container.getBoundingClientRect();
                var mouseX = e.clientX - (containerRect.left + containerRect.width / 2);
                var mouseY = e.clientY - (containerRect.top + containerRect.height / 2);

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
        });
    }

    // ==========================================================================
    // Magnifier Lens
    // ==========================================================================
    function initLensZoom() {
        document.querySelectorAll('[data-zoom="lens"]').forEach(function(container) {
            var img = container.querySelector('img');
            if (!img) return;

            var lens = createElement('div', 'zoom-lens');
            container.appendChild(lens);

            var result = createElement('div', 'zoom-result-panel');
            container.appendChild(result);

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

                var lensXPos = x - lens.offsetWidth / 2;
                var lensYPos = y - lens.offsetHeight / 2;

                lensXPos = Math.max(0, Math.min(rect.width - lens.offsetWidth, lensXPos));
                lensYPos = Math.max(0, Math.min(rect.height - lens.offsetHeight, lensYPos));

                lens.style.left = lensXPos + 'px';
                lens.style.top = lensYPos + 'px';

                var bgX = -lensXPos * zoomFactor;
                var bgY = -lensYPos * zoomFactor;
                lens.style.backgroundImage = 'url(' + img.src + ')';
                lens.style.backgroundSize = (rect.width * zoomFactor) + 'px ' + (rect.height * zoomFactor) + 'px';
                lens.style.backgroundPosition = bgX + 'px ' + bgY + 'px';

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

            container.addEventListener('mousemove', function(e) {
                var rect = img.getBoundingClientRect();
                var x = e.clientX - rect.left;
                var y = e.clientY - rect.top;

                // Store position as percentage for keyboard use
                lensX = (x / rect.width) * 100;
                lensY = (y / rect.height) * 100;

                updateLensPosition(x, y);
            });

            container.addEventListener('mouseleave', function() {
                hideLens();
            });

            container.addEventListener('mouseenter', function() {
                showLens();
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
