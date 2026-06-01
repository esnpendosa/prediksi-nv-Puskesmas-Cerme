<?php
header('Content-Type: application/json');
require_once '../config/koneksi.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if($id) {
    $stmt = $pdo->prepare("DELETE FROM hasil_prediksi WHERE id_prediksi = ?");
    if($stmt->execute([$id])) {
        echo json_encode(['success' => true, 'message' => 'Data berhasil dihapus']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Gagal menghapus data']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'ID tidak valid']);
}
?>
