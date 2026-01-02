<?php
namespace App\Services;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use RuntimeException;

class EmailService {
    
    protected ?PHPMailer $mail = null;
    protected string $systemEmail;
    protected string $systemName;

    public function __construct() {
        $this->mail = new PHPMailer(true);
        $this->configure();
    }

    public function configure(): void {
        $this->mail->isSMTP();
        $this->mail->Host = $_ENV['SMTP_HOST'];
        $this->mail->SMTPAuth = true;
        $this->mail->Username = $_ENV['SMTP_USERNAME'];
        $this->mail->Password = $_ENV['SMTP_PASSWORD'];
        $this->mail->SMTPSecure = $_ENV['SMTP_ENCRYPTION'] ?? 'tls';
        $this->mail->Port = (int)($_ENV['SMTP_PORT'] ?? 587);

        $this->mail->setFrom(
            $_ENV['SYSTEM_EMAIL'],
            $_ENV['SYSTEM_EMAIL_NAME'] ?? 'Task Management System'
        );

        $this->mail->isHTML(true);
    }

    public function sendMail(
        string $toEmail,
        string $toName,
        string $subject,
        string $htmlBody,
        string $plainText = ""
    ): bool {
        try {
            $this->mail->clearAllRecipients();
            $this->mail->clearAttachments();
            $this->mail->addAddress($toEmail, $toName);
            $this->mail->Subject = $subject;
            $this->mail->Body = $htmlBody;
            $this->mail->AltBody = $plainText ?: strip_tags($htmlBody);

            return $this->mail->send();

        } catch (Exception $e) {
            error_log("Mail Error: " . $e->getMessage());
            return false;
        }
    }

    protected function renderTemplate(string $template, array $data = []): string {
        $path = BASE_PATH . "/resources/mail/{$template}.php";
        if (!file_exists($path)) {
            throw new RuntimeException("Email template not found: {$template}");
        }
        extract($data, EXTR_SKIP);
        
        ob_start();
        require $path;
        return ob_get_clean();
    }

    public function sendTemplateMail(
        string $toEmail,
        string $toName,
        string $subject,
        string $template,
        array $data = [],
        string $plainText = ""
    ): bool {
        try {
            $this->mail->clearAllRecipients();
            $this->mail->clearAttachments();
            
            $htmlBody = $this->renderTemplate($template, $data);

            $this->mail->addAddress($toEmail, $toName);
            $this->mail->Subject = $subject;
            $this->mail->Body = $htmlBody;
            $this->mail->AltBody = $plainText ?: strip_tags($htmlBody);
            return $this->mail->send();
        } catch (Exception $e) {
            error_log("Mail Error: " . $e->getMessage());
            return false;
        }
    }
}
