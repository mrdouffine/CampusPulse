<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Session;
use App\Models\Attendance;
use App\Models\User;
use App\Models\Course;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class AttendanceController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('role:teacher');
    }

    public function index(Course $course): Response
    {
        $this->authorize('view', $course);

        $sessions = $course->sessions()
            ->with('attendances.user')
            ->orderBy('date', 'desc')
            ->get()
            ->map(function ($session) {
                return [
                    'id' => $session->id,
                    'date' => $session->date->format('Y-m-d'),
                    'topic' => $session->topic,
                    'duration_minutes' => $session->duration_minutes,
                    'attendance_rate' => $session->attendance_rate,
                    'present_count' => $session->presentAttendances()->count(),
                    'absent_count' => $session->absentAttendances()->count(),
                    'late_count' => $session->lateAttendances()->count(),
                    'total_students' => $session->attendances()->count(),
                    'is_completed' => $session->attendances()->count() > 0,
                ];
            });

        return Inertia::render('Teacher/Attendances/Index', [
            'course' => [
                'id' => $course->id,
                'name' => $course->name,
                'code' => $course->code,
            ],
            'sessions' => $sessions,
        ]);
    }

    public function create(Course $course): Response
    {
        $this->authorize('update', $course);

        $students = $course->users()
            ->students()
            ->orderBy('name')
            ->get(['id', 'name', 'student_number']);

        return Inertia::render('Teacher/Attendances/Create', [
            'course' => [
                'id' => $course->id,
                'name' => $course->name,
                'code' => $course->code,
            ],
            'students' => $students->map(function ($student) {
                return [
                    'id' => $student->id,
                    'name' => $student->name,
                    'student_number' => $student->student_number,
                    'status' => 'present', // Par défaut : présent
                    'note' => '',
                ];
            }),
        ]);
    }

    public function store(Request $request, Course $course): RedirectResponse
    {
        $this->authorize('update', $course);

        $validated = $request->validate([
            'date' => 'required|date',
            'topic' => 'nullable|string|max:255',
            'duration_minutes' => 'required|integer|min:1|max:480',
            'attendances' => 'required|array|min:1',
            'attendances.*.user_id' => 'required|exists:users,id',
            'attendances.*.status' => 'required|in:present,absent,late',
            'attendances.*.note' => 'nullable|string|max:500',
        ]);

        // Créer la session
        $session = Session::create([
            'course_id' => $course->id,
            'date' => $validated['date'],
            'topic' => $validated['topic'],
            'duration_minutes' => $validated['duration_minutes'],
        ]);

        // Créer les présences en batch
        $attendancesData = [];
        foreach ($validated['attendances'] as $attendance) {
            $attendancesData[] = [
                'session_id' => $session->id,
                'user_id' => $attendance['user_id'],
                'status' => $attendance['status'],
                'note' => $attendance['note'] ?? null,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        Attendance::insert($attendancesData);

        return redirect()
            ->route('teacher.courses.sessions.attendances.index', $course)
            ->with('success', 'La présence a été enregistrée avec succès.');
    }

    public function edit(Course $course, Session $session): Response
    {
        $this->authorize('update', $course);

        $session->load(['attendances.user']);

        $students = $course->users()
            ->students()
            ->orderBy('name')
            ->get(['id', 'name', 'student_number']);

        $attendancesMap = $session->attendances
            ->keyBy('user_id')
            ->map(function ($attendance) {
                return [
                    'id' => $attendance->id,
                    'status' => $attendance->status,
                    'note' => $attendance->note,
                ];
            });

        $studentsWithAttendances = $students->map(function ($student) use ($attendancesMap) {
            $attendance = $attendancesMap->get($student->id);
            
            return [
                'id' => $student->id,
                'name' => $student->name,
                'student_number' => $student->student_number,
                'status' => $attendance['status'] ?? 'present',
                'note' => $attendance['note'] ?? '',
                'attendance_id' => $attendance['id'] ?? null,
            ];
        });

        return Inertia::render('Teacher/Attendances/Edit', [
            'course' => [
                'id' => $course->id,
                'name' => $course->name,
                'code' => $course->code,
            ],
            'session' => [
                'id' => $session->id,
                'date' => $session->date->format('Y-m-d'),
                'topic' => $session->topic,
                'duration_minutes' => $session->duration_minutes,
            ],
            'students' => $studentsWithAttendances,
        ]);
    }

    public function update(Request $request, Course $course, Session $session): RedirectResponse
    {
        $this->authorize('update', $course);

        $validated = $request->validate([
            'date' => 'required|date',
            'topic' => 'nullable|string|max:255',
            'duration_minutes' => 'required|integer|min:1|max:480',
            'attendances' => 'required|array|min:1',
            'attendances.*.user_id' => 'required|exists:users,id',
            'attendances.*.status' => 'required|in:present,absent,late',
            'attendances.*.note' => 'nullable|string|max:500',
        ]);

        // Mettre à jour la session
        $session->update([
            'date' => $validated['date'],
            'topic' => $validated['topic'],
            'duration_minutes' => $validated['duration_minutes'],
        ]);

        // Mettre à jour les présences
        foreach ($validated['attendances'] as $attendanceData) {
            Attendance::updateOrCreate(
                [
                    'session_id' => $session->id,
                    'user_id' => $attendanceData['user_id'],
                ],
                [
                    'status' => $attendanceData['status'],
                    'note' => $attendanceData['note'] ?? null,
                ]
            );
        }

        return redirect()
            ->route('teacher.courses.sessions.attendances.index', $course)
            ->with('success', 'La présence a été mise à jour avec succès.');
    }

    public function destroy(Course $course, Session $session): RedirectResponse
    {
        $this->authorize('delete', $course);

        $session->attendances()->delete();
        $session->delete();

        return redirect()
            ->route('teacher.courses.sessions.attendances.index', $course)
            ->with('success', 'La séance a été supprimée avec succès.');
    }

    public function show(Course $course, Session $session): Response
    {
        $this->authorize('view', $course);

        $session->load(['attendances.user', 'course']);

        $attendances = $session->attendances->map(function ($attendance) {
            return [
                'id' => $attendance->id,
                'user' => [
                    'id' => $attendance->user->id,
                    'name' => $attendance->user->name,
                    'student_number' => $attendance->user->student_number,
                ],
                'status' => $attendance->status,
                'status_label' => $attendance->status_label,
                'status_color' => $attendance->status_color,
                'status_icon' => $attendance->status_icon,
                'note' => $attendance->note,
                'created_at' => $attendance->created_at->format('Y-m-d H:i:s'),
            ];
        });

        return Inertia::render('Teacher/Attendances/Show', [
            'course' => [
                'id' => $course->id,
                'name' => $course->name,
                'code' => $course->code,
            ],
            'session' => [
                'id' => $session->id,
                'date' => $session->date->format('Y-m-d'),
                'topic' => $session->topic,
                'duration_minutes' => $session->duration_minutes,
                'attendance_rate' => $session->attendance_rate,
                'present_count' => $session->presentAttendances()->count(),
                'absent_count' => $session->absentAttendances()->count(),
                'late_count' => $session->lateAttendances()->count(),
                'total_students' => $session->attendances()->count(),
            ],
            'attendances' => $attendances,
        ]);
    }
}
