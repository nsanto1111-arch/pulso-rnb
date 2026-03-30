<?php
namespace Plugin\ProgramacaoPlugin\Service;

use Doctrine\DBAL\Connection;

class FinanceService
{
    private Connection $db;

    public function __construct(Connection $connection)
    {
        $this->db = $connection;
    }

    public function getDashboard(int $empresaId): array
    {
        // Totais do mês
        $totais = $this->db->fetchAssociative("
            SELECT 
                COUNT(*) as total,
                COALESCE(SUM(total), 0) as valor_total,
                COALESCE(SUM(valor_pago), 0) as valor_pago,
                COALESCE(SUM(saldo), 0) as saldo
            FROM finance_facturas
            WHERE empresa_id = ? AND MONTH(data_emissao) = MONTH(CURRENT_DATE)
        ", [$empresaId]) ?: ['total' => 0, 'valor_total' => 0, 'valor_pago' => 0, 'saldo' => 0];

        // Facturas recentes
        $facturas = $this->db->fetchAllAssociative("
            SELECT f.*, c.nome as cliente_nome
            FROM finance_facturas f
            LEFT JOIN finance_clientes c ON c.id = f.cliente_id
            WHERE f.empresa_id = ?
            ORDER BY f.data_emissao DESC
            LIMIT 10
        ", [$empresaId]) ?: [];

        return [
            'mes_atual' => [
                'total_facturas' => (int)$totais['total'],
                'valor_total' => (float)$totais['valor_total'],
                'valor_pago' => (float)$totais['valor_pago'],
                'saldo' => (float)$totais['saldo'],
            ],
            'facturas_recentes' => $facturas,
            'cambio' => ['USD_AOA' => 825.50],
        ];
    }

    public function getClientes(int $empresaId): array
    {
        return $this->db->fetchAllAssociative("
            SELECT * FROM finance_clientes 
            WHERE empresa_id = ? AND ativo = 1 
            ORDER BY nome
        ", [$empresaId]) ?: [];
    }

    public function criarFactura(array $data): int
    {
        $this->db->insert('finance_facturas', $data);
        return (int)$this->db->lastInsertId();
    }
}
