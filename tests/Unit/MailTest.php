<?php

namespace Tests\Unit;

use App\Mail\Support;
use App\Mail\ValidationMail;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class MailTest extends TestCase
{
    public function test_validation_mail_has_correct_subject(): void
    {
        $mail = new ValidationMail('12345');

        $this->assertEquals('12345', $mail->validationCode);
        $this->assertStringContainsString('Validate your email', $mail->build()->subject);
    }

    public function test_validation_mail_uses_correct_view(): void
    {
        $mail = new ValidationMail('67890');

        $built = $mail->build();

        $this->assertEquals('mail.validation-mail', $built->view);
    }

    public function test_support_mail_stores_all_properties(): void
    {
        $file = UploadedFile::fake()->create('screenshot.png', 100, 'image/png');

        $mail = new Support(
            [$file],
            'user-uuid-123',
            'Help with planning',
            'bug',
            'TICKET-001',
            'I need help with my planning feature',
            'github@example.com',
            'google@example.com',
            '11999999999'
        );

        $this->assertEquals([$file], $mail->files);
        $this->assertEquals('user-uuid-123', $mail->userUUID);
        $this->assertEquals('Help with planning', $mail->title);
        $this->assertEquals('bug', $mail->category);
        $this->assertEquals('TICKET-001', $mail->ticketId);
        $this->assertEquals('I need help with my planning feature', $mail->description);
        $this->assertEquals('github@example.com', $mail->github_email);
        $this->assertEquals('google@example.com', $mail->google_email);
        $this->assertEquals('11999999999', $mail->cellphone_number);
    }

    public function test_support_mail_builds_correctly(): void
    {
        $file = UploadedFile::fake()->create('screenshot.png', 100, 'image/png');

        $mail = new Support(
            [$file],
            'user-uuid-123',
            'Test title',
            'feature',
            'TICKET-002',
            'Test description',
            'github@example.com',
            'google@example.com',
            '11999999999'
        );

        $built = $mail->build();

        $this->assertStringContainsString('Planossauro - support', $built->subject);
        $this->assertEquals('mail.support', $built->view);
    }

    public function test_support_mail_attachments_with_files(): void
    {
        $file1 = UploadedFile::fake()->create('screen1.png', 100, 'image/png');
        $file2 = UploadedFile::fake()->create('screen2.jpg', 200, 'image/jpeg');

        $mail = new Support(
            [$file1, $file2],
            'user-uuid-123',
            'Test',
            'bug',
            'TICKET-003',
            'Description',
            'github@example.com',
            'google@example.com',
            '11999999999'
        );

        $attachments = $mail->attachments();

        $this->assertCount(2, $attachments);
    }

    public function test_support_mail_attachments_without_files(): void
    {
        $mail = new Support(
            [],
            'user-uuid-123',
            'Test',
            'bug',
            'TICKET-004',
            'Description',
            'github@example.com',
            'google@example.com',
            '11999999999'
        );

        $attachments = $mail->attachments();

        $this->assertCount(0, $attachments);
    }

    public function test_support_mail_handles_null_files(): void
    {
        $mail = new Support(
            null,
            'user-uuid-123',
            'Test',
            'bug',
            'TICKET-005',
            'Description',
            'github@example.com',
            'google@example.com',
            '11999999999'
        );

        $this->assertNull($mail->files);
    }
}
