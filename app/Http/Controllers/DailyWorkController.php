<?php

namespace App\Http\Controllers;

use App\Imports\DailyWorkImport;
use App\Models\DailySummary;
use App\Models\DailyWork;
use App\Models\DailyWorkSummary;
use App\Models\Jurisdiction;
use App\Models\Report;
use App\Models\User;
use Carbon\Carbon;
use ErrorException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;

class DailyWorkController extends Controller
{

    public function index()
    {
        $user = Auth::user();
        $allData = $user->hasRole('Supervision Engineer')
            ? [
                'allInCharges' => [],
                'juniors' => User::where('report_to', $user->id)->get(),

            ]
            : ($user->hasRole('Quality Control Inspector') || $user->hasRole('Asst. Quality Control Inspector')
                ? []
                : ($user->hasRole('Administrator')
                    ? [
                        'allInCharges' => User::role('Supervision Engineer')->get(),
                        'juniors' => [],
                    ]
                    : []
                )
            );
        $reports = Report::all();
        $reports_with_daily_works = Report::with('daily_works')->has('daily_works')->get();
        $users = User::with('roles')->get();

        // Loop through each user and add a new field 'role' with the role name
        $users->transform(function ($user) {
            $user->role = $user->roles->first()->name;
            return $user;
        });

        $overallStartDate = DailyWork::min('date'); // Earliest date from all records
        $overallEndDate = DailyWork::max('date'); // Latest date from all records

        return Inertia::render('Project/DailyWorks', [
            'allData' => $allData,
            'jurisdictions' => Jurisdiction::all(),
            'users' => $users,
            'title' => 'Daily Works',
            'reports' => $reports,
            'reports_with_daily_works' => $reports_with_daily_works,
            'overallStartDate' => $overallStartDate,
            'overallEndDate' => $overallEndDate,
        ]);
    }


