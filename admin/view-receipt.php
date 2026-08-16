<?php
// admin/view-receipt.php

require_once __DIR__ . '/../includes/admin-auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../vendor/tcpdf/tcpdf.php';

$payment_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($payment_id <= 0) {
    die('Invalid payment ID');
}

$db = new Database();
$conn = $db->getConnection();

// Get payment details - No student_id restriction for admin
$stmt = $conn->prepare("SELECT p.*,
                              st.student_name, st.enrollment_no, st.father_name, st.mobile, st.address, st.city, st.state,
                              c.course_name, c.course_code,
                              d.department_name,
                              s.semester_name,
                              fs.tuition_fee, fs.examination_fee, fs.development_fee,
                              fs.library_fee, fs.laboratory_fee, fs.other_fee, fs.total_fee,
                              sf.paid_amount as total_paid, sf.pending_amount as total_pending,
                              sf.academic_session
                       FROM payments p
                       JOIN students st ON p.student_id = st.id
                       JOIN courses c ON st.course_id = c.id
                       JOIN departments d ON st.department_id = d.id
                       LEFT JOIN student_fees sf ON p.student_fee_id = sf.id
                       LEFT JOIN semesters s ON sf.semester_id = s.id
                       LEFT JOIN fee_structures fs ON sf.fee_structure_id = fs.id
                       WHERE p.id = ? AND p.payment_status = 'success'");
$stmt->execute([$payment_id]);
$payment = $stmt->fetch();

if (!$payment) {
    die('Payment record not found');
}

// Same TCPDF code as student/receipt.php
$pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
$pdf->SetCreator('ASCT');
$pdf->SetAuthor('All Saints College Of Technology');
$pdf->SetTitle('Fee Receipt - ' . $payment['receipt_no']);
$pdf->SetSubject('Fee Payment Receipt');
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);
$pdf->SetMargins(15, 15, 15);
$pdf->SetAutoPageBreak(TRUE, 15);
$pdf->AddPage();
$pdf->SetFont('helvetica', '', 10);

// College Header
$pdf->SetFont('helvetica', 'B', 20);
$pdf->Cell(0, 10, 'ALL SAINTS COLLEGE OF TECHNOLOGY', 0, 1, 'C');
$pdf->SetFont('helvetica', '', 12);
$pdf->Cell(0, 6, 'ASCT, Bhopal, Madhya Pradesh, India', 0, 1, 'C');
$pdf->Ln(2);

$pdf->SetFont('helvetica', 'B', 16);
$pdf->SetFillColor(102, 126, 234);
$pdf->SetTextColor(255, 255, 255);
$pdf->Cell(0, 10, 'FEE PAYMENT RECEIPT', 0, 1, 'C', true);
$pdf->SetTextColor(0, 0, 0);
$pdf->Ln(5);

// Receipt Information
$pdf->SetFont('helvetica', '', 10);
$html = '<table border="0" cellpadding="4">
    <tr>
        <td width="50%"><strong>Receipt No:</strong> ' . $payment['receipt_no'] . '</td>
        <td width="50%"><strong>Payment Date:</strong> ' . date('d-m-Y H:i', strtotime($payment['payment_date'])) . '</td>
    </tr>
    <tr>
        <td><strong>Payment ID:</strong> ' . $payment['razorpay_payment_id'] . '</td>
        <td><strong>Payment Method:</strong> ' . ucfirst($payment['payment_method']) . '</td>
    </tr>
</table>';
$pdf->writeHTML($html, true, false, true, false, '');
$pdf->Ln(3);

// Student Information
$pdf->SetFont('helvetica', 'B', 12);
$pdf->SetFillColor(240, 240, 240);
$pdf->Cell(0, 8, 'Student Information', 0, 1, 'L', true);
$pdf->SetFont('helvetica', '', 10);

