<?php
session_start();

if (isset($_SESSION['usuario'])) {
    header("Location: index.php");
    exit;
}

$error = '';

if ($_POST) {
    include "conexion.php";
    $user = $_POST['username'] ?? '';
    $pass = $_POST['password'] ?? '';

    if ($user && $pass) {
        $sql = "SELECT * FROM usuarios WHERE username='$user' AND password=MD5('$pass')";
        $result = $conn->query($sql);

        if ($result && $result->num_rows > 0) {
            $datos = $result->fetch_assoc();
            $_SESSION['usuario'] = $datos['username'];
            $_SESSION['tipo'] = $datos['tipo'];
            header("Location: index.php");
            exit;
        } else {
            $error = "Usuario o contraseña incorrectos";
        }
    } else {
        $error = "Complete todos los campos";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Login</title>
    <link rel="stylesheet" href="style.css">
    <style>
        body { display: flex; justify-content: center; align-items: center; min-height: 100vh; background: #f0f0f0; }
        .login-box { background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); width: 300px; }
        .login-box h2 { text-align: center; margin-bottom: 20px; }
        .login-box input { width: 100%; padding: 10px; margin: 10px 0; border: 1px solid #ddd; border-radius: 4px; }
        .login-box button { width: 100%; padding: 10px; background: #0ea5a4; color: white; border: none; border-radius: 4px; cursor: pointer; }
        .error { color: red; text-align: center; margin: 10px 0; }
        .demo { background: #f8f8f8; padding: 10px; margin-top: 15px; border-radius: 4px; font-size: 12px; }
    </style>
</head>
<body>
    <div class="login-box">
        <h2>Iniciar Sesión</h2>
        <?php if($error): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>

        <form method="POST">
            <input type="text" name="username" placeholder="Usuario" required>
            <input type="password" name="password" placeholder="Contraseña" required>
            <button type="submit">Entrar</button>
        </form>


    </div>
</body>
</html>