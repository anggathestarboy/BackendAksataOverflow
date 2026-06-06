<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use App\Models\Post;
use App\Models\Report;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;


class ReportController extends Controller
{

    public function getAllReports()
    {
        return response()->json([
            "message" => "success getting all reports",
            "data" => Report::with('reporter', 'resolvedBy', 'post', 'comment', 'user')->paginate(10)
        ]);
    }

    public function getUserReports(Request $request)
    {
        return response()->json([
            "message" => "success getting user reports",
            "data" => Report::with('reporter', 'resolvedBy', 'user', 'post', 'comment')->where('reporter_id', $request->user()->id)->paginate(10)
        ]);
    }



    public function store(Request $request)
    {
        $request->validate([
            'target_id' => 'required',
            'reason' => 'required',
            'description' => 'nullable|string|max:500'
        ]);

        $targetId = $request->target_id;

        if (Post::where('id', $targetId)->exists()) {

            $report = Report::create([
            'reporter_id' => Auth::id(),
            'target_type' => "post",
            'target_id' => $request->target_id,
            'reason' => $request->reason,
            'description' => $request->description,
        ]);

        // Opsional: Notify moderator
        // Event::dispatch(new ReportCreated($report));

        return response()->json([
            'message' => 'Report submitted successfully',
            'data' => $report,
        ], 201);
        }
        elseif (Comment::where('id', $targetId)->exists()) {
            $report = Report::create([
            'reporter_id' => Auth::id(),
            'target_type' => "comment",
            'target_id' => $request->target_id,
            'reason' => $request->reason,
            'description' => $request->description,
        ]);

        return response()->json([
            'message' => 'Report submitted successfully',
            'data' => $report,
        ], 201);
        }
        elseif (User::where('id', $targetId)->exists()) {
            $report = Report::create([
            'reporter_id' => Auth::id(),
            'target_type' => "user",
            'target_id' => $request->target_id,
            'reason' => $request->reason,
            'description' => $request->description,
        ]);

       

        return response()->json([
            'message' => 'Report submitted successfully',
            'data' => $report,
        ], 201);
        }
        else{
            return response()->json([
                "message" => "Invalid target"
            ], 400);
        }

      

     
    }


    public function resolveReport(Request $request, $id)
    {

        $request->validate([
            "status" => "required|in:resolved,dismissed,reviewed,pending"
        ]);

        $report = Report::find($id);
        if (!$report) {
            return response()->json([
                "message" => "Report not found"
            ], 404);
        }
    $report->update([
        "status" => $request->status,
        "resolved_by" => $request->user()->id,
        "resolved_at" => now(),
    ]);
        return response()->json([
            "message" => "Report resolved successfully",
            "data" => $report
        ]);
    }
}
