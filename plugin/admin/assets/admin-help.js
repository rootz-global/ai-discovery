/**
 * Rootz AI Discovery — Admin Help Tooltips
 *
 * Click-to-toggle info popups. No dependencies.
 *
 * @package Rootz_AI_Discovery
 */
(function () {
    'use strict';

    function closeAll() {
        document.querySelectorAll('.rootz-help-visible').forEach(function (el) {
            el.classList.remove('rootz-help-visible');
            var btn = el.previousElementSibling;
            if (btn) btn.setAttribute('aria-expanded', 'false');
        });
    }

    document.addEventListener('click', function (e) {
        var btn = e.target.closest('.rootz-help-toggle');
        if (btn) {
            e.preventDefault();
            var tip = btn.nextElementSibling;
            var isOpen = tip && tip.classList.contains('rootz-help-visible');
            closeAll();
            if (!isOpen && tip) {
                tip.classList.add('rootz-help-visible');
                btn.setAttribute('aria-expanded', 'true');
            }
            return;
        }
        // Click outside any tooltip — close all.
        if (!e.target.closest('.rootz-help-tip')) {
            closeAll();
        }
    });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            closeAll();
        }
    });
})();
