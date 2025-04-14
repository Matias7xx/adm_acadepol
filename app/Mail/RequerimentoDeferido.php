<?php

namespace App\Mail;

use App\Models\Requerimento;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class RequerimentoDeferido extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $requerimento;
    public $tipo_formatado;

    /**
     * Create a new message instance.
     */
    public function __construct(Requerimento $requerimento)
    {
        $this->requerimento = $requerimento;
        $this->tipo_formatado = $this->getTipoRequerimentoFormatado($requerimento->tipo);
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Requerimento Deferido - ACADEPOL',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'emails.requerimento.deferido',
            with: [
                'requerimento' => $this->requerimento,
                'tipo_formatado' => $this->tipo_formatado,
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
        $attachments = [];
        
        // Anexar o documento de resposta se existir
        if ($this->requerimento->documento_resposta) {
            $path = storage_path('app/public/' . $this->requerimento->documento_resposta);
            if (file_exists($path)) {
                $attachments[] = Attachment::fromPath($path)->withMime($this->getMimeType($path));
            }
        }
        
        return $attachments;
    }

    /**
     * Obter o nome formatado do tipo de requerimento
     */
    private function getTipoRequerimentoFormatado($tipo)
    {
        $tipos = [
            'segunda_via_certificado' => '2ª Via de Certificado',
            'declaracao_participacao' => 'Declaração de Participação em Curso',
            'outros' => 'Outros'
        ];

        return $tipos[$tipo] ?? $tipo;
    }

    /**
     * Determinar o tipo MIME do arquivo
     */
    private function getMimeType($path)
    {
        $mimeTypes = [
            'pdf' => 'application/pdf',
            'doc' => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png'
        ];
        
        $extension = pathinfo($path, PATHINFO_EXTENSION);
        return $mimeTypes[$extension] ?? 'application/octet-stream';
    }
}