<?php
require_once('../controller/koneksi/config.php');
require_once(__DIR__ . '/../vendor/tecnickcom/tcpdf/tcpdf.php');
require_once __DIR__ . '/../vendor/autoload.php';

class VerifikasiPermohonan {
    private $conn;

    public function __construct($conn) {
        $this->conn = $conn;
    }

    public function prosesVerifikasi($idPermohonan) {
        if (!isset($idPermohonan) || empty($idPermohonan)) {
            echo "Error: ID permohonan tidak valid.";
            return;
        }

        $query = "SELECT p.nomer_registrasi, p.nama_pengguna, p.opd_yang_dituju, p.tanggal_permohonan, r.nik, r.foto_ktp, r.email, r.no_hp, r.alamat, p.informasi_yang_dibutuhkan, p.alasan_pengguna_informasi
                  FROM registrasi r
                  JOIN permohonan_informasi p ON p.id_user = r.nik
                  WHERE p.id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $idPermohonan);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $nomorRegistrasi = $row['nomer_registrasi'];

            $cekVerifikasiQuery = "SELECT * FROM verifikasi_permohonan WHERE nomer_registrasi = ?";
            $stmtCek = $this->conn->prepare($cekVerifikasiQuery);
            $stmtCek->bind_param("s", $nomorRegistrasi);
            $stmtCek->execute();
            $resultCek = $stmtCek->get_result();

            if ($resultCek->num_rows > 0) {
                echo "Error: Data dengan nomor registrasi $nomorRegistrasi sudah terverifikasi sebelumnya.";
                return;
            }

            $insertQuery = "INSERT INTO verifikasi_permohonan (nomer_registrasi, nama_pengguna, tanggal_permohonan, nik, foto_ktp, email, no_hp, alamat, informasi_yang_dibutuhkan, alasan_pengguna_informasi, id_permohonan, opd_yang_dituju)
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmtInsert = $this->conn->prepare($insertQuery);
            $stmtInsert->bind_param(
                "ssssssssssis",
                $nomorRegistrasi, $row['nama_pengguna'], $row['tanggal_permohonan'], $row['nik'],
                $row['foto_ktp'], $row['email'], $row['no_hp'], $row['alamat'],
                $row['informasi_yang_dibutuhkan'], $row['alasan_pengguna_informasi'], $idPermohonan,
                $row['opd_yang_dituju']
            );

            if ($stmtInsert->execute()) {
                echo $nomorRegistrasi;
                include('../controller/pdfGenerate.php');
            } else {
                echo "Error: " . $stmtInsert->error;
            }
        } else {
            echo "Error: Nomor registrasi tidak ditemukan.";
        }
    }
}

// Membuat objek VerifikasiPermohonan
$verifikasi = new VerifikasiPermohonan($conn);

// Memproses verifikasi
if (isset($_POST['id'])) {
    $idPermohonan = $_POST['id'];
    $verifikasi->prosesVerifikasi($idPermohonan);
}
?>
