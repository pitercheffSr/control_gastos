<?php include '../includes/header.php'; ?>

<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header text-center">
                <h4>Inicio de Sesión</h4>
            </div>
            <div class="card-body">
                <?php
                session_start();
                if (isset($_SESSION['error_login'])) {
                    echo '<div class="alert alert-danger">' . $_SESSION['error_login'] . '</div>';
                    unset($_SESSION['error_login']);
                }
                ?>
                <form action="../procesar_login.php" method="POST">
                    <div class="mb-3">
                        <label for="email" class="form-label">Correo Electrónico</label>
                        <input type="email" class="form-control" id="email" name="email" required>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Contraseña</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary">Iniciar Sesión</button>
                    </div>
                    <div class="mt-3">
                        <a href="../index.php" class="btn btn-secondary w-100">Página Principal</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
