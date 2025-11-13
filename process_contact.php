<?php
header('Content-Type: application/json');

error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Method not allowed'
    ]);
    exit;
}

$smtpHost = 'mail.labocentralas.com';
$smtpPort = 587;
$smtpUser = 'noreply@labocentralas.com';
$smtpPass = '2NWdjewEEq';
$fromEmail = 'noreply@labocentralas.com';
$fromName = 'Laboratoire Central';
$notifyTo = 'saadmnhm@gmail.com';

$phpMailerAvailable = false;
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
    $phpMailerAvailable = class_exists('PHPMailer\PHPMailer\PHPMailer');
}

$name = isset($_POST['mf-text']) ? trim($_POST['mf-text']) : '';
$subject = isset($_POST['mf-text']) ? trim($_POST['mf-text']) : ''; 
$email = isset($_POST['mf-email']) ? trim($_POST['mf-email']) : '';
$phone = isset($_POST['mf-telephone']) ? trim($_POST['mf-telephone']) : '';
$message = isset($_POST['mf-textarea']) ? trim($_POST['mf-textarea']) : '';

$errors = [];

if (empty($name)) {
    $errors[] = 'Name is required';
}

if (empty($email)) {
    $errors[] = 'Email is required';
} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Invalid email format';
}
if (!empty($phone)) {
    if (!preg_match('/^\+?[0-9\-\s\(\)]+$/', $phone)) {
        $errors[] = 'Phone contains invalid characters';
    } else {
        $digits = preg_replace('/\D+/', '', $phone);
        if (strlen($digits) < 7 || strlen($digits) > 15) {
            $errors[] = 'Phone number length must be between 7 and 15 digits';
        }
    }
}
if (empty($message)) {
    $errors[] = 'Message is required';
}

if (!empty($errors)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Validation failed',
        'errors' => $errors
    ]);
    exit;
}

$contactData = [
    'id' => uniqid('contact_', true),
    'name' => htmlspecialchars($name, ENT_QUOTES, 'UTF-8'),
    'subject' => htmlspecialchars($subject, ENT_QUOTES, 'UTF-8'),
    'email' => htmlspecialchars($email, ENT_QUOTES, 'UTF-8'),
    'phone' => htmlspecialchars($phone, ENT_QUOTES, 'UTF-8'),
    'message' => htmlspecialchars($message, ENT_QUOTES, 'UTF-8'),
    'submitted_at' => date('Y-m-d H:i:s'),
];

$jsonFile = __DIR__ . '/contacts.json';

$contacts = [];
if (file_exists($jsonFile)) {
    $jsonContent = file_get_contents($jsonFile);
    $contacts = json_decode($jsonContent, true);
    
    if (!is_array($contacts)) {
        $contacts = [];
    }
}

$contacts[] = $contactData;

$jsonData = json_encode($contacts, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

if (file_put_contents($jsonFile, $jsonData, LOCK_EX) !== false) {
    $mailSent = false;
    $mailError = null;
    if ($phpMailerAvailable) {
        try {
            $mail = new PHPMailer\PHPMailer\PHPMailer(true);
            $mail->isSMTP();
            $mail->Host = $smtpHost;
            $mail->SMTPAuth = true;
            $mail->Username = $smtpUser;
            $mail->Password = $smtpPass;
            $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = $smtpPort;
            $mail->CharSet = 'UTF-8';

            $mail->setFrom($fromEmail, $fromName);
            $mail->addAddress($notifyTo);
            $mail->addReplyTo($contactData['email'], $contactData['name']);

            $mail->isHTML(true);
            $mail->Subject = 'Nouveau message de contact: ' . ($contactData['subject'] ?: 'Sans sujet');

            $body  = "<h3>Nouveau message de contact</h3>";
            $body .= "<p><strong>Nom:</strong> " . $contactData['name'] . "</p>";
            $body .= "<p><strong>Email:</strong> " . $contactData['email'] . "</p>";
            $body .= "<p><strong>Téléphone:</strong> " . $contactData['phone'] . "</p>";
            $body .= "<p><strong>Message:</strong><br>" . nl2br($contactData['message']) . "</p>";

            $logoPath = __DIR__ . '/assets/img/logo2.png';
            $logoCid = 'lab_logo_cid';
            if (file_exists($logoPath)) {
                try {
                    $mail->addEmbeddedImage($logoPath, $logoCid, 'logo2.png');
                    $body .= '<hr>';
                    $body .= '<p style="margin:0;">Cordialement,<br><strong>Laboratoire Central de Ain Sbaa</strong></p>';
                    $body .= '<p style="margin:6px 0 0;"><img src="cid:' . $logoCid . '" alt="Laboratoire Central" style="width:85px; height:auto;"></p>';
                } catch (\Exception $e) {
                    $body .= '<hr><p>Cordialement, Laboratoire Central de Ain Sbaa</p>';
                }
            } else {
                $body .= '<hr><p>Cordialement, Laboratoire Central de Ain Sbaa</p>';
            }

            $mail->Body = $body;
            $mail->AltBody = "Nom: {$contactData['name']}\nEmail: {$contactData['email']}\nPhone: {$contactData['phone']}\n\nMessage:\n{$contactData['message']}\n\nID: {$contactData['id']}";

            $mail->send();
            $mailSent = true;
        } catch (Exception $e) {
            $mailError = $mail->ErrorInfo ?? $e->getMessage();
        }
    }

    $response = [
        'success' => true,
        'message' => 'Votre message a été envoyé avec succès. Nous vous contacterons bientôt.',
        'contact_id' => $contactData['id'],
        'mail_sent' => $mailSent
    ];
    if (!$phpMailerAvailable) {
        $response['mail_warning'] = 'PHPMailer not installed. Run `composer require phpmailer/phpmailer` to enable email sending.';
    } elseif (!$mailSent && $mailError) {
        $response['mail_error'] = $mailError;
    }

    echo json_encode($response);
} else {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erreur lors de l\'enregistrement du message. Veuillez réessayer.'
    ]);
}
?>