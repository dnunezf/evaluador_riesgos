<?php

declare(strict_types=1);

/**
 * Central config for RMAN module.
 * Adjust env vars per host. Works on Linux (Docker) and Windows XE.
 */
return [
    // Oracle connect (OCI8). Use a dedicated low-priv user for metadata tables above.
    'oracle' => [
        'username' => getenv('APP_DB_USER') ?: 'APPUSER',
        'password' => getenv('APP_DB_PASS') ?: 'app_pass',
        // Examples: 'XE' or 'localhost/XEPDB1'
        'tns'      => getenv('APP_DB_TNS') ?: 'XE',
        'charset'  => 'AL32UTF8',
    ],

    // Where scripts and logs will be written
    'paths' => [
        'work_dir'   => __DIR__ . '/../work',              // created on demand
        'templates'  => __DIR__ . '/../templates',
        'bin'        => __DIR__ . '/../bin',
        'web_root'   => dirname(__DIR__),                  // for relative links
    ],

    // Execution environment
    'exec' => [
        'use_docker'      => getenv('RMAN_USE_DOCKER') === '1',   // Linux Mint host with Oracle in container
        'docker_container' => getenv('ORACLE_DOCKER_NAME') ?: 'oracle-xe',
        'rman_bin'        => PHP_OS_FAMILY === 'Windows' ? 'rman.exe' : 'rman',
        'shell'           => PHP_OS_FAMILY === 'Windows' ? 'powershell' : 'bash',
    ],

    // Safety checks
    'safety' => [
        'require_archivelog' => true,   // warn if DB not in ARCHIVELOG for hot backups
    ],
];
