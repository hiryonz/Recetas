<?php
require_once __DIR__ . '/../../config/config.php';

$title = "Recuperar Contraseña"; 

ob_start();

session_start();
if (isset($_SESSION['mensaje'])): ?>
    <div class="alert">
        <?php echo htmlspecialchars ($_SESSION['mensaje']); ?>
    </div>
<?php unset($_SESSION['mensaje']); endif; ?>


<div class="container-login">
    <h2>Recuperar Contraseña</h2>
    <form action="../../controllers/AuthController.php" method="POST">
        <label for="email">Correo Electrónico</label>
        <input type="email" id="email" name="email" placeholder="Ingresa tu correo" required>

        <button type="submit" name="action" value="forgot_password">Enviar Enlace de Recuperación</button>
    </form>
    <p><a href="login.php">Volver al inicio de sesión</a></p>
</div>

<?php
$content = ob_get_clean(); 
include BASE_PATH . '/views/layout.php';
