<?php

/**
 * Script de diagnóstico para probar endpoints
 * NO incluyas en producción. Solo para debugging local.
 * Accede via: http://localhost/control_gastos/test_endpoints.php
 */

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Test de Endpoints - Control Gastos</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .test-box { border: 1px solid #ccc; padding: 15px; margin: 10px 0; border-radius: 5px; }
        .success { background: #d4edda; border-color: #28a745; }
        .error { background: #f8d7da; border-color: #dc3545; }
        .info { background: #d1ecf1; border-color: #0c5460; }
        button { padding: 10px 15px; margin: 5px; cursor: pointer; }
        pre { background: #f5f5f5; padding: 10px; overflow-x: auto; max-height: 300px; }
    </style>
</head>
<body>

<h1>🔧 Test de Endpoints</h1>

<div class="test-box info">
    <h3>Estado de Sesión</h3>
    <p>Session ID: <?php echo session_id(); ?></p>
    <p>Usuario autenticado: <?php echo isset($_SESSION['usuario_id']) ? 'Sí (ID: ' . $_SESSION['usuario_id'] . ')' : 'No'; ?></p>
</div>

<div class="test-box">
    <h3>Test 1: api_transacciones.php</h3>
    <button onclick="testApi()">Probar API</button>
    <pre id="api-result"></pre>
</div>

<div class="test-box">
    <h3>Test 2: get_transaccion.php</h3>
    <input type="number" id="transaccion-id" placeholder="ID transacción" value="1">
    <button onclick="testGetTransaccion()">Probar GET</button>
    <pre id="get-result"></pre>
</div>

<div class="test-box">
    <h3>Test 3: procesar_transaccion_editar.php</h3>
    <p>Este test requiere una transacción válida. Primero carga la lista con Test 1.</p>
    <input type="number" id="edit-id" placeholder="ID transacción" value="1">
    <button onclick="testEditTransaccion()">Probar EDIT</button>
    <pre id="edit-result"></pre>
</div>

<script>
async function testApi() {
    const el = document.getElementById('api-result');
    try {
        const resp = await fetch('api_transacciones.php');
        const data = await resp.json();
        el.textContent = JSON.stringify(data, null, 2);
        el.parentElement.classList.remove('error');
        el.parentElement.classList.add('success');
    } catch (err) {
        el.textContent = 'ERROR: ' + err.message;
        el.parentElement.classList.remove('success');
        el.parentElement.classList.add('error');
    }
}

async function testGetTransaccion() {
    const id = document.getElementById('transaccion-id').value;
    const el = document.getElementById('get-result');
    try {
        const resp = await fetch('get_transaccion.php?id=' + id);
        const data = await resp.json();
        el.textContent = JSON.stringify(data, null, 2);
        el.parentElement.classList.remove('error');
        el.parentElement.classList.add('success');
    } catch (err) {
        el.textContent = 'ERROR: ' + err.message;
        el.parentElement.classList.remove('success');
        el.parentElement.classList.add('error');
    }
}

async function testEditTransaccion() {
    const id = document.getElementById('edit-id').value;
    const el = document.getElementById('edit-result');
    const payload = {
        id: id,
        fecha: '2025-12-06',
        descripcion: 'Test de edición',
        monto: '99.99',
        tipo: 'gasto',
        categoria: '',
        subcategoria: '',
        subsub: ''
    };
    
    try {
        const resp = await fetch('procesar_transaccion_editar.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(payload)
        });
        const data = await resp.json();
        el.textContent = JSON.stringify(data, null, 2);
        el.parentElement.classList.remove('error');
        el.parentElement.classList.add('success');
    } catch (err) {
        el.textContent = 'ERROR: ' + err.message;
        el.parentElement.classList.remove('success');
        el.parentElement.classList.add('error');
    }
}
</script>

</body>
</html>
