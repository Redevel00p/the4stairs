<?php
/**
 * THE 4 STAIRS MUSIC HALL - REPORT EXPORTER
 * ----------------------------------------
 * File ini digunakan untuk mengekspor data pembeli tiket, laporan keuangan,
 * dan daftar event dalam format Excel (CSV) atau PDF (layout cetak).
 */

session_start();

// Periksa login admin
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    die("Akses ditolak. Silakan login sebagai admin.");
}

include 'koneksi.php';

// Validasi & Ambil Parameter
$type = isset($_GET['type']) ? trim($_GET['type']) : 'buyers';
$format = isset($_GET['format']) ? trim($_GET['format']) : 'csv';
$start_date = isset($_GET['start_date']) && !empty($_GET['start_date']) ? $_GET['start_date'] : '2000-01-01';
$end_date = isset($_GET['end_date']) && !empty($_GET['end_date']) ? $_GET['end_date'] : '2099-12-31';

// Setup timezone & format tanggal
date_default_timezone_set('Asia/Jakarta');
$date_now = date('d-m-Y H:i');

// Cek koneksi database
if (!$db_selected || $conn->connect_error) {
    die("Error: Koneksi ke database terputus.");
}

$harga_tiket = 75000; // Harga tiket per kursi

// --------------------------------------------------------------------------
// PROSES EKSPOR CSV (MICROSOFT EXCEL COMPATIBLE)
// --------------------------------------------------------------------------
if ($format === 'csv') {
    // Bersihkan buffer output
    if (ob_get_length()) ob_end_clean();
    
    // Set headers untuk download file CSV
    $filename = "T4S_Ekspor_" . ucfirst($type) . "_" . date('Ymd_His') . ".csv";
    header('Content-Encoding: UTF-8');
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    
    $output = fopen('php://output', 'w');
    
    // Kirim Byte Order Mark (BOM) agar MS Excel mendeteksi UTF-8 dengan benar
    fputs($output, "\xEF\xBB\xBF");
    
    if ($type === 'buyers') {
        // Headers
        fputcsv($output, ['ID Tiket', 'Nama Pemesan', 'Email', 'No WhatsApp', 'Jumlah Tiket', 'Status Pembayaran', 'Hari Event', 'Tanggal Event', 'Nama Event', 'Waktu Pemesanan']);
        
        $sql = "SELECT p.id_pesanan, p.nama, p.email, p.no_wa, p.jumlah_tiket, p.status_pembayaran, p.waktu_pesan, j.hari, j.tanggal, j.nama_event 
                FROM `pesanan` p 
                JOIN `jadwal` j ON p.id_jadwal = j.id 
                WHERE j.tanggal BETWEEN ? AND ? 
                ORDER BY p.waktu_pesan DESC";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $start_date, $end_date);
        $stmt->execute();
        $res = $stmt->get_result();
        
        while ($row = $res->fetch_assoc()) {
            fputcsv($output, [
                $row['id_pesanan'],
                $row['nama'],
                $row['email'],
                $row['no_wa'],
                $row['jumlah_tiket'],
                $row['status_pembayaran'],
                $row['hari'],
                date('d-m-Y', strtotime($row['tanggal'])),
                $row['nama_event'],
                date('d-m-Y H:i', strtotime($row['waktu_pesan']))
            ]);
        }
        $stmt->close();
        
    } elseif ($type === 'finance') {
        // Headers
        fputcsv($output, ['ID Tiket', 'Nama Pembayar', 'Hari Show', 'Tanggal Show', 'Nama Event', 'Jumlah Tiket', 'Harga Tiket', 'Total Pembayaran (IDR)', 'Status', 'Waktu Pesan']);
        
        $sql = "SELECT p.id_pesanan, p.nama, p.jumlah_tiket, p.status_pembayaran, p.waktu_pesan, j.hari, j.tanggal, j.nama_event 
                FROM `pesanan` p 
                JOIN `jadwal` j ON p.id_jadwal = j.id 
                WHERE j.tanggal BETWEEN ? AND ? AND p.status_pembayaran IN ('Lunas - Belum Dipakai', 'Sudah Dipakai')
                ORDER BY j.tanggal ASC";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $start_date, $end_date);
        $stmt->execute();
        $res = $stmt->get_result();
        
        $grand_total = 0;
        $total_tickets = 0;
        
        while ($row = $res->fetch_assoc()) {
            $total_bayar = intval($row['jumlah_tiket']) * $harga_tiket;
            $grand_total += $total_bayar;
            $total_tickets += intval($row['jumlah_tiket']);
            
            fputcsv($output, [
                $row['id_pesanan'],
                $row['nama'],
                $row['hari'],
                date('d-m-Y', strtotime($row['tanggal'])),
                $row['nama_event'],
                $row['jumlah_tiket'],
                $harga_tiket,
                $total_bayar,
                $row['status_pembayaran'],
                date('d-m-Y H:i', strtotime($row['waktu_pesan']))
            ]);
        }
        $stmt->close();
        
        // Output Row Summary
        fputcsv($output, []);
        fputcsv($output, ['', '', '', '', 'TOTAL TIKET TERJUAL', $total_tickets]);
        fputcsv($output, ['', '', '', '', 'TOTAL PENDAPATAN LUNAS (IDR)', $grand_total]);
        
    } elseif ($type === 'events') {
        // Headers
        fputcsv($output, ['ID Event', 'Hari', 'Tanggal', 'Jam Show', 'Nama Event', 'Status Reservasi', 'Kuota Kursi', 'Tiket Terjual', 'Sisa Kursi', 'Pendapatan Terkonfirmasi (IDR)', 'Event Spesial (1=Ya)']);
        
        $sql = "SELECT *, (terjual * ?) as pendapatan 
                FROM `jadwal` 
                WHERE tanggal BETWEEN ? AND ? 
                ORDER BY tanggal ASC";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iss", $harga_tiket, $start_date, $end_date);
        $stmt->execute();
        $res = $stmt->get_result();
        
        $total_terjual_events = 0;
        $total_pendapatan_events = 0;
        
        while ($row = $res->fetch_assoc()) {
            $sisa = $row['kuota'] - $row['terjual'];
            $total_terjual_events += $row['terjual'];
            $total_pendapatan_events += $row['pendapatan'];
            
            fputcsv($output, [
                $row['id'],
                $row['hari'],
                date('d-m-Y', strtotime($row['tanggal'])),
                $row['jam'],
                $row['nama_event'],
                $row['status'],
                $row['kuota'],
                $row['terjual'],
                $sisa,
                $row['pendapatan'],
                $row['is_special']
            ]);
        }
        $stmt->close();
        
        // Output Row Summary
        fputcsv($output, []);
        fputcsv($output, ['', '', '', '', 'TOTAL SEMUA TIKET TERJUAL', $total_terjual_events]);
        fputcsv($output, ['', '', '', '', 'TOTAL PENDAPATAN SEMUA EVENT (IDR)', $total_pendapatan_events]);
    }
    
    fclose($output);
    exit;
}

