<?php
try {
    $pdo = new PDO("mysql:host=31.97.70.114;dbname=hosthome;port=3306", "p11l0tF0rSt2g1ng", 'v2ueF$xD8l89');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $pdo->prepare("SHOW TABLES LIKE 'user_notifications'");
    $stmt->execute();
    $table = $stmt->fetch(PDO::FETCH_ASSOC);

    echo "Table check result:\n";
    print_r($table);
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
