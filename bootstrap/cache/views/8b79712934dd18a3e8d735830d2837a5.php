<section class="mx-auto max-w-3xl">
    <div class="rounded-[2rem] border p-6 sm:p-8 <?php echo e($panelClass); ?>">
        <div class="flex flex-wrap items-start gap-4">
            <div class="flex h-14 w-14 items-center justify-center rounded-2xl border <?php echo e($iconShellClass); ?>">
                <?php echo $iconSvg; ?>

            </div>
            <div class="min-w-0 flex-1">
                <p class="text-xs font-semibold uppercase tracking-[0.24em] <?php echo e($eyebrowClass); ?>"><?php echo e($codeLabel); ?></p>
                <h1 class="mt-3 text-3xl font-black tracking-tight text-white"><?php echo e($title); ?></h1>
                <p class="mt-4 text-base leading-7 text-slate-200"><?php echo e($message); ?></p>
                <p class="mt-3 text-sm leading-6 text-slate-400"><?php echo e($hint); ?></p>
            </div>
        </div>

        <div class="mt-6 rounded-[1.5rem] border border-white/8 bg-white/5 p-4">
            <p class="text-sm font-semibold text-white">Yang bisa Anda lakukan sekarang</p>
            <div class="mt-3 space-y-2 text-sm leading-6 text-slate-300">
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $steps; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $step): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <p><?php echo e($step); ?></p>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            </div>
        </div>

        <div class="mt-6 flex flex-wrap gap-3">
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $actions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $action): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(($action['type'] ?? 'link') === 'button'): ?>
                    <button type="button" <?php if(!empty($action['onclick'])): ?> onclick="<?php echo e($action['onclick']); ?>" <?php endif; ?> class="<?php echo e($action['class']); ?>">
                        <?php echo e($action['label']); ?>

                    </button>
                <?php else: ?>
                    <a href="<?php echo e($action['href']); ?>" class="<?php echo e($action['class']); ?>">
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(!empty($action['prefix'])): ?>
                            <span><?php echo e($action['prefix']); ?></span>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        <?php echo e($action['label']); ?>

                    </a>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
        </div>
    </div>
</section>
<?php /**PATH /www/wwwroot/mtq.kemenagtanahdatar.id/resources/views/errors/partials/error-page.blade.php ENDPATH**/ ?>