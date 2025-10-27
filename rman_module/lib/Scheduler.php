<?php

declare(strict_types=1);

final class Scheduler
{
    public function __construct(private array $cfg) {}

    /** Construye el comando RMAN local (usado por “Run now”). */
    public function buildCommand(string $rmanScriptPath, string $logPath): string
    {
        $rman = $this->cfg['exec']['rman_bin'];
        $cmd  = PHP_OS_FAMILY === 'Windows'
            ? "$rman cmdfile=\"$rmanScriptPath\" log=\"$logPath\""
            : "$rman cmdfile='$rmanScriptPath' log='$logPath'";

        if (($this->cfg['exec']['use_docker'] ?? false) && PHP_OS_FAMILY !== 'Windows') {
            $container = $this->cfg['exec']['docker_container'];
            $cmd = "docker exec $container bash -lc \"$cmd\"";
        }
        return $cmd;
    }

    /**
     * Comando que ejecutará el Oracle Scheduler:
     * - Si use_http_runner=true: invoca la URL de strategy_run.php?id=<ID> (cataloga en la app)
     * - Si no: usa el comando RMAN directo (fallback).
     */
    public function buildTriggerCommand(int $strategyId, string $fallbackCmd): string
    {
        $useHttp = $this->cfg['scheduler']['use_http_runner'] ?? true;
        if (!$useHttp) {
            return $fallbackCmd;
        }

        $base = rtrim((string)($this->cfg['scheduler']['runner_url'] ?? ''), '?&=');
        if ($base === '') {
            return $fallbackCmd;
        }
        $url = $base . $strategyId . '&by=sch';

        if (PHP_OS_FAMILY === 'Windows') {
            // Opción A (corregida): escapar comillas simples dentro de la cadena PHP
            return 'powershell -NonInteractive -ExecutionPolicy Bypass -Command "try { Invoke-WebRequest -UseBasicParsing -Uri \'' . $url . '\' | Out-Null } catch { exit 1 }"';

            // --- Opción B (equivalente con HEREDOC, por si la prefieres) ---
            // $ps = <<<PS
            // powershell -NonInteractive -ExecutionPolicy Bypass -Command "try { Invoke-WebRequest -UseBasicParsing -Uri '$url' | Out-Null } catch { exit 1 }"
            // PS;
            // return trim($ps);
        }

        // Linux/Unix
        return "curl -fsS '$url'";
    }

    /** Crea/rehace un JOB EXECUTABLE en DBMS_SCHEDULER con repeat_interval dado. */
    public function createOracleScheduler(OracleClient $db, string $jobName, string $shellCommand, string $repeatInterval): void
    {
        // Ejecutable + argumentos por plataforma
        if (PHP_OS_FAMILY === 'Windows') {
            $execPath = 'powershell';
            $args = ['-NonInteractive', '-ExecutionPolicy', 'Bypass', '-Command', $shellCommand];
        } else {
            $execPath = '/bin/bash';
            $args = ['-lc', $shellCommand];
        }
        $numArgs = count($args);

        $plsql = "
DECLARE
  v_job   VARCHAR2(128) := :jn;
  v_act   VARCHAR2(4000) := :act;
  v_ri    VARCHAR2(4000) := :ri;
BEGIN
  -- Eliminar job previo si existe
  BEGIN
    DBMS_SCHEDULER.DROP_JOB(v_job, TRUE);
  EXCEPTION WHEN OTHERS THEN
    IF SQLCODE != -27475 THEN NULL; END IF;
  END;

  DBMS_SCHEDULER.CREATE_JOB(
      job_name            => v_job,
      job_type            => 'EXECUTABLE',
      job_action          => v_act,
      number_of_arguments => :nargs,
      start_date          => SYSTIMESTAMP,
      repeat_interval     => v_ri,
      enabled             => FALSE,
      auto_drop           => FALSE,
      comments            => 'RMAN strategy job');

  " . ($numArgs >= 1 ? "DBMS_SCHEDULER.SET_JOB_ARGUMENT_VALUE(v_job, 1, :a1);" : "") . "
  " . ($numArgs >= 2 ? "DBMS_SCHEDULER.SET_JOB_ARGUMENT_VALUE(v_job, 2, :a2);" : "") . "
  " . ($numArgs >= 3 ? "DBMS_SCHEDULER.SET_JOB_ARGUMENT_VALUE(v_job, 3, :a3);" : "") . "
  " . ($numArgs >= 4 ? "DBMS_SCHEDULER.SET_JOB_ARGUMENT_VALUE(v_job, 4, :a4);" : "") . "
  " . ($numArgs >= 5 ? "DBMS_SCHEDULER.SET_JOB_ARGUMENT_VALUE(v_job, 5, :a5);" : "") . "

  DBMS_SCHEDULER.ENABLE(v_job);
END;";

        $bind = [
            ':jn'    => $jobName,
            ':act'   => $execPath,
            ':nargs' => $numArgs,
            ':ri'    => $repeatInterval,
        ];
        if ($numArgs >= 1) $bind[':a1'] = $args[0];
        if ($numArgs >= 2) $bind[':a2'] = $args[1];
        if ($numArgs >= 3) $bind[':a3'] = $args[2];
        if ($numArgs >= 4) $bind[':a4'] = $args[3];
        if ($numArgs >= 5) $bind[':a5'] = $args[4];

        $db->exec($plsql, $bind);
    }
}
