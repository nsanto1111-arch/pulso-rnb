<?php

declare(strict_types=1);

spl_autoload_register(function ($class) {
    $prefix = 'Plugin\\ProgramacaoPlugin\\';
    $baseDir = '/var/azuracast/www/plugins/programacao-plugin/src/';
    
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }
    
    $relativeClass = substr($class, $len);
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';
    
    if (file_exists($file)) {
        require $file;
    }
});

return [
    \Plugin\ProgramacaoPlugin\Service\ProgramacaoService::class => function ($container) {
        $em = $container->get(\Doctrine\ORM\EntityManagerInterface::class);
        $connection = $em->getConnection();
        return new \Plugin\ProgramacaoPlugin\Service\ProgramacaoService($connection);
    },
    
    \Plugin\ProgramacaoPlugin\Controller\ProgramacaoApiController::class => function ($container) {
        $service = $container->get(\Plugin\ProgramacaoPlugin\Service\ProgramacaoService::class);
        return new \Plugin\ProgramacaoPlugin\Controller\ProgramacaoApiController($service);
    },
    
    \Plugin\ProgramacaoPlugin\Controller\ProgramacaoAdminController::class => function ($container) {
        $service = $container->get(\Plugin\ProgramacaoPlugin\Service\ProgramacaoService::class);
        return new \Plugin\ProgramacaoPlugin\Controller\ProgramacaoAdminController($service);
    },
    
    // FINANCE SERVICE
    \Plugin\ProgramacaoPlugin\Service\FinanceService::class => function($container) {
        $em = $container->get(\Doctrine\ORM\EntityManagerInterface::class);
        $connection = $em->getConnection();
        return new \Plugin\ProgramacaoPlugin\Service\FinanceService($connection);
    },
    
    \Plugin\ProgramacaoPlugin\Controller\FinanceController::class => function($container) {
        $service = $container->get(\Plugin\ProgramacaoPlugin\Service\FinanceService::class);
        return new \Plugin\ProgramacaoPlugin\Controller\FinanceController($service);
    },
];
