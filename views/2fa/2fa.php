<?php
include '../includes/config.php';

if (!isset($_SESSION['user_id'])) {
  header("Location: ../login.php");
  exit;
}

$user_id = $_SESSION['user_id'];

// Busca email e celular do usuário
$sql = "SELECT email, telefone_celular FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <title>Verificação em 2 Etapas</title>
  <link rel="stylesheet" href="../css/2fa.css">
</head>
<body>
  <div class="container">
    <h1>Verificação em Duas Etapas</h1>

    <?php if (isset($_GET['error'])): ?>
  <p style="color: #ff5252;">
    <?php
      if ($_GET['error'] === 'expired') echo "⚠️ O código expirou. Solicite um novo.";
      if ($_GET['error'] === 'invalid') echo "❌ Código incorreto. Tente novamente.";
      if ($_GET['error'] === 'notfound') echo "⚠️ Nenhum código foi gerado. Envie um novo.";
    ?>
  </p>
<?php endif; ?>


    <form action="send_2fa.php" method="post">
      <label>
        <input type="radio" name="method" value="email" checked>
        Enviar código para o e-mail: <?php echo htmlspecialchars($user['email']); ?>
      </label><br>
      <label>
        <input type="radio" name="method" value="sms">
        Enviar código para o celular: <?php echo htmlspecialchars($user['telefone_celular']); ?>
      </label><br><br>
      <button type="submit">Enviar Código</button>
    </form>

    <form action="verify_2fa.php" method="post" style="margin-top:20px;">
      <input type="text" name="code" placeholder="Digite o código recebido" required><br><br>
      <button type="submit">Verificar Código</button>
    </form>

    <p id="timer"></p>
  </div>

  <script src="2fa.js"></script>
  <script>initTTL(60);</script>
</body>
</html>