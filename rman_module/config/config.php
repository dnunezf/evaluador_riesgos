<?php

declare(strict_types=1);

/**
 * Central config for RMAN module.
 * Works both on Windows (XE) and Linux (Docker) if env vars are set.
 */
return [
    'oracle' => [
        'username' => 'APPUSER',
        'password' => 'app_pass',
        'tns'      => 'localhost/XEPDB1',
        'charset'  => 'AL32UTF8',
    ],

    'paths' => [
        'work_dir'   => __DIR__ . '/../work',
        'templates'  => __DIR__ . '/../templates',
        'bin'        => __DIR__ . '/../bin',
        'web_root'   => dirname(__DIR__),
    ],

    'exec' => [
        'use_docker'       => getenv('RMAN_USE_DOCKER') === '1',
        'docker_container' => getenv('ORACLE_DOCKER_NAME') ?: 'oracle-xe',
        'rman_bin'         => PHP_OS_FAMILY === 'Windows' ? 'rman.exe' : 'rman',
        'shell'            => PHP_OS_FAMILY === 'Windows' ? 'powershell' : 'bash',
    ],

    'safety' => [
        'require_archivelog' => true,
    ],
    'rman' => [
        'username' => 'C##RMAN',
        'password' => 'rman_pass',   // o el nuevo si cambiaste
        'tns'      => 'localhost/xe',  // servicio ROOT
    ],
];
