<?php

namespace App\Exports;

use App\Models\Attendance;
use App\Models\Leave;
use App\Models\User;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Border;
use Carbon\Carbon;
use Log;

class AttendanceExport implements FromCollection, WithHeadings, ShouldAutoSize, WithEvents
{

    protected $date;

    public function __construct(string $date)
    {
        $this->date = $date;
    }

    public function collection()
    {
        $users = User::join('designations', 'users.designation', '=', 'designations.id')
            ->select('users.*', 'designations.title as designation_title')
            ->get();


        $rows = collect();
        $counter = 1;
        $dateIsToday = now()->toDateString() === $this->date;

        foreach ($users as $user) {
            $attendances = Attendance::where('user_id', $user->id)
                ->whereDate('date', $this->date)
                ->orderBy('punchin')
                ->get();

            $leave = Leave::where('user_id', $user->id)
                ->whereDate('from_date', '<=', $this->date)
                ->whereDate('to_date', '>=', $this->date)
                ->first();

            if ($attendances->count()) {
                $firstIn = null;
                $lastOut = null;
                $totalMinutes = 0;
                $completePunches = 0;
                $incomplete = false;

                foreach ($attendances as $attendance) {
                    if ($attendance->punchin && !$firstIn) {
                        $firstIn = $attendance->punchin;
                    }
                    if ($attendance->punchout) {
                        $lastOut = $attendance->punchout;
                    }

                    if ($attendance->punchin && $attendance->punchout) {
                        $inTime = Carbon::parse($attendance->punchin);
                        $outTime = Carbon::parse($attendance->punchout);
                        if ($outTime->lt($inTime)) $outTime->addDay();
                        $totalMinutes += $inTime->diffInMinutes($outTime);
                        $completePunches++;
                    }

                    if ($attendance->punchin && !$attendance->punchout) {
                        $incomplete = true;
                    }
                }

                // Format work hours
                $workTime = $totalMinutes > 0
                    ? intval($totalMinutes / 60) . 'h ' . ($totalMinutes % 60) . 'm'
                    : '';

                $in = $firstIn ? Carbon::parse($firstIn)->format('h:i A') : 'Not clocked in';
                $out = $lastOut
                    ? Carbon::parse($lastOut)->format('h:i A')
                    : ($incomplete
                        ? ($dateIsToday ? 'Still working' : 'No punchout')
                        : 'Not punched out');

                // Define status and remarks
                if ($attendances->count() === 1) {
                    if ($incomplete) {
                        $workTime = $dateIsToday ? 'Still Working' : 'Not Punched Out';
                        $status = 'Present';
                        $remarks = $dateIsToday ? 'Currently Working' : 'Not Punched Out';
                    } else {
                        $status = 'Present';
                        $remarks = 'All punches complete';
                    }
                } elseif ($completePunches === $attendances->count()) {
                    $status = 'Present';
                    $remarks = 'All punches complete';
                } else {
                    $status = 'Present';
                    $remarks = 'Incomplete punches';
                    if ($incomplete && $dateIsToday) {
                        $workTime .= ' + Currently Working';
                    }
                }

                $rows->push([
                    'No.'              => $counter++,
                    'Date'             => date('M d, Y', strtotime($this->date)),
                    'Employee Name'    => $user->name,
                    'Employee ID'      => $user->employee_id,
                    'Designation'      => $user->designation_title ?? 'N/A',
                    'Phone'            => $user->phone,
                    'Clock In'         => $in,
                    'Clock Out'        => $out,
                    'Work Hours'       => $workTime ?: '0h 0m',
                    'Total Punches'    => $attendances->count(),
                    'Complete Punches' => $completePunches,
                    'Status'           => $status,
                    'Remarks'          => $remarks,
                ]);
            } elseif ($leave) {
                $duration = $leave->from_date->eq($leave->to_date)
                    ? $this->date
                    : $leave->from_date->format('Y-m-d') . " to " . $leave->to_date->format('Y-m-d');

                $remarks = "On {$leave->leave_type} Leave ({$duration}) - Status: {$leave->status}";

                $rows->push([
                    'No.'              => $counter++,
                    'Date'             => date('M d, Y', strtotime($this->date)),
                    'Employee Name'    => $user->name,
                    'Employee ID'      => $user->employee_id,
                    'Designation'      => $user->designation_title ?? 'N/A',
                    'Phone'            => $user->phone,
                    'Clock In'         => 'On Leave',
                    'Clock Out'        => 'On Leave',
                    'Work Hours'       => '0h 0m',
                    'Total Punches'    => 0,
                    'Complete Punches' => 0,
                    'Status'           => 'On Leave',
                    'Remarks'          => $remarks,
                ]);
            } else {
                $rows->push([
                    'No.'              => $counter++,
                    'Date'             => date('M d, Y', strtotime($this->date)),
                    'Employee Name'    => $user->name,
                    'Employee ID'      => $user->employee_id,
                    'Designation'      => $user->designation_title ?? 'N/A',
                    'Phone'            => $user->phone,
                    'Clock In'         => 'Absent',
                    'Clock Out'        => 'Absent',
                    'Work Hours'       => '0h 0m',
                    'Total Punches'    => 0,
                    'Complete Punches' => 0,
                    'Status'           => 'Absent',
                    'Remarks'          => 'Absent without leave',
                ]);
            }
        }

        return $rows;
    }



