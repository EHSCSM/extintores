<?php
// ==========================================
// RIESGOS CERO - API: PANEL GLOBAL SUPER ADMIN
// ==========================================
header('Content-Type: application/json; charset=utf-8');
require_once 'conexion.php';

try {
    // Unimos la tabla usuarios con empresas para ver quién trabaja dónde
    $query = "
        SELECT 
            u.id, 
            u.nombre, 
            u.email, 
            u.rol, 
            u.estatus, 
            e.nombre_comercial 
        FROM usuarios u
        LEFT JOIN empresas e ON u.empresa_id = e.id
        ORDER BY e.nombre_comercial ASC, u.rol ASC
    ";

    $stmt = $conexion->prepare($query);
    $stmt->execute();
    $usuarios = $stmt->fetchAll();

    echo json_encode(["error" => false, "datos" => $usuarios]);

} catch (PDOException $e) {
    echo json_encode(["error" => true, "mensaje" => "Error al consultar la base de datos global."]);
}
?>