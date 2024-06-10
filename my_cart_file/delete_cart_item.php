<?php
session_start();
if (!isset($_SESSION['username'])) {
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit;
}

$itemNo = $_POST['itemNo'];
$cartId = $_POST['cartId'];

// Oracle DB 연결 (UTF-8 인코딩 지정)
$conn = oci_connect('d202202485', '1111', 'localhost/xe', 'AL32UTF8');
if (!$conn) {
    $e = oci_error();
    echo json_encode(['success' => false, 'error' => 'Oracle connection error: ' . htmlentities($e['message'])]);
    exit;
}

// 항목 삭제 쿼리
$deleteQuery = 'DELETE FROM ORDERDETAIL WHERE ITEMNO = :itemno AND ID = :cartid';
$stid = oci_parse($conn, $deleteQuery);
oci_bind_by_name($stid, ':itemno', $itemNo);
oci_bind_by_name($stid, ':cartid', $cartId);

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