    public function headings(): array
    {
        return [
            'No.',
            'Date',
            'Employee Name',
            'Employee ID',
            'Designation',
            'Phone',
            'Clock In',
            'Clock Out',
            'Work Hours',
            'Total Punches',
            'Complete Punches',
            'Status',
            'Remarks'
        ];
    }



    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $dataStartRow = 5;
                $total = $sheet->getHighestDataRow()-1;
                $present = collect($sheet->toArray(null, true, false, true))
                    ->slice(0, $total)
                    ->where(11, '!=', 'Absent')
                    ->count();
                $absent = $total - $present;
                $sheet->insertNewRowBefore(1, 3); // Shift everything down by 4 rows

                // ====== Title ======
                $sheet->mergeCells('A1:M1');
                $sheet->setCellValue('A1', 'Daily Timesheet - ' . date('F d, Y', strtotime($this->date)));
                $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
                $sheet->getStyle('A1')->getAlignment()->setHorizontal('center');

                // ====== Generated on ======
                $sheet->mergeCells('A2:M2');
                $sheet->setCellValue('A2', 'Generated on: ' . now()->format('F d, Y h:i A'));
                $sheet->getStyle('A2')->getAlignment()->setHorizontal('center');

                // ====== Summary row ======


                $sheet->mergeCells('A3:M3');
                $sheet->setCellValue('A3', "Total Employees: {$total} (Present: {$present}, Absent: {$absent})");
                $sheet->getStyle('A3')->getAlignment()->setHorizontal('center');

                // ====== Style the headers ======
                $sheet->getStyle('A4:M4')->getFont()->setBold(true);
                $sheet->getStyle('A4:M4')->getFill()
                    ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                    ->getStartColor()->setARGB('E3F2FD');
                $sheet->getStyle('A4:M4')->getAlignment()->setHorizontal('center');

                // ====== Borders for the full range ======
                $highestRow = $sheet->getHighestRow();
                $highestCol = $sheet->getHighestColumn();
                $sheet->getStyle("A1:{$highestCol}{$highestRow}")
                    ->getBorders()->getAllBorders()
                    ->setBorderStyle(Border::BORDER_THIN);
                $sheet->getStyle("A1:{$highestCol}{$highestRow}")
                    ->getAlignment()->setHorizontal('center');

                // ====== Freeze pane after headers ======
                $sheet->freezePane('A5');
            },
        ];
    }
}
