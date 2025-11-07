// Date calculation for leave form
function calculateDays() {
    const startDate = document.getElementById('tanggal_mulai');
    const endDate = document.getElementById('tanggal_selesai');
    const lamaHari = document.getElementById('lama_hari');
    
    if (startDate && endDate && startDate.value && endDate.value) {
        const start = new Date(startDate.value);
        const end = new Date(endDate.value);
        const diffTime = Math.abs(end - start);
        const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24)) + 1;
        
        if (lamaHari) {
            lamaHari.value = diffDays;
        }
    }
}

// File upload handler
function handleFileUpload(inputId, cutiId, type) {
    const input = document.getElementById(inputId);
    const file = input.files[0];
    
    if (file) {
        const formData = new FormData();
        formData.append('signed_form', file);
        
        const endpoint = type === 'employee' 
            ? `/user/cuti/upload-signed/${cutiId}`
            : `/admin/cuti/upload-atasan/${cutiId}`;
        
        fetch(endpoint, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('File berhasil diupload');
                location.reload();
            } else {
                alert('Gagal upload file');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Terjadi kesalahan');
        });
    }
}

// Status update handler
function updateStatus(cutiId, status, persetujuan = null, catatan = null) {
    const formData = new FormData();
    formData.append('status', status);
    
    if (persetujuan) {
        formData.append('persetujuan_atasan', persetujuan);
    }
    
    if (catatan) {
        formData.append('catatan_atasan', catatan);
    }
    
    fetch(`/admin/cuti/update-status/${cutiId}`, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Status berhasil diupdate');
            location.reload();
        } else {
            alert('Gagal update status');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Terjadi kesalahan');
    });
}

// Initialize date inputs
document.addEventListener('DOMContentLoaded', function() {
    const startDate = document.getElementById('tanggal_mulai');
    const endDate = document.getElementById('tanggal_selesai');
    
    if (startDate) {
        startDate.addEventListener('change', calculateDays);
    }
    
    if (endDate) {
        endDate.addEventListener('change', calculateDays);
    }
});