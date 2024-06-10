<?php
session_start();
if (!isset($_SESSION['username'])) {
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit;
}

$cno = $_SESSION['cno'];  // 세션에서 CNO 가져오기

// Oracle DB 연결 (UTF-8 인코딩 지정)
$conn = oci_connect('d202202485', '1111', 'localhost/xe', 'AL32UTF8');
if (!$conn) {
    $e = oci_error();
    echo json_encode(['success' => false, 'error' => 'Oracle connection error: ' . htmlentities($e['message'])]);
    exit;
}

// 결제 처리 쿼리
$updateQuery = 'UPDATE CART SET PAID = 1, CART_DATE = SYSDATE, CART_TIME = TO_CHAR(SYSDATE, \'HH24:MI\') WHERE CNO = :cno AND PAID = 0';
$stid = oci_parse($conn, $updateQuery);
oci_bind_by_name($stid, ':cno', $cno);

if (!oci_execute($stid)) {
    $e = oci_error($stid);
    echo json_encode(['success' => false, 'error' => 'Oracle query error: ' . htmlentities($e['message'])]);
    oci_free_statement($stid);
    oci_close($conn);
    exit;
}

oci_free_statement($stid);
oci_close($conn);

echo json_encode(['success' => true]);
?>
