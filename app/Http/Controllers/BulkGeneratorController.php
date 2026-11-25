<?php

namespace App\Http\Controllers;

use App\Services\SoilLayerGenerator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Throwable;

class BulkGeneratorController extends Controller
{
    public function index()
    {
        return view('tools.bulk-generator');
    }

    public function generate(Request $request, SoilLayerGenerator $generator)
    {
        $validated = $request->validate([
            'master_file'  => 'required|file|mimes:xlsx',
            'total_layers' => 'required|integer|min:2|max:30',
            'start_comp'   => 'required|numeric',
            'step_comp'    => 'required|numeric',
            'start_moist'  => 'required|numeric',
            'step_moist'   => 'required|numeric',
            'custom_dates' => 'nullable|string',
            'skip_fridays' => 'nullable|boolean',
        ]);

        $total = (int) $validated['total_layers'];

        // optional custom dates: "2026-03-26, 2026-03-28, ..."  (one per layer)
        $customDates = [];
        $text = trim((string) ($validated['custom_dates'] ?? ''));
        if ($text !== '') {
            $customDates = array_map('trim', explode(',', $text));
            if (count($customDates) !== $total) {
                return back()->withInput()->with(
                    'error',
                    'You provided ' . count($customDates) . " dates but need exactly {$total} dates."
                );
            }
        }

        $output = tempnam(sys_get_temp_dir(), 'soil_bulk_');

        try {
            $started = microtime(true);

            $generator->generate(
                $request->file('master_file')->getPathname(),
                $output,
                [
                    'total_layers' => $total,
                    'start_comp'   => (float) $validated['start_comp'],
                    'step_comp'    => (float) $validated['step_comp'],
                    'start_moist'  => (float) $validated['start_moist'],
                    'step_moist'   => (float) $validated['step_moist'],
                    'custom_dates' => $customDates,
                    'skip_fridays' => $request->boolean('skip_fridays', true),
                    'data_offset'  => 2,
                    'meta_offset'  => 3,
                ]
            );

              // Record successful bulk generation
try {
    DB::table('generation_logs')->insert([
        'user_id'    => $request->user()->id,
        'tool_type'  => 'bulk_generator',
        'quantity'   => $total,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
} catch (Throwable $logError) {
    Log::error('Bulk generation history could not be saved: ' . $logError->getMessage());
}



            Log::debug('Bulk generator finished', [
                'layers'  => $total,
                'seconds' => round(microtime(true) - $started, 3),
                'peak_mb' => round(memory_get_peak_usage(true) / 1048576),
            ]);

            return response()
                ->download($output, 'Bulk_Generated_' . date('Ymd_His') . '.xlsx', [
                    'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                ])
                ->deleteFileAfterSend(true);
        } catch (Throwable $e) {
            @unlink($output);
            Log::error('Bulk Generator Error: ' . $e->getMessage());

            return back()->withInput()->with('error', 'Error: ' . $e->getMessage());
        }
    }
}
