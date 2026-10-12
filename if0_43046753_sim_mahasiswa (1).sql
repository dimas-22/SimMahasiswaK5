-- phpMyAdmin SQL Dump
-- version 4.9.0.1
-- https://www.phpmyadmin.net/
--
-- Host: sql213.infinityfree.com
-- Waktu pembuatan: 12 Okt 2026 pada 03.49
-- Versi server: 11.4.13-MariaDB
-- Versi PHP: 7.2.22

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `if0_43046753_sim_mahasiswa`
--

-- --------------------------------------------------------

--
-- Struktur dari tabel `audit_log`
--

CREATE TABLE `audit_log` (
  `id_audit` bigint(20) UNSIGNED NOT NULL,
  `id_pengguna` bigint(20) UNSIGNED DEFAULT NULL,
  `tabel_nama` varchar(100) NOT NULL,
  `record_id` varchar(100) DEFAULT NULL,
  `aksi` enum('LOGIN','LOGOUT','INSERT','UPDATE','DELETE','VIEW') NOT NULL,
  `data_lama` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL
) ;

--
-- Dumping data untuk tabel `audit_log`
--

INSERT INTO `audit_log` (`id_audit`, `id_pengguna`, `tabel_nama`, `record_id`, `aksi`, `data_lama`, `data_baru`, `ip_address`, `user_agent`, `waktu`) VALUES
(1, 6, 'pengguna', '6', 'LOGIN', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-09-28 10:01:52');

-- --------------------------------------------------------

--
-- Struktur dari tabel `fakultas`
--

CREATE TABLE `fakultas` (
  `id_fakultas` bigint(20) UNSIGNED NOT NULL,
  `id_universitas` bigint(20) UNSIGNED NOT NULL,
  `kode_fakultas` varchar(20) NOT NULL,
  `nama_fakultas` varchar(200) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `fakultas`
--

INSERT INTO `fakultas` (`id_fakultas`, `id_universitas`, `kode_fakultas`, `nama_fakultas`, `created_at`, `updated_at`) VALUES
(1, 1, 'FT', 'Fakultas Teknik', '2026-09-28 02:53:26', '2026-09-28 02:53:26');

-- --------------------------------------------------------

--
-- Struktur dari tabel `mahasiswa`
--

CREATE TABLE `mahasiswa` (
  `id_mahasiswa` bigint(20) UNSIGNED NOT NULL,
  `id_pengguna` bigint(20) UNSIGNED DEFAULT NULL,
  `id_program_studi` bigint(20) UNSIGNED NOT NULL,
  `npm` varchar(30) NOT NULL,
  `nama_mahasiswa` varchar(200) NOT NULL,
  `jenis_kelamin` enum('L','P') NOT NULL,
  `tempat_lahir` varchar(100) DEFAULT NULL,
  `tanggal_lahir` date DEFAULT NULL,
  `tanggal_masuk` date NOT NULL,
  `alamat` text DEFAULT NULL,
  `status_mahasiswa` enum('Aktif','Cuti','Lulus','Mengundurkan Diri','Drop Out','Tidak Aktif') NOT NULL DEFAULT 'Aktif',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `mahasiswa`
--

INSERT INTO `mahasiswa` (`id_mahasiswa`, `id_pengguna`, `id_program_studi`, `npm`, `nama_mahasiswa`, `jenis_kelamin`, `tempat_lahir`, `tanggal_lahir`, `tanggal_masuk`, `alamat`, `status_mahasiswa`, `created_at`, `updated_at`) VALUES
(1, 5, 1, '20260001', 'Contoh Mahasiswa', 'L', 'Bengkulu', '2005-01-15', '2026-09-01', 'Bengkulu', 'Aktif', '2026-09-28 02:53:27', '2026-09-28 02:53:27');

-- --------------------------------------------------------

--
-- Struktur dari tabel `pengguna`
--

CREATE TABLE `pengguna` (
  `id_pengguna` bigint(20) UNSIGNED NOT NULL,
  `id_role` smallint(5) UNSIGNED NOT NULL,
  `id_universitas` bigint(20) UNSIGNED DEFAULT NULL,
  `id_fakultas` bigint(20) UNSIGNED DEFAULT NULL,
  `id_program_studi` bigint(20) UNSIGNED DEFAULT NULL,
  `username` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `nama_lengkap` varchar(200) NOT NULL,
  `email` varchar(150) DEFAULT NULL,
  `no_hp` varchar(30) DEFAULT NULL,
  `status_aktif` enum('Aktif','Tidak Aktif') NOT NULL DEFAULT 'Aktif',
  `last_login` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `pengguna`
--

INSERT INTO `pengguna` (`id_pengguna`, `id_role`, `id_universitas`, `id_fakultas`, `id_program_studi`, `username`, `password_hash`, `nama_lengkap`, `email`, `no_hp`, `status_aktif`, `last_login`, `created_at`, `updated_at`) VALUES
(2, 2, 1, 1, 1, 'operator.ti', '$2y$10$REPLACE_WITH_PASSWORD_HASH', 'Operator Program Studi Teknik Informatika', 'operator.ti@umb.ac.id', NULL, 'Aktif', NULL, '2026-09-28 02:53:26', '2026-09-28 02:53:26'),
(3, 3, 1, 1, NULL, 'dekanat.ft', '$2y$10$REPLACE_WITH_PASSWORD_HASH', 'Operator Dekanat Fakultas Teknik', 'dekanat.ft@umb.ac.id', NULL, 'Aktif', NULL, '2026-09-28 02:53:26', '2026-09-28 02:53:26'),
(4, 4, 1, NULL, NULL, 'rektorat', '$2y$10$REPLACE_WITH_PASSWORD_HASH', 'Operator Rektorat', 'rektorat@umb.ac.id', NULL, 'Aktif', NULL, '2026-09-28 02:53:27', '2026-09-28 02:53:27'),
(5, 5, 1, 1, 1, '20260001', '$2y$10$REPLACE_WITH_PASSWORD_HASH', 'Contoh Mahasiswa', '20260001@student.umb.ac.id', NULL, 'Aktif', NULL, '2026-09-28 02:53:27', '2026-09-28 02:53:27'),
(6, 1, 1, NULL, NULL, 'Admin', '$2y$12$u/1rOXunsfvYgl5VB6izPuGdgaCWGLew4hzMNxku7WF2ZwG8WU.nq', 'Admin SIM Mhs UMB', 'harrywitriyono@umb.ac.id', NULL, 'Aktif', '2026-10-12 00:37:16', '2026-09-28 03:01:42', '2026-10-12 07:37:16'),
(7, 5, 1, 1, 1, '2355201152', '$2y$12$1l9OX4AKiSUHcso2ppTaEONknefrS9/WdoLVP9.MRaJYw2siOuWSm', 'Netri Nurhidayah', NULL, NULL, 'Aktif', NULL, '2026-10-12 07:04:25', '2026-10-12 07:05:55'),
(8, 5, 1, 1, 1, '2355201107', '$2y$12$BI52u.XelaRIR8ZvH2BWEOPSEklHW3VgNMpVgznOK.rLN8GgKwRX6', 'Roni Chandra', NULL, NULL, 'Aktif', '2026-10-12 00:39:16', '2026-10-12 07:05:32', '2026-10-12 07:39:16'),
(9, 5, 1, 1, 1, '2355201141', '$2y$12$vSfVbw1wjB7XqkVUUBZ03.yueS/tdBAW8QUvblO28dD9MbyiGtjB6', 'Pepti Kumala Dewi', NULL, NULL, 'Aktif', NULL, '2026-10-12 07:06:36', '2026-10-12 07:06:36');

-- --------------------------------------------------------

--
-- Struktur dari tabel `program_studi`
--

CREATE TABLE `program_studi` (
  `id_program_studi` bigint(20) UNSIGNED NOT NULL,
  `id_fakultas` bigint(20) UNSIGNED NOT NULL,
  `kode_program_studi` varchar(20) NOT NULL,
  `nama_program_studi` varchar(200) NOT NULL,
  `jenjang` varchar(20) NOT NULL DEFAULT 'S1',
  `status_aktif` enum('Aktif','Tidak Aktif') NOT NULL DEFAULT 'Aktif',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `program_studi`
--

INSERT INTO `program_studi` (`id_program_studi`, `id_fakultas`, `kode_program_studi`, `nama_program_studi`, `jenjang`, `status_aktif`, `created_at`, `updated_at`) VALUES
(1, 1, 'TI', 'Teknik Informatika', 'S1', 'Aktif', '2026-09-28 02:53:26', '2026-09-28 02:53:26');

-- --------------------------------------------------------

--
-- Struktur dari tabel `roles`
--

CREATE TABLE `roles` (
  `id_role` smallint(5) UNSIGNED NOT NULL,
  `kode_role` varchar(30) NOT NULL,
  `nama_role` varchar(100) NOT NULL,
  `keterangan` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `roles`
--

INSERT INTO `roles` (`id_role`, `kode_role`, `nama_role`, `keterangan`, `created_at`) VALUES
(1, 'ADMIN', 'Admin', 'Mengelola seluruh data dan konfigurasi aplikasi', '2026-09-28 02:53:26'),
(2, 'OPERATOR_PRODI', 'Operator Program Studi', 'Mengelola mahasiswa pada Program Studi yang menjadi kewenangannya', '2026-09-28 02:53:26'),
(3, 'DEKANAT', 'Dekanat', 'Melihat data mahasiswa pada Fakultas yang menjadi kewenangannya', '2026-09-28 02:53:26'),
(4, 'REKTORAT', 'Rektorat', 'Melihat data mahasiswa pada tingkat universitas', '2026-09-28 02:53:26'),
(5, 'MAHASISWA', 'Mahasiswa', 'Melihat data pribadi/rekord mahasiswa sendiri', '2026-09-28 02:53:26'),
(6, '55201', 'Prodi Teknik Informatika', NULL, '2026-09-28 03:07:56');

-- --------------------------------------------------------

--
-- Struktur dari tabel `universitas`
--

CREATE TABLE `universitas` (
  `id_universitas` bigint(20) UNSIGNED NOT NULL,
  `kode_universitas` varchar(20) NOT NULL,
  `nama_universitas` varchar(200) NOT NULL,
  `slogan` varchar(255) DEFAULT NULL,
  `alamat` text DEFAULT NULL,
  `kota` varchar(100) DEFAULT NULL,
  `provinsi` varchar(100) DEFAULT NULL,
  `kode_pos` varchar(10) DEFAULT NULL,
  `website` varchar(255) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `telepon` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `universitas`
--

INSERT INTO `universitas` (`id_universitas`, `kode_universitas`, `nama_universitas`, `slogan`, `alamat`, `kota`, `provinsi`, `kode_pos`, `website`, `email`, `telepon`, `created_at`, `updated_at`) VALUES
(1, 'UMB', 'Universitas Muhammadiyah Bengkulu', NULL, 'Jl. Bali, Kampung Bali', 'Bengkulu', 'Bengkulu', NULL, NULL, NULL, NULL, '2026-09-28 02:53:26', '2026-09-28 02:53:26');

-- --------------------------------------------------------

--
-- Struktur dari tabel `v_mahasiswa_per_prodi`
--

CREATE TABLE `v_mahasiswa_per_prodi` (
  `id_universitas` bigint(20) UNSIGNED DEFAULT NULL,
  `kode_universitas` varchar(20) DEFAULT NULL,
  `nama_universitas` varchar(200) DEFAULT NULL,
  `id_fakultas` bigint(20) UNSIGNED DEFAULT NULL,
  `kode_fakultas` varchar(20) DEFAULT NULL,
  `nama_fakultas` varchar(200) DEFAULT NULL,
  `id_program_studi` bigint(20) UNSIGNED DEFAULT NULL,
  `kode_program_studi` varchar(20) DEFAULT NULL,
  `nama_program_studi` varchar(200) DEFAULT NULL,
  `jenjang` varchar(20) DEFAULT NULL,
  `id_mahasiswa` bigint(20) UNSIGNED DEFAULT NULL,
  `npm` varchar(30) DEFAULT NULL,
  `nama_mahasiswa` varchar(200) DEFAULT NULL,
  `jenis_kelamin` enum('L','P') DEFAULT NULL,
  `jenis_kelamin_text` varchar(9) DEFAULT NULL,
  `tempat_lahir` varchar(100) DEFAULT NULL,
  `tanggal_lahir` date DEFAULT NULL,
  `tanggal_masuk` date DEFAULT NULL,
  `alamat` text DEFAULT NULL,
  `status_mahasiswa` enum('Aktif','Cuti','Lulus','Mengundurkan Diri','Drop Out','Tidak Aktif') DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `v_profil_mahasiswa`
--

CREATE TABLE `v_profil_mahasiswa` (
  `id_mahasiswa` bigint(20) UNSIGNED DEFAULT NULL,
  `npm` varchar(30) DEFAULT NULL,
  `nama_mahasiswa` varchar(200) DEFAULT NULL,
  `jenis_kelamin` enum('L','P') DEFAULT NULL,
  `tempat_lahir` varchar(100) DEFAULT NULL,
  `tanggal_lahir` date DEFAULT NULL,
  `tanggal_masuk` date DEFAULT NULL,
  `alamat` text DEFAULT NULL,
  `status_mahasiswa` enum('Aktif','Cuti','Lulus','Mengundurkan Diri','Drop Out','Tidak Aktif') DEFAULT NULL,
  `kode_program_studi` varchar(20) DEFAULT NULL,
  `nama_program_studi` varchar(200) DEFAULT NULL,
  `jenjang` varchar(20) DEFAULT NULL,
  `kode_fakultas` varchar(20) DEFAULT NULL,
  `nama_fakultas` varchar(200) DEFAULT NULL,
  `kode_universitas` varchar(20) DEFAULT NULL,
  `nama_universitas` varchar(200) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Indexes for dumped tables
--

--
-- Indeks untuk tabel `fakultas`
--
ALTER TABLE `fakultas`
  ADD PRIMARY KEY (`id_fakultas`),
  ADD UNIQUE KEY `uk_fakultas_univ_kode` (`id_universitas`,`kode_fakultas`);

--
-- Indeks untuk tabel `mahasiswa`
--
ALTER TABLE `mahasiswa`
  ADD PRIMARY KEY (`id_mahasiswa`),
  ADD UNIQUE KEY `uk_mahasiswa_npm` (`npm`),
  ADD UNIQUE KEY `uk_mahasiswa_pengguna` (`id_pengguna`),
  ADD KEY `idx_mahasiswa_prodi` (`id_program_studi`),
  ADD KEY `idx_mahasiswa_nama` (`nama_mahasiswa`),
  ADD KEY `idx_mahasiswa_status` (`status_mahasiswa`);

--
-- Indeks untuk tabel `pengguna`
--
ALTER TABLE `pengguna`
  ADD PRIMARY KEY (`id_pengguna`),
  ADD UNIQUE KEY `username` (`username`),
  ADD KEY `idx_pengguna_role` (`id_role`),
  ADD KEY `idx_pengguna_universitas` (`id_universitas`),
  ADD KEY `idx_pengguna_fakultas` (`id_fakultas`),
  ADD KEY `idx_pengguna_prodi` (`id_program_studi`);

--
-- Indeks untuk tabel `program_studi`
--
ALTER TABLE `program_studi`
  ADD PRIMARY KEY (`id_program_studi`),
  ADD UNIQUE KEY `uk_prodi_fakultas_kode` (`id_fakultas`,`kode_program_studi`);

--
-- Indeks untuk tabel `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id_role`),
  ADD UNIQUE KEY `kode_role` (`kode_role`);

--
-- Indeks untuk tabel `universitas`
--
ALTER TABLE `universitas`
  ADD PRIMARY KEY (`id_universitas`),
  ADD UNIQUE KEY `kode_universitas` (`kode_universitas`);

--
-- AUTO_INCREMENT untuk tabel yang dibuang
--

--
-- AUTO_INCREMENT untuk tabel `audit_log`
--
ALTER TABLE `audit_log`
  MODIFY `id_audit` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `fakultas`
--
ALTER TABLE `fakultas`
  MODIFY `id_fakultas` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT untuk tabel `mahasiswa`
--
ALTER TABLE `mahasiswa`
  MODIFY `id_mahasiswa` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT untuk tabel `pengguna`
--
ALTER TABLE `pengguna`
  MODIFY `id_pengguna` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT untuk tabel `program_studi`
--
ALTER TABLE `program_studi`
  MODIFY `id_program_studi` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT untuk tabel `roles`
--
ALTER TABLE `roles`
  MODIFY `id_role` smallint(5) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT untuk tabel `universitas`
--
ALTER TABLE `universitas`
  MODIFY `id_universitas` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Ketidakleluasaan untuk tabel pelimpahan (Dumped Tables)
--

--
-- Ketidakleluasaan untuk tabel `fakultas`
--
ALTER TABLE `fakultas`
  ADD CONSTRAINT `fk_fakultas_universitas` FOREIGN KEY (`id_universitas`) REFERENCES `universitas` (`id_universitas`) ON UPDATE CASCADE;

--
-- Ketidakleluasaan untuk tabel `mahasiswa`
--
ALTER TABLE `mahasiswa`
  ADD CONSTRAINT `fk_mahasiswa_pengguna` FOREIGN KEY (`id_pengguna`) REFERENCES `pengguna` (`id_pengguna`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_mahasiswa_prodi` FOREIGN KEY (`id_program_studi`) REFERENCES `program_studi` (`id_program_studi`) ON UPDATE CASCADE;

--
-- Ketidakleluasaan untuk tabel `pengguna`
--
ALTER TABLE `pengguna`
  ADD CONSTRAINT `fk_pengguna_fakultas` FOREIGN KEY (`id_fakultas`) REFERENCES `fakultas` (`id_fakultas`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_pengguna_prodi` FOREIGN KEY (`id_program_studi`) REFERENCES `program_studi` (`id_program_studi`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_pengguna_role` FOREIGN KEY (`id_role`) REFERENCES `roles` (`id_role`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_pengguna_universitas` FOREIGN KEY (`id_universitas`) REFERENCES `universitas` (`id_universitas`) ON UPDATE CASCADE;

--
-- Ketidakleluasaan untuk tabel `program_studi`
--
ALTER TABLE `program_studi`
  ADD CONSTRAINT `fk_prodi_fakultas` FOREIGN KEY (`id_fakultas`) REFERENCES `fakultas` (`id_fakultas`) ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
