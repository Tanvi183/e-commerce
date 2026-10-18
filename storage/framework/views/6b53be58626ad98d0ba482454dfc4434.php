
<?php
    $product = $product ?? null;

    // কালার অনুযায়ী গ্রুপ, প্রতিটি সাইজের আলাদা price / stock
    $variantGroups = collect();
    if ($product) {
        $variantGroups = $product->variantPrices
            ->groupBy(fn ($v) => $v->color_id ?? 'no_color')
            ->map(function ($rows, $colorKey) use ($product) {
                $colorId = $colorKey === 'no_color' ? null : $colorKey;
                $sizeIds = $rows->pluck('size_id')->filter()->values()->all();
                $values  = $rows->mapWithKeys(fn ($v) => [
                    ($v->size_id ?: 'none') => ['price' => (float) $v->price ?: '', 'stock' => (int) $v->stock],
                ]);
                $images = $product->images->filter(function ($img) use ($colorId, $sizeIds) {
                    $colorMatch = ($img->color_id == $colorId) || (empty($img->color_id) && empty($colorId));
                    $sizeMatch  = empty($sizeIds) ? empty($img->size_id) : in_array($img->size_id, $sizeIds);
                    return $colorMatch && $sizeMatch;
                })->unique('image');

                return compact('colorId', 'sizeIds', 'values', 'images');
            })
            ->values();
    }
    if ($variantGroups->isEmpty()) {
        $variantGroups = collect([['colorId' => null, 'sizeIds' => [], 'values' => collect(), 'images' => collect()]]);
    }
?>

