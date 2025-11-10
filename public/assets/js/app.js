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
        
        const xhr = new XMLHttpRequest();
        xhr.open('POST', endpoint, true);
        xhr.onload = function() {
            location.reload();
        };
        xhr.onerror = function() {
            location.reload();
        };
        xhr.send(formData);
    }
}

// Status update handler
function updateStatus(cutiId, status, persetujuan = null, catatan = null) {
    let body = `status=${status}`;
    if (persetujuan) body += `&persetujuan_atasan=${persetujuan}`;
    if (catatan) body += `&catatan_atasan=${encodeURIComponent(catatan)}`;
    
    const xhr = new XMLHttpRequest();
    xhr.open('POST', `/admin/cuti/${cutiId}/status`, true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.onload = function() {
        location.reload();
    };
    xhr.onerror = function() {
        location.reload();
    };
    xhr.send(body);
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