<?php
include '../includes/config.php';

// Verifica se o usuário está logado
if (!isset($_SESSION['user_id'])) {
  header("Location: /newFinanceFlowapp/views/login.php");
  exit;
}

$user_id = $_SESSION['user_id'];
$code_input = trim($_POST['code'] ?? '');

// Busca o último código ativo do usuário
$sql = "SELECT * FROM two_factor_codes WHERE user_id = ? ORDER BY created_at DESC LIMIT 1";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$stmt->close();

// Nenhum código encontrado
if (!$row) {
  header("Location: /newFinanceFlowapp/2fa/2fa.php?error=notfound");
  exit;
}

// Código expirado
if (time() > strtotime($row['expires_at'])) {
  $conn->query("DELETE FROM two_factor_codes WHERE user_id = $user_id");
  header("Location: /newFinanceFlowapp/2fa/2fa.php?error=expired");
  exit;
}

// Código correto
if (password_verify($code_input, $row['code_hash'])) {
  $conn->query("DELETE FROM two_factor_codes WHERE user_id = $user_id");
  $_SESSION['2fa_verified'] = true;

  // ✅ Redireciona para o dashboard
  header("Location: /newFinanceFlowapp/dashboard.php");
  exit;
} else {
  // Código incorreto
  $conn->query("UPDATE two_factor_codes SET attempts = attempts + 1 WHERE id = {$row['id']}");
  header("Location: /newFinanceFlowapp/2fa/2fa.php?error=invalid");
  exit;
}