<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use OpenApi\Attributes as OA;

class TugasController extends Controller
{
    #[OA\Get(
        path: "/api/tugas",
        tags: ["Tugas"],
        summary: "Get tasks (filter & search)",
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "kgt_id", in: "query", required: false, schema: new OA\Schema(type: "integer"), description: "Filter Kegiatan"),
            new OA\Parameter(name: "usr_id", in: "query", required: false, schema: new OA\Schema(type: "integer"), description: "Filter PIC"),
            new OA\Parameter(name: "status", in: "query", required: false, schema: new OA\Schema(type: "string"), description: "Filter Status"),
            new OA\Parameter(name: "search", in: "query", required: false, schema: new OA\Schema(type: "string"), description: "Search Name/Code")
        ],
        responses: [new OA\Response(response: 200, description: "List of tasks", content: new OA\JsonContent(type: "array", items: new OA\Items(type: "object")))]
    )]
    public function index(Request $request)
    {
        // Param: p_tgs_id(NULL), p_kgt_id, p_usr_id, p_status, p_search
        $tugas = DB::select('CALL sp_read_tugas(NULL, ?, ?, ?, ?)', [
            $request->kgt_id,
            $request->usr_id,
            $request->status,
            $request->search
        ]);

        return \App\Http\Resources\TugasResource::collection(collect($tugas));
    }

    #[OA\Post(
        path: "/api/tugas",
        tags: ["Tugas"],
        summary: "Create a tugas with auto-generated prefix",
        security: [["bearerAuth" => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                type: "object",
                required: ["kgt_id", "usr_id", "nama", "tgl_mulai", "tgl_selesai", "bobot"],
                properties: [
                    new OA\Property(property: "kgt_id", type: "integer", example: 1),
                    new OA\Property(property: "usr_id", type: "integer", example: 5),
                    new OA\Property(property: "nama", type: "string", example: "Integrasi API Payment"),
                    new OA\Property(property: "tgl_mulai", type: "string", format: "date", example: "2026-01-15"),
                    new OA\Property(property: "tgl_selesai", type: "string", format: "date", example: "2026-01-20"),
                    new OA\Property(property: "bobot", type: "integer", example: 10)
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: "Tugas created successfully"),
            new OA\Response(response: 422, description: "Validation error"),
            new OA\Response(response: 500, description: "Server error")
        ]
    )]
    public function store(Request $request)
    {
        // Parameter 'kode' dihapus karena digenerate otomatis oleh SP
        $request->validate([
            'kgt_id' => 'required|exists:kegiatan,kgt_id',
            'usr_id' => 'required|exists:users,usr_id',
            'nama' => 'required|string|max:200',
            'tgl_mulai' => 'required|date',
            'tgl_selesai' => 'required|date|after_or_equal:tgl_mulai',
            'bobot' => 'required|integer|min:1|max:100',
        ]);

        try {
            // Memanggil SP baru yang menerima 7 parameter (tanpa p_kode)
            $result = DB::select('CALL sp_create_tugas(?, ?, ?, ?, ?, ?, ?)', [
                $request->kgt_id,
                $request->usr_id,
                $request->nama,
                $request->tgl_mulai,
                $request->tgl_selesai,
                $request->bobot,
                Auth::user()->usr_username
            ]);

            return response()->json([
                'message' => 'Tugas created successfully',
                'data' => $result[0] ?? null
            ], 201);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    #[OA\Put(
        path: "/api/tugas/{id}",
        tags: ["Tugas"],
        summary: "Update tugas (Progress or Full Data)",
        security: [["bearerAuth" => []]],
        parameters: [new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"))],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                type: "object",
                properties: [
                    new OA\Property(property: "progress", type: "number"),
                    new OA\Property(property: "nama", type: "string"),
                    new OA\Property(property: "usr_id", type: "integer"),
                    new OA\Property(property: "tgl_selesai", type: "string", format: "date")
                ]
            )
        ),
        responses: [new OA\Response(response: 200, description: "Tugas updated")]
    )]
    public function update(Request $request, $id)
    {
        // 1. Validasi input
        $request->validate([
            'progress' => 'sometimes|numeric|min:0|max:100',
            'usr_id'   => 'sometimes|exists:users,usr_id',
            'nama'     => 'sometimes|string',
            'tgl_selesai' => 'sometimes|date'
        ]);

        // 2. Ambil data lama
        $result = DB::select('CALL sp_read_tugas(?, NULL, NULL, NULL, NULL)', [$id]);
        $oldData = $result[0] ?? null;

        if (!$oldData) {
            return response()->json(['message' => 'Tugas not found'], 404);
        }

        try {
            // 3. Pastikan urutan parameter SP sesuai: p_tgs_id, p_usr_id, p_nama, p_tgl_selesai, p_progress, p_modifier
            DB::select('CALL sp_update_tugas(?, ?, ?, ?, ?, ?)', [
                $id,
                $request->input('usr_id', $oldData->usr_id), // Mengambil dari request atau fallback ke data lama
                $request->input('nama', $oldData->tgs_nama),
                $request->input('tgl_selesai', $oldData->tgs_tanggal_selesai),
                $request->input('progress', $oldData->tgs_persentasi_progress),
                Auth::user()->usr_username
            ]);

            // Pastikan nilai progress project segera ter-update (jika trigger DB tidak jalan)
            $project = DB::table('modul')
                ->join('kegiatan', 'modul.mdl_id', '=', 'kegiatan.mdl_id')
                ->join('tugas', 'kegiatan.kgt_id', '=', 'tugas.kgt_id')
                ->where('tugas.tgs_id', $id)
                ->select('modul.pjk_id')
                ->first();

            if ($project && $project->pjk_id) {
                DB::select('CALL sp_kalkulasi_progress_projek(?)', [$project->pjk_id]);
            }

            return response()->json(['message' => 'Tugas updated successfully']);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    #[OA\Get(
        path: "/api/tugas/{id}",
        tags: ["Tugas"],
        summary: "Get tugas detail",
        security: [["bearerAuth" => []]],
        parameters: [new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"))],
        responses: [new OA\Response(response: 200, description: "Tugas details")]
    )]
    public function show($id)
    {
        // Param: p_tgs_id($id), sisanya NULL
        $result = DB::select('CALL sp_read_tugas(?, NULL, NULL, NULL, NULL)', [$id]);
        if (empty($result)) return response()->json(['message' => 'Not Found'], 404);

        return new \App\Http\Resources\TugasResource($result[0]);
    }

    #[OA\Delete(
        path: "/api/tugas/{id}",
        tags: ["Tugas"],
        summary: "Delete tugas",
        security: [["bearerAuth" => []]],
        parameters: [new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"))],
        responses: [new OA\Response(response: 200, description: "Tugas deleted")]
    )]
    public function destroy($id)
    {
        DB::select('CALL sp_delete_tugas(?)', [$id]);
        return response()->json(['message' => 'Tugas deleted']);
    }
}
