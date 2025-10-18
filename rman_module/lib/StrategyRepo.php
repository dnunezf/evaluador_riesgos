<?php

declare(strict_types=1);

final class StrategyRepo
{
    public function __construct(private OracleClient $db) {}

    public function listStrategies(): array
    {
        return $this->db->query(
            "SELECT ID, CODE, NAME, TYPE, PRIORITY, CREATED_AT
             FROM RBACKUP_STRATEGY
             ORDER BY CREATED_AT DESC"
        );
    }

    public function getStrategy(int $id): array
    {
        $rows = $this->db->query(
            "SELECT *
               FROM RBACKUP_STRATEGY
              WHERE ID = :id",
            [':id' => $id]
        );
        if (!$rows) {
            throw new RuntimeException('Strategy not found');
        }
        return $rows[0];
    }

    public function insert(array $s): int
    {
        $sql = "INSERT INTO RBACKUP_STRATEGY
                  (CODE, NAME, TYPE, INCREMENTAL_LVL, INCLUDE_CTRLFILE, INCLUDE_ARCHIVE,
                   PRIORITY, OBJECT_SCOPE, OUTPUT_DIR, COMPRESSION, ENCRYPTION)
                VALUES
                  (:CODE, :NAME, :TYPE, :LVL, :ICF, :IAR,
                   :PRIO, :OBJ, :OUT, :CMP, :ENC)";

        $this->db->exec($sql, [
            ':CODE' => $s['CODE'],
            ':NAME' => $s['NAME'],
            ':TYPE' => $s['TYPE'],
            ':LVL'  => $s['INCREMENTAL_LVL'],
            ':ICF'  => $s['INCLUDE_CTRLFILE'],
            ':IAR'  => $s['INCLUDE_ARCHIVE'],
            ':PRIO' => $s['PRIORITY'],
            ':OBJ'  => $s['OBJECT_SCOPE'] ?? null,
            ':OUT'  => $s['OUTPUT_DIR'],
            ':CMP'  => $s['COMPRESSION'],
            ':ENC'  => $s['ENCRYPTION'],
        ]);

        // Obtener el ID recién creado por CODE
        $row = $this->db->query(
            "SELECT ID
               FROM RBACKUP_STRATEGY
              WHERE CODE = :c",
            [':c' => $s['CODE']]
        )[0];

        return (int)$row['ID'];
    }

    public function createRun(int $strategyId, string $scriptPath, string $logPath, bool $byScheduler): int
    {
        $this->db->exec(
            "INSERT INTO RBACKUP_RUN (STRATEGY_ID, SCRIPT_PATH, LOG_PATH, BY_SCHEDULER)
             VALUES (:sid, :sp, :lp, :bs)",
            [
                ':sid' => $strategyId,
                ':sp'  => $scriptPath,
                ':lp'  => $logPath,
                ':bs'  => $byScheduler ? 'Y' : 'N',
            ]
        );

        $row = $this->db->query(
            "SELECT ID
               FROM RBACKUP_RUN
              WHERE STRATEGY_ID = :sid
           ORDER BY STARTED_AT DESC
           FETCH FIRST 1 ROWS ONLY",
            [':sid' => $strategyId]
        )[0];

        return (int)$row['ID'];
    }

    public function finishRun(int $runId, string $status, int $rc): void
    {
        $this->db->exec(
            "UPDATE RBACKUP_RUN
                SET ENDED_AT = SYSTIMESTAMP,
                    STATUS   = :st,
                    RETURN_CODE = :rc
              WHERE ID = :id",
            [':st' => $status, ':rc' => $rc, ':id' => $runId]
        );
    }

    public function appendLog(int $runId, string $level, string $msg): void
    {
        $this->db->exec(
        // 👇 usar LOG_LEVEL (no LEVEL) para evitar ORA-01747
            "INSERT INTO RBACKUP_LOG (RUN_ID, LOG_LEVEL, MESSAGE)
             VALUES (:rid, :lvl, :msg)",
            [':rid' => $runId, ':lvl' => $level, ':msg' => $msg]
        );
    }
}
