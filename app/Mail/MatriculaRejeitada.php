<?php

namespace App\Mail;

use App\Models\Matricula;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class MatriculaRejeitada extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $matricula;
    public $motivo;

    /**
     * Create a new message instance.
     */
    public function __construct(Matricula $matricula, ?string $motivo = null)
    {
        $this->matricula = $matricula;
        $this->motivo = $motivo;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Matrícula Não Aprovada - ACADEPOL',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'emails.matricula.rejeitada',
            with: [
                'matricula' => $this->matricula,
                'curso' => $this->matricula->curso,
                'aluno' => $this->matricula->aluno,
                'motivo' => $this->motivo,
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