<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class FieldToolController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        if ($user->has_field_tool) {
            return view('tools.field-tool', ['is_trial' => false]);
        }

        if (!$user->trial_ends_at) {
            $user->update(['trial_ends_at' => Carbon::now()->addDays(7)]);
        }

        if (Carbon::now()->lessThan($user->trial_ends_at)) {
            $daysLeft = ceil(Carbon::now()->diffInDays($user->trial_ends_at));
            return view('tools.field-tool', ['is_trial' => true, 'days_left' => $daysLeft]);
        }

        return redirect()->route('pricing')->with('error', 'Your 7-day free trial has ended. Please purchase Tool 1 for $1 to continue.');
    }

    public function generateExcel(Request $request)
    {
        $user = Auth::user();
        
        if (!$user->has_field_tool && Carbon::now()->greaterThan($user->trial_ends_at)) {
            abort(403, 'Payment required.');
        }

        $data = $request->validate([
            'A' => 'required|numeric', 
            'B' => 'required|numeric',
            'H' => 'required|numeric', 
            'P' => 'required|numeric',
            'M' => 'required|numeric',
            'N' => 'required|numeric',
            'project_title' => 'nullable|string|max:255',
            'contract_no' => 'nullable|string|max:255',
            'logo' => 'nullable|image|max:2048',
        ]);

        $templatePath = storage_path('app/templates/N10-9610.xlsx');
        if (!file_exists($templatePath)) {
            abort(500, 'Master Excel template not found at ' . $templatePath);
        }

        $spreadsheet = IOFactory::load($templatePath);
        $sheet = $spreadsheet->getActiveSheet();

        // 1. Inject Data
        $sheet->setCellValue('G18', $data['A']);
        $sheet->setCellValue('G19', $data['B']);
        $sheet->setCellValue('G26', $data['H']);
        $sheet->setCellValue('G35', $data['P']);
        $sheet->setCellValue('G31', $data['M']);
        $sheet->setCellValue('G32', $data['N']);
        
        $sheet->setCellValue('D10', date('Y-m-d'));

        // 2. Inject Custom Header Text (Centered)
        if (!empty($data['project_title'])) {
            $sheet->setCellValue('C5', $data['project_title']);
            $sheet->getStyle('C5')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        }
        if (!empty($data['contract_no'])) {
            $sheet->setCellValue('C6', $data['contract_no']);
            $sheet->getStyle('C6')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        }

                                 // 3. Inject Logo (Anchored to exact cells)
        if ($request->hasFile('logo')) {
            $imagePath = $request->file('logo')->getPathname();

            $drawing = new \PhpOffice\PhpSpreadsheet\Worksheet\Drawing();
            $drawing->setName('Logo');
            $drawing->setPath($imagePath);
            
            // Anchor the image from A1
            $drawing->setCoordinates('A1');
            
            // Set the image size to match the width of Columns A through K
            // and the height of Rows 1 through 4.
            // We use a multiplier here to force it to stretch to the edges.
            $drawing->setResizeProportional(false); // Allow stretching to fit perfectly
            
            // Approximate width of A to K in pixels is ~600, height of 4 rows is ~80
            $drawing->setWidth(760); 
            $drawing->setHeight(100); 
            
            // Nudge it perfectly to the top left
            $drawing->setOffsetX(5);
            $drawing->setOffsetY(2);
            
            $drawing->setWorksheet($sheet);
        }

       
        // 4. Generate Excel and record successful generation
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $fileName = 'Field_Checklist_' . date('Ymd_His') . '.xlsx';

        $outputPath = tempnam(sys_get_temp_dir(), 'fdt_');

        try {
            // Create Excel file first
            $writer->save($outputPath);
        } catch (\Throwable $e) {
            @unlink($outputPath);
            throw $e;
        }

        // Record successful generation
        try {
            DB::table('generation_logs')->insert([
                'user_id'    => $user->id,
                'tool_type'  => 'field_tool',
                'quantity'   => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (\Throwable $logError) {
            \Illuminate\Support\Facades\Log::error(
                'Field Tool generation history could not be saved: ' . $logError->getMessage()
            );
        }

        // Download Excel
        return response()
            ->download($outputPath, $fileName)
            ->deleteFileAfterSend(true);

    }
}