    public function paginate(Request $request)
    {
        $user = Auth::user();
        $perPage = $request->get('perPage', 30); // Default to 10 items per page
        $page = $request->get('search') != '' ? 1 : $request->get('page', 1);
        $search = $request->get('search'); // Search query
        $statusFilter = $request->get('status'); // Filter by status
        $inChargeFilter = $request->get('inCharge'); // Filter by inCharge
        $startDate = $request->get('startDate'); // Filter by start date
        $endDate = $request->get('endDate'); // Filter by end date

        // Base query depending on user's role
        $query = $user->hasRole('Supervision Engineer')
            ? DailyWork::with('reports')->where('incharge', $user->id)
            : ($user->hasRole('Quality Control Inspector') || $user->hasRole('Asst. Quality Control Inspector')
                ? DailyWork::with('reports')->where('assigned', $user->id)
                : ($user->hasRole('Administrator')
                    ? DailyWork::with('reports')
                    : DailyWork::query()
                )
            );

        // Apply search if provided
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('number', 'LIKE', "%{$search}%")
                    ->orWhere('location', 'LIKE', "%{$search}%")
                    ->orWhere('description', 'LIKE', "%{$search}%")
                    ->orWhere('date', 'LIKE', "%{$search}%");
            });
        }

        // Apply status filter if provided
        if ($statusFilter) {
            $query->where('status', $statusFilter);
        }

        // Apply in-charge filter if provided
        if ($inChargeFilter) {
            $query->where('incharge', $inChargeFilter);
        }

        // Apply date range filter if provided
        if ($startDate && $endDate) {
            $query->whereBetween('date', [$startDate, $endDate]);
        } elseif ($startDate) {
            $query->where('date', '>=', $startDate);
        }

        // Order by 'date' in descending order
        $paginatedDailyWorks = $query->orderBy('date', 'desc')->paginate($perPage, ['*'], 'page', $page);


        // Return the paginated response as JSON
        return response()->json($paginatedDailyWorks);
    }

    public function import(Request $request)
    {
        try {
            $request->validate([
                'file' => 'required|file|mimes:xlsx,csv',
            ]);

            $path = $request->file('file')->store('temp'); // Store uploaded file temporarily
            $importedDailyWorks = Excel::toArray(new DailyWorkImport, $path)[0];


            $validator = Validator::make($importedDailyWorks, [
                '*.0' => 'required|date_format:Y-m-d',
                '*.1' => 'required|string',
                '*.2' => 'required|string|in:Embankment,Structure,Pavement',
                '*.3' => 'required|string',
                '*.4' => 'required|string|custom_location', // Assuming custom validation rule exists for location
            ], [
                '*.0.required' => 'DailyWork number :taskNumber must have a valid date.',
                '*.0.date_format' => 'DailyWork number :taskNumber must be a date in the format Y-m-d.',
                '*.1.required' => 'DailyWork number :taskNumber must have a value for field 1.',
                '*.2.required' => 'DailyWork number :taskNumber must have a value for field 2.',
                '*.2.in' => 'DailyWork number :taskNumber must have a value for field 2 that is either Embankment, Structure, or Pavement.',
                '*.3.required' => 'DailyWork number :taskNumber must have a value for field 3.',
                '*.4.required' => 'DailyWork number :taskNumber must have a value for field 4.',
            ]);

// Validate the data
            if ($validator->fails()) {

                return response()->json(['errors' => $validator->errors()], 422);
            }



            $date = $importedDailyWorks[0][0];

            // Initialize summary variables
            $inChargeSummary = [];



            // Regex for extracting start and end chainages
            $chainageRegex = '/([A-Z]*K[0-9]+(?:\+[0-9]+(?:\.[0-9]+)?)?)-([A-Z]*K[0-9]+(?:\+[0-9]+(?:\.[0-9]+)?)?)|([A-Z]*K[0-9]+)(.*)/';

            foreach ($importedDailyWorks as $importedDailyWork) {
                // Extract chainages from location field
                if (preg_match($chainageRegex, $importedDailyWork[4], $matches)) {
                    // Extract start and end chainages, if available
                    $startChainage = $matches[1] === "" ? $matches[0] : $matches[1]; // e.g., K05+900 or K30
                    $endChainage = $matches[2] === "" ? null : $matches[2]; // e.g., K06+400 (optional)

                    // Convert chainages to a comparable string format for jurisdiction check
                    $startChainageFormatted = $this->formatChainage($startChainage);
                    $endChainageFormatted = $endChainage ? $this->formatChainage($endChainage) : null;

                    // Retrieve all jurisdictions and compare chainages as strings
                    $jurisdictions = Jurisdiction::all();


                    $jurisdictionFound = false; // Set to false initially

                    foreach ($jurisdictions as $jurisdiction) {
                        $formattedStartJurisdiction = $this->formatChainage($jurisdiction->start_chainage);
                        $formattedEndJurisdiction = $this->formatChainage($jurisdiction->end_chainage);

                        // Check if the start chainage is within the jurisdiction's range
                        if ($startChainageFormatted >= $formattedStartJurisdiction && $startChainageFormatted <= $formattedEndJurisdiction) {
                            Log::info('Jurisdiction Match Found: ' . $formattedStartJurisdiction . "-" . $formattedEndJurisdiction);
                            $jurisdictionFound = $jurisdiction; // Set the found jurisdiction
                            break; // Stop checking once a match is found
                        }

                        // If an end chainage exists, check if it's within the jurisdiction's range
                        if ($endChainageFormatted &&
                            $endChainageFormatted >= $formattedStartJurisdiction &&
                            $endChainageFormatted <= $formattedEndJurisdiction) {
                            Log::info('Jurisdiction Match Found for End Chainage: ' . $formattedStartJurisdiction . "-" . $formattedEndJurisdiction);
                            $jurisdictionFound = $jurisdiction; // Set the found jurisdiction
                            break; // Stop checking once a match is found
                        }
                    }

// After loop, check if a jurisdiction was found
                    if ($jurisdictionFound) {
                        $inCharge = $jurisdictionFound->incharge;
                        $inChargeName = User::find($inCharge)->user_name;

                        // Initialize incharge summary if not exists
                        if (!isset($inChargeSummary[$inChargeName])) {
                            $inChargeSummary[$inChargeName] = [
                                'totalDailyWorks' => 0,
                                'resubmissions' => 0,
                                'embankment' => 0,
                                'structure' => 0,
                                'pavement' => 0,
                            ];
                        }

                        // Update incharge summary
                        $inChargeSummary[$inChargeName]['totalDailyWorks']++;

                        // Handle task type
                        switch ($importedDailyWork[2]) {
                            case 'Embankment':
                                $inChargeSummary[$inChargeName]['embankment']++;
                                break;
                            case 'Structure':
                                $inChargeSummary[$inChargeName]['structure']++;
                                break;
                            case 'Pavement':
                                $inChargeSummary[$inChargeName]['pavement']++;
                                break;
                        }

                        // Handle resubmissions and new tasks as you have done previously
//                        $this->handleTaskSubmission($importedDailyWork, $inChargeSummary, $inChargeName, $jurisdiction);
                        $existingDailyWork = DailyWork::where('number', $importedDailyWork[1])->first();
                        if ($existingDailyWork) {
                            $inChargeSummary[$inChargeName]['resubmissions']++;
                            $resubmissionCount = $existingDailyWork->resubmission_count ?? 0;
                            $resubmissionCount++;
                            $resubmissionDate = $this->getResubmissionDate($existingDailyWork, $resubmissionCount);

                            // Create updated resubmission record
                            DailyWork::create([
                                'date' => ($existingDailyWork->status === 'completed' ? $existingDailyWork->date : $importedDailyWork[0]),
                                'number' => $importedDailyWork[1],
                                'status' => ($existingDailyWork->status === 'completed' ? 'completed' : 'resubmission'),
                                'type' => $importedDailyWork[2],
                                'description' => $importedDailyWork[3],
                                'location' => $importedDailyWork[4],
                                'side' => $importedDailyWork[5],
                                'qty_layer' => $importedDailyWork[6],
                                'planned_time' => $importedDailyWork[7],
                                'incharge' => $jurisdictionFound->incharge,
                                'resubmission_count' => $resubmissionCount,
                                'resubmission_date' => $resubmissionDate,
                            ]);

                            // Delete old record
                            $existingDailyWork->delete();
                        } else {
                            // Create new task
                            DailyWork::create([
                                'date' => $importedDailyWork[0],
                                'number' => $importedDailyWork[1],
                                'status' => 'new',
                                'type' => $importedDailyWork[2],
                                'description' => $importedDailyWork[3],
                                'location' => $importedDailyWork[4],
                                'side' => $importedDailyWork[5],
                                'qty_layer' => $importedDailyWork[6],
                                'planned_time' => $importedDailyWork[7],
                                'incharge' => $jurisdictionFound->incharge,
                            ]);
                        }
                    }
                } else {
                    return response()->json(['error' => 'Invalid chainage format for location: ' . $importedDailyWork[4]]);
                }
            }

            // Store summary data in DailySummary model for each incharge
            $this->storeSummaryData($inChargeSummary, $date);

            $user = Auth::user();
            $perPage = $request->get('perPage', 30); // Default to 10 items per page
            $page = $request->get('page', 1);

            // Base query depending on user's role
            $query = $user->hasRole('Supervision Engineer')
                ? DailyWork::with('reports')->where('incharge', $user->id)
                : ($user->hasRole('Quality Control Inspector') || $user->hasRole('Asst. Quality Control Inspector')
                    ? DailyWork::with('reports')->where('assigned', $user->id)
                    : ($user->hasRole('Administrator')
                        ? DailyWork::with('reports')
                        : DailyWork::query()
                    )
                );
            $paginatedDailyWorks = $query->orderBy('date', 'desc')->paginate($perPage, ['*'], 'page', $page);

            return response()->json($paginatedDailyWorks);

        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        } catch (ErrorException $e) {
            return response()->json(['message' => 'An error occurred.', 'error' => $e->getMessage()], 500);
        }
    }

