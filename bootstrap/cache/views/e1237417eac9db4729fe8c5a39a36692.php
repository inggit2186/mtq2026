<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">
    <title><?php echo e(config('app.name', 'e-MTQ')); ?> <?php echo $__env->yieldContent('title', ''); ?></title>
    <style>[x-cloak]{display:none!important;}</style>
    <?php echo app('Illuminate\Foundation\Vite')(['resources/css/app.css', 'resources/js/app.js']); ?>
    <?php echo \Livewire\Mechanisms\FrontendAssets\FrontendAssets::styles(); ?>

</head>
<body class="grid-bg min-h-screen overflow-x-hidden">
    <?php
        $sweetAlertPayload = [
            'status' => session('status'),
            'errors' => isset($errors) && $errors->any() ? $errors->all() : [],
        ];
    ?>
    <script type="application/json" id="mtq-swal-payload"><?php echo json_encode($sweetAlertPayload, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); ?></script>
    <main class="relative mx-auto flex min-h-screen max-w-7xl items-center px-4 py-8 sm:px-6 lg:px-8">
        <?php echo $__env->yieldContent('content'); ?>
    </main>

    <?php echo \Livewire\Mechanisms\FrontendAssets\FrontendAssets::scripts(); ?>

</body>
</html>
<?php /**PATH /www/wwwroot/mtq.kemenagtanahdatar.id/resources/views/layouts/guest.blade.php ENDPATH**/ ?>