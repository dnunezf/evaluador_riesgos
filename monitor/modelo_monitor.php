<?php
require_once __DIR__ . '/../config/config.php';
/** Lee config actual */
function monitor_get_config()
{
  global $pdo;
  $row = $pdo->query("SELECT critico_pct, delay_seg, habilitado FROM monitor_config WHERE id=1")->fetch();
  if (!$row) return ['critico_pct' => 80.0, 'delay_seg' => 5, 'habilitado' => 1];
  return $row;
}

/** Actualiza config */
function monitor_update_config($critico_pct, $delay_seg, $habilitado)
{
  global $pdo;
  $stmt = $pdo->prepare("UPDATE monitor_config SET critico_pct=?, delay_seg=?, habilitado=? WHERE id=1");
  $stmt->execute([max(0, min(100, $critico_pct)), max(1, $delay_seg), $habilitado ? 1 : 0]);
}

/**
 * Calcula % de uso del buffer.
 * Fallback seguro si alguna métrica falta.
 */
function monitor_calc_consumo()
{
  global $pdo;

  $sqlPages = "
      SELECT
        COALESCE(MAX(CASE WHEN VARIABLE_NAME='Innodb_buffer_pool_pages_data'  THEN VARIABLE_VALUE END),0) AS pages_data,
        COALESCE(MAX(CASE WHEN VARIABLE_NAME='Innodb_buffer_pool_pages_total' THEN VARIABLE_VALUE END),0) AS pages_total
      FROM information_schema.GLOBAL_STATUS
      WHERE VARIABLE_NAME IN ('Innodb_buffer_pool_pages_data','Innodb_buffer_pool_pages_total')
    ";
  $row = $pdo->query($sqlPages)->fetch();
  $data  = (float)$row['pages_data'];
  $total = (float)$row['pages_total'];

  // Si no hay total, intenta bytes 
  if ($total <= 0) {
    $sqlBytes = "
          SELECT
            COALESCE(MAX(CASE WHEN VARIABLE_NAME='Innodb_buffer_pool_bytes_data'  THEN VARIABLE_VALUE END),0) AS bytes_data,
            COALESCE(MAX(CASE WHEN VARIABLE_NAME='Innodb_buffer_pool_bytes_total' THEN VARIABLE_VALUE END),0) AS bytes_total
          FROM information_schema.GLOBAL_STATUS
          WHERE VARIABLE_NAME IN ('Innodb_buffer_pool_bytes_data','Innodb_buffer_pool_bytes_total')
        ";
    $r2 = $pdo->query($sqlBytes)->fetch();
    $data = (float)$r2['bytes_data'];
    $total = (float)$r2['bytes_total'];
  }

  $pct = ($total > 0) ? round(($data * 100.0) / $total, 2) : 0.00;

  // SQL textual usado para registrar en eventos
  $sql_text = ($total > 0)
    ? "SELECT Innodb_buffer_pool_pages_data/Innodb_buffer_pool_pages_total * 100 AS pct /* via GLOBAL_STATUS */"
    : "SELECT Innodb_buffer_pool_bytes_data/Innodb_buffer_pool_bytes_total * 100 AS pct /* via GLOBAL_STATUS */";

  return ['consumo_pct' => $pct, 'sql_text' => $sql_text];
}

/** Inserta evento crítico */
function monitor_log_evento($consumo_pct, $sql_text, $proceso = 'monitor')
{
  global $pdo;
  $usuario = $pdo->query("SELECT CURRENT_USER() AS u")->fetchColumn();
  $stmt = $pdo->prepare("
        INSERT INTO consumo_critico (fecha, hora, proceso, usuario, sql_text, consumo_pct, detalles)
        VALUES (CURRENT_DATE(), CURRENT_TIME(), ?, ?, ?, ?, ?)
    ");
  $detalles = 'BufferPool=%' . $consumo_pct;
  $stmt->execute([$proceso, $usuario, $sql_text, $consumo_pct, $detalles]);
}
