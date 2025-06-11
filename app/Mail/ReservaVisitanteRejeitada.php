<?php

namespace App\Mail;

use App\Models\Visitante;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ReservaVisitanteRejeitada extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $visitante;

    /**
     * Create a new message instance.
     */
    public function __construct(Visitante $visitante)
    {
        $this->visitante = $visitante;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Reserva de Alojamento Rejeitada - ACADEPOL',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'emails.visitante.reserva-rejeitada',
            with: [
                'visitante' => $this->visitante,
                'dataInicial' => $this->visitante->data_inicial->format('d/m/Y'),
                'dataFinal' => $this->visitante->data_final->format('d/m/Y'),
                'motivoRejeicao' => $this->visitante->motivo_rejeicao,
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