$html = '<table border="0" cellpadding="4">
    <tr>
        <td width="50%"><strong>Enrollment No:</strong> ' . $payment['enrollment_no'] . '</td>
        <td width="50%"><strong>Student Name:</strong> ' . $payment['student_name'] . '</td>
    </tr>
    <tr>
        <td><strong>Father\'s Name:</strong> ' . $payment['father_name'] . '</td>
        <td><strong>Mobile:</strong> ' . $payment['mobile'] . '</td>
    </tr>
    <tr>
        <td><strong>Department:</strong> ' . $payment['department_name'] . '</td>
        <td><strong>Course:</strong> ' . $payment['course_name'] . '</td>
    </tr>
    <tr>
        <td><strong>Semester:</strong> ' . $payment['semester_name'] . '</td>
        <td><strong>Academic Session:</strong> ' . $payment['academic_session'] . '</td>
    </tr>
</table>';
$pdf->writeHTML($html, true, false, true, false, '');
$pdf->Ln(3);

// Fee Details
$pdf->SetFont('helvetica', 'B', 12);
$pdf->SetFillColor(240, 240, 240);
$pdf->Cell(0, 8, 'Fee Details', 0, 1, 'L', true);
$pdf->SetFont('helvetica', '', 10);

$html = '<table border="1" cellpadding="5">
    <thead>
        <tr style="background-color: #f0f0f0; font-weight: bold;">
            <th width="60%">Particulars</th>
            <th width="40%" align="right">Amount (₹)</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td>Tuition Fee</td>
            <td align="right">' . number_format($payment['tuition_fee'], 2) . '</td>
        </tr>
        <tr>
            <td>Examination Fee</td>
            <td align="right">' . number_format($payment['examination_fee'], 2) . '</td>
        </tr>
        <tr>
            <td>Development Fee</td>
            <td align="right">' . number_format($payment['development_fee'], 2) . '</td>
        </tr>
        <tr>
            <td>Library Fee</td>
            <td align="right">' . number_format($payment['library_fee'], 2) . '</td>
        </tr>
        <tr>
            <td>Laboratory Fee</td>
            <td align="right">' . number_format($payment['laboratory_fee'], 2) . '</td>
        </tr>
        <tr>
            <td>Other Fee</td>
            <td align="right">' . number_format($payment['other_fee'], 2) . '</td>
        </tr>
        <tr style="background-color: #f8f9fa; font-weight: bold;">
            <td><strong>Total Semester Fee</strong></td>
            <td align="right"><strong>' . number_format($payment['total_fee'], 2) . '</strong></td>
        </tr>
        <tr style="background-color: #d4edda;">
            <td><strong>Amount Paid (This Transaction)</strong></td>
            <td align="right"><strong>' . number_format($payment['amount'], 2) . '</strong></td>
        </tr>
        <tr>
            <td>Total Paid Till Date</td>
            <td align="right">' . number_format($payment['total_paid'], 2) . '</td>
        </tr>
        <tr style="background-color: #f8d7da;">
            <td><strong>Remaining Balance</strong></td>
            <td align="right"><strong>' . number_format($payment['total_pending'], 2) . '</strong></td>
        </tr>
    </tbody>
</table>';
$pdf->writeHTML($html, true, false, true, false, '');
$pdf->Ln(8);

// Payment Note
$pdf->SetFont('helvetica', 'I', 9);
$payment_note = 'Payment received through ' . ucfirst($payment['payment_method']) . '. This is a computer-generated receipt and does not require a physical signature.';
$pdf->MultiCell(0, 5, $payment_note, 0, 'L');
$pdf->Ln(5);

// Signature
$pdf->SetFont('helvetica', '', 10);
$pdf->Cell(95, 5, '', 0, 0);
$pdf->Cell(0, 5, '_________________________', 0, 1, 'R');
$pdf->Cell(95, 5, '', 0, 0);
$pdf->Cell(0, 5, 'Authorized Signature', 0, 1, 'R');

// Footer
$pdf->SetY(-20);
$pdf->SetFont('helvetica', 'I', 8);
$pdf->Cell(0, 5, 'Generated on: ' . date('d-m-Y H:i:s'), 0, 1, 'C');
$pdf->Cell(0, 5, 'This is a system-generated receipt. For queries, contact: info@asct.edu.in', 0, 1, 'C');

$pdf->Output('Receipt_' . $payment['receipt_no'] . '.pdf', 'I');
exit;
?>