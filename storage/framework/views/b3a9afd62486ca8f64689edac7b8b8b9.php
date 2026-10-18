
<?php $sizeStocks = $product->sizeStocks(); ?>
<?php if($sizeStocks->isNotEmpty()): ?>
    <div class="d-flex flex-wrap gap-1 mt-1">
        <?php $__currentLoopData = $sizeStocks; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $sizeName => $qty): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <span class="badge <?php echo e($qty > 0 ? 'badge-soft-success' : 'badge-soft-danger'); ?>" style="font-size:10px;"
                  title="<?php echo e($qty > 0 ? $qty . ' in stock' : 'Stock Out'); ?>">
                <?php echo e($sizeName); ?>: <?php echo e($qty > 0 ? $qty : 'Out'); ?>

            </span>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </div>
<?php endif; ?>
<?php /**PATH C:\laragon\www\oversizebazar\resources\views/backEnd/product/partials/size_stock.blade.php ENDPATH**/ ?>