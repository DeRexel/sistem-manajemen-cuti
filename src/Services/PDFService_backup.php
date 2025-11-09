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
        <table border="1" cellpadding="3" width="100%">
            <tr><td colspan="4" style="background-color:#f0f0f0;"><b>I. DATA PEGAWAI</b></td></tr>
            <tr>
                <td width="20%">Nama</td>
                <td width="30%">' . $data['employee_nama'] . '</td>
                <td width="20%">NIP</td>
                <td width="30%">' . $data['employee_nip'] . '</td>
            </tr>
            <tr>
                <td width="20%">Jabatan</td>
                <td width="30%">' . $data['employee_jabatan'] . '</td>
                <td width="20%">Masa Kerja</td>
                <td width="30%">' . $data['masa_kerja_tahun'] . ' Tahun</td>
            </tr>
            <tr>
                <td width="20%">Unit Kerja</td>
                <td colspan="3" width="80%">' . $data['employee_unit'] . ' - Universitas Palangka Raya</td>
            </tr>
        </table><br>
        
        <table border="1" cellpadding="3" width="100%">
            <tr><td colspan="4" style="background-color:#f0f0f0;"><b>II. JENIS CUTI YANG DIAMBIL</b></td></tr>
            <tr>
                <td width="20%">1. Cuti Tahunan</td>
                <td width="30%">' . ($data['jenis_cuti'] == 'tahunan' ? '<b>V</b>' : '') . '</td>
                <td width="20%">2. Cuti Besar</td>
                <td width="30%">' . ($data['jenis_cuti'] == 'besar' ? '<b>V</b>' : '') . '</td>
            </tr>
            <tr>
                <td width="20%">3. Cuti Sakit</td>
                <td width="30%">' . ($data['jenis_cuti'] == 'sakit' ? '<b>V</b>' : '') . '</td>
                <td width="20%">4. Cuti Melahirkan</td>
                <td width="30%">' . ($data['jenis_cuti'] == 'melahirkan' ? '<b>V</b>' : '') . '</td>
            </tr>
            <tr>
                <td width="20%">5. Cuti Karena Alasan Penting</td>
                <td width="30%">' . ($data['jenis_cuti'] == 'alasan_penting' ? '<b>V</b>' : '') . '</td>
                <td width="20%">6. Cuti Diluar Tanggungan Negara</td>
                <td width="30%">' . ($data['jenis_cuti'] == 'diluar_tanggungan' ? '<b>V</b>' : '') . '</td>
            </tr>
        </table><br>
        
        <table border="1" cellpadding="3" width="100%">
            <tr><td style="background-color:#f0f0f0;"><b>III. ALASAN CUTI</b></td></tr>
            <tr><td>' . $data['alasan'] . '</td></tr>
        </table><br>
        
        <table border="1" cellpadding="3" width="100%">
            <tr><td colspan="5" style="background-color:#f0f0f0;"><b>IV. LAMANYA CUTI</b></td></tr>
            <tr>
                <td width="20%">' . $data['lama_hari'] . ' hari</td>
                <td width="20%">mulai tanggal</td>
                <td width="20%">' . date('d-m-Y', strtotime($data['tanggal_mulai'])) . '</td>
                <td width="10%">s/d</td>
                <td width="30%">' . date('d-m-Y', strtotime($data['tanggal_selesai'])) . '</td>
            </tr>
        </table><br>
        
        <table border="1" cellpadding="3" width="100%">
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
        
        <table border="1" cellpadding="3" width="100%">
            <tr><td colspan="2" style="background-color:#f0f0f0;"><b>VI. ALAMAT SELAMA MENJALANKAN CUTI</b></td></tr>
            <tr><td colspan="2">' . $data['alamat_cuti'] . '</td></tr>
            <tr><td width="70%"></td><td width="30%">Telp. ' . $data['telp_cuti'] . '</td></tr>
            <tr>
                <td></td>
                <td style="text-align:center;">
                    Hormat Saya,<br><br>
                    <div style="height:50px; border:1px dashed #ccc; margin:10px 0; display:flex; align-items:center; justify-content:center; color:#999;">
                        [Tanda Tangan]
                    </div>
                    ' . $data['employee_nama'] . '<br>
                    NIP. ' . $data['employee_nip'] . '
                </td>
            </tr>
        </table><br>
        
        <table border="1" cellpadding="3" width="100%">
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

    public function generateCutiFormWithDigitalSignature($data) {
        $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8');
        $pdf->SetCreator('SICUTI');
        $pdf->SetTitle('Formulir Cuti - Digital Signature');
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
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->Cell(0, 10, 'FORMULIR PERMINTAAN DAN PEMBERIAN CUTI', 0, 1, 'C');
        $pdf->Ln(5);
        
        // Content with digital signature
        $pdf->SetFont('helvetica', '', 8);
        
        // Prepare signature image
        $signatureHtml = '<div style="height:60px; border:2px dashed #666; margin:10px 0; display:flex; align-items:center; justify-content:center; color:#666; background-color:#f9f9f9;"><strong>[Tanda Tangan]</strong></div>';
        if ($data['digital_signature_path']) {
            $signaturePath = __DIR__ . '/../../public/uploads/employee_signatures/' . $data['digital_signature_path'];
            if (file_exists($signaturePath)) {
                // Convert image to base64 for embedding in PDF
                $imageData = base64_encode(file_get_contents($signaturePath));
                $imageType = pathinfo($signaturePath, PATHINFO_EXTENSION);
                $signatureHtml = '<img src="data:image/' . $imageType . ';base64,' . $imageData . '" width="100" height="50" style="border:1px solid #ccc;">';
            }
        }
        
        $html = '
        <table border="1" cellpadding="3" width="100%">
            <tr><td colspan="4" style="background-color:#f0f0f0;"><b>I. DATA PEGAWAI</b></td></tr>
            <tr>
                <td width="20%">Nama</td>
                <td width="30%">' . $data['employee_nama'] . '</td>
                <td width="20%">NIP</td>
                <td width="30%">' . $data['employee_nip'] . '</td>
            </tr>
            <tr>
                <td width="20%">Jabatan</td>
                <td width="30%">' . $data['employee_jabatan'] . '</td>
                <td width="20%">Masa Kerja</td>
                <td width="30%">' . $data['masa_kerja_tahun'] . ' Tahun</td>
            </tr>
            <tr>
                <td width="20%">Unit Kerja</td>
                <td colspan="3" width="80%">' . $data['employee_unit'] . ' - Universitas Palangka Raya</td>
            </tr>
        </table><br>
        
        <table border="1" cellpadding="3" width="100%">
            <tr><td colspan="4" style="background-color:#f0f0f0;"><b>II. JENIS CUTI YANG DIAMBIL</b></td></tr>
            <tr>
                <td width="20%">1. Cuti Tahunan</td>
                <td width="30%">' . ($data['jenis_cuti'] == 'tahunan' ? '<b>V</b>' : '') . '</td>
                <td width="20%">2. Cuti Besar</td>
                <td width="30%">' . ($data['jenis_cuti'] == 'besar' ? '<b>V</b>' : '') . '</td>
            </tr>
            <tr>
                <td width="20%">3. Cuti Sakit</td>
                <td width="30%">' . ($data['jenis_cuti'] == 'sakit' ? '<b>V</b>' : '') . '</td>
                <td width="20%">4. Cuti Melahirkan</td>
                <td width="30%">' . ($data['jenis_cuti'] == 'melahirkan' ? '<b>V</b>' : '') . '</td>
            </tr>
            <tr>
                <td width="20%">5. Cuti Karena Alasan Penting</td>
                <td width="30%">' . ($data['jenis_cuti'] == 'alasan_penting' ? '<b>V</b>' : '') . '</td>
                <td width="20%">6. Cuti Diluar Tanggungan Negara</td>
                <td width="30%">' . ($data['jenis_cuti'] == 'diluar_tanggungan' ? '<b>V</b>' : '') . '</td>
            </tr>
        </table><br>
        
        <table border="1" cellpadding="3" width="100%">
            <tr><td style="background-color:#f0f0f0;"><b>III. ALASAN CUTI</b></td></tr>
            <tr><td>' . $data['alasan'] . '</td></tr>
        </table><br>
        
        <table border="1" cellpadding="3" width="100%">
            <tr><td colspan="5" style="background-color:#f0f0f0;"><b>IV. LAMANYA CUTI</b></td></tr>
            <tr>
                <td width="20%">' . $data['lama_hari'] . ' hari</td>
                <td width="20%">mulai tanggal</td>
                <td width="20%">' . date('d-m-Y', strtotime($data['tanggal_mulai'])) . '</td>
                <td width="10%">s/d</td>
                <td width="30%">' . date('d-m-Y', strtotime($data['tanggal_selesai'])) . '</td>
            </tr>
        </table><br>
        
        <table border="1" cellpadding="3" width="100%">
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
        
        <table border="1" cellpadding="3" width="100%">
            <tr><td colspan="2" style="background-color:#f0f0f0;"><b>VI. ALAMAT SELAMA MENJALANKAN CUTI</b></td></tr>
            <tr><td colspan="2">' . $data['alamat_cuti'] . '</td></tr>
            <tr><td width="70%"></td><td width="30%">Telp. ' . $data['telp_cuti'] . '</td></tr>
            <tr>
                <td></td>
                <td style="text-align:center; padding:15px;">
                    <strong>Hormat Saya,</strong><br><br>
                    ' . $signatureHtml . '<br>
                    <strong>' . $data['employee_nama'] . '</strong><br>
                    <strong>NIP. ' . $data['employee_nip'] . '</strong><br>
                    <small style="color:blue; font-style:italic;">Ditandatangani secara digital</small>
                </td>
            </tr>
        </table><br>
        
        <table border="1" cellpadding="3" width="100%">
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