<?php
$password = 'admin123'; 

session_start();

if (!isset($_SESSION['authenticated'])) {
    if (isset($_POST['password']) && $_POST['password'] === $password) {
        $_SESSION['authenticated'] = true;
    } else {
        ?>
<!DOCTYPE html>
<html>

<head>
    <title>Login - View Contacts</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
            background: #f5f5f5;
        }

        form {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        input {
            padding: 10px;
            width: 250px;
            margin-bottom: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }

        button {
            padding: 10px 20px;
            background: #007bff;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
    </style>
</head>

<body>
    <form method="post">
        <h2>Login</h2>
        <input type="password" name="password" placeholder="Enter password" required>
        <button type="submit">Login</button>
    </form>
</body>

</html>
<?php
        exit;
    }
}

$jsonFile = __DIR__ . '/contacts.json';
$contacts = [];

if (file_exists($jsonFile)) {
    $jsonContent = file_get_contents($jsonFile);
    $contacts = json_decode($jsonContent, true) ?? [];
}

$contacts = array_reverse($contacts);
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Messages - Laboratoire Central</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            background: #f5f5f5;
            padding: 20px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        h1 {
            margin-bottom: 30px;
            color: #333;
        }

        .logout {
            float: right;
            padding: 8px 16px;
            background: #dc3545;
            color: white;
            text-decoration: none;
            border-radius: 4px;
        }

        .contact-card {
            background: #f8f9fa;
            padding: 20px;
            margin-bottom: 20px;
            border-radius: 8px;
            border-left: 4px solid #007bff;
        }

        .contact-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 15px;
        }

        .contact-name {
            font-size: 18px;
            font-weight: bold;
            color: #333;
        }

        .contact-date {
            color: #666;
            font-size: 14px;
        }

        .contact-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 10px;
            margin-bottom: 15px;
        }

        .info-item {
            display: flex;
            align-items: center;
            gap: 8px;
            color: #555;
        }

        .info-item i {
            color: #007bff;
        }

        .contact-message {
            background: white;
            padding: 15px;
            border-radius: 4px;
            margin-top: 15px;
        }

        .no-contacts {
            text-align: center;
            padding: 40px;
            color: #999;
        }

        .count {
            color: #666;
            margin-bottom: 20px;
        }
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>

<body>
    <div class="container">
        <h1>
            Messages de Contact
            <a href="?logout=1" class="logout">Déconnexion</a>
        </h1>

        <?php if (isset($_GET['logout'])): session_destroy(); header('Location: view_contacts.php'); exit; endif; ?>

        <p class="count">Total: <?php echo count($contacts); ?> message(s)</p>

        <?php if (empty($contacts)): ?>
        <div class="no-contacts">
            <i class="fas fa-inbox" style="font-size: 48px; margin-bottom: 20px;"></i>
            <p>Aucun message pour le moment</p>
        </div>
        <?php else: ?>
        <?php foreach ($contacts as $contact): ?>
        <div class="contact-card">
            <div class="contact-header">
                <div class="contact-name">
                    <i class="fas fa-user"></i> <?php echo htmlspecialchars($contact['name']); ?>
                </div>
                <div class="contact-date">
                    <i class="far fa-clock"></i> <?php echo htmlspecialchars($contact['submitted_at']); ?>
                </div>
            </div>

            <div class="contact-info">
                <div class="info-item">
                    <i class="fas fa-envelope"></i>
                    <a href="mailto:<?php echo htmlspecialchars($contact['email']); ?>">
                        <?php echo htmlspecialchars($contact['email']); ?>
                    </a>
                </div>
                <?php if (!empty($contact['phone'])): ?>
                <div class="info-item">
                    <i class="fas fa-phone"></i>
                    <a href="tel:<?php echo htmlspecialchars($contact['phone']); ?>">
                        <?php echo htmlspecialchars($contact['phone']); ?>
                    </a>
                </div>
                <?php endif; ?>
                <?php if (!empty($contact['subject'])): ?>
                <div class="info-item">
                    <i class="fas fa-tag"></i>
                    <?php echo htmlspecialchars($contact['subject']); ?>
                </div>
                <?php endif; ?>
            </div>

            <div class="contact-message">
                <strong><i class="fas fa-comment"></i> Message:</strong>
                <p style="margin-top: 10px; white-space: pre-wrap;"><?php echo htmlspecialchars($contact['message']); ?>
                </p>
            </div>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
    </div>
</body>

</html>