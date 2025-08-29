<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Starting index.php...<br>";

try {
    echo "1. Including config...<br>";
    require_once 'includes/config.php';
    echo "✅ Config loaded<br>";
    
    echo "2. Including database...<br>";
    require_once 'includes/database.php';
    echo "✅ Database included<br>";
    
    echo "3. Creating Database instance...<br>";
    $db = new Database();
    echo "✅ Database instance created<br>";
    
    echo "4. Loading data...<br>";
    $categories = $db->getCategories();
    echo "✅ Categories loaded: " . count($categories) . "<br>";
    
    $provinces = $db->getProvinces();
    echo "✅ Provinces loaded: " . count($provinces) . "<br>";
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "<br>";
    echo "Stack trace:<br>" . nl2br($e->getTraceAsString()) . "<br>";
    exit;
}

?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Passione Calabria - Test</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    <div class="container mx-auto py-8">
        <h1 class="text-3xl font-bold mb-4">Passione Calabria - Test Page</h1>
        
        <div class="bg-blue-500 text-white p-4 rounded mb-4">
            <?php echo "✅ PHP execution successful!"; ?>
        </div>
        
        <!-- Test Header Include -->
        <div class="bg-green-500 text-white p-4 rounded mb-4">
            <h2 class="text-xl font-bold mb-2">Testing Header Include:</h2>
            <?php 
            try {
                echo "Including header...<br>";
                include 'includes/header.php'; 
                echo "<br>✅ Header included successfully!";
            } catch (Exception $e) {
                echo "❌ Header error: " . $e->getMessage();
            }
            ?>
        </div>
        
        <div class="bg-purple-500 text-white p-4 rounded">
            <h2 class="text-xl font-bold">Data Summary:</h2>
            <p>Categories found: <?php echo count($categories ?? []); ?></p>
            <p>Provinces found: <?php echo count($provinces ?? []); ?></p>
        </div>
    </div>
</body>
</html>