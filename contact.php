<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Load private email configuration
$mailUsername = getenv('MAIL_USERNAME');
$mailPassword = getenv('MAIL_PASSWORD');

if (!$mailUsername || !$mailPassword) {
    die("Mail configuration is missing.");
}

// Load PHPMailer
require __DIR__ . '/vendor/autoload.php';

// Database connection
include __DIR__ . '/db.php';


if ($_SERVER["REQUEST_METHOD"] === "POST") {

    // Get form data
    $name = trim($_POST["name"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $phone = trim($_POST["phone"] ?? "");
    $message = trim($_POST["message"] ?? "");


    // -----------------------------
    // BASIC VALIDATION
    // -----------------------------

    if (empty($name) || empty($email) || empty($message)) {
        die("Please fill in all required fields.");
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        die("Please enter a valid email address.");
    }


    // -----------------------------
    // SAVE DATA INTO MYSQL
    // -----------------------------

    $sql = "INSERT INTO contact (name, email, phone, message)
            VALUES (?, ?, ?, ?)";

    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        die("SQL Error: " . $conn->error);
    }

    $stmt->bind_param(
        "ssss",
        $name,
        $email,
        $phone,
        $message
    );


    if ($stmt->execute()) {

        // -----------------------------
        // SEND EMAIL USING PHPMailer
        // -----------------------------

        $mail = new PHPMailer(true);

        try {

            // Gmail SMTP
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;

            // Get credentials from config.local.php
            $mail->Username = $mailUsername;
            $mail->Password = $mailPassword;

            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;


            // -----------------------------
            // SENDER
            // -----------------------------

            $mail->setFrom(
                $mailUsername,
                'My Portfolio Website'
            );


            // -----------------------------
            // RECEIVE EMAIL
            // -----------------------------

            $mail->addAddress(
                $mailUsername,
                'Portfolio Owner'
            );


            // -----------------------------
            // REPLY TO VISITOR
            // -----------------------------

            $mail->addReplyTo(
                $email,
                $name
            );


            // -----------------------------
            // EMAIL FORMAT
            // -----------------------------

            $mail->isHTML(true);

            $mail->Subject = 'New Contact Form Message';


            // Prevent HTML injection
            $safeName = htmlspecialchars(
                $name,
                ENT_QUOTES,
                'UTF-8'
            );

            $safeEmail = htmlspecialchars(
                $email,
                ENT_QUOTES,
                'UTF-8'
            );

            $safePhone = htmlspecialchars(
                $phone,
                ENT_QUOTES,
                'UTF-8'
            );

            $safeMessage = nl2br(
                htmlspecialchars(
                    $message,
                    ENT_QUOTES,
                    'UTF-8'
                )
            );


            // -----------------------------
            // HTML EMAIL BODY
            // -----------------------------

            $mail->Body = "
                <h2>New Contact Form Message</h2>

                <p>
                    <strong>Name:</strong>
                    {$safeName}
                </p>

                <p>
                    <strong>Email:</strong>
                    {$safeEmail}
                </p>

                <p>
                    <strong>Phone:</strong>
                    {$safePhone}
                </p>

                <p>
                    <strong>Message:</strong>
                </p>

                <p>
                    {$safeMessage}
                </p>

                <hr>

                <p>
                    This message was sent from your portfolio website.
                </p>
            ";


            // -----------------------------
            // PLAIN TEXT EMAIL
            // -----------------------------

            $mail->AltBody =
                "New Contact Form Message\n\n" .
                "Name: $name\n" .
                "Email: $email\n" .
                "Phone: $phone\n\n" .
                "Message:\n$message";


            // -----------------------------
            // SEND EMAIL
            // -----------------------------

            $mail->send();

            echo "Message sent successfully!";


        } catch (Exception $e) {

            echo "Data was saved, but the email could not be sent.";

            // For debugging only:
            // echo "<br>Mailer Error: " . $mail->ErrorInfo;
        }


    } else {

        echo "Insert failed: " . $stmt->error;
    }


    // Close database connection
    $stmt->close();
    $conn->close();


} else {

    echo "Invalid request.";
}

?>