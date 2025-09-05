<?php
// Mapa de clientes → origen y umbral por defecto
return [
  // Ejemplos:
  'ClienteRemoto' => ['dblink' => 'dblink_cliente_sim', 'umbral' => 85],
  // Si un cliente se monitorea por snapshots locales, deje dblink en null
  'ClienteLocal' => ['dblink' => null, 'umbral' => 30],
];
