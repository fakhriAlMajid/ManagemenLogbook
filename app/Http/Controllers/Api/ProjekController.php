<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use OpenApi\Attributes as OA;

class ProjekController extends Controller
{
    #[OA\Get(
        path: "/api/projek",
        tags: ["Projek"],
        summary: "List projects (Filter & Search)",
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "search", in: "query", required: false, schema: new OA\Schema(type: "string"), description: "Cari nama projek"),
            new OA\Parameter(name: "status", in: "query", required: false, schema: new OA\Schema(type: "string", enum: ["In Progress", "Completed", "On Hold"]), description: "Filter status"),
            new OA\Parameter(name: "kategori_id", in: "query", required: false, schema: new OA\Schema(type: "integer"), description: "Filter berdasarkan kategori")
        ],
        responses: [new OA\Response(response: 200, description: "List of projects", content: new OA\JsonContent(type: "array", items: new OA\Items(type: "object")))]
    )]
    public function index(Request $request)
    {
        $user = Auth::user();
        $userId = $user->usr_id;
        $userRole = $user->usr_role;

        // Normalisasi status filter (mendukung "InProgress" dan "In Progress")
        $status = $request->status;
        if (is_string($status) && trim($status) !== '') {
            $raw = trim($status);
            $normalized = strtolower(str_replace(' ', '', $raw));

            if ($normalized === 'inprogress') {
                $status = 'In Progress';
            } elseif ($normalized === 'onhold') {
                $status = 'On Hold';
            } elseif ($normalized === 'completed' || $normalized === 'selesai') {
                $status = 'Completed';
            }
        }

        if ($userRole === 'Admin') {
            $projek = DB::select('CALL sp_read_projek(NULL, ?, ?)', [
                $request->search,
                $status
            ]);
        } else {
            $query = "
                SELECT p.* FROM projek p
                INNER JOIN member_projek m ON p.pjk_id = m.pjk_id
                WHERE m.usr_id = ?
            ";

            if ($request->search) $query .= " AND p.pjk_nama LIKE '%{$request->search}%'";
            if ($request->status) $query .= " AND p.pjk_status = '{$request->status}'";
            if ($request->kategori_id) $query .= " AND p.ktg_id = {$request->kategori_id}";

            $projek = DB::select($query, [$userId]);
        }

        if ($userRole === 'Admin' && $request->kategori_id) {
            $projek = collect($projek)->filter(function ($p) use ($request) {
                return $p->ktg_id == $request->kategori_id;
            })->values()->toArray();
        }

        foreach ($projek as $p) {
            $stats = DB::select('CALL sp_get_dashboard_card_stats(?)', [$p->pjk_id]);
            $stats = $stats[0] ?? null;
            $p->total_tasks = $stats->total_tasks ?? 0;
            $p->completed_tasks = $stats->completed_tasks ?? 0;

            $leader = DB::select('SELECT CONCAT(u.usr_first_name, " ", u.usr_last_name) as leader_name FROM users u JOIN member_projek m ON u.usr_id = m.usr_id WHERE m.pjk_id = ? AND m.mpk_role_projek = "Ketua" LIMIT 1', [$p->pjk_id]);
            $p->leader_name = $leader[0]->leader_name ?? null;

            $pic = DB::select('SELECT CONCAT(u.usr_first_name, " ", u.usr_last_name) as pic_name FROM users u JOIN member_projek m ON u.usr_id = m.usr_id WHERE m.pjk_id = ? AND m.mpk_role_projek <> "Ketua" ORDER BY m.mpk_create_at ASC LIMIT 1', [$p->pjk_id]);
            $p->pic_name = $pic[0]->pic_name ?? null;

            $creator = DB::select('SELECT CONCAT(u.usr_first_name, " ", u.usr_last_name) as creator_name FROM users u WHERE u.usr_username = ? LIMIT 1', [$p->pjk_create_by]);
            $p->creator_name = $creator[0]->creator_name ?? null;
        }

        return \App\Http\Resources\ProjekResource::collection(collect($projek));
    }

    #[OA\Post(
        path: "/api/projek",
        tags: ["Projek"],
        summary: "Create a project",
        security: [["bearerAuth" => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                type: "object",
                required: ["nama", "pic", "tgl_mulai", "tgl_selesai"],
                properties: [
                    new OA\Property(property: "nama", type: "string", example: "Sistem Managemen Logbook"),
                    new OA\Property(property: "pic", type: "string", example: "Acid"),
                    new OA\Property(property: "deskripsi", type: "string", example: "Projek untuk mengelola logbook tugas"),
                    new OA\Property(property: "tgl_mulai", type: "string", format: "date", example: "2026-01-10"),
                    new OA\Property(property: "tgl_selesai", type: "string", format: "date", example: "2026-06-30")
                ],
                example: '{"nama":"Sistem Managemen Logbook", "pic":"Acid","deskripsi":"Projek untuk mengelola logbook tugas","tgl_mulai":"2026-01-10","tgl_selesai":"2026-06-30"}'
            )
        ),
        responses: [new OA\Response(response: 201, description: "Projek created"), new OA\Response(response: 400, description: "Validation error")]
    )]
    public function store(Request $request)
    {
        $request->validate([
            'nama' => 'required',
            'pic' => 'required',
            'tgl_mulai' => 'required|date',
            'tgl_selesai' => 'required|date',
        ]);

        $user = Auth::user();

        try {
            $result = DB::select('CALL sp_create_projek_with_leader(?, ?, ?, ?, ?, ?, ?)', [
                $request->nama,
                $request->deskripsi ?? '-',
                $request->pic,
                $request->tgl_mulai,
                $request->tgl_selesai,
                $user->usr_id,
                $user->usr_username
            ]);

            $projektId = $result[0]->pjk_id;

            if ($request->kategori_id) {
                DB::table('projek')
                    ->where('pjk_id', $projektId)
                    ->update(['ktg_id' => $request->kategori_id]);
            }

            return response()->json([
                'message' => 'Projek created successfully',
                'pjk_id' => $projektId
            ], 201);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    #[OA\Get(
        path: "/api/projek/{id}",
        tags: ["Projek"],
        summary: "Get project details",
        security: [["bearerAuth" => []]],
        parameters: [new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"))],
        responses: [new OA\Response(response: 200, description: "Project details"), new OA\Response(response: 404, description: "Not Found")]
    )]
    public function show($id)
    {
        $result = DB::select('CALL sp_read_projek(?, NULL, NULL)', [$id]);
        $projek = $result[0] ?? null;

        if (!$projek) return response()->json(['message' => 'Not Found'], 404);

        $stats = DB::select('CALL sp_get_dashboard_card_stats(?)', [$id]);

        $breakdown = DB::select('CALL sp_get_project_breakdown(?)', [$id]);

        return response()->json([
            'detail' => new \App\Http\Resources\ProjekResource($projek),
            'stats' => $stats[0] ?? null,
            'structure' => $breakdown
        ]);
    }

    #[OA\Put(
        path: "/api/projek/{id}",
        tags: ["Projek"],
        summary: "Update project",
        security: [["bearerAuth" => []]],
        parameters: [new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"))],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                type: "object",
                properties: [
                    new OA\Property(property: "nama", type: "string", example: "Nama Projek Baru"),
                    new OA\Property(property: "deskripsi", type: "string", example: "Deskripsi proyek"),
                    new OA\Property(property: "tgl_mulai", type: "string", format: "date", example: "2026-01-10"),
                    new OA\Property(property: "tgl_selesai", type: "string", format: "date", example: "2026-06-30"),
                    new OA\Property(property: "status", type: "string", example: "InProgress")
                ],
                example: '{"nama":"Nama Projek Baru","deskripsi":"Deskripsi proyek","tgl_mulai":"2026-01-10","tgl_selesai":"2026-06-30","status":"InProgress"}'
            )
        ),
        responses: [new OA\Response(response: 200, description: "Projek updated"), new OA\Response(response: 404, description: "Not Found")]
    )]
    public function update(Request $request, $id)
    {
        $oldData = DB::select('CALL sp_read_projek(?, NULL, NULL)', [$id])[0] ?? null;
        if (!$oldData) return response()->json(['message' => 'Not Found'], 404);

        try {
            DB::select('CALL sp_update_projek(?, ?, ?, ?, ?, ?, ?, ?)', [
                $id,
                $request->nama ?? $oldData->pjk_nama,
                $request->deskripsi ?? $oldData->pjk_deskripsi,
                $request->pic ?? $oldData->pjk_pic,
                $request->tgl_mulai ?? $oldData->pjk_tanggal_mulai,
                $request->tgl_selesai ?? $oldData->pjk_tanggal_selesai,
                $request->status ?? $oldData->pjk_status,
                Auth::user()->usr_username
            ]);

            if ($request->has('kategori_id')) {
                DB::table('projek')
                    ->where('pjk_id', $id)
                    ->update(['ktg_id' => $request->kategori_id]);
            }

            return response()->json(['message' => 'Projek updated']);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    #[OA\Delete(
        path: "/api/projek/{id}",
        tags: ["Projek"],
        summary: "Delete project",
        security: [["bearerAuth" => []]],
        parameters: [new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"))],
        responses: [new OA\Response(response: 200, description: "Projek deleted"), new OA\Response(response: 404, description: "Not Found")]
    )]
    public function destroy($id)
    {
        DB::select('CALL sp_delete_projek(?)', [$id]);
        return response()->json(['message' => 'Projek deleted']);
    }

    #[OA\Get(
        path: "/api/projek/{id}/stats",
        tags: ["Projek"],
        summary: "Get Project Dashboard Stats",
        security: [["bearerAuth" => []]],
        parameters: [new OA\Parameter(name: "id", in: "path", required: true, description: "ID Projek", schema: new OA\Schema(type: "integer"))],
        responses: [new OA\Response(response: 200, description: "Success")]
    )]
    public function getDashboardStats($id)
    {
        $stats = DB::select('CALL sp_get_dashboard_card_stats(?)', [$id]);
        if (empty($stats)) return response()->json(['message' => 'Projek tidak ditemukan atau data kosong'], 404);
        return response()->json($stats[0]);
    }

    #[OA\Get(
        path: "/api/projek/{id}/breakdown",
        tags: ["Projek"],
        summary: "Get Project Breakdown (Modul & Kegiatan)",
        security: [["bearerAuth" => []]],
        parameters: [new OA\Parameter(name: "id", in: "path", required: true, description: "ID Projek", schema: new OA\Schema(type: "integer"))],
        responses: [new OA\Response(response: 200, description: "Success")]
    )]
    public function getProjectBreakdown($id)
    {
        $breakdown = DB::select('CALL sp_get_project_breakdown(?)', [$id]);
        return response()->json($breakdown);
    }

    #[OA\Post(
        path: "/api/projek/{id}/recalculate",
        tags: ["Projek"],
        summary: "Force Recalculate Progress",
        security: [["bearerAuth" => []]],
        parameters: [new OA\Parameter(name: "id", in: "path", required: true, description: "ID Projek", schema: new OA\Schema(type: "integer"))],
        responses: [new OA\Response(response: 200, description: "Success")]
    )]
    public function recalculateProgress($id)
    {
        try {
            DB::select('CALL sp_kalkulasi_progress_projek(?)', [$id]);
            return response()->json(['message' => 'Progress projek berhasil dihitung ulang']);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Gagal menghitung ulang: ' . $e->getMessage()], 500);
        }
    }

    public function getMembers($id)
    {
        $rawMembers = DB::table('member_projek')
            ->join('users', 'member_projek.usr_id', '=', 'users.usr_id')
            ->where('member_projek.pjk_id', $id)
            ->select(
                'member_projek.mpk_id as id',
                'member_projek.mpk_role_projek as role',
                'users.usr_id as user_id',
                'users.usr_first_name',
                'users.usr_last_name',
                'users.usr_email'
            )
            ->get();

        $members = $rawMembers->map(function ($m) {
            return [
                'id' => $m->id,
                'role' => $m->role,
                'user' => [
                    'id' => $m->user_id,
                    'name' => trim($m->usr_first_name . ' ' . $m->usr_last_name),
                    'email' => $m->usr_email
                ]
            ];
        });

        return response()->json($members);
    }

    public function addMember(Request $request, $id)
    {
        $request->validate([
            'user_id' => 'required|exists:users,usr_id',
            'role' => 'required|string|max:50',
        ]);

        $exists = DB::table('member_projek')
            ->where('pjk_id', $id)
            ->where('usr_id', $request->user_id)
            ->exists();

        if ($exists) {
            return response()->json(['message' => 'User is already a member of this project'], 422);
        }

        DB::table('member_projek')->insert([
            'pjk_id' => $id,
            'usr_id' => $request->user_id,
            'mpk_role_projek' => $request->role,
        ]);

        return response()->json(['message' => 'Member added successfully'], 201);
    }

    #[OA\Delete(
        path: "/api/projek/{id}/member/{memberId}",
        tags: ["Projek"],
        summary: "Remove a project member",
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, description: "Project ID", schema: new OA\Schema(type: "integer")),
            new OA\Parameter(name: "memberId", in: "path", required: true, description: "Member ID", schema: new OA\Schema(type: "integer"))
        ],
        responses: [
            new OA\Response(response: 200, description: "Success"),
            new OA\Response(response: 403, description: "Forbidden"),
            new OA\Response(response: 404, description: "Not Found"),
            new OA\Response(response: 422, description: "Unprocessable Entity"),
            new OA\Response(response: 500, description: "Server Error")
        ]
    )]
    public function removeMember($id, $memberId)
    {
        try {
            $currentUser = Auth::user();

            $isLeader = DB::table('member_projek')
                ->where('pjk_id', $id)
                ->where('usr_id', $currentUser->usr_id)
                ->where('mpk_role_projek', 'Ketua')
                ->exists();

            if (!$isLeader && $currentUser->usr_role !== 'Admin') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Only the project leader or Admin can manage team members.'
                ], 403);
            }

            $member = DB::table('member_projek')
                ->where('mpk_id', $memberId)
                ->where('pjk_id', $id)
                ->first();

            if (!$member) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Member data not found.'
                ], 404);
            }

            $isAssignedToTask = DB::table('tugas')
                ->join('kegiatan', 'tugas.kgt_id', '=', 'kegiatan.kgt_id')
                ->join('modul', 'kegiatan.mdl_id', '=', 'modul.mdl_id')
                ->where('modul.pjk_id', $id)
                ->where('tugas.usr_id', $member->usr_id)
                ->exists();

            if ($isAssignedToTask) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Member cannot be removed because they are assigned as a PIC for one or more tasks in this project. Please reassign their tasks first.'
                ], 422);
            }

            DB::table('member_projek')
                ->where('mpk_id', $memberId)
                ->where('pjk_id', $id)
                ->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Member successfully removed from the project.'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to remove member: ' . $e->getMessage()
            ], 500);
        }
    }

    public function updateMember(Request $request, $id, $memberId)
    {
        $request->validate([
            'role' => 'required|string|max:50',
        ]);

        $updated = DB::table('member_projek')
            ->where('pjk_id', $id)
            ->where('mpk_id', $memberId)
            ->update([
                'mpk_role_projek' => $request->role,
            ]);

        if ($updated) {
            return response()->json(['message' => 'Member role updated successfully']);
        }

        return response()->json(['message' => 'Member not found or no changes made'], 404);
    }

    public function getUsers(Request $request)
    {
        $query = DB::table('users');

        if ($request->has('role')) {
            $query->where('usr_role', $request->role);
        }

        if ($request->has('exclude_project')) {
            $projectId = $request->exclude_project;

            $query->whereNotExists(function ($subquery) use ($projectId) {
                $subquery->select(DB::raw(1))
                    ->from('member_projek')
                    ->whereRaw('member_projek.usr_id = users.usr_id')
                    ->where('member_projek.pjk_id', $projectId);
            });
        }

        $users = $query->select(
            'usr_id as id',
            DB::raw("CONCAT(usr_first_name, ' ', usr_last_name) as name"),
            'usr_email as email'
        )->get();

        return response()->json($users);
    }
}