// --------------------------------------------------------------------------
// PROSES EKSPOR PDF (PREMIUM PRINT-FRIENDLY VIEW)
// --------------------------------------------------------------------------
if ($format === 'pdf'):
    // Kumpulkan data dari DB
    $data_rows = [];
    $range_text = "Semua Tanggal";
    if ($start_date !== '2000-01-01' || $end_date !== '2099-12-31') {
        $range_text = date('d M Y', strtotime($start_date)) . " s/d " . date('d M Y', strtotime($end_date));
    }
    
    if ($type === 'buyers') {
        $title_report = "Laporan Data Pembeli Tiket";
        $sql = "SELECT p.*, j.hari, j.tanggal, j.nama_event FROM `pesanan` p 
                JOIN `jadwal` j ON p.id_jadwal = j.id 
                WHERE j.tanggal BETWEEN ? AND ? 
                ORDER BY p.waktu_pesan DESC";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $start_date, $end_date);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) {
            $data_rows[] = $row;
        }
        $stmt->close();
        
    } elseif ($type === 'finance') {
        $title_report = "Laporan Arus Keuangan (Lunas)";
        $sql = "SELECT p.*, j.hari, j.tanggal, j.nama_event FROM `pesanan` p 
                JOIN `jadwal` j ON p.id_jadwal = j.id 
                WHERE j.tanggal BETWEEN ? AND ? AND p.status_pembayaran IN ('Lunas - Belum Dipakai', 'Sudah Dipakai') 
                ORDER BY j.tanggal ASC";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $start_date, $end_date);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) {
            $data_rows[] = $row;
        }
        $stmt->close();
        
    } elseif ($type === 'events') {
        $title_report = "Laporan Jadwal & Keterisian Event";
        $sql = "SELECT * FROM `jadwal` 
                WHERE tanggal BETWEEN ? AND ? 
                ORDER BY tanggal ASC";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $start_date, $end_date);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) {
            $data_rows[] = $row;
        }
        $stmt->close();
    }
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cetak Laporan - The 4 Stairs</title>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Outfit:wght@600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            color: #1c1917;
            background-color: #fff;
            margin: 0;
            padding: 30px;
            font-size: 11px;
            line-height: 1.4;
        }
        .header {
            border-bottom: 2px solid #dfb15b;
            padding-bottom: 20px;
            margin-bottom: 25px;
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
        }
        .header-logo {
            font-family: 'Outfit', sans-serif;
            font-size: 18px;
            font-weight: 700;
            letter-spacing: 1px;
            color: #090706;
        }
        .header-logo span {
            color: #8b1e22;
        }
        .report-title {
            font-size: 16px;
            font-weight: 750;
            color: #090706;
            margin: 0 0 5px 0;
            text-transform: uppercase;
        }
        .report-meta {
            font-size: 10px;
            color: #57534e;
        }
        .table-data {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }
        .table-data th, .table-data td {
            border: 1px solid #e7e5e4;
            padding: 8px 10px;
            text-align: left;
        }
        .table-data th {
            background-color: #f5f5f4;
            font-weight: 600;
            color: #090706;
            text-transform: uppercase;
            font-size: 9px;
            letter-spacing: 0.5px;
        }
        .table-data tr:nth-child(even) {
            background-color: #fafaf9;
        }
        .badge {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 4px;
            font-weight: 600;
            font-size: 8px;
            text-transform: uppercase;
            border: 1px solid transparent;
        }
        .badge.lunas {
            background-color: #d1fae5;
            color: #065f46;
            border-color: #a7f3d0;
        }
        .badge.pending {
            background-color: #fef3c7;
            color: #92400e;
            border-color: #fde68a;
        }
        .summary-box {
            float: right;
            width: 250px;
            border: 1px solid #e7e5e4;
            border-radius: 6px;
            background-color: #fafaf9;
            padding: 12px;
        }
        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 6px;
            font-size: 11px;
        }
        .summary-row.total {
            border-top: 1px dashed #d6d3d1;
            padding-top: 6px;
            font-weight: 700;
            font-size: 12px;
            color: #8b1e22;
        }
        .footer-print {
            position: fixed;
            bottom: 30px;
            left: 30px;
            right: 30px;
            display: flex;
            justify-content: space-between;
            font-size: 9px;
            color: #a8a29e;
            border-top: 1px solid #e7e5e4;
            padding-top: 10px;
        }
        .print-btn-container {
            margin-bottom: 20px;
            text-align: right;
        }
        .btn-print {
            background-color: #dfb15b;
            color: #090706;
            border: none;
            padding: 8px 16px;
            font-weight: 600;
            border-radius: 4px;
            font-size: 11px;
            cursor: pointer;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .btn-print:hover {
            background-color: #cda24c;
        }
        @media print {
            .print-btn-container {
                display: none;
            }
            body {
                padding: 0;
            }
        }
    </style>
</head>
<body>

    <!-- Print Action Trigger -->
    <div class="print-btn-container">
        <button class="btn-print" onclick="window.print()">Cetak / Simpan PDF</button>
    </div>

    <!-- Header -->
    <div class="header">
        <div>
            <h1 class="report-title"><?php echo $title_report; ?></h1>
            <div class="report-meta">
                Periode: <strong><?php echo $range_text; ?></strong> | Diekspor: <?php echo $date_now; ?> WIB
            </div>
        </div>
        <div class="text-right">
            <div class="header-logo">THE 4 <span>STAIRS</span></div>
            <div class="report-meta" style="margin-top: 3px;">Concert Venue & Music Hall</div>
        </div>
    </div>

    <!-- Data Table -->
    <?php if (empty($data_rows)): ?>
        <p style="text-align: center; font-style: italic; color: #78716c; padding: 40px 0;">Tidak ditemukan data dalam range tanggal yang dipilih.</p>
    <?php else: ?>
        <table class="table-data">
            <thead>
                <?php if ($type === 'buyers'): ?>
                    <tr>
                        <th style="width: 10%">ID Tiket</th>
                        <th style="width: 15%">Nama Pemesan</th>
                        <th style="width: 20%">Email</th>
                        <th style="width: 12%">No WhatsApp</th>
                        <th style="width: 8%">Jumlah</th>
                        <th style="width: 20%">Event Acara</th>
                        <th style="width: 10%">Waktu Event</th>
                        <th style="width: 5%">Status</th>
                    </tr>
                <?php elseif ($type === 'finance'): ?>
                    <tr>
                        <th style="width: 12%">ID Tiket</th>
                        <th style="width: 18%">Nama Pembayar</th>
                        <th style="width: 30%">Detail Event</th>
                        <th style="width: 10%">Hari & Tanggal</th>
                        <th style="width: 8%">Jumlah</th>
                        <th style="width: 12%">Harga Tiket</th>
                        <th style="width: 10%; text-align: right;">Total (IDR)</th>
                    </tr>
                <?php elseif ($type === 'events'): ?>
                    <tr>
                        <th style="width: 8%">ID Event</th>
                        <th style="width: 12%">Hari & Tanggal</th>
                        <th style="width: 10%">Jam Show</th>
                        <th style="width: 32%">Nama Event / Show Title</th>
                        <th style="width: 10%">Status</th>
                        <th style="width: 8%">Kuota</th>
                        <th style="width: 8%">Terjual</th>
                        <th style="width: 12%; text-align: right;">Pendapatan (IDR)</th>
                    </tr>
                <?php endif; ?>
            </thead>
            <tbody>
                <?php 
                $sum_tickets = 0;
                $sum_amount = 0;
                
                foreach ($data_rows as $row): 
                ?>
                    <?php if ($type === 'buyers'): ?>
                        <tr>
                            <td class="font-mono" style="font-weight: 600; color:#8b1e22;"><?php echo $row['id_pesanan']; ?></td>
                            <td style="font-weight: 500;"><?php echo htmlspecialchars($row['nama']); ?></td>
                            <td><?php echo htmlspecialchars($row['email']); ?></td>
                            <td><?php echo htmlspecialchars($row['no_wa']); ?></td>
                            <td><strong><?php echo $row['jumlah_tiket']; ?> Kursi</strong></td>
                            <td><?php echo htmlspecialchars($row['nama_event']); ?></td>
                            <td><?php echo $row['hari'] . ", " . date('d-m-Y', strtotime($row['tanggal'])); ?></td>
                            <td>
                                <span class="badge <?php echo strtolower($row['status_pembayaran']); ?>">
                                    <?php echo $row['status_pembayaran']; ?>
                                </span>
                            </td>
                        </tr>
                    <?php elseif ($type === 'finance'): 
                        $total_row_bayar = intval($row['jumlah_tiket']) * $harga_tiket;
                        $sum_tickets += intval($row['jumlah_tiket']);
                        $sum_amount += $total_row_bayar;
                    ?>
                        <tr>
                            <td class="font-mono" style="font-weight: 600; color:#8b1e22;"><?php echo $row['id_pesanan']; ?></td>
                            <td style="font-weight: 500;"><?php echo htmlspecialchars($row['nama']); ?></td>
                            <td><?php echo htmlspecialchars($row['nama_event']); ?></td>
                            <td><?php echo $row['hari'] . ", " . date('d-m-Y', strtotime($row['tanggal'])); ?></td>
                            <td><?php echo $row['jumlah_tiket']; ?> Kursi</td>
                            <td>Rp <?php echo number_format($harga_tiket, 0, ',', '.'); ?></td>
                            <td style="text-align: right; font-weight: 600;">Rp <?php echo number_format($total_row_bayar, 0, ',', '.'); ?></td>
                        </tr>
                    <?php elseif ($type === 'events'): 
                        $sum_tickets += $row['terjual'];
                        $sum_amount += intval($row['terjual']) * $harga_tiket;
                    ?>
                        <tr>
                            <td>#EV-<?php echo $row['id']; ?></td>
                            <td><?php echo $row['hari'] . ", " . date('d-m-Y', strtotime($row['tanggal'])); ?></td>
                            <td><?php echo htmlspecialchars($row['jam']); ?></td>
                            <td style="font-weight: 600;"><?php echo htmlspecialchars($row['nama_event']); ?> 
                                <?php if($row['is_special'] == 1): ?>
                                    <span style="font-size:8px; background-color:#fef3c7; color:#b45309; border: 1px solid #fde68a; padding:1px 4px; border-radius:3px; font-weight:bold; margin-left:5px;">SPECIAL</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge <?php echo strtolower($row['status']); ?>">
                                    <?php echo $row['status']; ?>
                                </span>
                            </td>
                            <td><?php echo $row['kuota']; ?></td>
                            <td><strong><?php echo $row['terjual']; ?></strong></td>
                            <td style="text-align: right; font-weight: 600;">Rp <?php echo number_format($row['terjual'] * $harga_tiket, 0, ',', '.'); ?></td>
                        </tr>
                    <?php endif; ?>
                <?php endforeach; ?>
            </tbody>
        </table>

        <!-- Summary Drawer -->
        <?php if ($type === 'finance' || $type === 'events'): ?>
            <div class="summary-box">
                <div class="summary-row">
                    <span>Total Karcis Terjual:</span>
                    <strong><?php echo $sum_tickets; ?> Kursi</strong>
                </div>
                <div class="summary-row total">
                    <span>Total Pendapatan:</span>
                    <span>Rp <?php echo number_format($sum_amount, 0, ',', '.'); ?></span>
                </div>
            </div>
        <?php endif; ?>
    <?php endif; ?>

    <!-- Signature Footer -->
    <div class="footer-print">
        <div>Laporan ini dihasilkan secara otomatis oleh sistem administrasi The 4 Stairs Music Hall.</div>
        <div>Halaman 1 dari 1</div>
    </div>

</body>
</html>
<?php endif; ?>
