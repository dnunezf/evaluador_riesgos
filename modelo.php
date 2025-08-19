<?php
// Funciones de acceso a datos
require_once './config/config.php';

// Obtener todas las tareas (actividades)
function getTareas()
{
    global $pdo;
    $sql = "SELECT Id, Nombre FROM TA ORDER BY Id";
    return $pdo->query($sql)->fetchAll();
}

// Obtener los requisitos asociados a una tarea
function getRequisitosPorTarea($taId)
{
    global $pdo;
    $sql = "
        SELECT RE.Id, RE.Nombre, RE.Texto, RE.NormaId
        FROM RE
        JOIN TR ON TR.REId = RE.Id
        WHERE TR.TAId = ?
        ORDER BY RE.Id
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$taId]);
    return $stmt->fetchAll();
}

// Obtener los riesgos asociados a un requisito
function getRiesgosPorRequisito($reId)
{
    global $pdo;
    $sql = "
        SELECT RI.Nombre, RI.Tipo
        FROM RI
        JOIN Re_Ri ON Re_Ri.RIId = RI.Id
        WHERE Re_Ri.REId = ?
        ORDER BY RI.Id
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$reId]);
    return $stmt->fetchAll();
}

// Obtener la norma de un requisito
function getNormaPorRequisito($reId)
{
    global $pdo;
    $sql = "
        SELECT Norma.Nombre
        FROM Norma
        JOIN RE ON RE.NormaId = Norma.Id
        WHERE RE.Id = ?
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$reId]);
    return $stmt->fetchColumn();
}

function getNormas()
{
    global $pdo;
    $sql = "SELECT Id, Nombre FROM Norma ORDER BY Id";
    $res = $pdo->query($sql)->fetchAll();
    return $res ?: [
        ['Id' => 1, 'Nombre' => 'ISO/IEC 27002:2005'],
        ['Id' => 2, 'Nombre' => 'COBIT 4.1']
    ];
}
