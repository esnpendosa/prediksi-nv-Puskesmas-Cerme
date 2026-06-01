<?php
header('Content-Type: application/json');
require_once '../config/koneksi.php';

$jsonInput = file_get_contents('php://input');
$data = json_decode($jsonInput, true);

if (!isset($data['id_atribut'])) {
    echo json_encode(['success' => false, 'message' => 'ID Atribut missing']);
    exit;
}

$id_atribut = $data['id_atribut'];

// 1. Ambil Data Uji
$stmt = $pdo->prepare("SELECT p.*, ak.*, (YEAR(ak.tanggal_pemeriksaan) - YEAR(p.tanggal_lahir)) AS umur 
                      FROM pasien p JOIN atribut_kesehatan ak ON p.id_pasien = ak.id_pasien 
                      WHERE ak.id_atribut = ?");
$stmt->execute([$id_atribut]);
$data_uji = $stmt->fetch(PDO::FETCH_ASSOC);

// 2. Ambil Data Training
$query_train = "SELECT hp.hasil_prediksi, ak.*, (YEAR(ak.tanggal_pemeriksaan) - YEAR(p.tanggal_lahir)) AS umur 
               FROM hasil_prediksi hp
               JOIN atribut_kesehatan ak ON hp.id_atribut_kesehatan = ak.id_atribut
               JOIN pasien p ON hp.id_pasien = p.id_pasien";
$data_training = $pdo->query($query_train)->fetchAll(PDO::FETCH_ASSOC);

// 3. Persiapkan untuk Python
$payload = [
    'data_uji' => $data_uji,
    'data_training' => $data_training
];

$tempFile = '../ml/temp_' . uniqid() . '.json';
file_put_contents($tempFile, json_encode($payload));

// 4. Jalankan Python
// Pastikan path ke python benar. Di Windows biasanya 'python'.
$command = "python ../ml/naive_bayes.py " . escapeshellarg($tempFile);
$output = shell_exec($command);

// 5. Cleanup
if (file_exists($tempFile)) unlink($tempFile);

// 6. Return Result
if ($output) {
    $result = json_decode($output, true);
    if (isset($result['error'])) {
        echo json_encode(['success' => false, 'message' => 'Python Error: ' . $result['error']]);
    } else {
        echo json_encode(['success' => true, 'result' => $result]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to execute Python script']);
}
