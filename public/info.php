<?php
echo "<h1>PHP está funcionando!</h1>";
echo "<p>Versão: " . phpversion() . "</p>";
echo "<p>Caminho atual: " . __DIR__ . "</p>";
echo "<p>REQUEST_URI: " . $_SERVER['REQUEST_URI'] . "</p>";
echo "<p>SCRIPT_NAME: " . $_SERVER['SCRIPT_NAME'] . "</p>";
echo "<hr>";
echo "<h2>Teste de rotas:</h2>";
echo "<ul>";
echo "<li><a href='/Gestor-php/public/'>Home (router)</a></li>";
echo "<li><a href='/Gestor-php/public/login'>Login (router)</a></li>";
echo "<li><a href='/Gestor-php/public/test.php'>Test.php (direto)</a></li>";
echo "</ul>";
