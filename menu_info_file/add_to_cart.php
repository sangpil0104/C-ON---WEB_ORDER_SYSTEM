<?php
session_start();
if (!isset($_SESSION['username'])) {
    // 로그인하지 않은 사용자는 로그인 페이지로 리디렉션
    header('Location: ../login_page_file/login_page.php');
    exit;
}

$username = $_SESSION['username'];
$cno = $_SESSION['cno'];
$item = $_POST['item'];
$quantity = (int)$_POST['quantity'];
$total = (int)$_POST['total'];

if ($quantity <= 0) {
    echo json_encode(['success' => false, 'error' => 'Quantity must be greater than 0']);
    exit;
}

// Oracle DB 연결 (UTF-8 인코딩 지정)
$conn = oci_connect('d202202485', '1111', 'localhost/xe', 'AL32UTF8');
if (!$conn) {
    $e = oci_error();
    echo json_encode(['success' => false, 'error' => 'Oracle connection error: ' . htmlentities($e['message'])]);
    exit;
}

// 현재 사용자의 열려있는 장바구니 확인
$checkCartQuery = 'SELECT ID FROM CART WHERE CNO = :cno AND PAID = 0';
$stid = oci_parse($conn, $checkCartQuery);
oci_bind_by_name($stid, ':cno', $cno);
oci_execute($stid);

$cartId = null;
if ($row = oci_fetch_array($stid, OCI_ASSOC)) {
    // 기존 장바구니가 있음
    $cartId = $row['ID'];
} else {
    // 새로운 장바구니 생성
    $cartQuery = 'INSERT INTO CART (ID, CNO) VALUES (:id, :cno)';
    $stid = oci_parse($conn, $cartQuery);
    $cartId = str_pad((string)rand(0, 99999999), 8, '0', STR_PAD_LEFT); // 8자리 Unique ID 생성
    oci_bind_by_name($stid, ':id', $cartId);
    oci_bind_by_name($stid, ':cno', $cno);

    if (!oci_execute($stid)) {
        $e = oci_error($stid);
        echo json_encode(['success' => false, 'error' => 'Oracle query error: ' . htmlentities($e['message'])]);
        oci_free_statement($stid);
        oci_close($conn);
        exit;
    }

    oci_free_statement($stid);
}

$orderQuery = 'INSERT INTO ORDERDETAIL (ITEMNO, ID, QUANTITY, TOTALPRICE, FOODNAME) VALUES (:itemno, :id, :quantity, :totalprice, :foodname)';
$stid = oci_parse($conn, $orderQuery);
$itemNo = str_pad((string)rand(0, 9999), 4, '0', STR_PAD_LEFT); // 4자리 Unique ITEMNO 생성
oci_bind_by_name($stid, ':itemno', $itemNo);
oci_bind_by_name($stid, ':id', $cartId);
oci_bind_by_name($stid, ':quantity', $quantity);
oci_bind_by_name($stid, ':totalprice', $total);
oci_bind_by_name($stid, ':foodname', $item);

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
