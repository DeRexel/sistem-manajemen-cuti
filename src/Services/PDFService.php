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
        $pdf->SetFont('helvetica', '', 8);
        $pdf->Cell(0, 0, 'Palangka Raya, ' . date('d F Y', strtotime($data['tanggal_pengajuan'])), 0, 1, 'L');
        $pdf->Cell(0, 0, 'Kepada Yth.', 0, 1, 'L');
        $pdf->Cell(0, 0, $data['pejabat_nama'], 0, 1, 'L');
        $pdf->Cell(0, 0, 'Universitas Palangka Raya', 0, 1, 'L');
        $pdf->Cell(0, 0, 'di -', 0, 1, 'L');
        $pdf->Cell(0, 0, 'PALANGKA RAYA', 0, 1, 'L');
        
        // Title
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->Cell(0, 10, 'FORMULIR PERMINTAAN DAN PEMBERIAN CUTI', 0, 1, 'C');
        
        // Content
        $pdf->SetFont('helvetica', '', 9);
        
        // Calculate quota usage for cuti tahunan
        $diambil_n1 = 0;
        $diambil_n = 0;
        $sisa_n1 = $data['sisa_cuti_n1'];
        $sisa_n = $data['sisa_cuti_n'];
        
        if ($data['jenis_cuti'] == 'tahunan') {
            $lama_cuti = $data['lama_hari'];
            
            // First use N-1 quota
            if ($sisa_n1 > 0) {
                $diambil_n1 = min($lama_cuti, $sisa_n1);
                $sisa_n1 = $sisa_n1 - $diambil_n1;
                $lama_cuti = $lama_cuti - $diambil_n1;
            }
            
            // Then use N quota
            if ($lama_cuti > 0) {
                $diambil_n = min($lama_cuti, $sisa_n);
                $sisa_n = $sisa_n - $diambil_n;
            }
        }
        
        $masa_kerja_text = $data['masa_kerja_tahun'] . ' Tahun';
        if (isset($data['masa_kerja_bulan']) && $data['masa_kerja_bulan'] > 0) {
            $masa_kerja_text .= ' ' . $data['masa_kerja_bulan'] . ' Bulan';
        }
        
        // Section I - Data Pegawai
        $html = '
        <div style="width:100%;">
        <table border="1" cellpadding="3" width="100%">
            <tr><td colspan="4" style="background-color:#f0f0f0;"><b>I. DATA PEGAWAI</b></td></tr>
            <tr>
                <td width="15%">Nama</td>
                <td width="35%">' . $data['employee_nama'] . '</td>
                <td width="15%">NIP</td>
                <td width="35%">' . $data['employee_nip'] . '</td>
            </tr>
            <tr>
                <td width="15%">Jabatan</td>
                <td width="35%">' . $data['employee_jabatan'] . '</td>
                <td width="15%">Masa Kerja</td>
                <td width="35%">' . $masa_kerja_text . '</td>
            </tr>
            <tr>
                <td width="15%">Unit Kerja</td>
                <td colspan="3" width="85%">' . $data['employee_unit'] . ' - Universitas Palangka Raya</td>
            </tr>
        </table><br>
        
        <table border="1" cellpadding="3" width="100%">
            <tr><td colspan="4" style="background-color:#f0f0f0;"><b>II. JENIS CUTI YANG DIAMBIL</b></td></tr>
            <tr>
                <td width="40%">1. Cuti Tahunan</td>
                <td width="10%">' . ($data['jenis_cuti'] == 'tahunan' ? '<b style="text-align:center;">V</b>': '') . '</td>
                <td width="40%">2. Cuti Besar</td>
                <td width="10%">' . ($data['jenis_cuti'] == 'besar' ? '<b  style="text-align:center;">V</b>': '') . '</td>
            </tr>
            <tr>
                <td width="40%">3. Cuti Sakit</td>
                <td width="10%">' . ($data['jenis_cuti'] == 'sakit' ? '<b  style="text-align:center;">V</b>': '') . '</td>
                <td width="40%">4. Cuti Melahirkan</td>
                <td width="10%">' . ($data['jenis_cuti'] == 'melahirkan' ? '<b  style="text-align:center;">V</b>': '') . '</td>
            </tr>
            <tr>
                <td width="40%">5. Cuti Karena Alasan Penting</td>
                <td width="10%">' . ($data['jenis_cuti'] == 'alasan_penting' ? '<b  style="text-align:center;">V</b>': '') . '</td>
                <td width="40%">6. Cuti Diluar Tanggungan Negara</td>
                <td width="10%">' . ($data['jenis_cuti'] == 'diluar_tanggungan' ? '<b  style="text-align:center;">V</b>': '') . '</td>
            </tr>
        </table><br>
        
        <table border="1" cellpadding="3" width="100%">
            <tr><td style="background-color:#f0f0f0;"><b>III. ALASAN CUTI</b></td></tr>
            <tr><td style="text-align: justify;">' . $data['alasan'] . '</td></tr>
        </table><br>
        
        <table border="1" cellpadding="3" width="100%">
            <tr><td colspan="5" style="background-color:#f0f0f0;"><b>IV. LAMANYA CUTI</b></td></tr>
            <tr>
                <td width="20%" style="text-align:center;">' . $data['lama_hari'] . ' hari</td>
                <td width="20%" style="text-align:center;">mulai tanggal</td>
                <td width="20%" style="text-align:center;">' . date('d-m-Y', strtotime($data['tanggal_mulai'])) . '</td>
                <td width="10%" style="text-align:center;">s/d</td>
                <td width="30%" style="text-align:center;">' . date('d-m-Y', strtotime($data['tanggal_selesai'])) . '</td>
            </tr>
        </table><br>
        
        <table border="1" cellpadding="3" width="100%">
            <tr><td colspan="2" style="background-color:#f0f0f0;"><b>V. CATATAN CUTI</b></td></tr>
            <tr>
                <td width="50%" style="vertical-align:top;">
                    <b>1. CUTI TAHUNAN</b><br>
                    <table border="1" cellpadding="2" width="100%">
                        <tr style="text-align:center;"><td>Tahun</td><td>Lama</td><td>Diambil</td><td>Sisa</td></tr>
                        <tr style="text-align:center;"><td>N-2</td><td>' . $data['sisa_cuti_n2'] . '</td><td>0</td><td>' . $data['sisa_cuti_n2'] . '</td></tr>
                        <tr style="text-align:center;"><td>N-1</td><td>' . $data['sisa_cuti_n1'] . '</td><td>' . $diambil_n1 . '</td><td>' . $sisa_n1 . '</td></tr>
                        <tr style="text-align:center;"><td>N</td><td>' . $data['sisa_cuti_n'] . '</td><td>' . $diambil_n . '</td><td>' . $sisa_n . '</td></tr>
                    </table>
                </td>
                <td width="50%" style="vertical-align:top;">
                    <table border="1" cellpadding="2" width="100%">
                        <tr><td width="85%">2. CUTI BESAR</td><td width="15%"></td></tr>
                        <tr><td>3. CUTI SAKIT</td><td></td></tr>
                        <tr><td>4. CUTI MELAHIRKAN</td><td></td></tr>
                        <tr><td>5. CUTI KARENA ALASAN PENTING</td><td></td></tr>
                        <tr><td>6. CUTI DI LUAR TANGGUNGAN NEGARA</td><td></td></tr>
                    </table>
                </td>
            </tr>
        </table><br>
        
        <table border="1" cellpadding="3" width="100%">
            <tr><td colspan="2" style="background-color:#f0f0f0;"><b>VI. ALAMAT SELAMA MENJALANKAN CUTI</b></td></tr>
            <tr><td colspan="2">' . nl2br($data['alamat_cuti']) . '</td></tr>
            <tr><td width="70%"></td><td width="30%">Telp. ' . $data['telp_cuti'] . '</td></tr>
            <tr>
                <td></td>
                <td style="text-align:center; height:100px;">
                    Hormat Saya,<br><br><br><br><br>
                    ' . $data['employee_nama'] . '<br>
                    NIP. ' . $data['employee_nip'] . '
                </td>
            </tr>
        </table><br>
        
        <table border="1" cellpadding="3" width="100%">
            <tr><td colspan="4" style="background-color:#f0f0f0;"><b>VII. PERTIMBANGAN ATASAN LANGSUNG</b></td></tr>
            <tr>
                <td width="25%" style="text-align:center;">DISETUJUI</td>
                <td width="25%" style="text-align:center;">PERUBAHAN</td>
                <td width="25%" style="text-align:center;">DITANGGUHKAN</td>
                <td width="25%" style="text-align:center;">TIDAK DISETUJUI</td>
            </tr>
            <tr>
                <td colspan="2"></td>
                <td colspan="2" style="text-align:center; height:100px;">
                    ' . $data['pejabat_jabatan'] . '<br><br><br><br><br>
                    ' . $data['pejabat_nama'] . '<br>
                    NIP. ' . $data['pejabat_nip'] . '
                </td>
            </tr>
        </table>
        </div>';
        
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
        $pdf->SetFont('helvetica', '', 8);
        $pdf->Cell(0, 0, 'Palangka Raya, ' . date('d F Y', strtotime($data['tanggal_pengajuan'])), 0, 1, 'L');
        $pdf->Cell(0, 0, 'Kepada Yth.', 0, 1, 'L');
        $pdf->Cell(0, 0, $data['pejabat_nama'], 0, 1, 'L');
        $pdf->Cell(0, 0, 'Universitas Palangka Raya', 0, 1, 'L');
        $pdf->Cell(0, 0, 'di -', 0, 1, 'L');
        $pdf->Cell(0, 0, 'PALANGKA RAYA', 0, 1, 'L');
        
        // Title
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->Cell(0, 10, 'FORMULIR PERMINTAAN DAN PEMBERIAN CUTI', 0, 1, 'C');
        
        // Content with digital signature
        $pdf->SetFont('helvetica', '', 9);
        
        // Calculate quota usage for cuti tahunan
        $diambil_n1 = 0;
        $diambil_n = 0;
        $sisa_n1 = $data['sisa_cuti_n1'];
        $sisa_n = $data['sisa_cuti_n'];
        
        if ($data['jenis_cuti'] == 'tahunan') {
            $lama_cuti = $data['lama_hari'];
            
            // First use N-1 quota
            if ($sisa_n1 > 0) {
                $diambil_n1 = min($lama_cuti, $sisa_n1);
                $sisa_n1 = $sisa_n1 - $diambil_n1;
                $lama_cuti = $lama_cuti - $diambil_n1;
            }
            
            // Then use N quota
            if ($lama_cuti > 0) {
                $diambil_n = min($lama_cuti, $sisa_n);
                $sisa_n = $sisa_n - $diambil_n;
            }
        }
        
        $masa_kerja_text = $data['masa_kerja_tahun'] . ' Tahun';
        if (isset($data['masa_kerja_bulan']) && $data['masa_kerja_bulan'] > 0) {
            $masa_kerja_text .= ' ' . $data['masa_kerja_bulan'] . ' Bulan';
        }
        
        // Prepare signature image
        $signatureHtml = '';
        $digitalText = '';
        if ($data['digital_signature_path']) {
            $signaturePath = __DIR__ . '/../../public/uploads/employee_signatures/' . $data['digital_signature_path'];
            if (file_exists($signaturePath)) {
                $imageData = base64_encode(file_get_contents($signaturePath));
                $imageType = pathinfo($signaturePath, PATHINFO_EXTENSION);
                $signatureHtml = '<img src="data:image/' . $imageType . ';base64,' . $imageData . '" width="100" height="50" style="border:1px solid #ccc;">';
            }
        }
        
        $html = '
        <div style="width:100%;">
        <table border="1" cellpadding="3" width="100%">
            <tr><td colspan="4" style="background-color:#f0f0f0;"><b>I. DATA PEGAWAI</b></td></tr>
            <tr>
                <td width="15%">Nama</td>
                <td width="35%">' . $data['employee_nama'] . '</td>
                <td width="15%">NIP</td>
                <td width="35%">' . $data['employee_nip'] . '</td>
            </tr>
            <tr>
                <td width="15%">Jabatan</td>
                <td width="35%">' . $data['employee_jabatan'] . '</td>
                <td width="15%">Masa Kerja</td>
                <td width="35%">' . $masa_kerja_text . '</td>
            </tr>
            <tr>
                <td width="15%">Unit Kerja</td>
                <td colspan="3" width="85%">' . $data['employee_unit'] . ' - Universitas Palangka Raya</td>
            </tr>
        </table><br>
        
        <table border="1" cellpadding="3" width="100%">
            <tr><td colspan="4" style="background-color:#f0f0f0;"><b>II. JENIS CUTI YANG DIAMBIL</b></td></tr>
            <tr>
                <td width="40%">1. Cuti Tahunan</td>
                <td width="10%">' . ($data['jenis_cuti'] == 'tahunan' ? '<b  style="text-align:center;">V</b>': '') . '</td>
                <td width="40%">2. Cuti Besar</td>
                <td width="10%">' . ($data['jenis_cuti'] == 'besar' ? '<b  style="text-align:center;">V</b>': '') . '</td>
            </tr>
            <tr>
                <td width="40%">3. Cuti Sakit</td>
                <td width="10%">' . ($data['jenis_cuti'] == 'sakit' ? '<b  style="text-align:center;">V</b>': '') . '</td>
                <td width="40%">4. Cuti Melahirkan</td>
                <td width="10%">' . ($data['jenis_cuti'] == 'melahirkan' ? '<b  style="text-align:center;">V</b>': '') . '</td>
            </tr>
            <tr>
                <td width="40%">5. Cuti Karena Alasan Penting</td>
                <td width="10%">' . ($data['jenis_cuti'] == 'alasan_penting' ? '<b  style="text-align:center;">V</b>': '') . '</td>
                <td width="40%">6. Cuti Diluar Tanggungan Negara</td>
                <td width="10%">' . ($data['jenis_cuti'] == 'diluar_tanggungan' ? '<b  style="text-align:center;">V</b>': '') . '</td>
            </tr>
        </table><br>
        
        <table border="1" cellpadding="3" width="100%">
            <tr><td style="background-color:#f0f0f0;"><b>III. ALASAN CUTI</b></td></tr>
            <tr><td style="text-align: justify;">' . $data['alasan'] . '</td></tr>
        </table><br>
        
        <table border="1" cellpadding="3" width="100%">
            <tr><td colspan="5" style="background-color:#f0f0f0;"><b>IV. LAMANYA CUTI</b></td></tr>
            <tr>
                <td width="20%" style="text-align:center;">' . $data['lama_hari'] . ' hari</td>
                <td width="20%" style="text-align:center;">mulai tanggal</td>
                <td width="20%" style="text-align:center;">' . date('d-m-Y', strtotime($data['tanggal_mulai'])) . '</td>
                <td width="10%" style="text-align:center;">s/d</td>
                <td width="30%" style="text-align:center;">' . date('d-m-Y', strtotime($data['tanggal_selesai'])) . '</td>
            </tr>
        </table><br>
        
        <table border="1" cellpadding="3" width="100%">
            <tr><td colspan="2" style="background-color:#f0f0f0;"><b>V. CATATAN CUTI</b></td></tr>
            <tr>
                <td width="50%" style="vertical-align:top;">
                    <b>1. CUTI TAHUNAN</b><br>
                    <table border="1" cellpadding="2" width="100%">
                        <tr style="text-align:center;"><td>Tahun</td><td>Lama</td><td>Diambil</td><td>Sisa</td></tr>
                        <tr style="text-align:center;"><td>N-2</td><td>' . $data['sisa_cuti_n2'] . '</td><td>0</td><td>' . $data['sisa_cuti_n2'] . '</td></tr>
                        <tr style="text-align:center;"><td>N-1</td><td>' . $data['sisa_cuti_n1'] . '</td><td>' . $diambil_n1 . '</td><td>' . $sisa_n1 . '</td></tr>
                        <tr style="text-align:center;"><td>N</td><td>' . $data['sisa_cuti_n'] . '</td><td>' . $diambil_n . '</td><td>' . $sisa_n . '</td></tr>
                    </table>
                </td>
                <td width="50%" style="vertical-align:top;">
                    <table border="1" cellpadding="2" width="100%">
                        <tr><td width="85%">2. CUTI BESAR</td><td width="15%"></td></tr>
                        <tr><td>3. CUTI SAKIT</td><td></td></tr>
                        <tr><td>4. CUTI MELAHIRKAN</td><td></td></tr>
                        <tr><td>5. CUTI KARENA ALASAN PENTING</td><td></td></tr>
                        <tr><td>6. CUTI DI LUAR TANGGUNGAN NEGARA</td><td></td></tr>
                    </table>
                </td>
            </tr>
        </table><br>
        
        <table border="1" cellpadding="3" width="100%">
            <tr><td colspan="2" style="background-color:#f0f0f0;"><b>VI. ALAMAT SELAMA MENJALANKAN CUTI</b></td></tr>
            <tr><td colspan="2">' . nl2br($data['alamat_cuti']) . '</td></tr>
            <tr><td width="70%"></td><td width="30%">Telp. ' . $data['telp_cuti'] . '</td></tr>
            <tr>
                <td></td>
                <td style="text-align:center; height:100px;">
                    <strong>Hormat Saya,</strong><br><br>
                    ' . $signatureHtml . '<br>
                    <strong>' . $data['employee_nama'] . '</strong><br>
                    <strong>NIP. ' . $data['employee_nip'] . '</strong><br>
                    ' . $digitalText . '
                </td>
            </tr>
        </table><br>
        
        <table border="1" cellpadding="3" width="100%">
            <tr><td colspan="4" style="background-color:#f0f0f0;"><b>VII. PERTIMBANGAN ATASAN LANGSUNG</b></td></tr>
            <tr>
                <td width="25%" style="text-align:center;">DISETUJUI</td>
                <td width="25%" style="text-align:center;">PERUBAHAN</td>
                <td width="25%" style="text-align:center;">DITANGGUHKAN</td>
                <td width="25%" style="text-align:center;">TIDAK DISETUJUI</td>
            </tr>
            <tr>
                <td colspan="2"></td>
                <td colspan="2" style="text-align:center; height:100px;">
                    ' . $data['pejabat_jabatan'] . '<br><br><br><br><br>
                    ' . $data['pejabat_nama'] . '<br>
                    NIP. ' . $data['pejabat_nip'] . '
                </td>
            </tr>
        </table>
        </div>';
        
        $pdf->writeHTML($html, true, false, true, false, '');
        
        return $pdf->Output('', 'S');
    }
}