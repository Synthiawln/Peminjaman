-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Waktu pembuatan: 02 Des 2025 pada 04.51
-- Versi server: 10.4.32-MariaDB
-- Versi PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `peminjaman`
--

-- --------------------------------------------------------

--
-- Struktur dari tabel `berita_acara`
--

CREATE TABLE `berita_acara` (
  `id` int(11) NOT NULL,
  `id_peminjaman` int(11) NOT NULL,
  `nomor_ba` varchar(50) DEFAULT NULL,
  `tanggal_dibuat` date DEFAULT NULL,
  `isi` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `kendaraan`
--

CREATE TABLE `kendaraan` (
  `id` int(11) NOT NULL,
  `nama_kendaraan` varchar(100) NOT NULL,
  `no_polisi` varchar(20) DEFAULT NULL,
  `status` enum('tersedia','dipinjam') DEFAULT 'tersedia',
  `keterangan` text DEFAULT NULL,
  `foto` varchar(255) DEFAULT NULL,
  `kode_barang` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `kendaraan`
--

INSERT INTO `kendaraan` (`id`, `nama_kendaraan`, `no_polisi`, `status`, `keterangan`, `foto`, `kode_barang`) VALUES
(2, 'Sedan Camry ', 'AB 11', 'tersedia', 'Kendaraan Dinas Jabatan', 'uploads/1763688361_Picture1.jpg', '3020101001'),
(3, 'Station Wagon Wuling', 'AB 1105 IA', 'tersedia', 'Kendaraan Dinas Operational', 'uploads/1763688526_Picture2.jpg', '3020101003'),
(4, 'Station Wagon Wuling', 'AB 1103 IA', 'tersedia', 'Kendaraan Dinas Operasional', 'uploads/1763688565_Picture3.jpg', '3020101003'),
(5, 'Mini Bus (Avanza) ', 'AB 1162 IA', 'tersedia', 'Kendaraan Dinas Operasional\r\n\r\n', 'uploads/1763688937_Picture4.jpg', '3020102003'),
(6, 'Mini Bus (Innova) ', 'AB 1156 IA', 'tersedia', 'Kendaraan Dinas Operasional', 'uploads/1763689036_Picture5.jpg', '3020102003'),
(7, 'Mini Bus (Avanza) ', 'AB 1163 IA', 'tersedia', 'Kendaraan Dinas Operasional', 'uploads/1763689128_Picture6.jpg', '3020102003'),
(8, 'Mini Bus (Innova) ', 'AB 1160 IA', 'tersedia', 'Kendaraan Dinas Operasional', 'uploads/1763689391_Picture7.jpg', '3020102003'),
(9, 'Mini Bus (Innova) ', 'AB II57 IA', 'tersedia', 'Kendaraan Dinas Operasional', 'uploads/1763689418_Picture8.jpg', '3020102003'),
(10, 'Mini Bus (Innova) ', 'AB 1158 IA', 'tersedia', 'Kendaraan Dinas Operasional', 'uploads/1763689447_Picture9.jpg', '3020102003'),
(11, 'Mini Bus (Innova) ', 'AB 1161 UH', 'tersedia', 'Kendaraan Dinas Operasional', 'uploads/1763689482_Picture10.jpg', '3020102003'),
(12, 'Mini Bus (Innova) ', 'AB 1169 UH', 'tersedia', 'Kendaraan Dinas Operasional', 'uploads/1763689515_Picture11.jpg', '3020102003'),
(13, 'Mini Bus (Alphard) ', 'AB 1168 ZZH', 'tersedia', 'Kendaraan Dinas Operasional', 'uploads/1763689564_Picture13.jpg', '3020102003'),
(14, 'Mini Bus (Innova) ', 'AB 1159 IF', 'tersedia', 'Kendaraan Dinas Operasional', 'uploads/1763690349_Picture12.jpg', '3020102003'),
(15, 'Pick Up Suzuki', 'AB 8611 IA', 'tersedia', 'Kendaraan Dinas Operasional', 'uploads/1763689597_Picture14.jpg', '3020103002'),
(16, 'Kendaraan Roda Tiga (Viar)', 'AB 3111 IF', 'tersedia', 'Kendaraan Dinas Operasional', 'uploads/1763689674_Picture15.jpg', '3020103008'),
(17, 'Sepeda Motor Honda - NF', 'AB 2824 UH ', 'tersedia', 'Kendaraan Dinas Operasional', 'uploads/1763689719_Picture16.jpg', '3020104001'),
(18, 'Sepeda Motor Yamaha - UE11', 'AB 2211 IF', 'tersedia', 'Kendaraan Dinas Operasional', 'uploads/1763689792_Picture17.jpg', '3020104001'),
(19, 'Sepeda Motor Yamaha - UE11', 'AB 2311 IF', 'tersedia', 'Kendaraan Dinas Operasional', 'uploads/1763689862_Picture18.jpg', '3020104001'),
(20, 'Sepeda Motor Yamaha Lexi', 'AB 2511 IF', 'tersedia', 'Kendaraan Dinas Operasional', 'uploads/1763689906_Picture19.jpg', '3020104001'),
(21, 'Sepeda Motor Yamaha Gear', 'AB 2411 IF', 'tersedia', 'Kendaraan Dinas Operasional', 'uploads/1763689937_Picture20.jpg', '3020104001'),
(22, 'Sepeda Motor Yamaha Mio', 'AB 2611 IF', 'tersedia', 'Kendaraan Dinas Operasional', 'uploads/1763689960_Picture21.jpg', '3020104001');

-- --------------------------------------------------------

--
-- Struktur dari tabel `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `id_user` int(11) NOT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `id_peminjaman` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `notifications`
--

INSERT INTO `notifications` (`id`, `id_user`, `message`, `is_read`, `created_at`, `id_peminjaman`) VALUES
(178, 3, 'Permintaan peminjaman ruangan baru menunggu persetujuan.', 0, '2025-12-02 03:16:40', NULL),
(179, 8, 'Permintaan peminjaman ruangan Anda disetujui. Klik untuk melihat/cetak Berita Acara: http://localhost/PinjamRuanganKendaraan/pdf-kembali/2025/Berita_Acara_Ruangan_01_BA-RUANG_XVIII.YOG.1.4_12_2025.pdf', 1, '2025-12-02 03:16:59', NULL),
(180, 3, 'Pengajuan pengembalian ruangan (01/BA-RUANG/XVIII.YOG.1.4/12/2025) menunggu persetujuan Anda.', 0, '2025-12-02 03:18:52', 104),
(181, 8, 'Pengembalian ruangan disetujui. ', 1, '2025-12-02 03:19:04', NULL),
(182, 3, 'Pengajuan pengembalian ruangan (ADM-20251202-9937) menunggu persetujuan Anda.', 0, '2025-12-02 03:19:54', 105),
(183, 8, 'Pengembalian ruangan disetujui. ', 0, '2025-12-02 03:20:15', NULL);

-- --------------------------------------------------------

--
-- Struktur dari tabel `peminjaman`
--

CREATE TABLE `peminjaman` (
  `id` int(11) NOT NULL,
  `kode_peminjaman` varchar(50) DEFAULT NULL,
  `id_user` int(11) DEFAULT NULL,
  `jenis` enum('ruangan','kendaraan') NOT NULL,
  `id_item` int(11) NOT NULL,
  `tanggal_pinjam` date DEFAULT NULL,
  `tanggal_kembali` date DEFAULT NULL,
  `status` enum('pending','approved','rejected','dipinjam','dikembalikan') DEFAULT 'pending',
  `keterangan_user` text DEFAULT NULL,
  `lo` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `tanggal_kembali_aktual` date DEFAULT NULL,
  `tambah_fasilitas` text DEFAULT NULL,
  `nama_manual` varchar(255) DEFAULT NULL,
  `nip_manual` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `peminjaman`
--

INSERT INTO `peminjaman` (`id`, `kode_peminjaman`, `id_user`, `jenis`, `id_item`, `tanggal_pinjam`, `tanggal_kembali`, `status`, `keterangan_user`, `lo`, `created_at`, `tanggal_kembali_aktual`, `tambah_fasilitas`, `nama_manual`, `nip_manual`) VALUES
(104, '01/BA-RUANG/XVIII.YOG.1.4/12/2025', 8, 'ruangan', 5, '2025-12-02', '2025-12-04', 'dikembalikan', '-', 'Admin Ruangan', '2025-12-02 03:16:40', '2025-12-02', '-', NULL, NULL),
(105, 'ADM-20251202-9937', 8, 'ruangan', 3, '2025-12-05', '2025-12-08', 'dikembalikan', '-', NULL, '2025-12-02 03:19:35', '2025-12-02', NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Struktur dari tabel `pengembalian`
--

CREATE TABLE `pengembalian` (
  `id` int(11) NOT NULL,
  `id_peminjaman` int(11) NOT NULL,
  `tanggal_pengembalian` date NOT NULL,
  `kondisi_barang` text DEFAULT NULL,
  `diterima_oleh` varchar(100) DEFAULT NULL,
  `catatan` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `ruangan`
--

CREATE TABLE `ruangan` (
  `id` int(11) NOT NULL,
  `nama_ruangan` varchar(100) NOT NULL,
  `lokasi` varchar(100) DEFAULT NULL,
  `kapasitas` int(11) DEFAULT NULL,
  `status` enum('tersedia','dipinjam') DEFAULT 'tersedia',
  `keterangan` text DEFAULT NULL,
  `foto` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `ruangan`
--

INSERT INTO `ruangan` (`id`, `nama_ruangan`, `lokasi`, `kapasitas`, `status`, `keterangan`, `foto`) VALUES
(2, 'Ruang Krapyak', 'Lantai 1', 20, 'tersedia', 'Rapat kecil/diskusi, terdapat Meja, Kursi, TV, dan AC', 'uploads/ruangan/1764259583_Ruang Panggung Krapyak.png'),
(3, 'Ruang Kotagede', 'Lantai 1', 30, 'tersedia', 'Ruang VIP, terdapat kursi, TV display, dan AC', 'uploads/ruangan/1764259572_Ruang Kota Gede.png'),
(4, 'Ruang Golong Gilig', 'Lantai 2', 15, 'tersedia', 'Ruang koordinasi/rapat, terdapat meja rapat, kursi, dan layar presentasi', 'uploads/ruangan/1764259557_Ruang Golong Gilig.png'),
(5, 'Auditorium', 'Lantai 1,5', 150, 'tersedia', 'Seminar/acara, terdapat panggung, sound system, layar dan proyektor', 'uploads/ruangan/1764259531_Ruang Auditorium.png'),
(6, 'Ruang Merapi', 'Lantai 4', 40, 'tersedia', 'Rapat/diskusi, terdapat proyektor, whiteboard, dan AC', 'uploads/ruangan/1764259598_Ruang Merapi.png'),
(7, 'Ruang Pasca Karya', 'Lantai 1', 25, 'tersedia', 'Kegiatan organisasi/pelatihan/diskusi, terdapat meja, kursi, AC, dan PC Dekstop', ''),
(8, 'Ruang Dharma Wanita', 'Lantai 1', 50, 'tersedia', 'Kegiatan organisasi/pelatihan/diskusi, terdapat meja, kursi, AC, dan PC Dekstop', '');

-- --------------------------------------------------------

--
-- Struktur dari tabel `user`
--

CREATE TABLE `user` (
  `id` int(11) NOT NULL,
  `nama` varchar(100) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('super_admin','admin_ruangan','admin_kendaraan','user') DEFAULT 'user',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `nip` varchar(30) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `user`
--

INSERT INTO `user` (`id`, `nama`, `username`, `password`, `role`, `created_at`, `nip`) VALUES
(1, 'Super Admin', 'superadmin', '$2y$10$XazBAdTImHl5GeBNIyjrfOngrk3TPF6G0SsL9spRjAjPkxasNhXAS', 'super_admin', '2025-11-04 02:53:32', NULL),
(2, 'Admin Kendaraan', 'adminkendaraan', '$2y$10$3EcWDTiWVkW2eVntaVBjf.aJUqRUcQNMJITZrwq3I7PSKwCdACWXe', 'admin_kendaraan', '2025-11-04 02:53:32', NULL),
(3, 'Admin Ruangan', 'adminruangan', '$2y$10$9yGj3rwNw9qNWO/tXXh9KeWs.IiKFGdJzvmEJAPBGY3cSBIlQPw5m', 'admin_ruangan', '2025-11-04 02:53:32', NULL),
(6, 'sinti', 'sisyn', '$2y$10$/zMpzByNQpFpW1.C8QC4juA6tYgupIyoi2me0srNXAnOkcm0lMC9S', 'user', '2025-11-13 06:12:05', NULL),
(8, 'sin', 'sisin', '$2y$10$5xbZNAUNEjbjovu2yzxVU.vPJbk7JIB.YYaUC0Ra7BgOcvCNGKOsy', 'user', '2025-11-21 02:57:38', '040104');

--
-- Indexes for dumped tables
--

--
-- Indeks untuk tabel `berita_acara`
--
ALTER TABLE `berita_acara`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nomor_ba` (`nomor_ba`),
  ADD KEY `id_peminjaman` (`id_peminjaman`);

--
-- Indeks untuk tabel `kendaraan`
--
ALTER TABLE `kendaraan`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_user` (`id_user`);

--
-- Indeks untuk tabel `peminjaman`
--
ALTER TABLE `peminjaman`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `kode_peminjaman` (`kode_peminjaman`),
  ADD KEY `id_user` (`id_user`);

--
-- Indeks untuk tabel `pengembalian`
--
ALTER TABLE `pengembalian`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_peminjaman` (`id_peminjaman`);

--
-- Indeks untuk tabel `ruangan`
--
ALTER TABLE `ruangan`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `user`
--
ALTER TABLE `user`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT untuk tabel yang dibuang
--

--
-- AUTO_INCREMENT untuk tabel `berita_acara`
--
ALTER TABLE `berita_acara`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT untuk tabel `kendaraan`
--
ALTER TABLE `kendaraan`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT untuk tabel `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=184;

--
-- AUTO_INCREMENT untuk tabel `peminjaman`
--
ALTER TABLE `peminjaman`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=106;

--
-- AUTO_INCREMENT untuk tabel `pengembalian`
--
ALTER TABLE `pengembalian`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `ruangan`
--
ALTER TABLE `ruangan`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT untuk tabel `user`
--
ALTER TABLE `user`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- Ketidakleluasaan untuk tabel pelimpahan (Dumped Tables)
--

--
-- Ketidakleluasaan untuk tabel `berita_acara`
--
ALTER TABLE `berita_acara`
  ADD CONSTRAINT `berita_acara_ibfk_1` FOREIGN KEY (`id_peminjaman`) REFERENCES `peminjaman` (`id`);

--
-- Ketidakleluasaan untuk tabel `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `fk_notifications_user` FOREIGN KEY (`id_user`) REFERENCES `user` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `peminjaman`
--
ALTER TABLE `peminjaman`
  ADD CONSTRAINT `peminjaman_ibfk_1` FOREIGN KEY (`id_user`) REFERENCES `user` (`id`);

--
-- Ketidakleluasaan untuk tabel `pengembalian`
--
ALTER TABLE `pengembalian`
  ADD CONSTRAINT `pengembalian_ibfk_1` FOREIGN KEY (`id_peminjaman`) REFERENCES `peminjaman` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
