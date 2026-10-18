
<script>
$(function () {
    const $wrapper = $('#variant-wrapper');
    if (!$wrapper.length) return;
    const $form = $wrapper.closest('form');

    // নতুন গ্রুপের জন্য খালি টেমপ্লেট (select2 init এর আগে)
    const $template = $wrapper.find('.variant-item').first().clone();
    $template.attr('data-values', '{}').removeData('values');
    $template.find('option').prop('selected', false);
    $template.find('.variant-existing-imgs').remove();

    function esc(s) {
        return $('<div>').text(s).html();
    }

    function initRow($row) {
        let vals = $row.data('values');
        if (!vals || Array.isArray(vals)) vals = {};
        $row.data('vals', vals);

        const $color = $row.find('.variant-color-select');
        $color.data('prev', $color.val() || '');
        $color.select2({ width: '100%', allowClear: true, placeholder: 'No color', dropdownParent: $wrapper });
        $row.find('.variant-size-select').select2({ width: '100%', placeholder: 'সাইজ সিলেক্ট করুন', dropdownParent: $wrapper });
        renderTable($row);
    }

    // সিলেক্ট করা সাইজ অনুযায়ী price / stock টেবিল
    function renderTable($row) {
        const vals = $row.data('vals');
        const sizes = $row.find('.variant-size-select option:selected').map(function () {
            return { id: String(this.value), name: $(this).text().trim() };
        }).get();
        const keys = sizes.length ? sizes : [{ id: 'none', name: 'সব (সাইজ নেই)' }];

        let html = '<div class="vr-table-row vr-th"><span>Size</span><span>Price (৳)</span><span>Stock</span><span class="text-center">Status</span></div>';

        if (keys.length > 1) {
            html += '<div class="vr-table-row vr-bulk">'
                + '<span class="small fw-semibold text-muted">সবগুলোতে</span>'
                + '<input type="number" step="0.01" min="0" class="form-control vr-bulk-price" placeholder="সব সাইজের দাম">'
                + '<input type="number" min="0" class="form-control vr-bulk-stock" placeholder="সব সাইজের স্টক">'
                + '<button type="button" class="btn btn-sm btn-outline-primary vr-bulk-apply">Apply</button>'
                + '</div>';
        }

        keys.forEach(function (s) {
            const v = vals[s.id] || {};
            html += '<div class="vr-table-row" data-size="' + s.id + '">'
                + '<span class="vr-size-name">' + esc(s.name) + '</span>'
                + '<input type="number" step="0.01" min="0" class="form-control vr-price" placeholder="মূল দাম" value="' + (v.price ?? '') + '">'
                + '<input type="number" min="0" class="form-control vr-stock" placeholder="0" value="' + (v.stock ?? '') + '">'
                + '<span class="vr-status"></span>'
                + '</div>';
        });

        $row.find('.vr-table').html(html);
        refreshRow($row);
    }

    function refreshRow($row) {
        let total = 0;
        $row.find('.vr-table-row[data-size]').each(function () {
            const stock = parseInt($(this).find('.vr-stock').val(), 10) || 0;
            total += stock;
            $(this).find('.vr-status')
                .text(stock > 0 ? 'In stock' : 'Stock Out')
                .toggleClass('in', stock > 0).toggleClass('out', stock <= 0);
        });
        $row.find('.vr-total').text('Stock: ' + total).toggleClass('is-out', total <= 0);

        const $color = $row.find('.variant-color-select option:selected');
        $row.find('.vr-title-text').text($color.val() ? $color.text().trim() : 'No color');
        $row.find('.vr-swatch').css('background', $color.data('hex') || '#e2e8f0');
        $row.find('.vr-title').toggleClass('no-color', !$color.val());
        if ($row.find('.vr-color-panel').is(':visible')) renderColorPanel($row, false);

        refreshSummary();
    }

    function refreshSummary() {
        let total = 0, out = 0;
        $wrapper.find('.vr-table-row[data-size]').each(function () {
            const stock = parseInt($(this).find('.vr-stock').val(), 10) || 0;
            total += stock;
            if (stock <= 0) out++;
        });
        $('#vr-grand-total').text(total);
        $('#vr-out-count').text(out);
        $('#vr-out-wrap').toggleClass('is-out', out > 0);

        const $items = $wrapper.find('.variant-item');
        $items.find('.remove-variant').toggle($items.length > 1);
    }

    $wrapper.find('.variant-item').each(function () { initRow($(this)); });

    // টাইপ করা মান রেখে দেওয়া (সাইজ বদলালেও হারাবে না)
    $wrapper.on('input', '.vr-price, .vr-stock', function () {
        const $row = $(this).closest('.variant-item');
        const size = $(this).closest('.vr-table-row').data('size');
        const vals = $row.data('vals');
        vals[size] = vals[size] || {};
        vals[size][$(this).hasClass('vr-price') ? 'price' : 'stock'] = $(this).val();
        refreshRow($row);
    });

    $wrapper.on('click', '.vr-bulk-apply', function () {
        const $row = $(this).closest('.variant-item');
        const price = $row.find('.vr-bulk-price').val();
        const stock = $row.find('.vr-bulk-stock').val();
        if (price !== '') $row.find('.vr-price').val(price).trigger('input');
        if (stock !== '') $row.find('.vr-stock').val(stock).trigger('input');
    });

    $wrapper.on('change', '.variant-size-select', function () {
        renderTable($(this).closest('.variant-item'));
    });
    $wrapper.on('change', '.variant-color-select', function () {
        const $select = $(this);
        const val = $select.val() || '';

        // একই কালার দুই গ্রুপে থাকলে ভ্যারিয়েন্ট ডুপ্লিকেট হবে
        if (val && usedColorIds($select.closest('.variant-item')).includes(val)) {
            notify('warning', 'এই কালারের গ্রুপ আগে থেকেই আছে। সেই গ্রুপেই সাইজ যোগ করুন।');
            $select.val($select.data('prev') || null).trigger('change.select2');
            return;
        }
        $select.data('prev', val);
        refreshRow($select.closest('.variant-item'));
    });

    // ================= 🎨 কালার এডিটর =================
    const colorSaveUrl = <?php echo json_encode(route('colors.quick_save'), 15, 512) ?>;
    const csrfToken = <?php echo json_encode(csrf_token(), 15, 512) ?>;

    function notify(type, msg) {
        if (typeof toastr !== 'undefined') toastr[type](msg);
        else alert(msg);
    }

    function usedColorIds($exceptRow) {
        return $wrapper.find('.variant-item').not($exceptRow).map(function () {
            return $(this).find('.variant-color-select').val() || null;
        }).get();
    }

    function renderColorPanel($row, resetForm) {
        const $panel = $row.find('.vr-color-panel');
        const $select = $row.find('.variant-color-select');
        const current = $select.val() || '';
        const used = usedColorIds($row);

        let html = '<button type="button" class="vr-cp-swatch none' + (current ? '' : ' is-active') + '" data-id="">'
            + '<span class="dot"></span>No color</button>';
        $select.find('option').each(function () {
            if (!this.value) return;
            const cls = (this.value === current ? ' is-active' : '') + (used.includes(this.value) ? ' is-used' : '');
            html += '<button type="button" class="vr-cp-swatch' + cls + '" data-id="' + this.value + '"'
                + (used.includes(this.value) ? ' title="অন্য গ্রুপে ব্যবহৃত"' : '') + '>'
                + '<span class="dot" style="background:' + esc($(this).data('hex') || '#e2e8f0') + '"></span>'
                + esc($(this).text().trim()) + '</button>';
        });
        $panel.find('.vr-cp-swatches').html(html);

        $panel.find('.vr-cp-update').prop('disabled', !current);
        $panel.find('.vr-cp-form-title').text(current ? 'কালার এডিট / নতুন কালার' : 'নতুন কালার তৈরি');

        if (resetForm) {
            const $opt = $select.find('option:selected');
            const hex = (current && $opt.data('hex')) || '#000000';
            $panel.find('.vr-cp-name').val(current ? $opt.text().trim() : '');
            $panel.find('.vr-cp-hex, .vr-cp-hex-picker').val(hex);
        }
    }

    $wrapper.on('click', '.vr-color-toggle', function () {
        const $row = $(this).closest('.variant-item');
        const $panel = $row.find('.vr-color-panel');
        const open = !$panel.is(':visible');
        if (open) renderColorPanel($row, true);
        $panel.stop(true, true).slideToggle(150);
        $(this).toggleClass('is-open', open);
    });

    $wrapper.on('click', '.vr-cp-close', function () {
        const $row = $(this).closest('.variant-item');
        $row.find('.vr-color-panel').slideUp(150);
        $row.find('.vr-color-toggle').removeClass('is-open');
    });

    $wrapper.on('click', '.vr-cp-swatch', function () {
        const $row = $(this).closest('.variant-item');
        $row.find('.variant-color-select').val($(this).data('id') ? String($(this).data('id')) : null).trigger('change');
        renderColorPanel($row, true);
    });

    // শেড পিকার ↔ HEX টেক্সট
    $wrapper.on('input', '.vr-cp-hex-picker', function () {
        $(this).siblings('.vr-cp-hex').val($(this).val());
    });
    $wrapper.on('input', '.vr-cp-hex', function () {
        let v = $(this).val().trim();
        if (v && v[0] !== '#') v = '#' + v;
        if (/^#[0-9a-fA-F]{6}$/.test(v)) $(this).siblings('.vr-cp-hex-picker').val(v);
    });

    // সব গ্রুপ + নতুন গ্রুপের টেমপ্লেটে কালার অপশন আপডেট
    function upsertColorOption(color) {
        const $selects = $wrapper.find('.variant-color-select').add($template.find('.variant-color-select'));
        $selects.each(function () {
            let $opt = $(this).find('option[value="' + color.id + '"]');
            if (!$opt.length) {
                $opt = $('<option>').val(color.id).appendTo(this);
            }
            $opt.text(color.name).attr('data-hex', color.hex).data('hex', color.hex);
        });
        $wrapper.find('.variant-color-select').trigger('change.select2');
    }

    function saveColor($row, isUpdate) {
        const $panel = $row.find('.vr-color-panel');
        const $select = $row.find('.variant-color-select');
        let hex = $panel.find('.vr-cp-hex').val().trim();
        if (hex && hex[0] !== '#') hex = '#' + hex;
        const name = $panel.find('.vr-cp-name').val().trim();

        if (!name) return notify('warning', 'কালারের নাম দিন');
        if (!/^#[0-9a-fA-F]{6}$/.test(hex)) return notify('warning', 'সঠিক HEX কোড দিন (যেমন #FF0000)');

        const $btns = $panel.find('.vr-cp-update, .vr-cp-create').prop('disabled', true);

        $.ajax({
            url: colorSaveUrl,
            type: 'POST',
            dataType: 'json',
            data: { _token: csrfToken, id: isUpdate ? $select.val() : '', colorName: name, color: hex },
        }).done(function (res) {
            if (!res || !res.success) return notify('error', (res && res.message) || 'সেভ করা যায়নি');
            upsertColorOption(res.color);
            if (!isUpdate) {
                $select.val(String(res.color.id)).trigger('change');
            }
            $wrapper.find('.variant-item').each(function () { refreshRow($(this)); });
            renderColorPanel($row, true);
            notify('success', isUpdate ? 'কালার আপডেট হয়েছে' : 'নতুন কালার যোগ হয়েছে');
        }).fail(function (xhr) {
            const r = xhr.responseJSON || {};
            const first = r.errors ? Object.values(r.errors)[0][0] : null;
            notify('error', first || r.message || 'সেভ করা যায়নি');
        }).always(function () {
            $btns.prop('disabled', false);
            $panel.find('.vr-cp-update').prop('disabled', !$select.val());
        });
    }

    $wrapper.on('click', '.vr-cp-update', function () { saveColor($(this).closest('.variant-item'), true); });
    $wrapper.on('click', '.vr-cp-create', function () { saveColor($(this).closest('.variant-item'), false); });

    $(document).on('click', '.add-variant', function () {
        const $row = $template.clone();
        $wrapper.append($row);
        initRow($row);
        $row[0].scrollIntoView({ behavior: 'smooth', block: 'center' });
    });

    $wrapper.on('click', '.remove-variant', function () {
        $(this).closest('.variant-item').remove();
        refreshSummary();
    });

    // Variant Image Preview & Clear
    $wrapper.on('change', '.variant-img-input', function () {
        const $preview = $(this).siblings('.variant-img-preview');
        const $img = $preview.find('img');
        const file = this.files[0];
        if (file && file.type.startsWith('image/')) {
            const reader = new FileReader();
            reader.onload = function (e) { $img.attr('src', e.target.result); $preview.show(); };
            reader.readAsDataURL(file);
        } else { $preview.hide(); $img.attr('src', ''); }
    });
    $wrapper.on('click', '.variant-img-clear', function () {
        const $preview = $(this).closest('.variant-img-preview');
        $preview.siblings('.variant-img-input').val('');
        $preview.find('img').attr('src', '');
        $preview.hide();
    });

    // সাবমিটে প্রতিটি সাইজের জন্য আলাদা variant_price[i][...] hidden input
    $form.on('submit', function () {
        $form.find('.vr-hidden').remove();
        let index = 0, rowIndex = 0;

        function add(name, value) {
            $('<input type="hidden" class="vr-hidden">').attr('name', name).val(value ?? '').appendTo($form);
        }

        $wrapper.find('.variant-item').each(function () {
            const $row = $(this);
            const colorId = $row.find('.variant-color-select').val() || '';
            const $sizeRows = $row.find('.vr-table-row[data-size]');
            const hasSizes = $sizeRows.filter('[data-size!="none"]').length > 0;
            const $file = $row.find('.variant-img-input');

            if (!colorId && !hasSizes) {
                $file.removeAttr('name');
                return;
            }

            $sizeRows.each(function () {
                const size = String($(this).data('size'));
                add('variant_price[' + index + '][color_id]', colorId);
                add('variant_price[' + index + '][size_id]', size === 'none' ? '' : size);
                add('variant_price[' + index + '][price]', $(this).find('.vr-price').val() || 0);
                add('variant_price[' + index + '][stock]', $(this).find('.vr-stock').val() || 0);
                add('variant_price[' + index + '][image_row]', rowIndex);
                index++;
            });

            $file.attr('name', 'variant_image[' + rowIndex + '][image]');
            rowIndex++;
        });
    });
});
</script>
<?php /**PATH C:\laragon\www\oversizebazar\resources\views/backEnd/product/partials/variant_section_script.blade.php ENDPATH**/ ?>