// Convert chainage like K05+900 to a comparable format (e.g., K005.900)
    private function formatChainage($chainage)
    {
        // Check if the chainage includes a range or just a single chainage
        if (preg_match('/([A-Z]*K)([0-9]+)(?:\+([0-9]+(?:\.[0-9]+)?))?/', $chainage, $matches)) {
            $kilometers = $matches[2]; // e.g., '14' from 'K14'
            $meters = $matches[3] ?? '000'; // Default to '0' if no meters are provided

            // Return a numeric format: kilometers and meters (with decimal if present)
            return $kilometers . $meters; // Remove decimal for sorting
        }

        // If the chainage is just a single K and number (e.g., 'K30'), format accordingly
        if (preg_match('/([A-Z]*K)([0-9]+)/', $chainage, $matches)) {
            $kilometers = $matches[2]; // e.g., '30'
            return $kilometers . '000'; // Assume no meters are present
        }

        return $chainage; // Return the chainage unchanged if it doesn't match the expected format
    }


    // Calculate the resubmission date based on resubmission count
    private function getResubmissionDate($existingDailyWork, $resubmissionCount)
    {
        // Assuming that each resubmission happens one day after the previous one.
        // You can adjust the logic to calculate the resubmission date in a different way if needed.

        // If there's an existing date, calculate the next resubmission date
        $initialDate = Carbon::parse($existingDailyWork->date);

        // Add the resubmission count as days to the initial date
        $resubmissionDate = $initialDate->addDays($resubmissionCount);

        return $resubmissionDate->format('Y-m-d');
    }


