<?php
session_start();
header('Content-Type: application/json');
require_once 'db.php';

$db = get_db();
$action = $_GET['action'] ?? '';

// Helpers
function getAbsoluteSem($semesterStr) {
    if (!$semesterStr) return 1;
    $str = strtolower(trim((string)$semesterStr));
    if (preg_match('/(\d+)(?:st|nd|rd|th)?\s+year/i', $str, $yMatch) && preg_match('/semester\s+(\d+)/i', $str, $sMatch)) {
        return (intval($yMatch[1]) - 1) * 2 + intval($sMatch[1]);
    }
    if (preg_match('/(\d+)(?:st|nd|rd|th)?\s+semester/i', $str, $m)) {
        return intval($m[1]);
    }
    return intval($str) ?: 1;
}

if ($action === 'get_dashboard_summary') {
    $reqDept = trim(strtolower($_GET['dept'] ?? 'ALL'));
    $reqYear = intval($_GET['year'] ?? 1);
    $reqSem = intval($_GET['sem'] ?? 1);
    $reqDiv = trim(strtoupper($_GET['div'] ?? 'A'));
    $search = trim(strtolower($_GET['search'] ?? ''));

    $absoluteSem = ($reqYear - 1) * 2 + $reqSem;

    // Filter Students
    $filteredStudents = [];
    $studentIds = [];
    foreach ($db['students'] ?? [] as $s) {
        $sDept = strtolower($s['department'] ?? $s['dept'] ?? 'it');
        $sDiv = strtoupper($s['division'] ?? 'A');
        
        // Extract division from dept string if necessary (e.g., "IT - Div A")
        if (empty($s['division']) && preg_match('/Div\s*([A-Z])/i', $sDept, $m)) {
            $sDiv = strtoupper($m[1]);
        }

        if ($search !== '') {
            $q = $search;
            $prn = strtolower($s['prn'] ?? '');
            $id = strtolower($s['id'] ?? '');
            $name = strtolower($s['name'] ?? '');
            $email = strtolower($s['email'] ?? '');
            if (strpos($prn, $q) !== false || strpos($id, $q) !== false || strpos($name, $q) !== false || strpos($email, $q) !== false) {
                $filteredStudents[] = $s;
                $studentIds[] = $s['id'] ?? $s['username'] ?? $s['prn'];
            }
        } else {
            $sSemVal = getAbsoluteSem($s['semester'] ?? '');
            $matchSem = ($sSemVal === $absoluteSem);
            $matchDiv = ($sDiv === $reqDiv);
            $matchDept = true;
            if ($reqDept !== 'all') {
                $matchDept = ($sDept === $reqDept || strpos($sDept, $reqDept) !== false);
            }
            if ($matchSem && $matchDiv && $matchDept) {
                $filteredStudents[] = $s;
                $studentIds[] = $s['id'] ?? $s['username'] ?? $s['prn'];
            }
        }
    }

    $totalStudents = count($filteredStudents);

    // Get all submissions for these students
    $studentSubs = [];
    foreach ($db['assignment_submissions'] ?? [] as $sub) {
        if (in_array($sub['student_id'], $studentIds) || in_array($sub['student_name'], array_column($filteredStudents, 'name'))) {
            $studentSubs[] = $sub;
        }
    }

    // Determine Required Subject Assignments for this cohort
    $requiredAssignments = [];
    $subjectNames = [];
    foreach ($db['subject_assignments'] ?? [] as $sa) {
        $saDept = strtolower($sa['department'] ?? '');
        $saDiv = strtoupper($sa['division'] ?? '');
        
        $matchDept = ($saDept === '' || $saDept === 'all');
        if (!$matchDept && $reqDept !== 'all') {
            $matchDept = (strpos($reqDept, $saDept) !== false || strpos($saDept, $reqDept) !== false);
            if (!$matchDept) {
                if ((strpos($reqDept, 'it') !== false || strpos($reqDept, 'information')) && (strpos($saDept, 'it') !== false || strpos($saDept, 'information'))) $matchDept = true;
                if ((strpos($reqDept, 'ce') !== false || strpos($reqDept, 'computer')) && (strpos($saDept, 'ce') !== false || strpos($saDept, 'computer'))) $matchDept = true;
            }
        }

        $matchDiv = ($saDiv === '' || $saDiv === 'ALL' || $saDiv === $reqDiv);
        
        // Also check if search matched some students, and if so, find assignments by their exact properties
        if ($search !== '') {
            $requiredAssignments[] = $sa; // Simplified for search mode, show all applicable to search results
        } elseif ($matchDept && $matchDiv) {
            $requiredAssignments[] = $sa;
            $subjName = $sa['subject_name'] ?? 'Unknown Subject';
            if (!in_array($subjName, $subjectNames)) {
                $subjectNames[] = $subjName;
            }
        }
    }
    
    if ($search !== '') {
        // Find subject names based on what search results returned
        foreach ($studentSubs as $s) {
            $sa = array_filter($requiredAssignments, fn($a) => $a['id'] == $s['subject_assignment_id']);
            if (!empty($sa)) {
                $sa = reset($sa);
                $subjName = $sa['subject_name'] ?? 'Unknown Subject';
                if (!in_array($subjName, $subjectNames)) {
                    $subjectNames[] = $subjName;
                }
            }
        }
    }

    $totalSubjects = count($subjectNames);
    $totalAssignmentsPublished = count($requiredAssignments);

    $totalExpected = $totalStudents * $totalAssignmentsPublished;
    
    // Group required assignments by subject
    $subjectSummary = [];
    foreach ($subjectNames as $subjName) {
        $subjectSummary[$subjName] = [
            'subject_name' => $subjName,
            'faculty_name' => 'Various Faculty', 
            'total_assignments' => 0,
            'expected_submissions' => 0,
            'submitted' => 0,
            'pending' => 0,
            'total_score' => 0,
            'graded_count' => 0,
            'assignments' => []
        ];
    }

    // Faculty map
    $facultyMap = [];
    foreach ($db['faculty'] ?? [] as $f) {
        $fSubjs = explode(',', $f['subjects'] ?? '');
        foreach ($fSubjs as $fs) {
            $facultyMap[trim($fs)] = $f['name'];
        }
    }

    $totalSubmitted = 0;
    $totalPending = 0;

    foreach ($requiredAssignments as $sa) {
        $subjName = $sa['subject_name'] ?? 'Unknown Subject';
        if (!in_array($subjName, $subjectNames)) continue;

        if (isset($facultyMap[$subjName])) {
            $subjectSummary[$subjName]['faculty_name'] = $facultyMap[$subjName];
        }

        $assignTitle = !empty($sa['assignment_title']) ? $sa['assignment_title'] : (!empty($sa['unit']) ? 'Unit ' . $sa['unit'] : 'Assignment');
        
        $aData = [
            'id' => $sa['id'],
            'title' => $assignTitle,
            'submitted' => 0,
            'pending' => $totalStudents,
            'total_score' => 0,
            'graded_count' => 0
        ];

        foreach ($filteredStudents as $s) {
            $sid = $s['id'] ?? $s['username'] ?? $s['prn'];
            $sname = $s['name'] ?? '';
            $foundSub = false;
            
            foreach ($studentSubs as $sub) {
                if (($sub['student_id'] == $sid || $sub['student_name'] === $sname) && $sub['subject_assignment_id'] == $sa['id']) {
                    $foundSub = true;
                    $aData['submitted']++;
                    $aData['pending']--;
                    $totalSubmitted++;
                    
                    if ($sub['marks'] !== 'Pending') {
                        $m = intval($sub['marks']);
                        if ($m >= 0) {
                            $aData['total_score'] += $m;
                            $aData['graded_count']++;
                        }
                    }
                    break;
                }
            }
            if (!$foundSub) {
                $totalPending++;
            }
        }

        $aData['completion'] = $totalStudents > 0 ? round(($aData['submitted'] / $totalStudents) * 100) : 0;

        $subjectSummary[$subjName]['total_assignments']++;
        $subjectSummary[$subjName]['expected_submissions'] += $totalStudents;
        $subjectSummary[$subjName]['submitted'] += $aData['submitted'];
        $subjectSummary[$subjName]['pending'] += $aData['pending'];
        $subjectSummary[$subjName]['total_score'] += $aData['total_score'];
        $subjectSummary[$subjName]['graded_count'] += $aData['graded_count'];
        $subjectSummary[$subjName]['assignments'][] = $aData;
    }

    $subjectArray = [];
    $highestSubj = ['name' => 'N/A', 'comp' => -1];
    $lowestSubj = ['name' => 'N/A', 'comp' => 101];
    $totalClassScore = 0;
    $totalClassGraded = 0;

    foreach ($subjectSummary as $subjName => $data) {
        $comp = $data['expected_submissions'] > 0 ? round(($data['submitted'] / $data['expected_submissions']) * 100) : 0;
        $data['completion'] = $comp;
        $data['avg_score'] = $data['graded_count'] > 0 ? round(($data['total_score'] / $data['graded_count']) * 10) : 0; // % assuming out of 10
        
        $totalClassScore += $data['total_score'];
        $totalClassGraded += $data['graded_count'];

        if ($comp >= 80) $data['status'] = 'Excellent';
        elseif ($comp >= 60) $data['status'] = 'Good';
        elseif ($comp >= 40) $data['status'] = 'Average';
        else $data['status'] = 'Needs Attention';

        if ($comp > $highestSubj['comp']) { $highestSubj = ['name' => $subjName, 'comp' => $comp]; }
        if ($comp < $lowestSubj['comp'] && $data['expected_submissions'] > 0) { $lowestSubj = ['name' => $subjName, 'comp' => $comp]; }

        $subjectArray[] = $data;
    }
    
    if ($lowestSubj['comp'] === 101) $lowestSubj = ['name' => 'N/A', 'comp' => 0];

    $classAvg = $totalClassGraded > 0 ? round(($totalClassScore / $totalClassGraded) * 10) : 0; // percentage

    // Faculty completion rates
    $facRates = [];
    foreach ($subjectArray as $s) {
        if (!isset($facRates[$s['faculty_name']])) {
            $facRates[$s['faculty_name']] = ['sub' => 0, 'exp' => 0];
        }
        $facRates[$s['faculty_name']]['sub'] += $s['submitted'];
        $facRates[$s['faculty_name']]['exp'] += $s['expected_submissions'];
    }
    $topFac = 'N/A';
    $topFacRate = -1;
    foreach ($facRates as $fname => $counts) {
        $rate = $counts['exp'] > 0 ? ($counts['sub'] / $counts['exp']) : 0;
        if ($rate > $topFacRate && $counts['exp'] > 0) {
            $topFacRate = $rate;
            $topFac = $fname;
        }
    }

    // Recent activity dummy implementation
    $recentActivity = array_slice($db['recent_activity'] ?? [], 0, 10);
    
    // Recent Assignments Table Data
    $recentAssignments = [];
    $allAss = array_reverse($requiredAssignments);
    foreach (array_slice($allAss, 0, 5) as $sa) {
        $subjName = $sa['subject_name'] ?? 'Unknown';
        $fac = $facultyMap[$subjName] ?? 'Various';
        $title = !empty($sa['assignment_title']) ? $sa['assignment_title'] : (!empty($sa['unit']) ? 'Unit ' . $sa['unit'] : 'Assignment');
        
        $subCount = 0;
        foreach ($studentSubs as $sub) {
            if ($sub['subject_assignment_id'] == $sa['id']) $subCount++;
        }
        
        $recentAssignments[] = [
            'id' => $sa['id'],
            'title' => $title,
            'subject' => $subjName,
            'faculty' => $fac,
            'due_date' => $sa['due_date'] ?? 'N/A',
            'submitted' => $subCount,
            'pending' => $totalStudents - $subCount,
            'total_students' => $totalStudents,
            'completion' => $totalStudents > 0 ? round(($subCount / $totalStudents) * 100) : 0
        ];
    }
    $matchedStudentsData = [];
    if ($search !== '') {
        foreach ($filteredStudents as $fs) {
            $matchedStudentsData[] = [
                'name' => $fs['name'] ?? 'Unknown',
                'prn' => $fs['prn'] ?? $fs['id'] ?? 'N/A',
                'year' => isset($fs['semester']) ? ceil(getAbsoluteSem($fs['semester']) / 2) : 'N/A',
                'semester' => $fs['semester'] ?? 'N/A',
                'division' => $fs['division'] ?? 'N/A',
                'department' => $fs['department'] ?? $fs['dept'] ?? 'N/A'
            ];
        }
    }

    echo json_encode([
        'success' => true,
        'stats' => [
            'total_students' => $totalStudents,
            'total_subjects' => $totalSubjects,
            'total_assignments' => $totalAssignmentsPublished,
            'submitted' => $totalSubmitted,
            'pending' => $totalPending,
            'expected' => $totalExpected
        ],
        'subject_summary' => array_values($subjectArray),
        'analytics' => [
            'class_average' => $classAvg,
            'highest_subject' => $highestSubj['name'],
            'lowest_subject' => $lowestSubj['name'],
            'top_faculty' => $topFac
        ],
        'recent_activity' => $recentActivity,
        'recent_assignments' => $recentAssignments,
        'matched_students' => $matchedStudentsData
    ]);
    exit;
}

