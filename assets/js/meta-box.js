/**
 * Klaw SEO — Meta Box JavaScript
 *
 * Vanilla JS (no jQuery). Handles:
 * - Tab switching
 * - Live character counters with red at limit
 * - Google search preview live update
 * - OG image picker via wp.media
 */

(function () {
    'use strict';

    /**
     * Initialize when DOM is ready.
     */
    document.addEventListener('DOMContentLoaded', function () {
        initTabs();
        initCounters();
        initPreview();
        initImagePicker();
    });

    /**
     * Tab switching.
     */
    function initTabs() {
        var tabs = document.querySelectorAll('.klaw-seo-tab');
        var contents = document.querySelectorAll('.klaw-seo-tab-content');

        tabs.forEach(function (tab) {
            tab.addEventListener('click', function () {
                var target = this.getAttribute('data-tab');

                // Deactivate all.
                tabs.forEach(function (t) { t.classList.remove('active'); });
                contents.forEach(function (c) { c.classList.remove('active'); });

                // Activate target.
                this.classList.add('active');
                var content = document.querySelector('.klaw-seo-tab-content[data-tab="' + target + '"]');
                if (content) {
                    content.classList.add('active');
                }
            });
        });
    }

    /**
     * Live character counters.
     */
    function initCounters() {
        var counters = document.querySelectorAll('.klaw-seo-counter');

        counters.forEach(function (counter) {
            var targetId = counter.getAttribute('data-target');
            var limit = parseInt(counter.getAttribute('data-limit'), 10) || 0;
            var numEl = counter.querySelector('.klaw-seo-counter-num');
            var input = document.getElementById(targetId);

            if (!input || !numEl) return;

            function update() {
                var value = input.value || input.getAttribute('data-default') || '';
                // Use the actual input value if user has typed, otherwise the placeholder/default
                if (input.value) {
                    value = input.value;
                } else {
                    value = input.getAttribute('data-default') || input.placeholder || '';
                }
                var len = value.length;
                numEl.textContent = len;

                if (limit > 0 && len > limit) {
                    counter.classList.add('over-limit');
                } else {
                    counter.classList.remove('over-limit');
                }
            }

            input.addEventListener('input', update);
            input.addEventListener('change', update);

            // Initial count.
            update();
        });
    }

    /**
     * Google search preview live update.
     */
    function initPreview() {
        var titleInput = document.getElementById('klaw-seo-title');
        var descInput = document.getElementById('klaw-seo-description');
        var preview = document.getElementById('klaw-seo-preview');

        if (!preview || !titleInput || !descInput) return;

        var previewTitle = preview.querySelector('.klaw-seo-preview-title');
        var previewDesc = preview.querySelector('.klaw-seo-preview-desc');

        var data = window.klawSeoData || {};
        var siteTitle = data.siteTitle || '';
        var separator = data.separator || '|';

        function getTitle() {
            if (titleInput.value) {
                return titleInput.value;
            }
            // Use the default (placeholder) value.
            return titleInput.getAttribute('data-default') || titleInput.placeholder || '';
        }

        function getDesc() {
            if (descInput.value) {
                return descInput.value;
            }
            return descInput.getAttribute('data-default') || descInput.placeholder || '';
        }

        function updatePreview() {
            if (previewTitle) {
                previewTitle.textContent = getTitle();
            }
            if (previewDesc) {
                previewDesc.textContent = getDesc();
            }
        }

        titleInput.addEventListener('input', updatePreview);
        descInput.addEventListener('input', updatePreview);

        // Initial.
        updatePreview();
    }

    /**
     * OG image picker via wp.media.
     */
    function initImagePicker() {
        // Handle all image pickers (meta box + settings).
        var pickButtons = document.querySelectorAll('.klaw-seo-pick-image');
        var removeButtons = document.querySelectorAll('.klaw-seo-remove-image');

        pickButtons.forEach(function (btn) {
            btn.addEventListener('click', function () {
                var picker = this.closest('.klaw-seo-image-picker');
                if (!picker) return;

                var input = picker.querySelector('input[type="hidden"]');
                if (!input && !picker.querySelector('#klaw-seo-og-image')) return;
                if (!input) input = picker.querySelector('#klaw-seo-og-image');

                var frame = wp.media({
                    title: 'Select Image',
                    multiple: false,
                    library: { type: 'image' }
                });

                frame.on('select', function () {
                    var attachment = frame.state().get('selection').first().toJSON();
                    input.value = attachment.url;

                    // Update or create preview image.
                    var img = picker.querySelector('.klaw-seo-og-preview-img');
                    if (!img) {
                        img = document.createElement('img');
                        img.className = 'klaw-seo-og-preview-img';
                        picker.insertBefore(img, picker.firstChild);
                    }
                    img.src = attachment.url;

                    // Show remove button.
                    var removeBtn = picker.querySelector('.klaw-seo-remove-image');
                    if (removeBtn) removeBtn.style.display = '';
                });

                frame.open();
            });
        });

        removeButtons.forEach(function (btn) {
            btn.addEventListener('click', function () {
                var picker = this.closest('.klaw-seo-image-picker');
                if (!picker) return;

                var input = picker.querySelector('input[type="hidden"]');
                if (!input) input = picker.querySelector('#klaw-seo-og-image');
                if (input) input.value = '';

                var img = picker.querySelector('.klaw-seo-og-preview-img');
                if (img) img.remove();

                this.style.display = 'none';
            });
        });
    }

})();
