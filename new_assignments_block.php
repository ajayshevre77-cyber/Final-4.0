                            <?php 
                            $units_list = [1, 2, 3, 4, 5, 6];
                            
                            // Find subjects assigned to student's department
                            $my_dept_id = null;
                            if (isset($db['departments'])) {
                                foreach ($db['departments'] as $d) {
                                    if (strcasecmp($d['name'], $student_dept) === 0) {
                                        $my_dept_id = intval($d['id']);
                                        break;
                                    }
                                }
                            }
                            
                            $my_subjects = [];
                            if (isset($db['subjects'])) {
                                foreach ($db['subjects'] as $sub) {
                                    if ($my_dept_id !== null && intval($sub['department_id']) === $my_dept_id) {
                                        $my_subjects[] = $sub;
                                    }
                                }
                            }

                            if (empty($my_subjects)): 
                            ?>
                                <tr>
                                    <td colspan="2" style="padding: 1.5rem; text-align: center; color: #64748b; font-size: 0.95rem; font-weight: 500;">
                                        No subjects are assigned to your department/semester.
                                    </td>
                                </tr>
                            <?php 
                            else:
                                $subject_index = 0;
                                foreach ($my_subjects as $subject): 
                                    $subject_index++;
                                    $subject_name = $subject['name'];
                            ?>
                                <tr class="assignment-subject-row" data-subject="<?php echo $subject['id']; ?>" style="cursor: pointer;" onclick="toggleSubjectDetails(<?php echo $subject['id']; ?>)">
                                    <td style="font-weight: 500; padding: 1.5rem; text-align: center; color: #4b5563; border-bottom: 1px solid #f1f5f9;"><?php echo $subject_index; ?></td>
                                    <td style="padding: 1.5rem; border-bottom: 1px solid #f1f5f9;">
                                        <div style="display: flex; gap: 1rem; align-items: center; justify-content: space-between; width: 100%;">
                                            <div style="display: flex; gap: 1rem; align-items: center;">
                                                <div style="width: 44px; height: 44px; background: #f3e8ff; color: #8b5cf6; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.25rem; flex-shrink: 0;">
                                                    <i class="fa-solid fa-file-lines"></i>
                                                </div>
                                                <div>
                                                    <div style="font-weight: 700; color: #1e293b; font-size: 1.1rem;"><?php echo htmlspecialchars($subject_name); ?></div>
                                                </div>
                                            </div>
                                            <!-- Accordion Rotating arrow -->
                                            <div class="accordion-arrow subject-arrow-<?php echo $subject['id']; ?>" style="font-size: 1rem; color: #64748b; transition: transform 0.3s ease; padding: 0.5rem; cursor: pointer;">
                                                <i class="fa-solid fa-chevron-down"></i>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                <tr class="assignment-details-row" id="subject-details-<?php echo $subject['id']; ?>" style="display: none; background: white;">
                                    <td colspan="2" style="padding: 1rem 1.5rem 2rem 1.5rem;">
                                        <div style="background: #fafafa; border: 1px solid #f1f5f9; border-radius: 12px; overflow: hidden; padding: 1rem;">
                                            <table style="width: 100%; border-collapse: collapse; text-align: left;">
                                                <thead>
                                                    <tr style="border-bottom: 1px solid #e2e8f0;">
                                                        <th style="padding: 1rem; color: #64748b; font-size: 0.8rem; font-weight: 600; text-transform: uppercase;">#</th>
                                                        <th style="padding: 1rem; color: #64748b; font-size: 0.8rem; font-weight: 600; text-transform: uppercase;">Assignment</th>
                                                        <th style="padding: 1rem; color: #64748b; font-size: 0.8rem; font-weight: 600; text-transform: uppercase;">Due Date</th>
                                                        <th style="padding: 1rem; color: #64748b; font-size: 0.8rem; font-weight: 600; text-transform: uppercase; text-align: center;">Upload</th>
                                                        <th style="padding: 1rem; color: #64748b; font-size: 0.8rem; font-weight: 600; text-transform: uppercase; text-align: center;">Marks (Out of 10)</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                                                        <?php 
                                            $has_assignments = false;
                                            ob_start();
                                            foreach ($units_list as $unit_num): 
