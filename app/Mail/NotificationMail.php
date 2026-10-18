<?php

namespace App\Mail;

use App\Models\Notification;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Branded ISU OSAS Hub notification email.
 *
 * Carries only non-confidential, already-persisted data. It is intentionally
 * model-free (except for serialisation friendliness) so it can safely be queued.
 */
class NotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $recipientName,
        public string $subjectLine,
        public string $heading,
        public string $intro,
        public string $categoryLabel,
        public ?string $referenceLabel = null,
        public ?string $reference = null,
        public ?string $status = null,
        public ?string $updatedAt = null,
        public ?string $nextStep = null,
        public ?string $reason = null,
        public ?string $actionUrl = null,
    ) {}

    /**
     * Build the email from a persisted notification. The notification's message
     * body is used as the intro so the email mirrors the in-app record.
     */
    public static function fromNotification(Notification $notification, string $recipientName): self
    {
        return new self(
            recipientName: $recipientName,
            subjectLine: 'ISU OSAS Hub: '.$notification->title,
            heading: $notification->title,
            intro: $notification->message,
            categoryLabel: $notification->getCategoryLabel(),
            referenceLabel: $notification->referenceLabel(),
            reference: $notification->reference(),
            status: $notification->statusLabel(),
            updatedAt: $notification->updated_at?->format('F j, Y g:i A'),
            nextStep: $notification->nextStep(),
            reason: $notification->staffExplanation(),
            actionUrl: $notification->getDetailUrl(),
        );
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->subjectLine,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.notification',
        );
    }

    /**
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