<style>
    .product-form-page .vr-toolbar {
        display: flex; flex-wrap: wrap; align-items: center; gap: 8px 16px;
        font-size: 12px; color: #64748b; margin: -8px 0 14px;
    }
    .product-form-page .vr-toolbar .vr-stat b { color: #1e293b; }
    .product-form-page .vr-toolbar .vr-stat.is-out b { color: #dc2626; }

    .product-form-page .variant-item .vr-head {
        display: flex; align-items: center; justify-content: space-between; gap: 8px;
        margin-bottom: 12px;
    }
    .product-form-page .vr-title {
        font-weight: 700; font-size: 13px; color: #312e81;
        display: inline-flex; align-items: center; gap: 8px;
        background: #fff; border: 1px solid #e0e7ff; border-radius: 999px;
        padding: 4px 12px 4px 6px; cursor: pointer; transition: border-color .15s, box-shadow .15s;
    }
    .product-form-page .vr-title:hover,
    .product-form-page .vr-title.is-open { border-color: #6366f1; box-shadow: 0 0 0 3px rgba(99,102,241,.12); }
    .product-form-page .vr-title .vr-swatch { width: 18px; height: 18px; }
    .product-form-page .vr-title.no-color .vr-swatch {
        background: repeating-linear-gradient(45deg, #fff 0 3px, #e2e8f0 3px 6px) !important;
    }
    .product-form-page .vr-edit-icon { font-size: 12px; color: #94a3b8; }

    /* 🎨 কালার এডিটর প্যানেল */
    .product-form-page .vr-color-panel {
        background: #fff; border: 1px solid #e0e7ff; border-radius: 10px;
        padding: 12px 14px; margin-bottom: 12px; box-shadow: 0 6px 18px rgba(15,23,42,.06);
    }
    .product-form-page .vr-cp-label { font-size: 11px; font-weight: 700; color: #475569; text-transform: uppercase; letter-spacing: .03em; margin-bottom: 8px; }
    .product-form-page .vr-cp-label small { text-transform: none; color: #94a3b8; font-weight: 600; }
    .product-form-page .vr-cp-swatches { display: flex; flex-wrap: wrap; gap: 6px; }
    .product-form-page .vr-cp-swatch {
        display: inline-flex; align-items: center; gap: 6px; font-size: 12px; font-weight: 600; color: #334155;
        border: 1px solid #e2e8f0; background: #f8fafc; border-radius: 999px; padding: 3px 10px 3px 4px; cursor: pointer;
    }
    .product-form-page .vr-cp-swatch:hover { border-color: #a5b4fc; }
    .product-form-page .vr-cp-swatch.is-active { border-color: #4f46e5; background: #eef2ff; color: #312e81; }
    .product-form-page .vr-cp-swatch.is-used { opacity: .45; }
    .product-form-page .vr-cp-swatch .dot { width: 16px; height: 16px; border-radius: 50%; border: 1px solid rgba(0,0,0,.15); }
    .product-form-page .vr-cp-swatch.none .dot { background: repeating-linear-gradient(45deg, #fff 0 3px, #e2e8f0 3px 6px); }
    .product-form-page .vr-cp-form { display: flex; gap: 8px; align-items: center; }
    .product-form-page .vr-cp-hex-picker { width: 38px; height: 32px; padding: 2px; border: 1px solid #e2e8f0; border-radius: 6px; background: #fff; cursor: pointer; flex: none; }
    .product-form-page .vr-cp-hex { max-width: 100px; font-family: monospace; }
    .product-form-page .vr-cp-actions { display: flex; flex-wrap: wrap; gap: 6px; margin-top: 10px; }
    .product-form-page .vr-cp-note { font-size: 11px; color: #94a3b8; margin-top: 8px; }
    .product-form-page .vr-swatch {
        width: 14px; height: 14px; border-radius: 50%;
        border: 1px solid rgba(0,0,0,.15); background: #e2e8f0; display: inline-block;
    }
    .product-form-page .vr-total {
        font-size: 12px; font-weight: 600; padding: 3px 10px; border-radius: 999px;
        background: #dcfce7; color: #166534; white-space: nowrap;
    }
    .product-form-page .vr-total.is-out { background: #fee2e2; color: #991b1b; }
    .product-form-page .vr-remove {
        border: none; background: transparent; color: #94a3b8; font-size: 16px;
        width: 32px; height: 32px; border-radius: 8px; line-height: 1;
    }
    .product-form-page .vr-remove:hover { background: #fee2e2; color: #dc2626; }

    /* সাইজ অনুযায়ী টেবিল */
    .product-form-page .vr-table {
        background: #fff; border: 1px solid #e2e8f0; border-radius: 10px; overflow: hidden;
        margin-top: 12px;
    }
    .product-form-page .vr-table-row {
        display: grid; grid-template-columns: minmax(70px, 1fr) 1.4fr 1.2fr 90px;
        gap: 10px; align-items: center; padding: 8px 12px;
        border-top: 1px solid #f1f5f9;
    }
    .product-form-page .vr-table-row:first-child { border-top: none; }
    .product-form-page .vr-table-row.vr-th {
        background: #f8fafc; font-size: 11px; font-weight: 700; text-transform: uppercase;
        letter-spacing: .03em; color: #64748b; padding-top: 7px; padding-bottom: 7px;
    }
    .product-form-page .vr-table-row.vr-bulk { background: #f5f7ff; }
    .product-form-page .vr-size-name { font-weight: 700; color: #1e293b; font-size: 13px; }
    .product-form-page .vr-table .form-control { height: 34px; font-size: 13px; }
    .product-form-page .vr-status {
        font-size: 11px; font-weight: 700; text-align: center; border-radius: 6px; padding: 3px 0;
    }
    .product-form-page .vr-status.in { background: #dcfce7; color: #166534; }
    .product-form-page .vr-status.out { background: #fee2e2; color: #991b1b; }
    .product-form-page .vr-empty {
        padding: 14px; text-align: center; font-size: 12px; color: #94a3b8;
    }

    /* ইমেজ */
    .product-form-page .vr-image { margin-top: 12px; display: flex; flex-wrap: wrap; align-items: center; gap: 10px; }
    .product-form-page .vr-image .form-label { margin: 0; }
    .product-form-page .vr-image .variant-img-upload { flex: 1 1 220px; max-width: 320px; }

    @media (max-width: 575.98px) {
        .product-form-page .vr-table-row { grid-template-columns: 1fr 1fr; }
        .product-form-page .vr-table-row.vr-th { display: none; }
        .product-form-page .vr-size-name { grid-column: 1 / -1; }
        .product-form-page .vr-status { grid-column: 1 / -1; }
    }
</style>

<div class="card mb-4" id="variant_section">
    <div class="card-body">
        <div class="section-title d-flex justify-content-between align-items-center">
            <span><i class="fe-layers me-1"></i> Product Variants (Color & Size)</span>
            <button type="button" class="btn btn-sm btn-success add-variant rounded-pill px-3"><i class="fa fa-plus me-1"></i> Add Color Group</button>
        </div>

        <div class="vr-toolbar">
            <span class="vr-stat">মোট স্টক: <b id="vr-grand-total">0</b></span>
            <span class="vr-stat" id="vr-out-wrap">Stock Out: <b id="vr-out-count">0</b> টি</span>
            <span><i class="fa fa-info-circle"></i> প্রতিটি কালারের জন্য একটি গ্রুপ, প্রতিটি সাইজের দাম ও স্টক আলাদা। দাম খালি রাখলে মূল দাম (New Price) প্রযোজ্য হবে।</span>
        </div>

        <div id="variant-wrapper">
            <?php $__currentLoopData = $variantGroups; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $group): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <div class="variant-card variant-item" data-values='<?php echo json_encode($group['values'], 15, 512) ?>'>
                    <div class="vr-head">
                        <button type="button" class="vr-title vr-color-toggle" title="কালার পরিবর্তন / এডিট">
                            <span class="vr-swatch"></span><span class="vr-title-text">No color</span>
                            <i class="fe-edit-2 vr-edit-icon"></i>
                        </button>
                        <span class="d-flex align-items-center gap-2">
                            <span class="vr-total">Stock: 0</span>
                            <button type="button" class="vr-remove remove-variant" title="এই গ্রুপ মুছুন"><i class="fe-trash-2"></i></button>
                        </span>
                    </div>

                    
                    <div class="vr-color-panel" style="display:none;">
                        <div class="vr-cp-label">কালার বাছাই করুন <small>(Optional)</small></div>
                        <div class="vr-cp-swatches"></div>

                        <div class="vr-cp-label mt-3 vr-cp-form-title">কালার এডিট / নতুন কালার</div>
                        <div class="vr-cp-form">
                            <input type="color" class="vr-cp-hex-picker" value="#000000" title="শেড বাছাই">
                            <input type="text" class="form-control form-control-sm vr-cp-hex" maxlength="7" placeholder="#000000">
                            <input type="text" class="form-control form-control-sm vr-cp-name" maxlength="100" placeholder="কালারের নাম (যেমন Red)">
                        </div>
                        <div class="vr-cp-actions">
                            <button type="button" class="btn btn-sm btn-primary vr-cp-update"><i class="fe-save me-1"></i>এই কালার আপডেট</button>
                            <button type="button" class="btn btn-sm btn-success vr-cp-create"><i class="fe-plus me-1"></i>নতুন কালার হিসেবে সেভ</button>
                            <button type="button" class="btn btn-sm btn-light vr-cp-close ms-auto">বন্ধ</button>
                        </div>
                        <div class="vr-cp-note"><i class="fa fa-info-circle"></i> কালার আপডেট করলে যেসব প্রোডাক্টে এই কালার আছে সবখানে পরিবর্তন হবে।</div>
                    </div>

                    <div class="row g-2">
                        <div class="col-md-4">
                            <label class="form-label">Color <small class="text-muted">(Optional)</small></label>
                            <select class="form-control variant-color-select">
                                <option value="">No color</option>
                                <?php $__currentLoopData = $variantColors; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $color): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <option value="<?php echo e($color->id); ?>" data-hex="<?php echo e($color->color); ?>" <?php if($group['colorId'] == $color->id): echo 'selected'; endif; ?>>
                                        <?php echo e($color->colorName ?? $color->name); ?>

                                    </option>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </select>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label">Sizes <small class="text-muted">(একাধিক সিলেক্ট করা যাবে)</small></label>
                            <select class="form-control variant-size-select" multiple>
                                <?php $__currentLoopData = $variantSizes; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $size): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <option value="<?php echo e($size->id); ?>" <?php if(in_array($size->id, $group['sizeIds'])): echo 'selected'; endif; ?>>
                                        <?php echo e($size->sizeName ?? $size->name); ?>

                                    </option>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </select>
                        </div>
                    </div>

                    <div class="vr-table"></div>

                    <div class="vr-image">
                        <label class="form-label"><i class="fe-image me-1"></i>Variant Image</label>
                        <?php if($group['images']->isNotEmpty()): ?>
                            <div class="variant-existing-imgs d-flex flex-wrap gap-1">
                                <?php $__currentLoopData = $group['images']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $vImg): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <div class="position-relative">
                                        <img src="<?php echo e(asset($vImg->image)); ?>" class="rounded border" style="width:44px;height:44px;object-fit:cover;" alt="">
                                        <a href="<?php echo e(route('products.image.destroy', ['id' => $vImg->id])); ?>" class="btn btn-xs btn-danger position-absolute rounded-circle" style="padding:0 4px;top:-6px;right:-6px;" onclick="return confirm('Delete this image?')"><i class="mdi mdi-close"></i></a>
                                    </div>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </div>
                        <?php endif; ?>
                        <div class="variant-img-upload">
                            <input type="file" class="form-control form-control-sm variant-img-input" accept="image/*">
                            <div class="variant-img-preview mt-1" style="display:none;">
                                <img src="" alt="Preview" class="rounded border" style="max-width:60px;max-height:60px;object-fit:cover;">
                                <button type="button" class="btn btn-sm btn-danger variant-img-clear ms-1" title="Remove"><i class="fe-x"></i></button>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>
    </div>
</div>
<?php /**PATH C:\laragon\www\oversizebazar\resources\views/backEnd/product/partials/variant_section.blade.php ENDPATH**/ ?>