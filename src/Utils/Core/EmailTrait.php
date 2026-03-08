<?php

namespace Utils\Core;

use PHPMailer\PHPMailer\PHPMailer;
use RuntimeException;

/**
 * Клас для работы с отправкой сообщений
 */
trait EmailTrait {
    public function send_mail(string $to, string $template, array $data = [], ?string $subject = null): bool {
        $mail = new PHPMailer(true);

        $mail->CharSet = 'UTF-8';
        $mail->isHTML();
        $mail->setFrom('no-reply@aiphra.com', 'Aiphra');
        $mail->addAddress($to);
        $mail->Subject = $this->resolveSubject($template, $subject, $data);

        $body = $this->renderTemplate($template, $data);
        $mail->Body = $body;
        $mail->AltBody = trim(strip_tags(str_ireplace(['<br>', '<br/>', '<br />'], PHP_EOL, $body)));

        $this->configureTransport($mail);

        return $mail->send();
    }

    private function configureTransport(PHPMailer $mail): void {
        $host = getenv('MAIL_HOST');
        $host = is_string($host) ? trim($host) : '';
        if ($host === '') {
            $mail->isMail();

            return;
        }

        $mail->isSMTP();
        $mail->Host = $host;
        $mail->Port = filter_var(getenv('MAIL_PORT'), FILTER_VALIDATE_INT);
        $mail->SMTPAuth = (bool)getenv('MAIL_SMTP_AUTH');
        $mail->Username = getenv('MAIL_USERNAME');
        $mail->Password = getenv('MAIL_PASSWORD');

        $encryption = getenv('MAIL_ENCRYPTION');
        $encryption = is_string($encryption) ? strtolower(trim($encryption)) : '';
        if ($encryption === 'ssl') {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        } elseif ($encryption === 'tls') {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        } elseif ($encryption !== '') {
            throw new RuntimeException('MAIL_ENCRYPTION must be tls, ssl, or empty.');
        }
    }

    private function resolveSubject(string $template, ?string $subject, array $data): string {
        if ($subject !== null && trim($subject) !== '') {
            return trim($subject);
        }

        if (isset($data['_subject']) && is_scalar($data['_subject'])) {
            $value = trim(sprintf('%s', $data['_subject']));
            if ($value !== '') {
                return $value;
            }
        }

        $default = getenv('MAIL_SUBJECT');
        $default = is_string($default) ? trim($default) : '';
        if ($default !== '') {
            return $default;
        }

        return 'Notification: ' . basename($template);
    }

    private function renderTemplate(string $template, array $data): string {
        $path = $this->resolveTemplatePath($template);
        $content = file_get_contents($path);
        if ($content === false) {
            throw new RuntimeException('Template read failed: ' . $path);
        }

        $result = preg_replace_callback('/{{\s*([a-zA-Z0-9_.-]+)\s*}}/', function (array $matches) use ($data): string {
            $path = $matches[1];
            if (array_key_exists($path, $data)) {
                $value = $data[$path];
            } else {
                $value = $data;
                foreach (explode('.', $path) as $key) {
                    if (!is_array($value) || !array_key_exists($key, $value)) {
                        $value = '';
                        break;
                    }
                    $value = $value[$key];
                }
            }

            if (is_scalar($value) || $value === null) {
                return htmlspecialchars(sprintf('%s', $value), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            }

            return '';
        }, $content);

        return is_string($result) ? $result : $content;
    }

    private function resolveTemplatePath(string $template): string {
        if ($template === '') {
            throw new RuntimeException('Template path is empty.');
        }

        $ext = strtolower(pathinfo($template, PATHINFO_EXTENSION));
        if ($ext !== 'html') {
            throw new RuntimeException('Template must be an .html file.');
        }

        $path = dirname(__DIR__, 2)
            . DIRECTORY_SEPARATOR . 'src'
            . DIRECTORY_SEPARATOR . 'templates'
            . DIRECTORY_SEPARATOR . ltrim($template, '/\\');

        if (is_file($path)) {
            $realPath = realpath($path);

            return $realPath !== false ? $realPath : $path;
        }

        throw new RuntimeException('Template not found: ' . $template);
    }
}