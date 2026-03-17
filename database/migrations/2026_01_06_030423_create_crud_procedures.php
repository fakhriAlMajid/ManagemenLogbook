<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // ==============================================================================
        // 1. CRUD USERS
        // ==============================================================================

        // CREATE USER
        DB::unprepared('
            DROP PROCEDURE IF EXISTS sp_create_user;
            CREATE PROCEDURE sp_create_user(
                IN p_username VARCHAR(50),
                IN p_email VARCHAR(100),
                IN p_password VARCHAR(255),
                IN p_first_name VARCHAR(50),
                IN p_last_name VARCHAR(50),
                IN p_role VARCHAR(20)
            )
            BEGIN
                INSERT INTO users (usr_username, usr_email, usr_password, usr_first_name, usr_last_name, usr_role, usr_create_at)
                VALUES (p_username, p_email, p_password, p_first_name, p_last_name, p_role, NOW());
                
                SELECT LAST_INSERT_ID() as new_usr_id;
            END
        ');

        // READ USER (FIX: COLLATE pada Role & Search)
        DB::unprepared('
            DROP PROCEDURE IF EXISTS sp_read_users;
            CREATE PROCEDURE sp_read_users(
                IN p_usr_id INT,
                IN p_search VARCHAR(100),
                IN p_role VARCHAR(20)
            )
            BEGIN
                SELECT * FROM users
                WHERE 
                    (p_usr_id IS NULL OR usr_id = p_usr_id)
                    AND (p_role IS NULL OR usr_role = p_role COLLATE utf8mb4_unicode_ci) -- Fix Equal
                    AND (p_search IS NULL OR (
                        usr_username LIKE CONCAT("%", p_search, "%") COLLATE utf8mb4_unicode_ci OR
                        usr_email LIKE CONCAT("%", p_search, "%") COLLATE utf8mb4_unicode_ci OR
                        CONCAT(usr_first_name, " ", usr_last_name) LIKE CONCAT("%", p_search, "%") COLLATE utf8mb4_unicode_ci
                    ));
            END
        ');

        // ==============================================================================
        // 2. CRUD PROJEK
        // ==============================================================================

        // CREATE PROJEK
        DB::unprepared('
            DROP PROCEDURE IF EXISTS sp_create_projek_with_leader;
            CREATE PROCEDURE sp_create_projek_with_leader(
                IN p_nama VARCHAR(100),
                IN p_deskripsi TEXT,
                IN p_pjk_pic VARCHAR(255),
                IN p_tgl_mulai DATE,
                IN p_tgl_selesai DATE,
                IN p_creator_id INT,
                IN p_creator_username VARCHAR(50)
            )
            BEGIN
                DECLARE new_pjk_id INT;
                START TRANSACTION;

                INSERT INTO projek (
                    pjk_nama, pjk_pic, pjk_deskripsi, pjk_tanggal_mulai, 
                    pjk_tanggal_selesai, pjk_status, pjk_persentasi_progress, 
                    pjk_create_at, pjk_create_by
                )
                VALUES (
                    p_nama, p_pjk_pic, p_deskripsi, p_tgl_mulai, 
                    p_tgl_selesai, "In Progress", 0, NOW(), p_creator_username
                );
                
                SET new_pjk_id = LAST_INSERT_ID();

                INSERT INTO member_projek (pjk_id, usr_id, mpk_role_projek, mpk_create_at)
                VALUES (new_pjk_id, p_creator_id, "Ketua", NOW());

                COMMIT;
                SELECT new_pjk_id as pjk_id;
            END
        ');

        // READ PROJEK
        DB::unprepared('
            DROP PROCEDURE IF EXISTS sp_read_projek;
            CREATE PROCEDURE sp_read_projek(
                IN p_pjk_id INT,
                IN p_search VARCHAR(100),
                IN p_status VARCHAR(20)
            )
            BEGIN

                -- Hitung progress dan update status project
                UPDATE projek p
                JOIN (
                    SELECT 
                        p.pjk_id,
                        COUNT(t.tgs_id) AS total_tasks,
                        SUM(
                            CASE 
                                WHEN t.tgs_status = "Completed"
                                OR t.tgs_persentasi_progress = 100
                                THEN 1
                                ELSE 0
                            END
                        ) AS completed_tasks
                    FROM projek p
                    LEFT JOIN modul m ON p.pjk_id = m.pjk_id
                    LEFT JOIN kegiatan k ON m.mdl_id = k.mdl_id
                    LEFT JOIN tugas t ON k.kgt_id = t.kgt_id
                    GROUP BY p.pjk_id
                ) progress_data
                ON p.pjk_id = progress_data.pjk_id
                SET p.pjk_status =
                    CASE
                        WHEN progress_data.total_tasks > 0 
                            AND progress_data.total_tasks = progress_data.completed_tasks
                        THEN "Completed"
                        ELSE "In Progress"
                    END;

                -- Ambil data project
                SELECT *
                FROM projek
                WHERE 
                    (p_pjk_id IS NULL OR pjk_id = p_pjk_id)
                    AND (p_status IS NULL OR pjk_status = p_status COLLATE utf8mb4_unicode_ci)
                    AND (p_search IS NULL OR (
                        pjk_nama LIKE CONCAT("%", p_search, "%") COLLATE utf8mb4_unicode_ci OR
                        pjk_pic LIKE CONCAT("%", p_search, "%") COLLATE utf8mb4_unicode_ci
                    ))
                ORDER BY pjk_create_at DESC;

            END;
        ');

        // UPDATE PROJEK 
        DB::unprepared('
            DROP PROCEDURE IF EXISTS sp_update_projek;
            CREATE PROCEDURE sp_update_projek(
                IN p_pjk_id INT,
                IN p_nama VARCHAR(100),
                IN p_deskripsi TEXT,
                IN p_pjk_pic VARCHAR(255),
                IN p_tgl_mulai DATE,
                IN p_tgl_selesai DATE,
                IN p_status VARCHAR(20),
                IN p_modifier_username VARCHAR(50)
            )
            BEGIN
                UPDATE projek 
                SET pjk_nama = p_nama,
                    pjk_pic = p_pjk_pic, -- Update PIC
                    pjk_deskripsi = p_deskripsi,
                    pjk_tanggal_mulai = p_tgl_mulai,
                    pjk_tanggal_selesai = p_tgl_selesai,
                    pjk_status = p_status,
                    pjk_modified_at = NOW(),
                    pjk_modified_by = p_modifier_username
                WHERE pjk_id = p_pjk_id;
            END
        ');

        // DELETE PROJEK
        DB::unprepared('
            DROP PROCEDURE IF EXISTS sp_delete_projek;
            CREATE PROCEDURE sp_delete_projek(IN p_pjk_id INT)
            BEGIN
                DELETE FROM projek WHERE pjk_id = p_pjk_id;
            END
        ');

        // ==============================================================================
        // 3. CRUD TUGAS
        // ==============================================================================

        DB::unprepared('
            DROP PROCEDURE IF EXISTS sp_create_tugas;
            CREATE PROCEDURE sp_create_tugas(
                IN p_kgt_id INT,
                IN p_usr_id INT,
                IN p_nama VARCHAR(200),
                IN p_tgl_mulai DATE,
                IN p_tgl_selesai DATE,
                IN p_bobot INT,
                IN p_creator_username VARCHAR(50)
            )
            BEGIN
                DECLARE v_kgt_prefix VARCHAR(20);
                DECLARE v_tugas_count INT;
                DECLARE v_kode_tugas VARCHAR(30);

                -- Ambil kode prefix dari kegiatan induk
                SELECT kgt_kode_prefix INTO v_kgt_prefix FROM kegiatan WHERE kgt_id = p_kgt_id;

                -- Hitung jumlah tugas yang sudah ada di kegiatan tersebut
                SELECT COUNT(*) INTO v_tugas_count FROM tugas WHERE kgt_id = p_kgt_id;

                -- Gabungkan menjadi format (I.A1)
                SET v_kode_tugas = CONCAT(v_kgt_prefix, (v_tugas_count + 1));

                INSERT INTO tugas (
                    kgt_id, usr_id, tgs_kode_prefix, tgs_nama, 
                    tgs_tanggal_mulai, tgs_tanggal_selesai, tgs_bobot, 
                    tgs_persentasi_progress, tgs_status, 
                    tgs_create_at, tgs_create_by
                )
                VALUES (
                    p_kgt_id, p_usr_id, v_kode_tugas, p_nama,
                    p_tgl_mulai, p_tgl_selesai, p_bobot,
                    0, "Pending",
                    NOW(), p_creator_username
                );
                
                SELECT LAST_INSERT_ID() as new_tgs_id, v_kode_tugas as generated_kode;
            END
        ');

        // READ TUGAS   
        DB::unprepared('
            DROP PROCEDURE IF EXISTS sp_read_tugas;
            CREATE PROCEDURE sp_read_tugas(
                IN p_tgs_id INT,
                IN p_kgt_id INT,
                IN p_usr_id INT,
                IN p_status VARCHAR(20),
                IN p_search VARCHAR(100)
            )
            BEGIN
                SELECT 
                    t.*,
                    CONCAT(u.usr_first_name, " ", u.usr_last_name) as pic_name
                FROM tugas t
                LEFT JOIN users u ON t.usr_id = u.usr_id
                WHERE 
                    (p_tgs_id IS NULL OR t.tgs_id = p_tgs_id)
                    AND (p_kgt_id IS NULL OR t.kgt_id = p_kgt_id)
                    AND (p_usr_id IS NULL OR t.usr_id = p_usr_id)
                    AND (p_status IS NULL OR t.tgs_status = p_status COLLATE utf8mb4_unicode_ci) -- Fix Equal
                    AND (p_search IS NULL OR (
                        t.tgs_nama LIKE CONCAT("%", p_search, "%") COLLATE utf8mb4_unicode_ci OR 
                        t.tgs_kode_prefix LIKE CONCAT("%", p_search, "%") COLLATE utf8mb4_unicode_ci
                    ))
                ORDER BY t.tgs_create_at DESC;
            END
        ');

        // UPDATE TUGAS
        DB::unprepared('
            DROP PROCEDURE IF EXISTS sp_update_tugas;
            CREATE PROCEDURE sp_update_tugas(
                IN p_tgs_id INT,
                IN p_usr_id INT,
                IN p_nama VARCHAR(200),
                IN p_tgl_selesai DATE,
                IN p_progress DECIMAL(5,2),
                IN p_modifier_username VARCHAR(50)
            )
            BEGIN
                UPDATE tugas 
                SET usr_id = p_usr_id,
                    tgs_nama = p_nama,
                    tgs_tanggal_selesai = p_tgl_selesai,
                    tgs_persentasi_progress = p_progress,
                    tgs_modified_at = NOW(),
                    tgs_modified_by = p_modifier_username
                WHERE tgs_id = p_tgs_id;
            END
        ');

        // DELETE TUGAS
        DB::unprepared('
            DROP PROCEDURE IF EXISTS sp_delete_tugas;
            CREATE PROCEDURE sp_delete_tugas(IN p_tgs_id INT)
            BEGIN
                DELETE FROM tugas WHERE tgs_id = p_tgs_id;
            END
        ');

        // ==============================================================================
        // 4. CRUD LOGBOOK
        // ==============================================================================

        // CREATE LOGBOOK
        DB::unprepared('
            DROP PROCEDURE IF EXISTS sp_create_logbook;
            CREATE PROCEDURE sp_create_logbook(
                IN p_tgs_id INT,
                IN p_tanggal DATE,
                IN p_deskripsi TEXT,
                IN p_komentar TEXT,
                IN p_progress INT
            )
            BEGIN
                INSERT INTO logbook (tgs_id, lbk_tanggal, lbk_deskripsi, lbk_komentar, lbk_progress, lbk_create_at)
                VALUES (p_tgs_id, p_tanggal, p_deskripsi, p_komentar, p_progress, NOW());
            END
        ');

        // READ LOGBOOK (FIX: COLLATE pada Search)
        DB::unprepared('
            DROP PROCEDURE IF EXISTS sp_read_logbook;
            CREATE PROCEDURE sp_read_logbook(
                IN p_lbk_id INT,
                IN p_tgs_id INT,
                IN p_tanggal DATE,
                IN p_search VARCHAR(100)
            )
            BEGIN
                SELECT 
                    l.*, 
                    t.tgs_nama,
                    t.tgs_kode_prefix,
                    CONCAT(u.usr_first_name, " ", u.usr_last_name) as pic_name,
                    u.usr_avatar_url,
                    u.usr_role
                FROM logbook l
                JOIN tugas t ON l.tgs_id = t.tgs_id
                JOIN users u ON t.usr_id = u.usr_id
                WHERE 
                    (p_lbk_id IS NULL OR l.lbk_id = p_lbk_id)
                    AND (p_tgs_id IS NULL OR l.tgs_id = p_tgs_id)
                    AND (p_tanggal IS NULL OR l.lbk_tanggal = p_tanggal)
                    AND (p_search IS NULL OR (
                        l.lbk_deskripsi LIKE CONCAT("%", p_search, "%") COLLATE utf8mb4_unicode_ci OR
                        l.lbk_komentar LIKE CONCAT("%", p_search, "%") COLLATE utf8mb4_unicode_ci
                    ))
                ORDER BY l.lbk_tanggal DESC;
            END
        ');

        // UPDATE LOGBOOK
        DB::unprepared('
            DROP PROCEDURE IF EXISTS sp_update_logbook;
            CREATE PROCEDURE sp_update_logbook(
                IN p_lbk_id INT,
                IN p_tanggal DATE,
                IN p_deskripsi TEXT,
                IN p_komentar TEXT,
                IN p_progress INT
            )
            BEGIN
                UPDATE logbook
                SET 
                    lbk_tanggal = p_tanggal,
                    lbk_deskripsi = p_deskripsi,
                    lbk_komentar = p_komentar,
                    lbk_progress = p_progress,
                    lbk_modified_at = NOW()
                WHERE lbk_id = p_lbk_id;
            END
        ');

        // DELETE LOGBOOK
        DB::unprepared('
            DROP PROCEDURE IF EXISTS sp_delete_logbook;
            CREATE PROCEDURE sp_delete_logbook(
                IN p_lbk_id INT
            )
            BEGIN
                DELETE FROM logbook WHERE lbk_id = p_lbk_id;
            END
        ');

        DB::unprepared('DROP PROCEDURE IF EXISTS sp_get_logbooks_by_task;');

        // ==============================================================================
        // 5. CRUD MODUL
        // ==============================================================================

        // CREATE MODUL
        DB::unprepared('
            DROP PROCEDURE IF EXISTS sp_create_modul;
            CREATE PROCEDURE sp_create_modul(
                IN p_pjk_id INT,
                IN p_nama VARCHAR(100),
                IN p_urut INT,
                IN p_creator_username VARCHAR(50)
            )
            BEGIN
                INSERT INTO modul (pjk_id, mdl_nama, mdl_urut, mdl_create_at, mdl_create_by)
                VALUES (p_pjk_id, p_nama, p_urut, NOW(), p_creator_username);
            END
        ');

        // READ MODUL (FIX: COLLATE pada Search)
        DB::unprepared('
            DROP PROCEDURE IF EXISTS sp_read_modul;
            CREATE PROCEDURE sp_read_modul(
                IN p_mdl_id INT,
                IN p_pjk_id INT,
                IN p_search VARCHAR(100)
            )
            BEGIN
                SELECT * FROM modul
                WHERE 
                    (p_mdl_id IS NULL OR mdl_id = p_mdl_id)
                    AND (p_pjk_id IS NULL OR pjk_id = p_pjk_id)
                    AND (p_search IS NULL OR mdl_nama LIKE CONCAT("%", p_search, "%") COLLATE utf8mb4_unicode_ci)
                ORDER BY mdl_urut ASC;
            END
        ');

        // UPDATE MODUL
        DB::unprepared('
            DROP PROCEDURE IF EXISTS sp_update_modul;
            CREATE PROCEDURE sp_update_modul(
                IN p_mdl_id INT,
                IN p_nama VARCHAR(100),
                IN p_urut INT,
                IN p_modifier_username VARCHAR(50)
            )
            BEGIN
                UPDATE modul 
                SET mdl_nama = p_nama,
                    mdl_urut = p_urut,
                    mdl_modified_at = NOW(),
                    mdl_modified_by = p_modifier_username
                WHERE mdl_id = p_mdl_id;
            END
        ');

        // DELETE MODUL
        DB::unprepared('
            DROP PROCEDURE IF EXISTS sp_delete_modul;
            CREATE PROCEDURE sp_delete_modul(IN p_mdl_id INT)
            BEGIN
                DELETE FROM modul WHERE mdl_id = p_mdl_id;
            END
        ');

        DB::unprepared('
            DROP PROCEDURE IF EXISTS sp_create_kegiatan;
            CREATE PROCEDURE sp_create_kegiatan(
                IN p_mdl_id INT,
                IN p_nama VARCHAR(100),
                IN p_creator_username VARCHAR(50)
            )
            BEGIN
                DECLARE v_modul_urut INT;
                DECLARE v_kegiatan_count INT;
                DECLARE v_romawi VARCHAR(10);
                DECLARE v_huruf CHAR(1);
                DECLARE v_kode_prefix VARCHAR(20);

                -- Ambil urutan modul
                SELECT mdl_urut INTO v_modul_urut FROM modul WHERE mdl_id = p_mdl_id;

                -- Konversi urutan modul ke Romawi (Sederhana 1-10)
                SET v_romawi = CASE v_modul_urut
                    WHEN 1 THEN "I" WHEN 2 THEN "II" WHEN 3 THEN "III" WHEN 4 THEN "IV" WHEN 5 THEN "V"
                    WHEN 6 THEN "VI" WHEN 7 THEN "VII" WHEN 8 THEN "VIII" WHEN 9 THEN "IX" WHEN 10 THEN "X"
                    ELSE CAST(v_modul_urut AS CHAR) END;

                -- Hitung urutan kegiatan di modul tersebut untuk huruf (A, B, C...)
                SELECT COUNT(*) INTO v_kegiatan_count FROM kegiatan WHERE mdl_id = p_mdl_id;
                
                -- ASCII 65 adalah "A".
                SET v_huruf = CHAR(65 + v_kegiatan_count);

                -- Gabungkan menjadi format (I.A)
                SET v_kode_prefix = CONCAT(v_romawi, ".", v_huruf);

                INSERT INTO kegiatan (mdl_id, kgt_nama, kgt_kode_prefix, kgt_create_at, kgt_create_by)
                VALUES (p_mdl_id, p_nama, v_kode_prefix, NOW(), p_creator_username);
                
                SELECT LAST_INSERT_ID() as new_kgt_id, v_kode_prefix as generated_kode;
            END
        ');

        DB::unprepared(
            '
            DROP PROCEDURE IF EXISTS sp_update_user_profile;
            CREATE PROCEDURE sp_update_user_profile(
                IN p_usr_id INT,
                IN p_first_name VARCHAR(50),
                IN p_last_name VARCHAR(50),
                IN p_email VARCHAR(100),
                IN p_password VARCHAR(255),
                IN p_avatar_url TEXT
            )
            BEGIN
                UPDATE users 
                SET usr_first_name = p_first_name,
                    usr_last_name = p_last_name,
                    usr_email = p_email,
                    usr_password = IF(p_password IS NULL, usr_password, p_password),
                    usr_avatar_url = p_avatar_url,
                    usr_modified_at = NOW()
                WHERE usr_id = p_usr_id;
            END'
        );
    }

    public function down(): void
    {
        // 1. Users
        DB::unprepared('DROP PROCEDURE IF EXISTS sp_create_user');
        DB::unprepared('DROP PROCEDURE IF EXISTS sp_read_users');

        // 2. Projek
        DB::unprepared('DROP PROCEDURE IF EXISTS sp_create_projek_with_leader');
        DB::unprepared('DROP PROCEDURE IF EXISTS sp_read_projek');
        DB::unprepared('DROP PROCEDURE IF EXISTS sp_update_projek');
        DB::unprepared('DROP PROCEDURE IF EXISTS sp_delete_projek');

        // 3. Tugas
        DB::unprepared('DROP PROCEDURE IF EXISTS sp_create_tugas');
        DB::unprepared('DROP PROCEDURE IF EXISTS sp_read_tugas');
        DB::unprepared('DROP PROCEDURE IF EXISTS sp_update_tugas');
        DB::unprepared('DROP PROCEDURE IF EXISTS sp_delete_tugas');

        // 4. Logbook
        DB::unprepared('DROP PROCEDURE IF EXISTS sp_create_logbook');
        DB::unprepared('DROP PROCEDURE IF EXISTS sp_read_logbook');
        DB::unprepared('DROP PROCEDURE IF EXISTS sp_update_logbook');
        DB::unprepared('DROP PROCEDURE IF EXISTS sp_delete_logbook');
        DB::unprepared('DROP PROCEDURE IF EXISTS sp_get_logbooks_by_task');

        // 5. Modul
        DB::unprepared('DROP PROCEDURE IF EXISTS sp_create_modul');
        DB::unprepared('DROP PROCEDURE IF EXISTS sp_read_modul');
        DB::unprepared('DROP PROCEDURE IF EXISTS sp_update_modul');
        DB::unprepared('DROP PROCEDURE IF EXISTS sp_delete_modul');
    }
};
