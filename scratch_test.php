<?php
try {
    $pdo = new PDO("mysql:host=31.97.70.114;dbname=hosthome;port=3306", "p11l0tF0rSt2g1ng", 'v2ueF$xD8l89');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $pdo->prepare("SELECT * FROM user_notifications ORDER BY id DESC LIMIT 5");
    $stmt->execute();
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "Latest notifications in DB:\n";
    print_r($notifications);
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