if ($action === 'get_assignment_students') {
    $saId = $_GET['sa_id'] ?? '';
    if (!$saId) {
        echo json_encode(['success' => false, 'error' => 'Missing sa_id']);
        exit;
    }

    $reqDept = trim(strtolower($_GET['dept'] ?? 'ALL'));
    $reqYear = intval($_GET['year'] ?? 1);
    $reqSem = intval($_GET['sem'] ?? 1);
    $reqDiv = trim(strtoupper($_GET['div'] ?? 'A'));
    $search = trim(strtolower($_GET['search'] ?? ''));
    $absoluteSem = ($reqYear - 1) * 2 + $reqSem;

    // Filter Students for this cohort
    $cohortStudents = [];
    foreach ($db['students'] ?? [] as $s) {
        $sDept = strtolower($s['department'] ?? $s['dept'] ?? 'it');
        $sDiv = strtoupper($s['division'] ?? 'A');
        
        if (empty($s['division']) && preg_match('/Div\s*([A-Z])/i', $sDept, $m)) {
            $sDiv = strtoupper($m[1]);
        }

        $sSemVal = getAbsoluteSem($s['semester'] ?? '');
        $matchSem = ($sSemVal === $absoluteSem);
        $matchDiv = ($sDiv === $reqDiv);
        $matchDept = true;
        if ($reqDept !== 'all') {
            $matchDept = ($sDept === $reqDept || strpos($sDept, $reqDept) !== false);
        }
        
        if ($search !== '') {
            $q = $search;
            $prn = strtolower($s['prn'] ?? '');
            $id = strtolower($s['id'] ?? '');
            $name = strtolower($s['name'] ?? '');
            $email = strtolower($s['email'] ?? '');
            if (strpos($prn, $q) !== false || strpos($id, $q) !== false || strpos($name, $q) !== false || strpos($email, $q) !== false) {
                $cohortStudents[] = $s;
            }
        } elseif ($matchSem && $matchDiv && $matchDept) {
            $cohortStudents[] = $s;
        }
    }

    // Get all submissions for this specific assignment
    $assignmentSubs = [];
    foreach ($db['assignment_submissions'] ?? [] as $sub) {
        if ($sub['subject_assignment_id'] == $saId) {
            $assignmentSubs[$sub['student_id'] ?? ''] = $sub;
            // Map by name as well due to some weirdness in db
            $assignmentSubs[$sub['student_name'] ?? ''] = $sub;
        }
    }

    $studentsData = [];
    foreach ($cohortStudents as $s) {
        $sid = $s['id'] ?? $s['username'] ?? $s['prn'];
        $sname = $s['name'] ?? '';
        
        $sub = $assignmentSubs[$sid] ?? $assignmentSubs[$sname] ?? null;
        
        $status = 'Not Uploaded';
        $marks = '-';
        $percentage = 0;
        $submittedAt = '-';
        $isLate = false; 

        if ($sub) {
            if ($sub['marks'] === 'Pending') {
                $status = 'Pending'; // This implies Not Evaluated but submitted
            } else {
                $status = 'Submitted'; // Graded
                $marks = $sub['marks'];
                $m = intval($marks);
                $percentage = ($m > 0) ? ($m / 10) * 100 : 0; 
            }
            $submittedAt = $sub['submitted_at'] ?? '-';
        }

        $studentsData[] = [
            'photo' => $s['avatar'] ?? 'https://ui-avatars.com/api/?name=' . urlencode($s['name']),
            'roll' => $s['prn'] ?? $sid,
            'name' => $s['name'],
            'status' => $status,
            'marks' => $marks,
            'percentage' => $percentage,
            'submitted_at' => $submittedAt,
            'is_late' => $isLate
        ];
    }

    echo json_encode([
        'success' => true,
        'students' => $studentsData
    ]);
    exit;
}

if ($action === 'export_csv') {
    // Generate CSV code placeholder
    // A quick simple CSV export logic
    $data = $_POST['data'] ?? '[]';
    $decoded = json_decode($data, true) ?? [];
    
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="report.csv"');
    $output = fopen('php://output', 'w');
    if (!empty($decoded)) {
        fputcsv($output, array_keys($decoded[0]));
        foreach ($decoded as $row) {
            fputcsv($output, array_values($row));
        }
    }
    fclose($output);
    exit;
}

echo json_encode(['success' => false, 'error' => 'Invalid action']);
exit;
