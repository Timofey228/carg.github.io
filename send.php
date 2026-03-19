<?php
require __DIR__ . '/vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

define('SMTP_HOST', 'smtp.yandex.ru');
define('SMTP_PORT', 465);
define('SMTP_USER', 'gru2operevozcki@yandex.ru');
define('SMTP_PASS', 'timkasimka228');
define('SMTP_FROM', 'gru2operevozcki@yandex.ru');
define('SMTP_FROM_NAME', 'Грузоперевозки');
define('ADMIN_EMAIL', 'timka.simka228@gmail.com');

session_start();

function generateCsrfToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCsrfToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.html');
    exit;
}

$name        = isset($_POST['name']) ? trim(strip_tags($_POST['name'])) : '';
$phone       = isset($_POST['phone']) ? trim(strip_tags($_POST['phone'])) : '';
$email       = isset($_POST['email']) ? trim(strip_tags($_POST['email'])) : '';
$from_city   = isset($_POST['from_city']) ? trim(strip_tags($_POST['from_city'])) : '';
$to_city     = isset($_POST['to_city']) ? trim(strip_tags($_POST['to_city'])) : '';
$weight      = isset($_POST['weight']) ? floatval($_POST['weight']) : 0;
$volume      = isset($_POST['volume']) ? floatval($_POST['volume']) : 0;
$cargo_type  = isset($_POST['cargo_type']) ? trim(strip_tags($_POST['cargo_type'])) : '';
$message     = isset($_POST['message']) ? trim(strip_tags($_POST['message'])) : '';
$website     = isset($_POST['website']) ? trim($_POST['website']) : ''; // Honeypot
$csrf_token  = isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '';

if (!empty($website)) {
    http_response_code(403);
    die('Spam detected.');
}

if (!verifyCsrfToken($csrf_token)) {
    http_response_code(403);
    die('Invalid CSRF token.');
}

$errors = [];
if (empty($name)) {
    $errors[] = 'Не указано имя';
}
if (empty($phone)) {
    $errors[] = 'Не указан телефон';
}
if (empty($from_city)) {
    $errors[] = 'Не указан город отправления';
}
if (empty($to_city)) {
    $errors[] = 'Не указан город назначения';
}

if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Некорректный email';
}


if (!empty($errors)) {
    echo '<h2>Ошибки в форме:</h2><ul>';
    foreach ($errors as $error) {
        echo '<li>' . htmlspecialchars($error) . '</li>';
    }
    echo '</ul><a href="javascript:history.back()">Вернуться</a>';
    exit;
}

$body = "Новая заявка на грузоперевозку:\n\n";
$body .= "Имя: $name\n";
$body .= "Телефон: $phone\n";
if (!empty($email)) {
    $body .= "Email: $email\n";
}
$body .= "Откуда: $from_city\n";
$body .= "Куда: $to_city\n";
if ($weight > 0) {
    $body .= "Вес: $weight кг\n";
}
if ($volume > 0) {
    $body .= "Объём: $volume м³\n";
}
if (!empty($cargo_type)) {
    $body .= "Тип груза: $cargo_type\n";
}
if (!empty($message)) {
    $body .= "Дополнительно:\n$message\n";
}

// 5. Отправка через PHPMailer
$mail = new PHPMailer(true);
try {
    // Серверные настройки
    $mail->isSMTP();
    $mail->Host       = SMTP_HOST;
    $mail->SMTPAuth   = true;
    $mail->Username   = SMTP_USER;
    $mail->Password   = SMTP_PASS;
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS; // или ENCRYPTION_STARTTLS
    $mail->Port       = SMTP_PORT;

    // Отправитель
    $mail->setFrom(SMTP_FROM, SMTP_FROM_NAME);
    // Получатель
    $mail->addAddress(ADMIN_EMAIL);
    // Тема
    $mail->Subject = 'Заявка на грузоперевозку от ' . $name;
    // Тело письма (текстовое)
    $mail->Body    = $body;

    $mail->send();

    // Успех – можно сделать редирект на страницу "спасибо"
    header('Location: thanks.html');
    exit;

} catch (Exception $e) {
    // Ошибка отправки
    http_response_code(500);
    echo "Не удалось отправить заявку. Ошибка: " . $mail->ErrorInfo;
    // Для отладки можно залогировать ошибку, но не показывать детали пользователю
    error_log("Mailer Error: " . $mail->ErrorInfo);
}