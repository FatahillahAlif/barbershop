<?php

declare(strict_types=1);

use Slim\App;

return function (App $app) {
    //Tabel Barber
    $customerRoutes = require __DIR__.'/API/barber.php';
    $customerRoutes($app);

    //Tabel Costumer
    $productRoutes = require __DIR__.'/API/costumer.php';
    $productRoutes($app);

    //Tabel Detail Pembayaran
    $productRoutes = require __DIR__.'/API/detail_pembayaran.php';
    $productRoutes($app);
    
    //Tabel Service Menu
    $productRoutes = require __DIR__.'/API/service_menu.php';
    $productRoutes($app);

    //Tabel Service
    $productRoutes = require __DIR__.'/API/service.php';
    $productRoutes($app);
};