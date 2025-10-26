<?php

namespace App\Http\Controllers;

use App\Models\Matricula;
use App\Models\Curso;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Barryvdh\DomPDF\Facade\Pdf;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Font;
use PhpOffice\PhpSpreadsheet\Style\Border;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MatriculaRelatorioController extends Controller
{
  /**
   * Gerar relatório de matrículas aprovadas
   */
  public function gerarRelatorioPDF(Curso $curso)
  {
    try {
      $this->authorize('viewAny', Matricula::class);

      // Buscar apenas matrículas aprovadas do curso específico
      $matriculas = Matricula::with(['curso', 'aluno'])
        ->where('curso_id', $curso->id)
        ->where('status', 'aprovada')
        ->orderBy('created_at', 'desc')
        ->get();

      // Dados para o relatório
      $dados = [
        'matriculas' => $matriculas,
        'curso' => $curso,
        'total' => $matriculas->count(),
        'data_geracao' => now()->format('d/m/Y H:i'),
        'gerado_por' => Auth::user()->name,
      ];

      // Gerar PDF
      $pdf = PDF::loadView('relatorios.matriculas-curso', $dados);
      $pdf->setPaper('A4', 'portrait');

      $nomeArquivo =
        'servidores_matriculados_' .
        str_replace(' ', '_', $curso->nome) .
        '_' .
        now()->format('Y-m-d_H-i-s') .
        '.pdf';

      Log::info('Relatório PDF de matrículas gerado para curso específico', [
        'admin_id' => Auth::id(),
        'curso_id' => $curso->id,
        'total_matriculas' => $matriculas->count(),
      ]);

      return $pdf->download($nomeArquivo);
    } catch (\Exception $e) {
      Log::error('Erro ao gerar relatório PDF de matrículas', [
        'error' => $e->getMessage(),
        'curso_id' => $curso->id,
        'admin_id' => Auth::id(),
      ]);

      return back()->with(
        'error',
        'Erro ao gerar relatório PDF: ' . $e->getMessage(),
      );
    }
  }

  /**
   * Gerar relatório de matrículas aprovadas em Excel para um curso específico
   */
  public function gerarRelatorioExcel(Curso $curso)
  {
    try {
      $this->authorize('viewAny', Matricula::class);

      // Buscar apenas matrículas aprovadas do curso específico
      $matriculas = Matricula::with(['curso', 'aluno'])
        ->where('curso_id', $curso->id)
        ->where('status', 'aprovada')
        ->orderBy('created_at', 'desc')
        ->get();

      // Criar planilha
      $spreadsheet = new Spreadsheet();
      $sheet = $spreadsheet->getActiveSheet();

      // Configurar título
      $sheet->setCellValue('A1', 'LISTA DE SERVIDORES MATRICULADOS');
      $sheet->mergeCells('A1:F1');
      $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
      $sheet
        ->getStyle('A1')
        ->getAlignment()
        ->setHorizontal(Alignment::HORIZONTAL_CENTER);

      // Informações do curso
      $sheet->setCellValue('A3', 'Curso: ' . $curso->nome);
      $sheet->setCellValue(
        'A4',
        'Total de Servidores: ' . $matriculas->count(),
      );
      $sheet->setCellValue(
        'A5',
        'Data de Geração: ' . now()->format('d/m/Y H:i'),
      );

      // Aplicar negrito e centralizar as informações do curso
      $sheet->getStyle('A3:A5')->getFont()->setBold(true);
      $sheet
        ->getStyle('A3:A5')
        ->getAlignment()
        ->setHorizontal(Alignment::HORIZONTAL_LEFT);

      // Cabeçalhos da tabela
      $linha = 8;
      $colunas = ['A', 'B', 'C', 'D', 'E', 'F'];
      $cabecalhos = [
        'Nome do Servidor',
        'Matrícula',
        'Email',
        'Telefone',
        'Data de Inscrição',
        'CPF',
      ];

      foreach ($cabecalhos as $index => $cabecalho) {
        $celula = $colunas[$index] . $linha;
        $sheet->setCellValue($celula, $cabecalho);
        $sheet->getStyle($celula)->getFont()->setBold(true);
        $sheet
          ->getStyle($celula)
          ->getBorders()
          ->getAllBorders()
          ->setBorderStyle(Border::BORDER_THIN);
        $sheet
          ->getStyle($celula)
          ->getAlignment()
          ->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet
          ->getStyle($celula)
          ->getAlignment()
          ->setVertical(Alignment::VERTICAL_CENTER);
      }

      // Dados das matrículas
      $linha++;
      foreach ($matriculas as $matricula) {
        $sheet->setCellValue('A' . $linha, $matricula->aluno->name ?? 'N/A');
        $sheet->setCellValue(
          'B' . $linha,
          $matricula->aluno->matricula ?? 'N/A',
        );
        $sheet->setCellValue('C' . $linha, $matricula->aluno->email ?? 'N/A');
        $sheet->setCellValue(
          'D' . $linha,
          $matricula->aluno->telefone ?? 'N/A',
        );
        $sheet->setCellValue(
          'E' . $linha,
          $matricula->created_at->format('d/m/Y'),
        );
        $sheet->setCellValue('F' . $linha, $matricula->aluno->cpf ?? 'N/A');

        // Aplicar bordas e centralizar todos os dados
        foreach ($colunas as $coluna) {
          $celula = $coluna . $linha;
          $sheet
            ->getStyle($celula)
            ->getBorders()
            ->getAllBorders()
            ->setBorderStyle(Border::BORDER_THIN);

          // Centralizar horizontalmente e verticalmente
          $sheet
            ->getStyle($celula)
            ->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER)
            ->setVertical(Alignment::VERTICAL_CENTER);
        }

        $linha++;
      }

      // Ajustar largura das colunas
      foreach ($colunas as $coluna) {
        $sheet->getColumnDimension($coluna)->setAutoSize(true);
      }

      // Definir altura mínima das linhas para melhor visualização
      for ($i = 9; $i < $linha; $i++) {
        $sheet->getRowDimension($i)->setRowHeight(20);
      }

      // Criar response para download
      $nomeArquivo =
        'servidores_matriculados_' .
        str_replace(' ', '_', $curso->nome) .
        '_' .
        now()->format('Y-m-d_H-i-s') .
        '.xlsx';

      Log::info('Relatório Excel de matrículas gerado para curso específico', [
        'admin_id' => Auth::id(),
        'curso_id' => $curso->id,
        'total_matriculas' => $matriculas->count(),
      ]);

      return new StreamedResponse(
        function () use ($spreadsheet) {
          $writer = new Xlsx($spreadsheet);
          $writer->save('php://output');
        },
        200,
        [
          'Content-Type' =>
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
          'Content-Disposition' =>
            'attachment; filename="' . $nomeArquivo . '"',
          'Cache-Control' => 'max-age=0',
        ],
      );
    } catch (\Exception $e) {
      Log::error('Erro ao gerar relatório Excel de matrículas', [
        'error' => $e->getMessage(),
        'curso_id' => $curso->id,
        'admin_id' => Auth::id(),
      ]);

      return back()->with(
        'error',
        'Erro ao gerar relatório Excel: ' . $e->getMessage(),
      );
    }
  }
}
