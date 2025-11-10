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

# Configure database
# Edit config/database.php sesuai setting MySQL

# Set permissions
chmod 755 public/uploads/
chmod 755 public/uploads/signed_forms/
chmod 755 public/uploads/employee_signatures/

# Access application
# http://localhost/sicuti
```

## 🔐 Default Login
- **Admin**: `admin` / `password`
- **User**: `budi` / `password`

## 📁 Struktur Project
```
sicuti/
├── public/           # Web root & assets
├── src/             # Application code
│   ├── Controllers/ # Business logic
│   ├── Models/      # Database models
│   ├── Middleware/  # Auth & security
│   └── Services/    # PDF generation
├── templates/       # Twig templates
├── config/          # Configuration
└── database.sql     # Database schema
```

## 🔄 Workflow
1. **Pegawai**: Login → Ajukan Cuti → Download PDF → TTD → Upload → Submit
2. **Admin**: Login → Review Pending → Proses → Upload TTD Atasan → Keputusan → Selesai

## 🤝 Contributing
1. Fork repository
2. Create feature branch (`git checkout -b feature/AmazingFeature`)
3. Commit changes (`git commit -m 'Add AmazingFeature'`)
4. Push to branch (`git push origin feature/AmazingFeature`)
5. Open Pull Request

## 📝 License
Distributed under MIT License. See `LICENSE` for more information.

## 📞 Contact
- **Developer**: [Your Name]
- **Email**: [your.email@domain.com]
- **Project Link**: [https://github.com/username/sicuti]