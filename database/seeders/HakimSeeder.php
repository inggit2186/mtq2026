<?php

namespace Database\Seeders;

use App\Models\Hakim;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class HakimSeeder extends Seeder
{
    public function run(): void
    {
        // Disable foreign key checks temporarily
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('hakim_golongan')->truncate();
        DB::table('hakim')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $hakims = [
            ['id' => 1, 'nama' => 'H. Afrizon, S.Ag, M.Pd', 'asal' => 'Kepala Bagian Kesra'],
            ['id' => 2, 'nama' => 'H. Hendri Pani Dias, S.Ag, MA', 'asal' => 'Kepala Kemenag Tanah Datar'],
            ['id' => 3, 'nama' => 'Drs. H. Masnefi, MS', 'asal' => 'Pemerhati MTQ'],
            ['id' => 4, 'nama' => 'H. Zulhermi, S.Ag', 'asal' => 'LPTQ Tanah Datar'],
            ['id' => 5, 'nama' => 'Yanti Novita, SIQ, MA', 'asal' => 'UIN Imam Bonjol'],
            ['id' => 6, 'nama' => 'Sofia Deswati, S.Sos.I', 'asal' => 'Pondok Alquran Pariangan'],
            ['id' => 7, 'nama' => 'Irnawati, S.Sos.I, M.Pd.I', 'asal' => 'Kemenag Tanah Datar'],
            ['id' => 8, 'nama' => 'Rona Oktavia, SEI', 'asal' => 'Pondok Alquran Lima Kaum'],
            ['id' => 9, 'nama' => 'Rustam', 'asal' => 'Pondok Alquran Tanjung Emas'],
            ['id' => 10, 'nama' => 'Erdinal Anas, S.IQ', 'asal' => 'Pondok Alquran Lima Kaum'],
            ['id' => 11, 'nama' => 'H. Adrizal, S.IQ', 'asal' => 'STAI-PIQ Padang'],
            ['id' => 12, 'nama' => 'H. Alfion, S.Ag', 'asal' => 'SMA 3 Batusangkar'],
            ['id' => 13, 'nama' => 'H. Zulhermi, S.Ag', 'asal' => 'Dinas Pendidikan'],
            ['id' => 14, 'nama' => 'H. Jhoni Efendi, Lc, MA', 'asal' => 'LPTQ Sumatera Barat'],
            ['id' => 15, 'nama' => 'Mawardi, A.Ma', 'asal' => 'Pensiunan Kemenag'],
            ['id' => 16, 'nama' => 'Arisgianto, S.Pd.I', 'asal' => 'Pondok Alquran Lima Kaum'],
            ['id' => 17, 'nama' => 'Muhammad Rifat, S.IQ', 'asal' => 'Guru SD Kota Padang'],
            ['id' => 18, 'nama' => 'Rosmalinar, A.Md.Kep', 'asal' => 'RSUD Padang Panjang'],
            ['id' => 19, 'nama' => 'H. Albizar, S.IQ, M.Pd', 'asal' => 'STAI-PIQ Padang'],
            ['id' => 20, 'nama' => 'Indra Hadi, S.IQ', 'asal' => 'STAI-PIQ Padang'],
            ['id' => 21, 'nama' => 'Aulia Raflis, Lc, MH', 'asal' => 'Pondok Tahfizh Pariangan'],
            ['id' => 22, 'nama' => 'Asri Ade Putra', 'asal' => 'Pembina Tahfizh Lima Kaum'],
            ['id' => 23, 'nama' => 'Hidayaturrahmi, MH', 'asal' => 'Pembina Tahfizh Lima Kaum'],
            ['id' => 24, 'nama' => 'H. Burhanudin, S.IQ', 'asal' => 'Pembina Tahfizh Tanjung Emas'],
            ['id' => 25, 'nama' => 'H. Bakri, S.IQ, S.Th.I, M.Pd.I', 'asal' => 'Kanwil Kemenag Sumatera Barat'],
            ['id' => 26, 'nama' => 'H. Irsyad, S.IQ', 'asal' => 'STAI-PIQ Padang'],
            ['id' => 27, 'nama' => 'Nadrial, S.Ag', 'asal' => 'KUA Candung Agam'],
            ['id' => 28, 'nama' => 'Hamdani, Lc, MA', 'asal' => 'UIN Syech Djamil Djambek'],
            ['id' => 29, 'nama' => 'SS. Dt. Bijayo', 'asal' => 'LPTQ Tanah Datar'],
            ['id' => 30, 'nama' => 'M. Irfan, S.Pd.I', 'asal' => 'KUA Rambatan'],
            ['id' => 31, 'nama' => 'Zainal Hamdi, S.IQ', 'asal' => 'Kakankemenag Mentawai'],
            ['id' => 32, 'nama' => 'Ismet, S.IQ, S.Ag', 'asal' => 'SMPN 2 Padang Panjang'],
            ['id' => 33, 'nama' => 'H. Budi Hendri, S.IQ', 'asal' => 'STAI-PIQ Padang'],
            ['id' => 34, 'nama' => 'Mustarijal, S.IQ, S.Ag', 'asal' => 'SDN 4 Padang Panjang Timur'],
            ['id' => 35, 'nama' => 'Jefriansyah, SE', 'asal' => 'Qari Tanah Datar'],
            ['id' => 36, 'nama' => 'Usfanil, S.Sos', 'asal' => 'KUA Lintau Buo Utara'],
            ['id' => 37, 'nama' => 'Dr. Inong Satriadi, MA', 'asal' => 'UIN Mahmud Yunus'],
            ['id' => 38, 'nama' => 'Yusri Amir, MA', 'asal' => 'UIN Imam Bonjol'],
            ['id' => 39, 'nama' => 'Rivi Pratama Putra, S.Hum', 'asal' => 'Pondok Alquran Lintau Buo'],
            ['id' => 40, 'nama' => 'Abu Hanifah, SS', 'asal' => 'Kemenag Tanah Datar'],
            ['id' => 41, 'nama' => 'Yuni Alfat, S.Pd.I', 'asal' => 'Pondok Alquran Lima Kaum'],
            ['id' => 42, 'nama' => 'Gusneli', 'asal' => 'Pondok Alquran Salimpaung'],
            ['id' => 43, 'nama' => 'Depi Dasmal, S.Ag, M.Ag', 'asal' => 'Kemenag Kota Padang'],
            ['id' => 44, 'nama' => 'Drs. H. Rifai Ramli', 'asal' => 'Pensiunan Kemenag Sijunjung'],
            ['id' => 45, 'nama' => 'Ahmad Anshori, MA', 'asal' => 'UIN Mahmud Yunus'],
            ['id' => 46, 'nama' => 'Doni Nurhadi, MH', 'asal' => 'Pondok Alquran'],
            ['id' => 47, 'nama' => 'Dr. H. Andrialdi, MA', 'asal' => 'UIN Mahmud Yunus'],
            ['id' => 48, 'nama' => 'Dr. Rifi Fitla, M.Ag', 'asal' => 'UIN Imam Bonjol'],
            ['id' => 49, 'nama' => 'H. Muhammad Abrar, Lc, MA', 'asal' => 'Kemenag Tanah Datar'],
            ['id' => 50, 'nama' => 'H. Burhanudin, Lc, MA', 'asal' => 'Ponpes Darul Ulum'],
            ['id' => 51, 'nama' => 'H. Tri Purna Jeri, Lc, M.Ag', 'asal' => 'Ponpes Muallimin Muhammadiyah'],
            ['id' => 52, 'nama' => 'Drs. H. Emrizal Dt Hyang Basa, MM', 'asal' => 'UIN Mahmud Yunus'],
            ['id' => 53, 'nama' => 'Irfan Eko Juanda', 'asal' => 'Ponpes Padang Pariaman'],
            ['id' => 54, 'nama' => 'Darussalam, S.Pd.I', 'asal' => 'Penyuluh Agama Tanjung Emas'],
            ['id' => 55, 'nama' => 'Darmanto, S.Pd.I', 'asal' => 'Pondok Alquran Lintau Buo Utara'],
            ['id' => 56, 'nama' => 'Prof. Dr. Syukri Iska', 'asal' => 'UIN Mahmud Yunus'],
            ['id' => 57, 'nama' => 'Dr. Zaim Rais', 'asal' => 'UIN Imam Bonjol'],
            ['id' => 58, 'nama' => 'Dr. Faisal, M.Ag', 'asal' => 'UIN Imam Bonjol'],
            ['id' => 59, 'nama' => 'Irwandi, S.Ag, MA', 'asal' => 'UIN Mahmud Yunus'],
            ['id' => 60, 'nama' => 'Rita Gamasari, MA', 'asal' => 'LPTQ Sumatera Barat'],
            ['id' => 61, 'nama' => 'Dr. Rika Maria, MA', 'asal' => 'MAN 1 Tanah Datar'],
            ['id' => 62, 'nama' => 'Afnazianto, S.Pd.I', 'asal' => 'MTsN 6 Tanah Datar'],
            ['id' => 63, 'nama' => 'Isnaini, S.Ag', 'asal' => 'MIN Puncak Alai'],
            ['id' => 64, 'nama' => 'Zulmairici, S.Pd.I', 'asal' => 'Pondok Alquran X Koto'],
            ['id' => 65, 'nama' => 'Agusrimanda, MH', 'asal' => 'Pondok Alquran Tanjung Emas'],
            ['id' => 66, 'nama' => 'H. Yendri Junaidi, Lc, MA', 'asal' => 'MUI Tanah Datar'],
            ['id' => 67, 'nama' => 'Dr. H. Arif ZM, M.Ag', 'asal' => 'UIN Mahmud Yunus'],
            ['id' => 68, 'nama' => 'Fikran Aulia Afsyah, Lc, M.Ag', 'asal' => 'Ponpes Surau Qur\'an Simpuruik'],
        ];

        $hakimGolongan = [
            [5, 5], [5, 6], [6, 5], [6, 6], [7, 5], [7, 6], [8, 5], [8, 6],
            [9, 5], [9, 6], [10, 5], [10, 6], [11, 7], [11, 8], [12, 7], [12, 8],
            [13, 7], [13, 8], [14, 7], [14, 8], [15, 7], [15, 8], [16, 7], [16, 8],
            [17, 7], [17, 8], [18, 7], [18, 8], [19, 7], [19, 8], [20, 10],
            [20, 12], [20, 13], [21, 10], [21, 12], [21, 13], [22, 10], [22, 12],
            [22, 13], [23, 10], [23, 12], [23, 13], [24, 10], [24, 12], [24, 13],
            [25, 9], [25, 11], [26, 9], [26, 11], [27, 9], [27, 11], [28, 9],
            [28, 11], [29, 9], [29, 11], [30, 9], [30, 11], [31, 14], [31, 15],
            [31, 16], [32, 14], [32, 15], [32, 16], [33, 14], [33, 15], [33, 16],
            [34, 14], [34, 15], [34, 16], [35, 14], [35, 15], [35, 16], [36, 14],
            [36, 15], [36, 16], [37, 20], [37, 21], [38, 20], [38, 21], [39, 20],
            [39, 21], [40, 22], [40, 23], [41, 22], [41, 23], [42, 22], [42, 23],
            [43, 24], [43, 25], [44, 24], [44, 25], [45, 24], [45, 25], [46, 24],
            [46, 25], [47, 17], [47, 18], [47, 19], [48, 17], [48, 18], [48, 19],
            [49, 17], [49, 18], [49, 19], [50, 17], [50, 18], [50, 19], [51, 17],
            [51, 18], [51, 19], [52, 28], [52, 29], [53, 28], [53, 29], [54, 28],
            [54, 29], [55, 28], [55, 29], [56, 30], [57, 30], [58, 30], [59, 30],
            [60, 26], [60, 27], [61, 26], [61, 27], [62, 26], [62, 27], [63, 26],
            [63, 27], [64, 26], [64, 27], [65, 26], [65, 27], [66, 31], [66, 32],
            [67, 31], [67, 32], [68, 31], [68, 32],
        ];

        foreach ($hakims as $hakim) {
            Hakim::create($hakim);
        }

        foreach ($hakimGolongan as [$hakimId, $golonganId]) {
            DB::table('hakim_golongan')->insert([
                'hakim_id' => $hakimId,
                'golongan_id' => $golonganId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
