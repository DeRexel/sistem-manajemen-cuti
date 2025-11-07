<?php
namespace App\Services;

use TCPDF;

class PDFService {
    public function generateCutiForm($data) {
        $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8');
        $pdf->SetCreator('SICUTI');
        $pdf->SetTitle('Formulir Cuti');
        $pdf->SetMargins(20, 20, 20);
        $pdf->AddPage();
        
        // Header
        $pdf->SetFont('helvetica', '', 10);
        $pdf->Cell(0, 5, 'Palangka Raya, ' . date('d F Y', strtotime($data['tanggal_pengajuan'])), 0, 1, 'R');
        $pdf->Cell(0, 5, 'Kepada Yth.', 0, 1, 'R');
        $pdf->Cell(0, 5, $data['pejabat_nama'], 0, 1, 'R');
        $pdf->Cell(0, 5, 'Universitas Palangka Raya', 0, 1, 'R');
        $pdf->Cell(0, 5, 'di -', 0, 1, 'R');
        $pdf->Cell(0, 5, 'PALANGKA RAYA', 0, 1, 'R');
        $pdf->Ln(10);
        
        // Title
        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->Cell(0, 10, 'FORMULIR PERMINTAAN DAN PEMBERIAN CUTI', 0, 1, 'C');
        $pdf->Ln(5);
        
        // Content
        $pdf->SetFont('helvetica', '', 9);
        
        // Section I - Data Pegawai
        $html = '
        <table border="1" cellpadding="3">
            <tr><td colspan="4" style="background-color:#f0f0f0;"><b>I. DATA PEGAWAI</b></td></tr>
            <tr>
                <td width="15%">Nama</td>
                <td width="35%">' . $data['employee_nama'] . '</td>
                <td width="15%">NIP</td>
                <td width="35%">' . $data['employee_nip'] . '</td>
            </tr>
            <tr>
                <td>Jabatan</td>
                <td>' . $data['employee_jabatan'] . '</td>
                <td>Masa Kerja</td>
                <td>' . $data['masa_kerja_tahun'] . ' Tahun</td>
            </tr>
            <tr>
                <td>Unit Kerja</td>
                <td colspan="3">' . $data['employee_unit'] . ' - Universitas Palangka Raya</td>
            </tr>
        </table><br>
        
        <table border="1" cellpadding="3">
            <tr><td colspan="4" style="background-color:#f0f0f0;"><b>II. JENIS CUTI YANG DIAMBIL</b></td></tr>
            <tr>
                <td width="40%">1. Cuti Tahunan</td>
                <td width="10%">' . ($data['jenis_cuti'] == 'tahunan' ? '✓' : '') . '</td>
                <td width="40%">2. Cuti Besar</td>
                <td width="10%">' . ($data['jenis_cuti'] == 'besar' ? '✓' : '') . '</td>
            </tr>
            <tr>
                <td>3. Cuti Sakit</td>
                <td>' . ($data['jenis_cuti'] == 'sakit' ? '✓' : '') . '</td>
                <td>4. Cuti Melahirkan</td>
                <td>' . ($data['jenis_cuti'] == 'melahirkan' ? '✓' : '') . '</td>
            </tr>
            <tr>
                <td>5. Cuti Karena Alasan Penting</td>
                <td>' . ($data['jenis_cuti'] == 'alasan_penting' ? '✓' : '') . '</td>
                <td>6. Cuti Diluar Tanggungan Negara</td>
                <td>' . ($data['jenis_cuti'] == 'diluar_tanggungan' ? '✓' : '') . '</td>
            </tr>
        </table><br>
        
        <table border="1" cellpadding="3">
            <tr><td style="background-color:#f0f0f0;"><b>III. ALASAN CUTI</b></td></tr>
            <tr><td>' . $data['alasan'] . '</td></tr>
        </table><br>
        
        <table border="1" cellpadding="3">
            <tr><td colspan="5" style="background-color:#f0f0f0;"><b>IV. LAMANYA CUTI</b></td></tr>
            <tr>
                <td width="20%">' . $data['lama_hari'] . ' hari</td>
                <td width="20%">mulai tanggal</td>
                <td width="20%">' . date('d-m-Y', strtotime($data['tanggal_mulai'])) . '</td>
                <td width="10%">s/d</td>
                <td width="30%">' . date('d-m-Y', strtotime($data['tanggal_selesai'])) . '</td>
            </tr>
        </table><br>
        
        <table border="1" cellpadding="3">
            <tr><td colspan="2" style="background-color:#f0f0f0;"><b>V. CATATAN CUTI</b></td></tr>
            <tr>
                <td width="50%">
                    <b>1. CUTI TAHUNAN</b><br>
                    <table border="1" cellpadding="2">
                        <tr><td>Tahun</td><td>Sisa</td><td>Keterangan</td></tr>
                        <tr><td>N-2</td><td>' . $data['sisa_cuti_n2'] . '</td><td></td></tr>
                        <tr><td>N-1</td><td>' . $data['sisa_cuti_n1'] . '</td><td></td></tr>
                        <tr><td>N</td><td>' . $data['sisa_cuti_n'] . '</td><td></td></tr>
                    </table>
                </td>
                <td width="50%">
                    2. CUTI BESAR<br>
                    3. CUTI SAKIT<br>
                    4. CUTI MELAHIRKAN<br>
                    5. CUTI KARENA ALASAN PENTING<br>
                    6. CUTI DI LUAR TANGGUNGAN NEGARA
                </td>
            </tr>
        </table><br>
        
        <table border="1" cellpadding="3">
            <tr><td colspan="2" style="background-color:#f0f0f0;"><b>VI. ALAMAT SELAMA MENJALANKAN CUTI</b></td></tr>
            <tr><td colspan="2">' . $data['alamat_cuti'] . '</td></tr>
            <tr><td width="70%"></td><td width="30%">Telp. ' . $data['telp_cuti'] . '</td></tr>
            <tr>
                <td></td>
                <td style="text-align:center;">
                    Hormat Saya,<br><br><br>
                    ' . $data['employee_nama'] . '<br>
                    NIP. ' . $data['employee_nip'] . '
                </td>
            </tr>
        </table><br>
        
        <table border="1" cellpadding="3">
            <tr><td colspan="4" style="background-color:#f0f0f0;"><b>VII. PERTIMBANGAN ATASAN LANGSUNG</b></td></tr>
            <tr>
                <td width="25%">DISETUJUI</td>
                <td width="25%">PERUBAHAN</td>
                <td width="25%">DITANGGUHKAN</td>
                <td width="25%">TIDAK DISETUJUI</td>
            </tr>
            <tr>
                <td colspan="3"></td>
                <td style="text-align:center;">
                    ' . $data['pejabat_jabatan'] . '<br><br><br>
                    ' . $data['pejabat_nama'] . '
                </td>
            </tr>
        </table>';
        
        $pdf->writeHTML($html, true, false, true, false, '');
        
        return $pdf->Output('', 'S');
    }
}