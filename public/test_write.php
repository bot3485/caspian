<?php
$dir = __DIR__ . '/../storage/framework/views';
$testFile = tempnam($dir, 'test_');
if ($testFile) {
    echo "✅ PHP может писать в storage! Файл: " . $testFile;
    unlink($testFile);
} else {
    echo "❌ PHP НЕ МОЖЕТ писать в $dir. Ошибка: " . error_get_last()['message'];
}