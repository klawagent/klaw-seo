/**
 * Klaw SEO — FAQ Repeater
 *
 * Vanilla JS (no jQuery). Handles:
 * - Adding new FAQ rows
 * - Removing FAQ rows
 * - Reindexing name attributes
 */

(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {
        var container = document.getElementById('klaw-seo-faq-items');
        var addBtn = document.getElementById('klaw-seo-faq-add');

        if (!container || !addBtn) return;

        /**
         * Get the next available index.
         */
        function getNextIndex() {
            var items = container.querySelectorAll('.klaw-seo-faq-item');
            var max = -1;
            items.forEach(function (item) {
                var idx = parseInt(item.getAttribute('data-index'), 10);
                if (idx > max) max = idx;
            });
            return max + 1;
        }

        /**
         * Add a new FAQ row.
         */
        addBtn.addEventListener('click', function () {
            var index = getNextIndex();

            var item = document.createElement('div');
            item.className = 'klaw-seo-faq-item';
            item.setAttribute('data-index', index);

            item.innerHTML =
                '<input type="text" name="klaw_seo_faq[' + index + '][question]" placeholder="Question" />' +
                '<textarea name="klaw_seo_faq[' + index + '][answer]" rows="2" placeholder="Answer"></textarea>' +
                '<button type="button" class="button klaw-seo-faq-remove">Remove</button>';

            container.appendChild(item);

            // Focus the new question field.
            var input = item.querySelector('input');
            if (input) input.focus();
        });

        /**
         * Remove a FAQ row via event delegation.
         */
        container.addEventListener('click', function (e) {
            if (!e.target.classList.contains('klaw-seo-faq-remove')) return;

            var item = e.target.closest('.klaw-seo-faq-item');
            if (item) {
                item.remove();
                reindex();
            }
        });

        /**
         * Reindex all FAQ items to maintain sequential name attributes.
         */
        function reindex() {
            var items = container.querySelectorAll('.klaw-seo-faq-item');
            items.forEach(function (item, i) {
                item.setAttribute('data-index', i);

                var questionInput = item.querySelector('input[type="text"]');
                var answerTextarea = item.querySelector('textarea');

                if (questionInput) {
                    questionInput.name = 'klaw_seo_faq[' + i + '][question]';
                }
                if (answerTextarea) {
                    answerTextarea.name = 'klaw_seo_faq[' + i + '][answer]';
                }
            });
        }
    });

})();
