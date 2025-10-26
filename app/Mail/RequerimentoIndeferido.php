<?php

namespace App\Mail;

use App\Models\Requerimento;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class RequerimentoIndeferido extends Mailable implements ShouldQueue
{
  use Queueable, SerializesModels;

  public $requerimento;

  /**
   * Create a new message instance.
   */
  public function __construct(Requerimento $requerimento)
  {
    $this->requerimento = $requerimento;
  }

  /**
   * Get the message envelope.
   */
  public function envelope(): Envelope
  {
    return new Envelope(subject: 'Requerimento Indeferido - ACADEPOL');
  }

  /**
   * Get the message content definition.
   */
  public function content(): Content
  {
    // Formatar o tipo de requerimento para exibição
    $tipoFormatado = $this->getTipoRequerimentoFormatado(
      $this->requerimento->tipo,
    );

    return new Content(
      markdown: 'emails.requerimento.indeferido',
      with: [
        'requerimento' => $this->requerimento,
        'tipo_formatado' => $tipoFormatado,
        'motivo' => $this->requerimento->motivo_indeferimento,
      ],
    );
  }

  /**
   * Get the attachments for the message.
   *
   * @return array<int, \Illuminate\Mail\Mailables\Attachment>
   */
  public function attachments(): array
  {
    return [];
  }

  /**
   * Formatar o tipo de requerimento para exibição
   */
  private function getTipoRequerimentoFormatado($tipo)
  {
    $tipos = [
      'segunda_via_certificado' => '2ª Via de Certificado',
      'declaracao_participacao' => 'Declaração de Participação em Curso',
      'outros' => 'Outros',
    ];

    return $tipos[$tipo] ?? $tipo;
  }
}
