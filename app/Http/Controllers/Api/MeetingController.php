<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Meeting;
use App\Models\MeetingAttendance;
use App\Models\Vote;
use App\Models\VoteResponse;
use App\Models\Notification;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class MeetingController extends Controller
{
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'meeting_type' => 'required|in:agm,executive,committee,emergency,regular',
            'meeting_date' => 'required|date|after:now',
            'venue' => 'required|string|max:255',
            'agenda' => 'required|string',
            'notify_members' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $meeting = Meeting::create([
            'title' => $request->title,
            'description' => $request->description,
            'meeting_type' => $request->meeting_type,
            'meeting_date' => $request->meeting_date,
            'venue' => $request->venue,
            'agenda' => $request->agenda,
            'notify_members' => $request->notify_members ?? true,
            'created_by' => $request->user()->id,
        ]);

        ActivityLog::log(
            $request->user()->id,
            'meeting_created',
            "Meeting created: {$meeting->title}",
            $meeting
        );

        if ($meeting->notify_members) {
            $members = \App\Models\User::whereHas('member', function ($q) {
                $q->where('status', 'active');
            })->get();

            foreach ($members as $member) {
                Notification::createMeetingNotice(
                    $member->id,
                    $meeting->title,
                    $meeting->meeting_date->format('Y-m-d H:i')
                );
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Meeting created successfully',
            'data' => $meeting
        ], 201);
    }

    public function index(Request $request)
    {
        $query = Meeting::with('createdBy', 'attendance');

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('meeting_type')) {
            $query->where('meeting_type', $request->meeting_type);
        }

        if ($request->has('from_date') && $request->has('to_date')) {
            $query->whereBetween('meeting_date', [$request->from_date, $request->to_date]);
        }

        $meetings = $query->orderBy('meeting_date', 'desc')
            ->paginate($request->per_page ?? 20);

        return response()->json([
            'success' => true,
            'data' => $meetings
        ]);
    }

    public function show(Request $request, $id)
    {
        $meeting = Meeting::with([
            'createdBy',
            'attendance.member',
            'votes',
            'votes.responses.member',
            'documents'
        ])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $meeting
        ]);
    }

    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'venue' => 'nullable|string|max:255',
            'agenda' => 'nullable|string',
            'minutes' => 'nullable|string',
            'status' => 'nullable|in:scheduled,ongoing,completed,cancelled',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $meeting = Meeting::findOrFail($id);

        $meeting->update($request->only([
            'title',
            'description',
            'venue',
            'agenda',
            'minutes',
            'status'
        ]));

        ActivityLog::log(
            $request->user()->id,
            'meeting_updated',
            "Meeting updated: {$meeting->title}",
            $meeting
        );

        return response()->json([
            'success' => true,
            'message' => 'Meeting updated successfully',
            'data' => $meeting
        ]);
    }

    public function destroy(Request $request, $id)
    {
        $meeting = Meeting::findOrFail($id);
        $title = $meeting->title;
        $meeting->delete();

        ActivityLog::log(
            $request->user()->id,
            'meeting_deleted',
            "Meeting deleted: {$title}"
        );

        return response()->json([
            'success' => true,
            'message' => 'Meeting deleted successfully'
        ]);
    }

    public function markAttendance(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:present,absent,excused',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $member = $request->user()->member;

        if (!$member) {
            return response()->json([
                'success' => false,
                'message' => 'Member profile not found'
            ], 404);
        }

        $meeting = Meeting::findOrFail($id);

        $attendance = $meeting->markAttendance(
            $member->id,
            $request->status,
            $request->status === 'present' ? now() : null
        );

        return response()->json([
            'success' => true,
            'message' => 'Attendance recorded successfully',
            'data' => $attendance
        ]);
    }

    public function createVote(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'question' => 'required|string|max:500',
            'vote_type' => 'required|in:yes_no,multiple_choice,open',
            'options' => 'nullable|array',
            'options.*' => 'string',
            'start_time' => 'nullable|date',
            'end_time' => 'nullable|date|after:start_time',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        if ($request->vote_type === 'multiple_choice' && empty($request->options)) {
            return response()->json([
                'success' => false,
                'message' => 'Options are required for multiple choice votes'
            ], 422);
        }

        $meeting = Meeting::findOrFail($id);

        $vote = Vote::create([
            'meeting_id' => $meeting->id,
            'question' => $request->question,
            'vote_type' => $request->vote_type,
            'options' => $request->options,
            'start_time' => $request->start_time ?? now(),
            'end_time' => $request->end_time ?? now()->addHours(24),
            'created_by' => $request->user()->id,
        ]);

        ActivityLog::log(
            $request->user()->id,
            'vote_created',
            "Vote created: {$vote->question}",
            $vote
        );

        return response()->json([
            'success' => true,
            'message' => 'Vote created successfully',
            'data' => $vote
        ], 201);
    }

    public function castVote(Request $request, $id, $voteId)
    {
        $validator = Validator::make($request->all(), [
            'response' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $member = $request->user()->member;

        if (!$member) {
            return response()->json([
                'success' => false,
                'message' => 'Member profile not found'
            ], 404);
        }

        $vote = Vote::findOrFail($voteId);

        if ($vote->status !== 'active') {
            return response()->json([
                'success' => false,
                'message' => 'This vote is no longer active'
            ], 400);
        }

        if ($vote->hasVoted($member->id)) {
            return response()->json([
                'success' => false,
                'message' => 'You have already voted'
            ], 400);
        }

        $response = $vote->castVote($member->id, $request->response);

        if (!$response) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to cast vote'
            ], 500);
        }

        $vote->increment('total_votes');

        return response()->json([
            'success' => true,
            'message' => 'Vote cast successfully',
            'data' => [
                'vote' => $vote,
                'current_results' => $vote->results,
            ]
        ]);
    }
}