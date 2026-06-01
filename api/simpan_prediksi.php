<?php
header('Content-Type: application/json');
require_once '../config/koneksi.php';

if(session_status() === PHP_SESSION_NONE) { session_start(); }
if(!isset($_SESSION['user_id'])) {
    echo json_encode(['status'=>'error', 'message'=>'Unauthorized Access']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    $id_pasien = $input['id_pasien'] ?? null;
    $id_atribut = $input['id_atribut'] ?? null;
    $hasil_prediksi = $input['hasil_prediksi'] ?? null; 
    
    if ($id_pasien && $id_atribut && $hasil_prediksi) {
        try {
            $stmt = $pdo->prepare("INSERT INTO hasil_prediksi (id_pasien, id_atribut_kesehatan, tanggal_prediksi, hasil_prediksi) VALUES (?, ?, CURDATE(), ?)");
            $success = $stmt->execute([$id_pasien, $id_atribut, $hasil_prediksi]);
            
            if ($success) {
                echo json_encode(['status' => 'success', 'message' => 'Data hasil prediksi berhasil disimpan.']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Gagal mengubah status di database.']);
            }
        } catch (Exception $e) {
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Data POST tidak lengkap.']);
    }
}
?>
