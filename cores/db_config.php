<?php
// cores/db_config.php
$host = '127.0.0.1';
$dbname = 'tanda'; 
$username = 'root'; 
$password = '0705.KenBen'; 

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    date_default_timezone_set('Asia/Ho_Chi_Minh');

    // Kéo toàn bộ Settings ra một mảng Global để xài chung cho Header/Footer
    $stmt = $conn->query("SELECT setting_key, setting_value FROM settings");
    $sys_settings = [];
    while ($row = $stmt->fetch()) {
        $sys_settings[$row['setting_key']] = $row['setting_value'];
    }

} catch(PDOException $e) {
    die("LỖI DATABASE: " . $e->getMessage());
}
?>