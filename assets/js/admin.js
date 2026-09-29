jQuery(function ($) {
    'use strict';

    var i18n = window.SmartBannerAdmin || {};
    var $form = $('input[name="save_banner"], button[name="save_banner"]').closest('form');

    /* Live preview */
    function pad(n) {
        return String(n).padStart(2, '0');
    }

    function updatePreview() {
        var title = $('input[name="title"]').val() || i18n.previewTitle || 'Banner Title';
        var sub = $('textarea[name="content"]').val() || '';
        var btnText = $('input[name="button_text"]').val() || '';
        var bg = $('input[name="background_color"]').val() || '#f7b733';
        var txt = $('input[name="text_color"]').val() || '#1a1a1a';
        var btn = $('input[name="button_color"]').val() || '#e85d04';
        var cdOn = $('#spb-cd-toggle').is(':checked');
        var cdDate = $('#spb-cd-date').val();

        $('#spb-preview-bar').css({ background: bg, color: txt });
        $('#spb-preview-title').text(title);
        $('#spb-preview-sub').text(sub);

        if (btnText) {
            $('#spb-preview-btn').text(btnText).css('background', btn).show();
        } else {
            $('#spb-preview-btn').hide();
        }

        clearInterval(window.spbPreviewTimer);

        if (cdOn && cdDate) {
            var target = new Date(cdDate).getTime();
            var tick = function () {
                var diff = Math.max(0, target - Date.now());
                $('#spb-prev-cd-days').text(pad(Math.floor(diff / 86400000)));
                $('#spb-prev-cd-hours').text(pad(Math.floor((diff % 86400000) / 3600000)));
                $('#spb-prev-cd-minutes').text(pad(Math.floor((diff % 3600000) / 60000)));
                $('#spb-prev-cd-seconds').text(pad(Math.floor((diff % 60000) / 1000)));
            };
            $('#spb-preview-countdown').show();
            tick();
            window.spbPreviewTimer = setInterval(tick, 1000);
        } else {
            $('#spb-preview-countdown').hide();
        }
    }

    $form.on('input change', 'input, textarea, select', updatePreview);
    updatePreview();

    /* Countdown toggle shows the date field */
    $('#spb-cd-toggle').on('change', function () {
        $('#spb-cd-date-wrap').toggle(this.checked);
    });

    /* Unsaved changes guard, banner form only */
    var formDirty = false;
    $form.on('change input', 'input, textarea, select', function () {
        formDirty = true;
    });
    $form.on('submit', function () {
        formDirty = false;
    });
    $(window).on('beforeunload', function () {
        if (formDirty) {
            return i18n.unsaved || 'You have unsaved changes.';
        }
    });

    /* Delete confirmation */
    $(document).on('click', '.spb-delete', function (e) {
        if (!window.confirm(i18n.confirmDelete || 'Delete this banner permanently?')) {
            e.preventDefault();
        } else {
            formDirty = false;
        }
    });

    /* Drag to reorder */
    var tbody = document.getElementById('spb-sortable-body');
    if (!tbody) {
        return;
    }

    var dragging = null;

    function currentOrder() {
        return Array.prototype.map.call(tbody.querySelectorAll('tr[data-id]'), function (r) {
            return r.getAttribute('data-id');
        }).join(',');
    }

    var initialOrder = currentOrder();

    Array.prototype.forEach.call(tbody.querySelectorAll('tr'), function (row) {
        row.setAttribute('draggable', 'true');

        row.addEventListener('dragstart', function () {
            dragging = row;
            setTimeout(function () {
                row.classList.add('spb-dragging');
            }, 0);
        });

        row.addEventListener('dragend', function () {
            row.classList.remove('spb-dragging');
            Array.prototype.forEach.call(tbody.querySelectorAll('tr'), function (r) {
                r.classList.remove('spb-drag-over');
            });

            var order = currentOrder();
            if (order !== initialOrder) {
                formDirty = false;
                $('#spb-order-input').val(order);
                $('#spb-reorder-form').trigger('submit');
            }
        });

        row.addEventListener('dragover', function (e) {
            e.preventDefault();
            if (row === dragging) {
                return;
            }
            Array.prototype.forEach.call(tbody.querySelectorAll('tr'), function (r) {
                r.classList.remove('spb-drag-over');
            });
            row.classList.add('spb-drag-over');

            var rect = row.getBoundingClientRect();
            if (e.clientY < rect.top + rect.height / 2) {
                tbody.insertBefore(dragging, row);
            } else {
                tbody.insertBefore(dragging, row.nextSibling);
            }
        });
    });
});