// Find assignment_id for this unit
                                                $target_assign_id = 0;
                                                if (isset($db['assignments'])) {
                                                    foreach ($db['assignments'] as $a) {
                                                        if (intval($a['unit']) === $unit_num) {
                                                            $target_assign_id = intval($a['id']);
                                                            break;
                                                        }
                                                    }
                                                }

                                                // Find the faculty assignment for this unit and subject
                                                $sa_item = null;
                                                if (isset($db['subject_assignments'])) {
                                                    foreach ($db['subject_assignments'] as $sa) {
                                                        if (intval($sa['assignment_id']) === $target_assign_id && $sa['subject_name'] === $subject_name) {
                                                            $sa_dept = $sa['department'] ?? '';
                                                            $sa_div = $sa['division'] ?? '';
                                                            $sa_sem = $sa['semester'] ?? '';

                                                            $match_dept = (strcasecmp($sa_dept, $student_dept) === 0);
                                                            $match_div = (empty($sa_div) || strcasecmp($sa_div, $student_div) === 0);

                                                            if ($match_dept && $match_div) {
                                                                $sa_item = $sa;
                                                                break;
                                                            }
                                                        }
                                                    }
                                                }
                                                
                                                                                                if (!$sa_item) continue;
                                                $has_assignments = true;
                                                
                                                // Check submission status
                                                $my_sub = null;
                                                if ($sa_item) {
                                                    foreach ($db['assignment_submissions'] ?? [] as $sub) {
                                                        if ($sub['subject_assignment_id'] == $sa_item['id'] && $sub['student_id'] === $student_id) {
                                                            $my_sub = $sub;
                                                            break;
                                                        }
                                                    }
                                                }
                                                $sub_status = $my_sub ? $my_sub['status'] : 'Not Submitted';
                                                $sub_marks = $my_sub ? $my_sub['marks'] : 'Pending';
                                                $sub_remarks = $my_sub ? $my_sub['remarks'] : '';
                                                $sub_file = $my_sub ? ($my_sub['file_path'] ?? $my_sub['file'] ?? '') : '';
                                                $sub_file_name = $my_sub ? ($my_sub['file_name'] ?? '') : '';
                                                $submitted_at = $my_sub ? ($my_sub['submitted_at'] ?? '') : '';
                                                
                                                // Check grievance status for this assignment
                                                $my_sa_grievances = [];
                                                if ($sa_item) {
                                                    foreach ($db['assignment_grievances'] ?? [] as $g) {
                                                        if ($g['subject_assignment_id'] == $sa_item['id'] && $g['student_id'] === $student_id) {
                                                            $my_sa_grievances[] = $g;
                                                        }
                                                    }
                                                }
                                                
                                                $row_id = $sa_item ? $sa_item['id'] : "dummy_{$subject['id']}_{$unit_num}";
                                            ?>
                                                <tr style="border-bottom: 1px solid #f1f5f9; transition: background 0.2s;">
                                                    <td style="padding: 1.25rem 1rem; color: #4b5563; font-weight: 500; font-size: 0.95rem;"><?php echo $unit_num; ?></td>
                                                    <td style="padding: 1.25rem 1rem;">
                                                        <div style="display: flex; align-items: center; gap: 0.75rem;">
                                                            <div style="width: 36px; height: 36px; background: #f3e8ff; color: #8b5cf6; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 1rem;">
                                                                <i class="fa-solid fa-file-invoice"></i>
                                                            </div>
                                                            <span style="font-weight: 700; color: #1e293b; font-size: 0.95rem;">Assignment <?php echo $unit_num; ?></span>
                                                        </div>
                                                    </td>
                                                    <td style="padding: 1.25rem 1rem; color: #475569; font-size: 0.9rem; font-weight: 500;">
                                                        <?php echo $sa_item ? date('d M Y', strtotime($sa_item['due'] ?? $sa_item['due_date'])) : '--'; ?>
                                                    </td>
                                                    <td style="padding: 1.25rem 1rem; text-align: center;">
                                                        <?php if ($sa_item): ?>
                                                            <?php if ($sub_status !== 'Not Submitted' && $sub_status !== 'Rejected'): ?>
                                                                <button onclick="toggleActionRow('<?php echo $row_id; ?>')" style="background: #f0fdf4; color: #16a34a; border: none; padding: 0.5rem 1rem; border-radius: 8px; font-size: 0.85rem; font-weight: 700; cursor: pointer; display: inline-flex; align-items: center; gap: 6px;">
                                                                    <i class="fa-solid fa-check"></i> View
                                                                </button>
                                                            <?php else: ?>
                                                                <button onclick="toggleActionRow('<?php echo $row_id; ?>')" style="background: #f3e8ff; color: #8b5cf6; border: none; padding: 0.5rem 1rem; border-radius: 8px; font-size: 0.85rem; font-weight: 700; cursor: pointer; display: inline-flex; align-items: center; gap: 6px;">
                                                                    <i class="fa-solid fa-arrow-up-from-bracket"></i> Upload
                                                                </button>
                                                            <?php endif; ?>
                                                        <?php else: ?>
                                                            <span style="color: #94a3b8; font-size: 0.85rem; font-weight: 500;">Not Published</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td style="padding: 1.25rem 1rem; text-align: center; color: #64748b; font-weight: 500; font-size: 0.95rem;">
                                                        <?php 
                                                            if ($sub_marks !== 'Pending') {
                                                                echo '<span style="color: #10b981; font-weight: 700;">' . htmlspecialchars($sub_marks) . '</span>/10';
                                                            } else {
                                                                echo '__/10';
                                                            }
                                                        ?>
                                                    </td>
                                                </tr>
                                                
                                                <!-- Action Inline Row (Hidden by default) -->
                                                <?php if ($sa_item): ?>
                                                <tr id="action-row-<?php echo $row_id; ?>" style="display: none; background: white;">
                                                    <td colspan="5" style="padding: 1.5rem; border-bottom: 1px solid #e2e8f0; border-top: 1px solid #e2e8f0; box-shadow: inset 0 2px 4px rgba(0,0,0,0.02);">
                                                        
                                                        <div style="display: grid; grid-template-columns: 1.2fr 1fr; gap: 1.5rem; align-items: start;">
                                                            
                                                            <!-- COLUMN 1: SECTION 1 (FACULTY ASSIGNMENT) & SECTION 3 (GRIEVANCE) -->
                                                            <div style="display: flex; flex-direction: column; gap: 1.5rem;">
                                                                <!-- SECTION 1: FACULTY ASSIGNMENT -->
                                                                <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 1.25rem;">
                                                                    <h6 style="margin: 0 0 0.75rem 0; font-size: 0.85rem; text-transform: uppercase; color: #64748b; font-weight: 700; border-bottom: 1px dashed #cbd5e1; padding-bottom: 0.5rem; display: flex; align-items: center; gap: 6px;">
                                                                        <i class="fa-solid fa-file-signature" style="color: #4f46e5;"></i> FACULTY ASSIGNMENT DETAILS
                                                                    </h6>
                                                                    <div style="display: flex; flex-direction: column; gap: 0.5rem; font-size: 0.85rem;">
                                                                        <div><strong>Assignment Title:</strong> <span style="color: #4f46e5; font-weight: 600;"><?= htmlspecialchars($sa_item['assignment_title']) ?></span></div>
                                                                        <div><strong>Description:</strong> <p style="margin: 0.25rem 0 0 0; color: #64748b; font-size: 0.85rem; line-height: 1.4;"><?= nl2br(htmlspecialchars($sa_item['description'] ?? 'Solve all tasks.')) ?></p></div>
                                                                        <div><strong>Faculty Name:</strong> <span style="color: #334155; font-weight: 600;"><?= htmlspecialchars($sa_item['created_by']) ?></span></div>
                                                                        <div style="margin-top: 0.75rem; display: flex; gap: 0.75rem;">
                                                                            <a href="uploads/<?= htmlspecialchars($sa_item['question_pdf']) ?>" target="_blank" style="display: inline-flex; align-items: center; gap: 4px; padding: 0.4rem 0.8rem; background: #e0f2fe; color: #0369a1; border-radius: 6px; font-weight: 700; font-size: 0.8rem; text-decoration: none;">
                                                                                <i class="fa-solid fa-eye"></i> View PDF
                                                                            </a>
                                                                            <a href="uploads/<?= htmlspecialchars($sa_item['question_pdf']) ?>" download style="display: inline-flex; align-items: center; gap: 4px; padding: 0.4rem 0.8rem; background: #f0fdf4; color: #166534; border-radius: 6px; font-weight: 700; font-size: 0.8rem; text-decoration: none;">
                                                                                <i class="fa-solid fa-download"></i> Download PDF
                                                                            </a>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                
                                                                <!-- SECTION 3: GRIEVANCE -->
                                                                <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 1.25rem;">
                                                                    <h6 style="margin: 0 0 0.75rem 0; font-size: 0.85rem; text-transform: uppercase; color: #64748b; font-weight: 700; border-bottom: 1px dashed #cbd5e1; padding-bottom: 0.5rem; display: flex; align-items: center; gap: 6px;">
                                                                        <i class="fa-solid fa-circle-exclamation" style="color: #ef4444;"></i> GRIEVANCE
                                                                    </h6>
                                                                    
                                                                    <?php if (!empty($my_sa_grievances)): ?>
                                                                        <div style="display: flex; flex-direction: column; gap: 0.75rem; margin-bottom: 1rem;">
                                                                            <?php foreach ($my_sa_grievances as $idx => $g): ?>
                                                                                <div style="background: white; border: 1px solid #cbd5e1; padding: 0.75rem; border-radius: 6px;">
                                                                                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.25rem;">
                                                                                        <span style="font-weight: 700; font-size: 0.8rem; color: #334155;">Issue: <?= htmlspecialchars($g['issue_type']) ?></span>
                                                                                        <?php 
                                                                                        $st = strtolower($g['status'] ?? 'pending');
                                                                                        $p_bg = '#fee2e2'; $p_col = '#b91c1c';
                                                                                        if ($st === 'resolved') { $p_bg = '#dcfce7'; $p_col = '#15803d'; }
                                                                                        elseif ($st === 'in review') { $p_bg = '#dbeafe'; $p_col = '#1d4ed8'; }
                                                                                        elseif ($st === 'rejected') { $p_bg = '#f3f4f6'; $p_col = '#4b5563'; }
                                                                                        ?>
                                                                                        <span style="background: <?= $p_bg ?>; color: <?= $p_col ?>; padding: 0.2rem 0.5rem; border-radius: 4px; font-size: 0.7rem; font-weight: 700; text-transform: uppercase;"><?= htmlspecialchars($g['status'] ?? 'Pending') ?></span>
                                                                                    </div>
                                                                                    <div style="font-size: 0.8rem; color: #475569; margin-bottom: 0.5rem;">
                                                                                        <?= nl2br(htmlspecialchars($g['description'])) ?>
                                                                                    </div>
                                                                                    <?php if (!empty($g['reply'])): ?>
                                                                                        <div style="background: #f8fafc; border-left: 3px solid #3b82f6; padding: 0.5rem; font-size: 0.8rem; color: #1e293b;">
                                                                                            <strong>Faculty Reply:</strong> <?= htmlspecialchars($g['reply']) ?>
                                                                                        </div>
                                                                                    <?php endif; ?>
                                                                                </div>
                                                                                                                        <?php endforeach; 
                                                $rows_html = ob_get_clean();
                                                
                                                if (!$has_assignments) {
                                                    echo '<tr><td colspan="5" style="padding: 2rem; text-align: center; color: #94a3b8; font-weight: 500; font-size: 0.95rem;"><i class="fa-solid fa-folder-open" style="font-size: 2rem; color: #e2e8f0; margin-bottom: 0.5rem; display: block;"></i> No assignments published by faculty yet.</td></tr>';
                                                } else {
                                                    echo $rows_html;
                                                }
                                            ?>
                                                                        </div>
                                                                    <?php endif; ?>
                                                                    
                                                                    <button type="button" onclick="openSubjectGrievanceModal(<?= $sa_item['id'] ?>, '<?= htmlspecialchars(addslashes($subject_name)) ?>', '<?= htmlspecialchars(addslashes($sa_item['assignment_title'])) ?>')" style="background: #ef4444; color: white; border: none; padding: 0.5rem 1rem; border-radius: 6px; font-weight: 700; font-size: 0.8rem; cursor: pointer; display: inline-flex; align-items: center; gap: 4px; box-shadow: 0 2px 4px rgba(239,68,68,0.2);">
                                                                        <i class="fa-solid fa-triangle-exclamation"></i> Raise Grievance
                                                                    </button>
                                                                </div>
                                                            </div>
                                                            
                                                            <!-- COLUMN 2: SECTION 2 (STUDENT SUBMISSION) -->
                                                            <div>
                                                                <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 1.25rem;">
                                                                    <h6 style="margin: 0 0 0.75rem 0; font-size: 0.85rem; text-transform: uppercase; color: #64748b; font-weight: 700; border-bottom: 1px dashed #cbd5e1; padding-bottom: 0.5rem; display: flex; align-items: center; gap: 6px;">
                                                                        <i class="fa-solid fa-cloud-arrow-up" style="color: #10b981;"></i> STUDENT SUBMISSION
                                                                    </h6>
                                                                    
                                                                    <?php 
                                                                    $due_passed = time() > strtotime($sa_item['due'] ?? $sa_item['due_date'] ?? '');
                                                                    ?>
                                                                    
                                                                    <!-- Upload Portal State -->
                                                                    <div id="submission-portal-container-<?php echo $sa_item['id']; ?>">
                                                                        <?php if ($sub_status !== 'Not Submitted' && $sub_status !== 'Rejected'): ?>
                                                                            <!-- Already Submitted View -->
                                                                            <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                                                                                <div style="display: flex; align-items: center; gap: 6px; color: #15803d; font-weight: 700; font-size: 0.9rem;">
                                                                                    <i class="fa-solid fa-circle-check"></i> Submitted Successfully
                                                                                </div>
                                                                                <div style="font-size: 0.8rem; color: #64748b;">
                                                                                    <strong>Submission Date & Time:</strong> <?= htmlspecialchars($submitted_at) ?>
                                                                                </div>
                                                                                <?php
                                                                                $sub_names = json_decode($sub_file_name, true) ?: [$sub_file_name];
                                                                                $sub_paths = json_decode($sub_file, true) ?: [$sub_file];
                                                                                ?>
                                                                                <div style="font-size: 0.8rem; color: #64748b;">
                                                                                    <strong>File Name:</strong> <?= htmlspecialchars(implode(', ', $sub_names)) ?>
                                                                                </div>
                                                                                
                                                                                <div style="display: flex; gap: 0.5rem; margin-top: 0.5rem; flex-wrap: wrap;">
                                                                                    <?php foreach ($sub_paths as $idx => $path): ?>
                                                                                    <div style="display: flex; gap: 0.25rem;">
                                                                                        <a href="uploads/<?= htmlspecialchars($path) ?>" target="_blank" style="padding: 0.35rem 0.75rem; background: #e0f2fe; color: #0369a1; border-radius: 4px; font-size: 0.75rem; font-weight: 700; text-decoration: none; display: inline-flex; align-items: center; gap: 4px;">
                                                                                            <i class="fa-solid fa-eye"></i> View <?php echo count($sub_paths) > 1 ? ($idx+1) : 'File'; ?>
                                                                                        </a>
                                                                                        <a href="uploads/<?= htmlspecialchars($path) ?>" download style="padding: 0.35rem 0.75rem; background: #f0fdf4; color: #166534; border-radius: 4px; font-size: 0.75rem; font-weight: 700; text-decoration: none; display: inline-flex; align-items: center; gap: 4px;">
                                                                                            <i class="fa-solid fa-download"></i> Download <?php echo count($sub_paths) > 1 ? ($idx+1) : 'File'; ?>
                                                                                        </a>
                                                                                    </div>
                                                                                                                                <?php endforeach; 
                                                $rows_html = ob_get_clean();
                                                
                                                if (!$has_assignments) {
                                                    echo '<tr><td colspan="5" style="padding: 2rem; text-align: center; color: #94a3b8; font-weight: 500; font-size: 0.95rem;"><i class="fa-solid fa-folder-open" style="font-size: 2rem; color: #e2e8f0; margin-bottom: 0.5rem; display: block;"></i> No assignments published by faculty yet.</td></tr>';
                                                } else {
                                                    echo $rows_html;
                                                }
                                            ?>
                                                                                </div>

                                                                                <!-- Evaluation Marks / Remarks -->
                                                                                <?php if ($sub_marks !== 'Pending'): ?>
                                                                                    <div style="background: white; border: 1px solid #dcfce7; padding: 0.75rem; border-radius: 6px; margin-top: 0.5rem;">
                                                                                        <div style="font-weight: 700; color: #15803d; font-size: 0.85rem; margin-bottom: 2px;">Evaluation Result</div>
                                                                                        <div style="font-size: 0.8rem; color: #334155;"><strong>Marks Given:</strong> <span style="font-weight: 700; color: #16a34a;"><?= htmlspecialchars($sub_marks) ?></span></div>
                                                                                        <?php if (!empty($sub_remarks)): ?>
                                                                                            <div style="font-size: 0.8rem; color: #475569; margin-top: 2px;"><strong>Faculty Remarks:</strong> <em>"<?= htmlspecialchars($sub_remarks) ?>"</em></div>
                                                                                        <?php endif; ?>
                                                                                    </div>
                                                                                <?php endif; ?>

                                                                                <!-- Replace submission button before due date only -->
                                                                                <?php if (!$due_passed): ?>
                                                                                    <button type="button" onclick="showPortalUploadForm(<?= $sa_item['id'] ?>)" style="background: #e2e8f0; color: #475569; border: none; padding: 0.45rem 1rem; border-radius: 6px; font-weight: 700; font-size: 0.8rem; cursor: pointer; margin-top: 0.5rem; display: inline-flex; align-items: center; gap: 4px; justify-content: center; width: 100%;">
                                                                                        <i class="fa-solid fa-arrows-rotate"></i> Replace Submission
                                                                                    </button>
                                                                                <?php else: ?>
                                                                                    <button type="button" disabled style="background: #f1f5f9; color: #94a3b8; border: none; padding: 0.45rem 1rem; border-radius: 6px; font-weight: 700; font-size: 0.8rem; cursor: not-allowed; margin-top: 0.5rem; display: inline-flex; align-items: center; gap: 4px; justify-content: center; width: 100%;" title="Due date has passed. Replacement disabled.">
                                                                                        <i class="fa-solid fa-lock"></i> Replace Submission (Disabled - Due Date Passed)
                                                                                    </button>
                                                                                <?php endif; ?>
                                                                            </div>
                                                                        <?php endif; ?>

                                                                        <!-- Upload Form -->
                                                                        <div id="upload-form-wrapper-<?php echo $sa_item['id']; ?>" style="<?= ($sub_status !== 'Not Submitted' && $sub_status !== 'Rejected') ? 'display: none;' : '' ?>">
                                                                            <?php if ($sub_status === 'Rejected'): ?>
                                                                                <div style="background: #fef2f2; border: 1px solid #fecaca; color: #dc2626; padding: 0.75rem; border-radius: 6px; font-size: 0.85rem; margin-bottom: 1rem; font-weight: 600;">
                                                                                    <i class="fa-solid fa-circle-exclamation"></i> Your previous submission was rejected by the faculty. Please resubmit your assignment.
                                                                                </div>
                                                                            <?php endif; ?>
                                                                            <?php if ($due_passed): ?>
                                                                                <div style="color: #94a3b8; font-size: 0.85rem; text-align: center; padding: 1rem 0;">
                                                                                    <i class="fa-solid fa-lock" style="font-size: 1.5rem; display: block; margin-bottom: 0.5rem; color: #cbd5e1;"></i>
                                                                                    Due date has passed. Submissions are closed.
                                                                                </div>
                                                                            <?php else: ?>
                                                                                <form onsubmit="handlePortalAssignmentUpload(event, <?= $sa_item['id'] ?>)" style="display: flex; flex-direction: column; gap: 0.75rem;">
                                                                                    <div class="drag-drop-zone" id="drag-drop-zone-<?= $sa_item['id'] ?>" ondragover="event.preventDefault(); this.style.borderColor='#4f46e5';" ondragleave="this.style.borderColor='#cbd5e1';" ondrop="handlePortalFileDrop(event, <?= $sa_item['id'] ?>)" style="border: 2px dashed #cbd5e1; border-radius: 8px; padding: 1.5rem; text-align: center; background: white; cursor: pointer; transition: border-color 0.2s;" onclick="document.getElementById('portal-file-input-<?= $sa_item['id'] ?>').click()">
                                                                                        <i class="fa-solid fa-cloud-arrow-up" style="font-size: 2rem; color: #94a3b8; margin-bottom: 0.5rem;"></i>
                                                                                        <span style="display: block; font-size: 0.85rem; font-weight: 600; color: #475569;">Drag & Drop upload</span>
                                                                                        <span style="display: block; font-size: 0.75rem; color: #94a3b8; margin: 4px 0;">or</span>
                                                                                        <button type="button" style="background: #4f46e5; color: white; border: none; padding: 0.35rem 0.75rem; border-radius: 4px; font-weight: 700; font-size: 0.75rem; cursor: pointer;">Browse File</button>
                                                                                        <input type="file" id="portal-file-input-<?= $sa_item['id'] ?>" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png" multiple style="display: none;" onchange="handlePortalFileSelect(this, <?= $sa_item['id'] ?>)">
                                                                                    </div>
                                                                                    
                                                                                    <!-- Selected File Preview Container -->
                                                                                    <div id="file-preview-container-<?= $sa_item['id'] ?>" style="display: none; background: white; border: 1px solid #e2e8f0; border-radius: 6px; padding: 0.75rem; display: flex; align-items: center; justify-content: space-between;">
                                                                                        <div style="display: flex; align-items: center; gap: 8px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                                                                            <i class="fa-regular fa-file" style="color: #4f46e5; font-size: 1.25rem;"></i>
                                                                                            <div>
                                                                                                <div class="selected-file-name" style="font-size: 0.8rem; font-weight: 700; color: #1e293b;">file.pdf</div>
                                                                                                <div class="selected-file-size" style="font-size: 0.7rem; color: #94a3b8;">1.2 MB</div>
                                                                                            </div>
                                                                                        </div>
                                                                                        <button type="button" onclick="clearPortalSelectedFile(<?= $sa_item['id'] ?>)" style="background: none; border: none; color: #ef4444; font-size: 1rem; cursor: pointer; padding: 0.25rem;"><i class="fa-solid fa-trash-can"></i></button>
                                                                                    </div>
                                                                                    
                                                                                    <!-- Upload Progress Bar -->
                                                                                    <div id="progress-container-<?= $sa_item['id'] ?>" style="display: none; width: 100%;">
                                                                                        <div style="display: flex; justify-content: space-between; font-size: 0.7rem; font-weight: 700; color: #475569; margin-bottom: 2px;">
                                                                                            <span>Uploading...</span>
                                                                                            <span class="progress-percentage-<?= $sa_item['id'] ?>">0%</span>
                                                                                        </div>
                                                                                        <div style="width: 100%; height: 6px; background: #e2e8f0; border-radius: 3px; overflow: hidden;">
                                                                                            <div class="progress-bar-fill-<?= $sa_item['id'] ?>" style="width: 0%; height: 100%; background: #10b981; transition: width 0.1s;"></div>
                                                                                        </div>
                                                                                    </div>

                                                                                    <div style="font-size: 0.75rem; color: #94a3b8; display: flex; flex-direction: column; gap: 2px; padding: 2px;">
                                                                                        <span>• Accepted files: PDF, DOC, DOCX, JPG, JPEG, PNG</span>
                                                                                        <span>• Maximum size: 20 MB</span>
                                                                                    </div>

                                                                                    <button type="submit" id="btn-submit-portal-<?= $sa_item['id'] ?>" style="background: #10b981; color: white; border: none; padding: 0.6rem; border-radius: 6px; font-weight: 700; font-size: 0.85rem; cursor: pointer; display: flex; align-items: center; gap: 6px; justify-content: center; box-shadow: 0 2px 4px rgba(16,185,129,0.2);">
                                                                                        <i class="fa-solid fa-cloud-arrow-up"></i> Submit Assignment
                                                                                    </button>
                                                                                </form>
                                                                            <?php endif; ?>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            
                                                        </div>

                                                    </td>
                                                </tr>
                                                <?php endif; ?>
                                                                                        <?php endforeach; 
                                                $rows_html = ob_get_clean();
                                                
                                                if (!$has_assignments) {
                                                    echo '<tr><td colspan="5" style="padding: 2rem; text-align: center; color: #94a3b8; font-weight: 500; font-size: 0.95rem;"><i class="fa-solid fa-folder-open" style="font-size: 2rem; color: #e2e8f0; margin-bottom: 0.5rem; display: block;"></i> No assignments published by faculty yet.</td></tr>';
                                                } else {
                                                    echo $rows_html;
                                                }
                                            ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; endif; ?>
