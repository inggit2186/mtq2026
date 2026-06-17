<?php

namespace App\Http\Controllers;

use App\Models\CompetitionCategory;
use App\Models\District;
use App\Models\Participant;
use App\Models\ScoreEntry;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class AdminExportController extends Controller
{
    public function index(): View
    {
        abort_unless(in_array(auth()->user()?->role, ['admin', 'panitia']), 403);

        $categories = CompetitionCategory::query()
            ->orderBy('sort_order')
            ->orderBy('branch')
            ->orderBy('name')
            ->get()
            ->groupBy('branch');

        $branches = $categories->keys()->toArray();

        return view('pages/admin-export-v2', [
            'assets' => app(PageController::class)->viteAssets(),
            'categories' => $categories,
            'branches' => $branches,
        ]);
    }

    public function exportExcelByCategory(Request $request): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        abort_unless(in_array(auth()->user()?->role, ['admin', 'panitia']), 403);

        $categoryId = $request->query('category_id');
        $category = CompetitionCategory::findOrFail($categoryId);

        $participants = Participant::with(['district', 'category'])
            ->where('competition_category_id', $categoryId)
            ->where('verification_status', 'verified')
            ->orderBy('district_id')
            ->orderBy('name')
            ->get();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Header styling
        $headerStyle = [
            'font' => ['bold' => true],
            'fill' => ['type' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '0D9488']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
        ];

        $headerFont = ['bold' => true, 'color' => ['rgb' => 'FFFFFF']];
        $headerBg = ['fill' => ['type' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '0D9488']]];

        // Set headers
        $headers = ['No', 'Nama Peserta', 'Gender', 'NIK', 'Nomor KK', 'Tempat Lahir', 'Tanggal Lahir', 'Kecamatan', 'Nomor Lot', 'Keterangan'];
        $columnWidths = [6, 30, 10, 20, 20, 25, 15, 20, 12, 20];

        foreach ($headers as $col => $header) {
            $cell = chr(65 + $col) . '1';
            $sheet->setCellValue($cell, $header);
            $sheet->getStyle($cell)->applyFromArray([
                'font' => $headerFont,
                'fill' => $headerBg,
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
            ]);
            $sheet->getColumnDimension(chr(65 + $col))->setWidth($columnWidths[$col]);
        }

        $sheet->getRowDimension(1)->setRowHeight(30);

        // Data rows
        $row = 2;
        $no = 1;
        foreach ($participants as $participant) {
            $sheet->setCellValue('A' . $row, $no);
            $sheet->setCellValue('B' . $row, $participant->name);
            $sheet->setCellValue('C' . $row, strtoupper($participant->gender ?? '-'));
            $sheet->setCellValue('D' . $row, $participant->nik ?? '-');
            $sheet->setCellValue('E' . $row, $participant->kk_number ?? '-');
            $sheet->setCellValue('F' . $row, $participant->birth_place ?? '-');
            $sheet->setCellValue('G' . $row, $participant->birth_date ? date('d/m/Y', strtotime($participant->birth_date)) : '-');
            $sheet->setCellValue('H' . $row, $participant->district?->name ?? '-');
            $sheet->setCellValue('I' . $row, $participant->lot_number ?? '-');
            $sheet->setCellValue('J' . $row, ''); // Keterangan - empty

            // Row styling
            $rowStyle = [
                'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
            ];
            $sheet->getStyle('A' . $row . ':J' . $row)->applyFromArray($rowStyle);
            $sheet->getStyle('A' . $row)->applyFromArray(['alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]]);
            $sheet->getStyle('C' . $row)->applyFromArray(['alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]]);
            $sheet->getStyle('I' . $row)->applyFromArray(['alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]]);

            $row++;
            $no++;
        }

        $filename = 'Data_Peserta_' . preg_replace('/[^a-zA-Z0-9]/', '_', $category->branch) . '_' . preg_replace('/[^a-zA-Z0-9]/', '_', $category->name) . '_' . date('Ymd') . '.xlsx';

        $writer = new Xlsx($spreadsheet);

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    public function downloadAllKokarde(Request $request): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        abort_unless(in_array(auth()->user()?->role, ['admin', 'panitia']), 403);

        $categoryId = $request->query('category_id');
        $query = Participant::with(['district', 'category'])
            ->where('verification_status', 'verified');

        if ($categoryId) {
            $query->where('competition_category_id', $categoryId);
        }

        $participants = $query->orderBy('district_id')->orderBy('name')->get();

        if ($participants->isEmpty()) {
            return redirect()->back()->with('error', 'Tidak ada peserta untuk diunduh.');
        }

        // For now, we'll create individual downloads as a zip would require additional libraries
        // Return the first one as a demo, or redirect to individual download page
        if ($participants->count() === 1) {
            return redirect()->route('participants.kokarde', $participants->first()->id);
        }

        // If multiple participants, create a page with download links
        return redirect()->route('admin.export.kokarde.page', ['category_id' => $categoryId]);
    }

    public function kokardePage(Request $request): View
    {
        abort_unless(in_array(auth()->user()?->role, ['admin', 'panitia']), 403);

        $categoryId = $request->query('category_id');
        $category = $categoryId ? CompetitionCategory::find($categoryId) : null;

        $query = Participant::with(['district', 'category'])
            ->where('verification_status', 'verified');

        if ($categoryId) {
            $query->where('competition_category_id', $categoryId);
        }

        $participants = $query->orderBy('district_id')->orderBy('name')->get();

        return view('pages/admin-kokarde-download', [
            'assets' => app(PageController::class)->viteAssets(),
            'participants' => $participants,
            'category' => $category,
        ]);
    }
}
