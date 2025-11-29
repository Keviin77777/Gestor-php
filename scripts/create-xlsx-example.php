<?php
/**
 * Script para criar arquivo XLSX de exemplo para importação de clientes
 * Execute: php scripts/create-xlsx-example.php
 */

require_once __DIR__ . '/../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Font;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

try {
    // Criar novo spreadsheet
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle('Clientes');

    // Definir cabeçalhos
    $headers = [
        'A1' => 'nome',
        'B1' => 'usuario_iptv',
        'C1' => 'senha_iptv',
        'D1' => 'whatsapp',
        'E1' => 'vencimento',
        'F1' => 'servidor',
        'G1' => 'aplicativo',
        'H1' => 'mac',
        'I1' => 'plano',
        'J1' => 'email'
    ];

    // Aplicar cabeçalhos
    foreach ($headers as $cell => $value) {
        $sheet->setCellValue($cell, $value);
    }

    // Estilizar cabeçalhos
    $headerStyle = [
        'font' => [
            'bold' => true,
            'color' => ['rgb' => 'FFFFFF'],
            'size' => 12
        ],
        'fill' => [
            'fillType' => Fill::FILL_SOLID,
            'startColor' => ['rgb' => '6366F1']
        ],
        'alignment' => [
            'horizontal' => Alignment::HORIZONTAL_CENTER,
            'vertical' => Alignment::VERTICAL_CENTER
        ]
    ];
    $sheet->getStyle('A1:J1')->applyFromArray($headerStyle);

    // Dados de exemplo
    $data = [
        [
            'João Silva',
            'joao123',
            'senha123',
            '11987654321',
            '2024-12-31',
            'Servidor Principal',
            'NextApp',
            '00:1A:2B:3C:4D:5E',
            'Premium',
            'joao.silva@email.com'
        ],
        [
            'Maria Santos',
            'maria456',
            'senha456',
            '11976543210',
            '2024-12-25',
            'Servidor Principal',
            'SmartIPTV',
            '00:1A:2B:3C:4D:5F',
            'Básico',
            'maria.santos@email.com'
        ],
        [
            'Pedro Oliveira',
            'pedro789',
            'senha789',
            '11965432109',
            '2024-12-20',
            'Servidor Secundário',
            'IPTV Smarters',
            '00:1A:2B:3C:4D:60',
            'VIP',
            'pedro.oliveira@email.com'
        ]
    ];

    // Inserir dados
    $row = 2;
    foreach ($data as $rowData) {
        $col = 'A';
        foreach ($rowData as $value) {
            $sheet->setCellValue($col . $row, $value);
            $col++;
        }
        $row++;
    }

    // Ajustar largura das colunas
    foreach (range('A', 'J') as $col) {
        $sheet->getColumnDimension($col)->setAutoSize(true);
    }

    // Adicionar bordas
    $styleArray = [
        'borders' => [
            'allBorders' => [
                'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                'color' => ['rgb' => 'CCCCCC']
            ]
        ]
    ];
    $sheet->getStyle('A1:J' . ($row - 1))->applyFromArray($styleArray);

    // Salvar arquivo
    $writer = new Xlsx($spreadsheet);
    $filename = __DIR__ . '/../exemplo-importacao-clientes.xlsx';
    $writer->save($filename);

    echo "✅ Arquivo criado com sucesso!\n";
    echo "📋 Contém 3 clientes de exemplo\n";
    echo "📁 Localização: exemplo-importacao-clientes.xlsx\n";
    echo "\n";
    echo "Campos incluídos:\n";
    echo "- nome: Nome completo do cliente\n";
    echo "- usuario_iptv: Login IPTV\n";
    echo "- senha_iptv: Senha IPTV\n";
    echo "- whatsapp: Número com DDD\n";
    echo "- vencimento: Data no formato YYYY-MM-DD\n";
    echo "- servidor: Nome do servidor\n";
    echo "- aplicativo: Nome do aplicativo\n";
    echo "- mac: Endereço MAC\n";
    echo "- plano: Nome do plano\n";
    echo "- email: Email do cliente\n";

} catch (Exception $e) {
    echo "❌ Erro ao criar arquivo: " . $e->getMessage() . "\n";
    echo "\n";
    echo "Certifique-se de que a biblioteca PhpSpreadsheet está instalada:\n";
    echo "composer require phpoffice/phpspreadsheet\n";
}
