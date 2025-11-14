<?php
include '../includes/config.php';

// ATENÇÃO: Verificação de acesso (usuário logado E 2FA pendente)
if (!isset($_SESSION['id']) || !isset($_SESSION['2fa_pending'])) {
    header("Location: /newFinanceFlowapp/views/login.php");
    exit;
}

$user_id = $_SESSION['id'];
$code_input = trim($_POST['code'] ?? '');

$sql = "SELECT id, code_hash, expires_at FROM two_factor_codes WHERE user_id = ? ORDER BY created_at DESC LIMIT 1";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$stmt->close();

if (!$row) {
    header("Location: /newFinanceFlowapp/views/2fa.php?error=notfound");
    exit;
}

// ⚠️ CORREÇÃO DE SEGURANÇA: Usar declaração preparada para DELETE
$delete_stmt = $conn->prepare("DELETE FROM two_factor_codes WHERE user_id = ?");
$delete_stmt->bind_param("i", $user_id);

if (time() > strtotime($row['expires_at'])) {
    $delete_stmt->execute();
    $delete_stmt->close();
    header("Location: /newFinanceFlowapp/views/2fa.php?error=expired");
    exit;
}

if (password_verify($code_input, $row['code_hash'])) {
    $delete_stmt->execute();
    $delete_stmt->close();
    
    // Sucesso: Remove a flag de pendência e concede acesso total
    unset($_SESSION['2fa_pending']);
    $_SESSION['2fa_verified'] = true;

    header("Location: dashboard.php");
    exit;
} else {
    $delete_stmt->close(); // Fecha o statement DELETE não executado aqui

    // ⚠️ CORREÇÃO DE SEGURANÇA: Usar declaração preparada para UPDATE attempts
    $update_stmt = $conn->prepare("UPDATE two_factor_codes SET attempts = attempts + 1 WHERE id = ?");
    $update_stmt->bind_param("i", $row['id']);
    $update_stmt->execute();
    $update_stmt->close();

    header("Location: /newFinanceFlowapp/views/2fa.php?error=invalid");
    exit;
}
?>