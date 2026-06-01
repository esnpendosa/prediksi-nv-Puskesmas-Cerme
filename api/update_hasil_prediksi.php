<?php
header('Content-Type: application/json');
require_once '../config/koneksi.php';

$data = json_decode(file_get_contents('php://input'), true);

if(isset($data['id_prediksi']) && isset($data['hasil_prediksi'])) {
    $stmt = $pdo->prepare("UPDATE hasil_prediksi SET hasil_prediksi = ? WHERE id_prediksi = ?");
    if($stmt->execute([$data['hasil_prediksi'], $data['id_prediksi']])) {
        echo json_encode(['success' => true, 'message' => 'Data berhasil diperbarui']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Gagal memperbarui data']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Data tidak lengkap']);
}
?>
