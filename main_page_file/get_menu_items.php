<?php
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $category = $_POST['category'];

    // Oracle DB 연결 (UTF-8 인코딩 지정)
    $conn = oci_connect('d202202485', '1111', 'localhost/xe', 'AL32UTF8');
    if (!$conn) {
        $e = oci_error();
        error_log('Oracle connection error: ' . htmlentities($e['message']));
        echo json_encode(['error' => 'Oracle connection error: ' . htmlentities($e['message'])]);
        exit;
    }

    // 메뉴 항목 정보 가져오기
    $menuQuery = 'SELECT F.FOODNAME 
                  FROM FOOD F
                  JOIN CONTAINS C ON F.FOODNAME = C.FOODNAME
                  WHERE C.CATEGORYNAME = :category';
    $stid = oci_parse($conn, $menuQuery);
    oci_bind_by_name($stid, ':category', $category);
    if (!oci_execute($stid)) {
        $e = oci_error($stid);
        error_log('Oracle query error: ' . htmlentities($e['message']));
        echo json_encode(['error' => 'Oracle query error: ' . htmlentities($e['message'])]);
        oci_free_statement($stid);
        oci_close($conn);
        exit;
    }

    $menuItems = [];
    while ($row = oci_fetch_array($stid, OCI_ASSOC)) {
        $menuItems[] = $row['FOODNAME'];
    }

    oci_free_statement($stid);
    oci_close($conn);

    echo json_encode($menuItems);
}
?>
