<?php
header('Content-Type: application/json');
$pdo = new PDO("mysql:host=127.0.0.1;dbname=azuracast", "azuracast", "CKxR234fxpJG");

$totais = $pdo->query("
    SELECT 
        COUNT(*) as total,
        COALESCE(SUM(total), 0) as valor_total,
        COALESCE(SUM(valor_pago), 0) as valor_pago
    FROM finance_facturas
    WHERE empresa_id = 1 AND MONTH(data_emissao) = MONTH(CURRENT_DATE)
")->fetch(PDO::FETCH_ASSOC);

$facturas = $pdo->query("
    SELECT f.*, c.nome as cliente_nome
    FROM finance_facturas f
    LEFT JOIN finance_clientes c ON c.id = f.cliente_id
    WHERE f.empresa_id = 1
    ORDER BY f.data_emissao DESC
    LIMIT 10
")->fetchAll(PDO::FETCH_ASSOC);

echo json_encode([
    'mes_atual' => $totais,
    'facturas' => $facturas,
    'cambio' => ['USD_AOA' => 825.50]
]);
