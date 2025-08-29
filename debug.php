<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!DOCTYPE html><html><head><title>Debug</title></head><body>";
echo "<h1>Debug Information</h1>";

echo "<h2>Basic PHP Info</h2>";
echo "PHP Version: " . PHP_VERSION . "<br>";
echo "Server Software: " . ($_SERVER['SERVER_SOFTWARE'] ?? 'N/A') . "<br>";

echo "<h2>Current Working Directory</h2>";
echo "CWD: " . getcwd() . "<br>";
echo "Script: " . (__FILE__) . "<br>";

echo "<h2>File Existence Checks</h2>";
echo "includes/config.php exists: " . (file_exists('includes/config.php') ? 'YES' : 'NO') . "<br>";
echo "includes/database.php exists: " . (file_exists('includes/database.php') ? 'YES' : 'NO') . "<br>";
echo "includes/header.php exists: " . (file_exists('includes/header.php') ? 'YES' : 'NO') . "<br>";
echo "passione_calabria.db exists: " . (file_exists('passione_calabria.db') ? 'YES' : 'NO') . "<br>";

echo "<h2>Include Test</h2>";
try {
    echo "Testing config.php include...<br>";
    require_once 'includes/config.php';
    echo "✅ Config included successfully<br>";
    
    echo "Testing database.php include...<br>";
    require_once 'includes/database.php';
    echo "✅ Database class included successfully<br>";
    
    echo "Testing database connection...<br>";
    $db = new Database();
    echo "✅ Database object created successfully<br>";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
    echo "Trace: " . $e->getTraceAsString() . "<br>";
}

echo "<h2>$_SERVER Variables (first few)</h2>";
$serverVars = array_slice($_SERVER, 0, 10, true);
foreach ($serverVars as $key => $value) {
    echo htmlspecialchars($key) . " = " . htmlspecialchars($value) . "<br>";
}

echo "</body></html>";
?>