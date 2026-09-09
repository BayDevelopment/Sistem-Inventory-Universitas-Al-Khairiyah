<?php

namespace App\Http\Controllers;

use App\Models\Procurement;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class ProcurementPdfController extends Controller
{
    public function pdf(Procurement $procurement)
    {
        Gate::authorize('view', $procurement);

        /*
        |--------------------------------------------------------------------------
        | Dokumen hanya dapat dicetak setelah disetujui
        |--------------------------------------------------------------------------
        */

        if (
            !in_array(
                $procurement->status,
                [
                    Procurement::STATUS_APPROVED,
                    Procurement::STATUS_COMPLETED,
                ],
                true
            )
        ) {
            throw ValidationException::withMessages([
                'procurement' => 'Dokumen hanya dapat dicetak setelah pengajuan disetujui.',
            ]);
        }

        if (empty($procurement->document_number)) {
            throw ValidationException::withMessages([
                'procurement' => 'Nomor dokumen belum tersedia.',
            ]);
        }

        $procurement->load([
            'faculty:id,name,code,letterhead_path,dean,dean_nip,dean_signature',
            'requester:id,name,position,nip',
            'room:id,faculty_id,code,name,building,floor',
            'processor:id,name,position,nip',
        ]);

        $typeLabels = [
            'replacement' => 'Penggantian Barang',
            'new_item' => 'Barang Baru',
        ];

        $statusLabels = [
            'pending' => 'Menunggu Verifikasi',
            'approved' => 'Disetujui',
            'rejected' => 'Ditolak',
            'completed' => 'Selesai',
        ];

        $room = $procurement->room;
        $roomLabel = $room
            ? trim(($room->code ? $room->code . ' - ' : '') . $room->name)
            : '-';

        $roomLocation = null;
        if ($room) {
            $parts = array_filter([
                $room->building,
                $room->floor ? 'Lt. ' . $room->floor : null,
            ]);
            $roomLocation = $parts ? implode(' · ', $parts) : null;
        }

        /*
        |--------------------------------------------------------------------------
        | Resolve gambar (kop surat & tanda tangan) jadi absolute path lokal
        |--------------------------------------------------------------------------
        |
        | dompdf butuh path file fisik di server, bukan URL relatif seperti
        | "/storage/..." yang biasa dipakai di Vue.
        |
        */

        $resolveImage = function (?string $path, ?string $fallbackPublicPath = null): ?string {
            $toDataUri = function (string $fullPath): ?string {
                if (!file_exists($fullPath) || !is_readable($fullPath)) {
                    return null;
                }

                $mimeType = mime_content_type($fullPath) ?: 'image/png';
                $contents = file_get_contents($fullPath);

                if ($contents === false) {
                    return null;
                }

                return 'data:' . $mimeType . ';base64,' . base64_encode($contents);
            };

            if ($path) {
                $value = trim($path);

                // Kalau memang sudah URL/base64, langsung pakai
                if (str_starts_with($value, 'http') || str_starts_with($value, 'data:image')) {
                    return $value;
                }

                $clean = ltrim($value, '/');
                $clean = preg_replace('#^(public/|storage/)#', '', $clean);

                $candidates = [
                    storage_path('app/public/' . $clean),
                    storage_path('app/' . $clean),
                    public_path('storage/' . $clean),
                    public_path($clean),
                ];

                foreach ($candidates as $candidate) {
                    $dataUri = $toDataUri($candidate);
                    if ($dataUri) {
                        return $dataUri;
                    }
                }
            }

            if ($fallbackPublicPath) {
                $dataUri = $toDataUri(public_path($fallbackPublicPath));
                if ($dataUri) {
                    return $dataUri;
                }
            }

            return null;
        };

        $letterheadPath = $resolveImage(
            $procurement->faculty?->letterhead_path,
            'images/kop-surat.png'
        );

        $data = [
            'procurement' => $procurement,
            'faculty' => $procurement->faculty,
            'requester' => $procurement->requester,
            'processor' => $procurement->processor,
            'roomLabel' => $roomLabel,
            'roomLocation' => $roomLocation,
            'typeLabel' => $typeLabels[$procurement->type] ?? '-',
            'statusLabel' => $statusLabels[$procurement->status] ?? '-',
            'requestedAt' => $procurement->requested_at
                ? Carbon::parse($procurement->requested_at)->translatedFormat('d F Y \p\u\k\u\l H:i')
                : '-',
            'processedAt' => $procurement->processed_at
                ? Carbon::parse($procurement->processed_at)->translatedFormat('d F Y \p\u\k\u\l H:i')
                : null,
            'printedAt' => Carbon::now()->translatedFormat('d F Y'),
            'letterheadPath' => $letterheadPath,
            'requesterSignature' => $resolveImage($procurement->requester_signature),
            'processorSignature' => $resolveImage($procurement->approver_signature),
            'deanSignature' => $resolveImage($procurement->faculty?->dean_signature),
        ];

        // dd([
        //     'raw_letterhead_path' => $procurement->faculty?->letterhead_path,
        //     'resolved' => $letterheadPath,
        //     'file_exists' => $letterheadPath ? file_exists($letterheadPath) : false,
        // ]);

        $pdf = Pdf::loadView('pdf.procurement', $data)
            ->setPaper('a4', 'portrait')
            ->setOptions([
                'isRemoteEnabled' => true,
                'isHtml5ParserEnabled' => true,
                'defaultFont' => 'DejaVu Sans',
            ]);

        return $pdf->stream('pengadaan-' . $procurement->id . '.pdf');
    }
}
