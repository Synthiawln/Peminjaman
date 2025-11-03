CREATE DATABASE peminjaman;
USE peminjaman;

CREATE TABLE user (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nama VARCHAR(100) NOT NULL,
  username VARCHAR(50) UNIQUE NOT NULL,
  password VARCHAR(255) NOT NULL,
  role ENUM('admin', 'pegawai') DEFAULT 'pegawai',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE ruangan (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nama_ruangan VARCHAR(100) NOT NULL,
  lokasi VARCHAR(100),
  kapasitas INT,
  status ENUM('tersedia', 'dipinjam') DEFAULT 'tersedia',
  keterangan TEXT,
  foto VARCHAR(255)
);

CREATE TABLE kendaraan (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nama_kendaraan VARCHAR(100) NOT NULL,
  no_polisi VARCHAR(20),
  status ENUM('tersedia', 'dipinjam') DEFAULT 'tersedia',
  keterangan TEXT,
  foto VARCHAR(255)
);

CREATE TABLE peminjaman (
  id INT AUTO_INCREMENT PRIMARY KEY,
  kode_peminjaman VARCHAR(50) UNIQUE,
  id_user INT,
  jenis ENUM('ruangan', 'kendaraan') NOT NULL,
  id_item INT NOT NULL,
  tanggal_pinjam DATE,
  tanggal_kembali DATE,
  status ENUM('dipinjam', 'dikembalikan') DEFAULT 'dipinjam',
  lo VARCHAR(100), --penanggung jawab
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (id_user) REFERENCES user(id)
);

CREATE TABLE pengembalian (
  id INT AUTO_INCREMENT PRIMARY KEY,
  id_peminjaman INT NOT NULL,
  tanggal_pengembalian DATE NOT NULL,
  kondisi_barang TEXT,
  diterima_oleh VARCHAR(100),
  catatan TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (id_peminjaman) REFERENCES peminjaman(id)
);


CREATE TABLE berita_acara (
  id INT AUTO_INCREMENT PRIMARY KEY,
  id_peminjaman INT NOT NULL,
  nomor_ba VARCHAR(50) UNIQUE,
  tanggal_dibuat DATE,
  isi TEXT,
  file_BA VARCHAR(255),
  FOREIGN KEY (id_peminjaman) REFERENCES peminjaman(id)
);
