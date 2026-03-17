<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // ==============================================================================
        // BAGIAN 1: CORE ENGINE (KALKULASI PROGRESS OTOMATIS)
        // ==============================================================================

        DB::unprepared('
            DROP PROCEDURE IF EXISTS sp_kalkulasi_progress_projek;
            
            CREATE PROCEDURE sp_kalkulasi_progress_projek(IN id_projek_target INT)
            BEGIN
                DECLARE total_progress_baru DECIMAL(5,2);

                -- Rumus: SUM( (Progress% * Bobot) / 100 ) dari semua tugas di projek
                SELECT 
                    IFNULL(SUM((t.tgs_persentasi_progress * t.tgs_bobot) / 100), 0)
                INTO total_progress_baru
                FROM tugas t
                JOIN kegiatan k ON t.kgt_id = k.kgt_id
                JOIN modul m ON k.mdl_id = m.mdl_id
                WHERE m.pjk_id = id_projek_target;

                -- Capping max 100%
                IF total_progress_baru > 100 THEN SET total_progress_baru = 100; END IF;

                -- Update Header Projek
                UPDATE projek 
                SET pjk_persentasi_progress = total_progress_baru,
                    pjk_modified_at = NOW(),
                    pjk_modified_by = "SYSTEM"
                WHERE pjk_id = id_projek_target;
            END
        ');

        // 2. TRIGGER: Auto-Status "Completed" jika 100% (Before Update)
        DB::unprepared('
            DROP TRIGGER IF EXISTS trg_update_status_tugas;
            
            CREATE TRIGGER trg_update_status_tugas
            BEFORE UPDATE ON tugas
            FOR EACH ROW
            BEGIN
                -- Jika user isi 100%, status jadi Completed
                IF NEW.tgs_persentasi_progress = 100 THEN
                    SET NEW.tgs_status = "Completed";
                END IF;
                
                -- Jika user set status Completed, progress jadi 100%
                IF NEW.tgs_status = "Completed" AND OLD.tgs_status != "Completed" THEN
                    SET NEW.tgs_persentasi_progress = 100;
                END IF;
            END
        ');

        // 3. TRIGGER: Panggil SP Kalkulasi setelah update (After Update)
        DB::unprepared('
            DROP TRIGGER IF EXISTS trg_kalkulasi_parent_projek;
            
            CREATE TRIGGER trg_kalkulasi_parent_projek
            AFTER UPDATE ON tugas
            FOR EACH ROW
            BEGIN
                DECLARE id_projek_induk INT;

                -- Cari ID Projek
                SELECT m.pjk_id INTO id_projek_induk
                FROM modul m
                JOIN kegiatan k ON m.mdl_id = k.mdl_id
                WHERE k.kgt_id = NEW.kgt_id
                LIMIT 1;

                -- Jalankan Kalkulasi
                IF id_projek_induk IS NOT NULL THEN
                    CALL sp_kalkulasi_progress_projek(id_projek_induk);
                END IF;
            END
        ');

        // ==============================================================================
        // BAGIAN 2: DASHBOARD STATS (UNTUK KARTU PROJEK)
        // ==============================================================================

        // 4. SP: Ambil Statistik Ringkas (Contoh output: "18/26 Done")
        // Digunakan di halaman "Projek.png" untuk progress bar kecil "Tasks 18/26 done"
        DB::unprepared('
            DROP PROCEDURE IF EXISTS sp_get_dashboard_card_stats;
            CREATE PROCEDURE sp_get_dashboard_card_stats(IN id_projek INT)
            BEGIN
                SELECT 
                    p.pjk_id,
                    p.pjk_nama,
                    p.pjk_pic, -- Kolom PIC baru
                    p.pjk_tanggal_mulai,
                    p.pjk_tanggal_selesai,
                    p.pjk_persentasi_progress as project_progress,
                    COUNT(t.tgs_id) as total_tasks,
                    SUM(CASE WHEN t.tgs_status = "Completed" OR t.tgs_persentasi_progress = 100 THEN 1 ELSE 0 END) as completed_tasks
                FROM projek p
                LEFT JOIN modul m ON p.pjk_id = m.pjk_id
                LEFT JOIN kegiatan k ON m.mdl_id = k.mdl_id
                LEFT JOIN tugas t ON k.kgt_id = t.kgt_id
                WHERE p.pjk_id = id_projek
                GROUP BY p.pjk_id, p.pjk_nama, p.pjk_pic, p.pjk_tanggal_mulai, p.pjk_tanggal_selesai, p.pjk_persentasi_progress;
            END
        ');


        DB::unprepared('
            DROP PROCEDURE IF EXISTS sp_get_project_breakdown;

            DROP PROCEDURE IF EXISTS sp_get_project_breakdown;

            CREATE PROCEDURE sp_get_project_breakdown(IN id_projek INT)
            BEGIN
                DECLARE v_total_bobot_projek DECIMAL(10,2);
                
                -- Ambil total bobot seluruh tugas dalam satu projek
                SELECT SUM(tgs_bobot) INTO v_total_bobot_projek 
                FROM tugas t
                JOIN kegiatan k ON t.kgt_id = k.kgt_id
                JOIN modul m ON k.mdl_id = m.mdl_id
                WHERE m.pjk_id = id_projek;

                -- Jika total bobot 0, set ke 1 untuk menghindari pembagian dengan nol
                IF v_total_bobot_projek IS NULL OR v_total_bobot_projek = 0 THEN 
                    SET v_total_bobot_projek = 1; 
                END IF;

                -- 1. Ambil data MODUL
                SELECT 
                    m.mdl_nama as nama_item,
                    "Modul" as tipe_item,
                    m.mdl_urut as urutan,
                    IFNULL(AVG(t.tgs_persentasi_progress), 0) as progress_persen,
                    IFNULL(SUM(t.tgs_bobot), 0) as bobot_angka,
                    -- Rumus: (Total Bobot Modul / Total Bobot Projek) * 100
                    IFNULL((SUM(t.tgs_bobot) / v_total_bobot_projek) * 100, 0) as prosentase_item,
                    -- Kontribusi terhadap Projek (Weighted)
                    IFNULL(SUM((t.tgs_persentasi_progress / 100) * (t.tgs_bobot / v_total_bobot_projek) * 100), 0) as kontribusi_total
                FROM modul m
                LEFT JOIN kegiatan k ON m.mdl_id = k.mdl_id
                LEFT JOIN tugas t ON k.kgt_id = t.kgt_id
                WHERE m.pjk_id = id_projek
                GROUP BY m.mdl_id
                
                UNION ALL
                
                -- 2. Ambil data KEGIATAN
                SELECT 
                    k.kgt_nama as nama_item,
                    "Kegiatan" as tipe_item,
                    m.mdl_urut as urutan, 
                    IFNULL(AVG(t.tgs_persentasi_progress), 0) as progress_persen,
                    IFNULL(SUM(t.tgs_bobot), 0) as bobot_angka,
                    IFNULL((SUM(t.tgs_bobot) / v_total_bobot_projek) * 100, 0) as prosentase_item,
                    IFNULL(SUM((t.tgs_persentasi_progress / 100) * (t.tgs_bobot / v_total_bobot_projek) * 100), 0) as kontribusi_total
                FROM kegiatan k
                JOIN modul m ON k.mdl_id = m.mdl_id
                LEFT JOIN tugas t ON k.kgt_id = t.kgt_id
                WHERE m.pjk_id = id_projek
                GROUP BY k.kgt_id
                
                ORDER BY urutan ASC, tipe_item DESC; 
            END
        ');
    }

    public function down(): void
    {
        DB::unprepared('DROP PROCEDURE IF EXISTS sp_get_project_breakdown');
        DB::unprepared('DROP PROCEDURE IF EXISTS sp_get_dashboard_card_stats');
        DB::unprepared('DROP TRIGGER IF EXISTS trg_kalkulasi_parent_projek');
        DB::unprepared('DROP TRIGGER IF EXISTS trg_update_status_tugas');
        DB::unprepared('DROP PROCEDURE IF EXISTS sp_kalkulasi_progress_projek');
    }
};
