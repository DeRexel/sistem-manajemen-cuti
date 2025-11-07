# SICUTI - Sistem Manajemen Cuti Pegawai

## 📋 Deskripsi
Sistem Manajemen Cuti Pegawai (SICUTI) adalah aplikasi web untuk mengelola pengajuan dan persetujuan cuti pegawai di Universitas Palangka Raya.

## 🚀 Fitur Utama
- **User (Pegawai)**: Pengajuan cuti, download formulir PDF, upload TTD, monitoring status
- **Admin**: Approval workflow, kelola pegawai, kelola pejabat, statistik dashboard
- **PDF Generator**: Formulir cuti otomatis sesuai format resmi
- **Role-based Access**: User dan Admin dengan hak akses berbeda

## 🛠️ Tech Stack
- **Backend**: PHP 8.4 + Slim Framework 4.x
- **Frontend**: Twig Templates + Tailwind CSS + Flowbite
- **Database**: MySQL 8.0+
- **PDF**: TCPDF
- **Authentication**: PHP Session + Custom Middleware

## 📦 Instalasi

### Prerequisites
- PHP 8.0+
- MySQL 8.0+
- Composer
- Web Server (Apache/Nginx)

### Setup
```bash
# Clone repository
git clone <repository-url>
cd sicuti

# Install dependencies
composer install

# Setup database
mysql -u root -p
CREATE DATABASE sicuti_db;
exit
mysql -u root -p sicuti_db < database.sql


## 🔐 Default Login
- **Admin**: `admin` / `password`
- **User**: `budi` / `password`

## 🔄 Workflow
1. **Pegawai**: Login → Ajukan Cuti → Download PDF → TTD → Upload → Submit
2. **Admin**: Login → Review Pending → Proses → Upload TTD Atasan → Keputusan → Selesai
