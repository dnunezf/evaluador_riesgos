<?php

declare(strict_types=1);

final class OracleClient
{
    private $conn;

    public function __construct(private array $cfg) {}

    public function connect(): void
    {
        $this->conn = @oci_connect(
            $this->cfg['username'],
            $this->cfg['password'],
            $this->cfg['tns'],
            $this->cfg['charset']
        );
        if (!$this->conn) {
            $e = oci_error();
            throw new RuntimeException('Oracle connect failed: ' . ($e['message'] ?? 'unknown'));
        }
    }

    public function query(string $sql, array $bind = []): array
    {
        $stid = oci_parse($this->conn, $sql);
        foreach ($bind as $k => $v) {
            oci_bind_by_name($stid, $k, $bind[$k]);
        }
        if (!oci_execute($stid)) {
            $e = oci_error($stid);
            throw new RuntimeException('Oracle query failed: ' . $e['message']);
        }
        $rows = [];
        while (($row = oci_fetch_array($stid, OCI_ASSOC | OCI_RETURN_NULLS)) !== false) {
            $rows[] = $row;
        }
        oci_free_statement($stid);
        return $rows;
    }

    public function exec(string $sql, array $bind = []): int
    {
        $stid = oci_parse($this->conn, $sql);
        foreach ($bind as $k => $v) {
            oci_bind_by_name($stid, $k, $bind[$k]);
        }
        if (!oci_execute($stid, OCI_COMMIT_ON_SUCCESS)) {
            $e = oci_error($stid);
            throw new RuntimeException('Oracle exec failed: ' . $e['message']);
        }
        $rows = oci_num_rows($stid);
        oci_free_statement($stid);
        return $rows;
    }

    public function close(): void
    {
        if ($this->conn) {
            oci_close($this->conn);
        }
    }
}
