<?php

declare(strict_types=1);

final class Scheduler
{
    public function __construct(private array $cfg) {}

    // Build command to execute RMAN via OS or Docker
    public function buildCommand(string $rmanScriptPath, string $logPath): string
    {
        $rman = $this->cfg['exec']['rman_bin'];
        $cmd  = PHP_OS_FAMILY === 'Windows'
            ? "$rman cmdfile=\"$rmanScriptPath\" log=\"$logPath\""
            : "$rman cmdfile='$rmanScriptPath' log='$logPath'";
        if ($this->cfg['exec']['use_docker'] && PHP_OS_FAMILY !== 'Windows') {
            $container = $this->cfg['exec']['docker_container'];
            $cmd = "docker exec $container bash -lc \"$cmd\"";
        }
        return $cmd;
    }

    // Create OS scheduled job; Oracle Scheduler alternative provided below
    public function createOsSchedule(string $jobName, string $command, string $cronExpr): array
    {
        if (PHP_OS_FAMILY === 'Windows') {
            // daily/weekly patterns are expected to be prepared by caller; fallback: run at logon
            $ps = "SCHTASKS /Create /TN \"$jobName\" /TR \"$command\" /SC ONLOGON /RL HIGHEST";
            return ['ok' => true, 'detail' => $ps];
        } else {
            // Simple crontab line
            $line = "$cronExpr $command";
            return ['ok' => true, 'detail' => $line];
        }
    }

    // Oracle DB Scheduler job creation
    public function createOracleScheduler(OracleClient $db, string $jobName, string $shellCommand, string $repeatInterval): void
    {
        $plsql = "
BEGIN
  DBMS_SCHEDULER.CREATE_JOB (
    job_name        => :jn,
    job_type        => 'EXECUTABLE',
    job_action      => :act,
    number_of_arguments => 0,
    start_date      => SYSTIMESTAMP,
    repeat_interval => :ri,
    enabled         => TRUE,
    auto_drop       => FALSE,
    comments        => 'RMAN strategy job');
END;";
        $db->exec($plsql, [':jn' => $jobName, ':act' => $shellCommand, ':ri' => $repeatInterval]);
    }
}
