<?php

namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\AttendanceBreak;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AttendanceController extends Controller
{
    public function index($date = null)
{
    // 初期表示は今日
    $targetDate = $date ? Carbon::parse($date) : Carbon::today();

    // targetDate の勤怠のみ取得
    $attendances = Attendance::with('user')
        ->whereDate('date', $targetDate)
        ->orderBy('user_id')
        ->get();

    // 前日/翌日
    $prevDate = $targetDate->copy()->subDay()->format('Y-m-d');
    $nextDate = $targetDate->copy()->addDay()->format('Y-m-d');

    return view('admin.attendances.index', compact(
        'attendances', 'targetDate', 'prevDate', 'nextDate'
    ));
}


    public function showUserMonthly($userId, $year, $month)
{
    $attendances = Attendance::with(['breaks', 'user'])
    ->where('user_id', $userId)
    ->whereYear('date', $year)
    ->whereMonth('date', $month)
    ->orderBy('date')
    ->get();


    $user = User::findOrFail($userId);

    return view('admin.attendances.monthly', compact(
        'attendances', 'user', 'year', 'month'
    ));
}


public function show($id)
{
    $attendance = Attendance::with('user')->findOrFail($id);

    return view('admin.attendances.show', compact('attendance'));
}

public function update(Request $request, $id)
{
    $attendance = Attendance::with('breaks')->findOrFail($id);

    // 勤怠（出勤・退勤・備考）
    $attendance->update([
        'clock_in'  => $request->clock_in,
        'clock_out' => $request->clock_out,
        'note'      => $request->note,
    ]);

    // 休憩（複数対応）
    if ($request->has('breaks')) {
        foreach ($request->breaks as $breakId => $breakData) {

            // 空送信防止（これが超重要）
            if (empty($breakData['break_start']) || empty($breakData['break_end'])) {
                continue;
            }

            AttendanceBreak::where('id', $breakId)
                ->where('attendance_id', $attendance->id)
                ->update([
                    'break_start' => $breakData['break_start'],
                    'break_end'   => $breakData['break_end'],
                ]);
        }
    }

    return redirect()->back()->with('success', '勤怠を修正しました。');
}

}