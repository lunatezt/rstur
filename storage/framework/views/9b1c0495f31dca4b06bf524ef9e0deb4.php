<!DOCTYPE html>
<html lang="<?php echo e(str_replace('_', '-', app()->getLocale())); ?>">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">
        <title>Restaurant POS</title>
        <link rel="icon" href="/favicon.ico">
        <?php echo app('Illuminate\Foundation\Vite')(['resources/css/app.css', 'resources/js/app.js']); ?>
    </head>
    <body class="antialiased">
        <div id="app"></div>
    </body>
</html>
<?php /**PATH C:\Users\Qasim Hassanzada\Desktop\resturant\restaurant-pos-system\resources\views/welcome.blade.php ENDPATH**/ ?>