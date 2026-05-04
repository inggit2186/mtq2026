<section class="mt-6">
    <div class="app-footer-shell overflow-hidden rounded-[2rem] border border-cyan-400/16 bg-gradient-to-br from-slate-900/92 via-sky-950/72 to-blue-950/72 p-6 shadow-[0_20px_60px_-34px_rgba(34,211,238,0.35)]">
        <div class="flex flex-wrap items-start justify-between gap-6">
            <div class="flex flex-wrap items-center gap-4">
                <div class="flex h-20 w-20 items-center justify-center rounded-[1.5rem] border border-white/10 bg-slate-950/35 p-3 shadow-[0_18px_45px_-30px_rgba(34,211,238,0.45)]">
                    <img src="<?= e(asset('images/favicon.png')) ?>" alt="Logo Kementerian Agama" class="h-full w-full object-contain">
                </div>
                <div class="flex h-20 w-20 items-center justify-center rounded-[1.5rem] border border-cyan-200/30 bg-slate-50 p-2.5 shadow-[0_18px_45px_-30px_rgba(125,211,252,0.45)]">
                    <img src="<?= e(asset('images/logo-emtq-temp.svg')) ?>" alt="Logo e-MTQ" class="h-full w-full object-contain">
                </div>
            </div>
            <div class="max-w-3xl flex-1 min-w-[280px]">
                <p class="text-xs font-semibold uppercase tracking-[0.28em] text-cyan-200">Tentang Sistem</p>
                <h2 class="mt-3 text-xl font-black tracking-tight text-white sm:text-2xl">e-MTQ Kabupaten Tanah Datar</h2>
                <p class="mt-3 text-sm leading-7 text-slate-300">
                    Ruang kerja digital ini dihadirkan untuk membantu proses pendaftaran, verifikasi, penilaian, dan pengelolaan data MTQ
                    secara lebih tertata.
                </p>
                <p class="mt-3 flex flex-wrap items-center gap-x-2 gap-y-1 text-sm leading-7 text-slate-300">
                    <span class="inline-flex h-6 w-6 items-center justify-center rounded-full border border-white/10 bg-slate-950/45 text-xs font-bold text-cyan-100">&copy;</span>
                    <strong class="text-white">Ridho Saputra S.Kom</strong>
                    <span class="text-cyan-100">- Pranata Komputer Ahli Pertama - Kantor Kementerian Agama Kab.Tanah Datar</span>
                </p>
            </div>
            <div class="min-w-[220px] rounded-[1.5rem] border border-white/10 bg-slate-950/30 px-5 py-4">
                <p class="text-xs uppercase tracking-[0.22em] text-slate-400">Kontak & Informasi</p>
                <p class="mt-3 text-sm font-semibold text-white">CP WhatsApp</p>
                <a href="https://wa.me/6289509007078" target="_blank" rel="noreferrer" class="mt-2 inline-flex items-center gap-2 text-lg font-black tracking-[0.04em] text-cyan-200 transition hover:text-cyan-100">
                    <span class="inline-flex h-9 w-9 items-center justify-center rounded-full border border-[#5de0a6]/40 bg-[linear-gradient(135deg,#25D366_0%,#128C7E_52%,#34b7f1_100%)] text-[#dcfce7] shadow-[0_10px_24px_-14px_rgba(37,211,102,0.85)]">
                        <svg aria-hidden="true" viewBox="0 0 24 24" class="h-5 w-5 fill-current">
                        <path d="M19.05 4.91A9.82 9.82 0 0 0 12.03 2C6.62 2 2.22 6.4 2.22 11.82c0 1.73.45 3.42 1.3 4.9L2 22l5.43-1.42a9.8 9.8 0 0 0 4.6 1.17h.01c5.41 0 9.81-4.4 9.81-9.82 0-2.62-1.02-5.08-2.8-6.99ZM12.04 20.1h-.01a8.13 8.13 0 0 1-4.14-1.13l-.3-.18-3.22.84.86-3.14-.2-.32a8.14 8.14 0 0 1-1.25-4.34c0-4.5 3.66-8.16 8.17-8.16 2.18 0 4.23.84 5.77 2.39a8.1 8.1 0 0 1 2.38 5.78c0 4.5-3.67 8.16-8.16 8.16Zm4.47-6.1c-.25-.12-1.47-.72-1.7-.8-.22-.08-.38-.12-.55.13-.16.24-.63.8-.77.96-.14.16-.29.18-.53.06-.25-.12-1.04-.38-1.98-1.23-.73-.64-1.22-1.43-1.36-1.67-.14-.24-.01-.37.1-.49.1-.1.25-.29.37-.43.12-.15.16-.24.24-.4.08-.16.04-.31-.02-.43-.06-.12-.55-1.33-.75-1.82-.2-.48-.4-.41-.55-.42h-.47c-.16 0-.43.06-.65.31-.22.24-.85.83-.85 2.03 0 1.2.87 2.35.99 2.52.12.16 1.7 2.6 4.12 3.64.58.25 1.03.4 1.38.5.58.18 1.1.15 1.52.09.46-.07 1.47-.6 1.68-1.18.2-.58.2-1.07.14-1.18-.05-.1-.21-.16-.45-.28Z"/>
                        </svg>
                    </span>
                    <span>0895-0900-7078</span>
                </a>
                <p class="mt-3 text-xs leading-6 text-slate-400">Dipakai sebagai sarana pendukung operasional e-MTQ dan komunikasi teknis pengembangan sistem.</p>
            </div>
        </div>
    </div>
</section>
<?php require __DIR__.'/ongoing-schedules.php'; ?>
