<?php

namespace App\Mail;

use App\Models\Alojamento;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NovaReservaAlojamento extends Mailable implements ShouldQueue
{
  //A classe Mailable referencia o template Blade, e o Controller acessa as configurações do arquivo de configuração.

  use Queueable, SerializesModels;

  public $alojamento;

  /**
   * Create a new message instance.
   */
  public function __construct(Alojamento $alojamento)
  {
    $this->alojamento = $alojamento;
  }

  /**
   * Get the message envelope.
   */
  public function envelope(): Envelope
  {
    return new Envelope(subject: 'Nova Solicitação de Reserva de Alojamento');
  }

  /**
   * Get the message content definition.
   */
  public function content(): Content
  {
    return new Content(
      markdown: 'emails.alojamento.nova-reserva',
      with: [
        'alojamento' => $this->alojamento,
        'url' => route('admin.alojamento.show', $this->alojamento->id),
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
}