// Handle task submissions (new or resubmission)
    private function handleTaskSubmission($importedDailyWork, &$inChargeSummary, $inChargeName, $jurisdiction)
    {

    }

// Store summary data in the DailySummary model
    private function storeSummaryData($inChargeSummary, $date)
    {
        foreach ($inChargeSummary as $inChargeName => $summaryData) {
            $user = User::where('user_name', $inChargeName)->first();
            DailyWorkSummary::create([
                'date' => $date,
                'incharge' => $user->id,
                'totalDailyWorks' => $summaryData['totalDailyWorks'],
                'resubmissions' => $summaryData['resubmissions'],
                'embankment' => $summaryData['embankment'],
                'structure' => $summaryData['structure'],
                'pavement' => $summaryData['pavement'],
            ]);
        }
    }

    public function update(Request $request): \Illuminate\Http\JsonResponse
    {
        try {
            // Find task by ID
            $dailyWork = DailyWork::find($request->id);

            // If task not found, return 404 error response
            if (!$dailyWork) {
                return response()->json(['error' => 'Daily work not found'], 404);
            }

            $messages = [];  // Initialize an empty array to hold success messages

            // Check if the 'ruleSet' is 'details' and perform validation
            if ($request->has('ruleSet') && $request->input('ruleSet') === 'details') {
                $validatedData = $request->validate([
                    'date' => 'required|date',
                    'number' => 'required|string',
                    'type' => 'required|string',
                    'description' => 'nullable|string',
                    'location' => 'required|string',
                    'side' => 'nullable|string',
                    'qty_layer' => 'nullable|string',
                    'planned_time' => 'required|string',
                ]);

                // Update the daily work fields with the validated data
                $fields = [
                    'date' => 'Date',
                    'number' => 'Number',
                    'type' => 'Type',
                    'description' => 'Description',
                    'location' => 'Location',
                    'side' => 'Side',
                    'qty_layer' => 'Quantity Layer',
                    'planned_time' => 'Planned Time',
                ];

                foreach ($fields as $key => $label) {
                    if (array_key_exists($key, $validatedData) && $dailyWork->$key !== $validatedData[$key]) {
                        $dailyWork->$key = $validatedData[$key];
                        $messages[] = "{$label} updated successfully to '{$validatedData[$key]}'";
                    }
                }
            }


            // Additional fields update checks
            if ($request->has('status')) {
                $request->validate(['status' => 'required|string']);
                $dailyWork->status = $request->status;
                $messages[] = 'Daily work status updated to ' . $dailyWork->status;
            }
            if ($request->has('assigned')) {
                $request->validate(['assigned' => 'required|exists:users,id']);
                $dailyWork->assigned = $request->assigned;
                $messages[] = 'Daily work assigned to ' . User::find($request->assigned)->name;
            }
            if ($request->has('incharge')) {
                $request->validate(['incharge' => 'required|exists:users,id']);
                $dailyWork->incharge = $request->incharge;
                $messages[] = 'Daily work incharge updated to ' . User::find($request->incharge)->name;
            }
            if ($request->has('inspection_details')) {
                $dailyWork->inspection_details = $request->inspection_details;
                $messages[] = 'Inspection details updated successfully';
            }
            if ($request->has('rfi_submission_date')) {
                $dailyWork->rfi_submission_date = $request->rfi_submission_date;
                $messages[] = 'RFI Submission date updated';
            }
            if ($request->has('completion_time')) {
                $dailyWork->completion_time = $request->completion_time;
                $messages[] = 'Completion date-time updated';
            }

            // Save the daily work with the updated fields
            $dailyWork->save();

            // Return JSON response with the success messages
            return response()->json(['messages' => $messages]);

        } catch (ValidationException $e) {
            // Validation failed, return error response
            return response()->json(['errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            if ($e instanceof \Illuminate\Session\TokenMismatchException) {
                return response()->json(['error' => 'CSRF token mismatch'], 419);
            }
            // Other exceptions occurred, return error response
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function delete(Request $request): \Illuminate\Http\JsonResponse
    {
        try {
            // Validate the incoming request
            $request->validate([
                'id' => 'required|exists:daily_works,id',
            ]);

            // Find the daily work by ID
            $dailyWork = DailyWork::find($request->id);

            if (!$dailyWork) {
                return response()->json(['error' => 'Daily work not found'], 404);
            }

            // Delete the daily work
            $dailyWork->delete();

            $user = Auth::user();
            $perPage = $request->get('perPage', 30); // Default to 10 items per page
            $page = $request->get('page', 1);
            $search = $request->get('search'); // Search query
            $statusFilter = $request->get('status'); // Filter by status
            $inChargeFilter = $request->get('inCharge'); // Filter by inCharge
            $startDate = $request->get('startDate'); // Filter by start date
            $endDate = $request->get('endDate'); // Filter by end date

            // Base query depending on user's role
            $query = $user->hasRole('Supervision Engineer')
                ? DailyWork::with('reports')->where('incharge', $user->id)
                : ($user->hasRole('Quality Control Inspector') || $user->hasRole('Asst. Quality Control Inspector')
                    ? DailyWork::with('reports')->where('assigned', $user->id)
                    : ($user->hasRole('Administrator')
                        ? DailyWork::with('reports')
                        : DailyWork::query()
                    )
                );

            // Apply search if provided
            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'LIKE', "%{$search}%")
                        ->orWhere('description', 'LIKE', "%{$search}%")
                        ->orWhere('date', 'LIKE', "%{$search}%");
                });
            }

            // Apply status filter if provided
            if ($statusFilter) {
                $query->where('status', $statusFilter);
            }

            // Apply in-charge filter if provided
            if ($inChargeFilter) {
                $query->where('incharge', $inChargeFilter);
            }

            // Apply date range filter if provided
            if ($startDate && $endDate) {
                $query->whereBetween('date', [$startDate, $endDate]);
            }

            // Order by 'date' in descending order
            $paginatedDailyWorks = $query->orderBy('date', 'desc')->paginate($perPage, ['*'], 'page', $page);

            // Return the paginated response as JSON
            return response()->json($paginatedDailyWorks);

        } catch (\Exception $e) {
            // Catch any exceptions and return an error response
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }


    public function addTask(Request $request)
    {
        $user = Auth::user();

        $inchargeName = '';

        try {
            // Validate incoming request data
            $validatedData = $request->validate([
                'date' => 'required|date',
                'number' => 'required|string',
                'time' => 'required|string',
                'status' => 'required|string',
                'type' => 'required|string',
                'description' => 'required|string',
                'location' => 'required|string|custom_location',
                'side' => 'required|string',
                'qty_layer' => $request->input('type') === 'Embankment' ? 'required|string' : '',
                'completion_time' => $request->input('status') === 'completed' ? 'required|string' : '',
                'inspection_details' => 'nullable|string',
            ],[
                'date.required' => 'RFI Date is required.',
                'number.required' => 'RFI Number is required.',
                'time.required' => 'RFI Time is required.',
                'time.string' => 'RFI Time is not string.',
                'status.required' => 'Status is required.',
                'type.required' => 'Type is required.',
                'description.required' => 'Description is required.',
                'location.required' => 'Location is required.',
                'location.custom_location' => 'The :attribute must start with \'K\' and be in the range K0 to K48.',
                'side.required' => 'Road Type is required.',
                'qty_layer.required' => $request->input('type') === 'Embankment' ? 'Layer No. is required when the type is Embankment.' : '',
                'completion_time.required' => 'Completion time is required.',
                'qty_layer.string' => 'Quantity/Layer No. is not string'
            ]);

            // Check if a task with the same number already exists
            $existingTask = Tasks::where('number', $validatedData['number'])->first();
            if ($existingTask) {
                return response()->json(['error' => 'A task with the same RFI number already exists.'], 422);
            }

            $k = intval(substr($validatedData['location'], 1)); // Extracting the numeric part after 'K'

            $workLocation = WorkLocation::where('start_chainage', '<=', $k)
                ->where('end_chainage', '>=', $k)
                ->first();

            $inchargeName = $workLocation->incharge;

            // Create a new DailyWork instance
            $task = new Tasks();
            $task->date = $validatedData['date'];
            $task->number = $validatedData['number'];
            $task->planned_time = $validatedData['time'];
            $task->status = $validatedData['status'];
            $task->type = $validatedData['type'];
            $task->description = $validatedData['description'];
            $task->location = $validatedData['location'];
            $task->side = $validatedData['side'];
            $task->qty_layer = $validatedData['qty_layer'];
            $task->incharge = $user && $user->hasRole('Administrator') ? $inchargeName : ($user?->user_name);
            $task->completion_time = $validatedData['completion_time'];
            $task->inspection_details = $validatedData['inspection_details'];

            // Save the task to the database
            $task->save();

            $tasks = $user->hasRole('Supervision Engineer')
                ? [
                    'tasks' => Tasks::with('ncrs')->where('incharge', $user->user_name)->get(),
                    'juniors' => User::where('incharge', $user->user_name)->get(),
                    'message' => 'DailyWork added successfully',
                ] : ($user->hasRole('Quality Control Inspector') || $user->hasRole('Asst. Quality Control Inspecto')
                    ? [
                        'tasks' => Tasks::with('ncrs')->where('assigned', $user->user_name)->get(),
                        'message' => 'DailyWork added successfully'
                    ]
                    : ($user->hasRole('Administrator')
                        ? [
                            'tasks' => Tasks::with('ncrs')->get(),
                            'incharges' => User::role('Supervision Engineer')->get(),
                            'message' => 'DailyWork added successfully'
                        ]
                        : ['tasks' => [],
                            'message' => 'DailyWork added successfully'
                        ]
                    )
                );

            // Return a response
            return response()->json($tasks);
        } catch (ValidationException $e) {
            // Validation failed, return error response
            return response()->json(['error' => $e->errors()], 422);
        } catch (\Exception $e) {
            if ($e instanceof \Illuminate\Session\TokenMismatchException) {
                return response()->json(['error' => 'CSRF token mismatch'], 419);
            }
            // Other exceptions occurred, return error response
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }



    /**
     * Update the status of a task via AJAX request.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getOrdinalNumber($number): string
    {
        $number = (int) $number; // Ensure integer type
        $suffix = array('th', 'st', 'nd', 'rd', 'th', 'th', 'th', 'th', 'th', 'th');
        if ($number % 100 >= 11 && $number % 100 <= 19) {
            $suffix = "th";
        } else {
            $lastDigit = $number % 10;
            $suffix = $suffix[$lastDigit];
        }
        return $number . $suffix;
    }

    public function attachReport(Request $request)
    {
        $taskId = $request->input('task_id');
        $selectedReport = $request->input('selected_report');

        // Find the task by ID
        $task = Tasks::findOrFail($taskId);

        // Split the selected option into type and id
        list($type, $id) = explode('_', $selectedReport);

        // Check the type and handle accordingly
        if ($type === 'ncr') {
            // Handle NCRs
            $ncr = NCR::where('ncr_no', $id)->firstOrFail();
            // Check if the NCR is already attached to the task
            if (!$task->ncrs()->where('ncr_no', $ncr->ncr_no)->exists()) {
                $task->ncrs()->attach($ncr->id);
            }
        } elseif ($type === 'obj') {
            // Handle Objections
            $objection = Objection::where('obj_no', $id)->firstOrFail();
            // Check if the Objection is already attached to the task
            if (!$task->objections()->where('obj_no', $objection->obj_no)->exists()) {
                $task->objections()->attach($objection->id);
            }
        }

        // Update the timestamp of the task
        $task->touch();

        // Retrieve the updated task data
        $updatedTask = Tasks::with('ncrs', 'objections')->findOrFail($taskId);

        // Return response with success message and updated row data
        return response()->json(['message' => $type . " " . $id . ' attached to ' . $updatedTask->number . ' successfully.', 'updatedRowData' => $updatedTask]);
    }



    public function detachReport(Request $request)
    {
        $taskId = $request->input('task_id');

        // Find the task by ID
        $task = Tasks::findOrFail($taskId);

        // If selected option starts with 'ncr_', detach all NCRs
        if ($task->ncrs->count() > 0) {
            $detachedNCRs = $task->ncrs()->detach();
            $message = $detachedNCRs > 0 ? 'NCR detached from task ' . $task->number . ' successfully.' : 'No NCRs were attached to task ' . $task->number . '.';
        }
        // If selected option starts with 'obj_', detach all Objections
        elseif ($task->objections->count() > 0) {
            $detachedObjections = $task->objections()->detach();
            $message = $detachedObjections > 0 ? 'Objection detached from task ' . $task->number . ' successfully.' : 'No Objections were attached to task ' . $task->number . '.';
        }
        // Otherwise, handle as an invalid selection
        else {
            return response()->json(['error' => 'Invalid selection format.']);
        }

        // Update the timestamp of the task
        $task->touch();

        // Retrieve the updated task data
        $updatedTask = Tasks::with('ncrs', 'objections')->findOrFail($taskId);

        // Return response with success message and updated row data
        return response()->json(['message' => $message, 'updatedRowData' => $updatedTask]);
    }



}

