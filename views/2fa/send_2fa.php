<?php
include '../includes/config.php';

if (!isset($_SESSION['user_id'])) {
  header("Location: ../login.php");
  exit;
}

$user_id = $_SESSION['user_id'];
$method = $_POST['method'] ?? 'email';
$code = rand(100000, 999999);
$expires_at = date('Y-m-d H:i:s', time() + 60); // 1 minuto
$code_hash = password_hash($code, PASSWORD_DEFAULT);

// Garante que a tabela exista
$conn->query("CREATE TABLE IF NOT EXISTS two_factor_codes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  code_hash VARCHAR(255) NOT NULL,
  method ENUM('email','sms') DEFAULT 'email',
  expires_at DATETIME NOT NULL,
  attempts INT DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
)");

// Remove códigos anteriores
$conn->query("DELETE FROM two_factor_codes WHERE user_id = $user_id");

// Insere novo código
$stmt = $conn->prepare("INSERT INTO two_factor_codes (user_id, code_hash, method, expires_at) VALUES (?, ?, ?, ?)");
$stmt->bind_param("isss", $user_id, $code_hash, $method, $expires_at);
$stmt->execute();
$stmt->close();

// Aqui você poderá implementar o envio real via e-mail/SMS futuramente.
// Por enquanto, apenas redireciona para a tela de verificação.
header("Location: 2fa.php");
exit;