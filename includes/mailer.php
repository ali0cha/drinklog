<?php
// Inclusion manuelle de PHPMailer (sans Composer)
// Placez les 3 fichiers dans includes/PHPMailer/
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/PHPMailer/Exception.php';
require_once __DIR__ . '/PHPMailer/PHPMailer.php';
require_once __DIR__ . '/PHPMailer/SMTP.php';

function sendPasswordResetEmail(string $toEmail, string $toName, string $resetLink): bool {
    require_once __DIR__ . '/../config.php';

    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USER;
        $mail->Password   = SMTP_PASS;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = SMTP_PORT;
        $mail->CharSet    = 'UTF-8';

        $mail->setFrom(SMTP_FROM, SMTP_FROM_NAME);
        $mail->addAddress($toEmail, $toName);

        $mail->isHTML(true);
        $mail->Subject = '[' . APP_NAME . '] Réinitialisation de votre mot de passe';
        $mail->Body    = "
        <div style='font-family:sans-serif;max-width:500px;margin:auto'>
            <h2 style='color:#6d28d9'>🍵 " . APP_NAME . "</h2>
            <p>Bonjour <strong>" . htmlspecialchars($toName) . "</strong>,</p>
            <p>Vous avez demandé la réinitialisation de votre mot de passe.</p>
            <p>Cliquez sur le bouton ci-dessous pour choisir un nouveau mot de passe.
            Ce lien est valable <strong>1 heure</strong>.</p>
            <p style='text-align:center;margin:30px 0'>
                <a href='" . htmlspecialchars($resetLink) . "'
                   style='background:#6d28d9;color:#fff;padding:14px 28px;border-radius:8px;
                          text-decoration:none;font-size:16px'>
                    Réinitialiser le mot de passe
                </a>
            </p>
            <p style='color:#6b7280;font-size:13px'>
                Si vous n'avez pas demandé cette réinitialisation, ignorez simplement cet email.
            </p>
            <hr style='border:none;border-top:1px solid #e5e7eb'>
            <p style='color:#9ca3af;font-size:12px'>
                Lien direct : <a href='" . htmlspecialchars($resetLink) . "'>" . htmlspecialchars($resetLink) . "</a>
            </p>
        </div>";
        $mail->AltBody = "Réinitialisez votre mot de passe DrinkLog ici : " . $resetLink;

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log('Mailer error: ' . $mail->ErrorInfo);
        return false;
    }
}
