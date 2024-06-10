<?php
session_start();
if (!isset($_SESSION['username'])) {
    // 로그인하지 않은 사용자는 로그인 페이지로 리디렉션
    header('Location: ../login_page_file/login_page.php');
    exit;
}

$username = $_SESSION['username'];
$cno = $_SESSION['cno'];  // 세션에서 CNO 가져오기

// Oracle DB 연결 (UTF-8 인코딩 지정)
$conn = oci_connect('d202202485', '1111', 'localhost/xe', 'AL32UTF8');
if (!$conn) {
    $e = oci_error();
    echo "Oracle connection error: " . htmlentities($e['message']);
    exit;
}

// 기본 주문 내역 쿼리
$orderQuery = "
    SELECT C.ID, C.CART_DATE, C.CART_TIME, OD.FOODNAME, OD.QUANTITY, OD.TOTALPRICE
    FROM ORDERDETAIL OD
    JOIN CART C ON OD.ID = C.ID
    WHERE C.CNO = :cno AND C.PAID = 1
";

$searchInput = isset($_GET['searchInput']) ? $_GET['searchInput'] : '';
$startDate = isset($_GET['startDate']) ? $_GET['startDate'] : '';
$endDate = isset($_GET['endDate']) ? $_GET['endDate'] : '';

if (!empty($searchInput)) {
    $orderQuery .= " AND LOWER(OD.FOODNAME) LIKE LOWER(:searchInput)";
}
if (!empty($startDate) && !empty($endDate)) {
    $orderQuery .= " AND C.CART_DATE BETWEEN TO_DATE(:startDate, 'YYYY-MM-DD') AND TO_DATE(:endDate, 'YYYY-MM-DD')";
}

$orderQuery .= " ORDER BY C.CART_DATE DESC, C.CART_TIME DESC";

$stid = oci_parse($conn, $orderQuery);
oci_bind_by_name($stid, ':cno', $cno);
if (!empty($searchInput)) {
    $searchTerm = '%' . $searchInput . '%';
    oci_bind_by_name($stid, ':searchInput', $searchTerm);
}
if (!empty($startDate) && !empty($endDate)) {
    oci_bind_by_name($stid, ':startDate', $startDate);
    oci_bind_by_name($stid, ':endDate', $endDate);
}
oci_execute($stid);

$orderHistory = [];
while ($row = oci_fetch_array($stid, OCI_ASSOC)) {
    $orderHistory[] = $row;
}

oci_free_statement($stid);
oci_close($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order History</title>
    <link rel="stylesheet" href="../order_history_file/order_history.css?v=<?php echo time(); ?>">
</head>
<body>
    <div class="header">
        <div class="text" onclick="window.location.href='../main_page_file/main_page.php'">C:ON</div>
        <div class="user-info">
            Logged in as: <?php echo htmlspecialchars($username); ?>
        </div>
        <div class="logout">
            <button id="logoutButton" onclick="window.location.href='../login_page_file/login_page.php'">Log Out</button>
        </div>
    </div>
    <div class="container">
        <h2>Order History</h2>
        <div class="search">
            <label for="searchInput">Search:</label>
            <input type="text" id="searchInput" placeholder="Enter food name..." value="<?php echo htmlspecialchars($searchInput); ?>">
            <label for="startDate">Start Date:</label>
            <input type="date" id="startDate" value="<?php echo htmlspecialchars($startDate); ?>">
            <label for="endDate">End Date:</label>
            <input type="date" id="endDate" value="<?php echo htmlspecialchars($endDate); ?>">
            <button onclick="searchOrders()">Search</button>
        </div>
        <?php if (empty($orderHistory)): ?>
            <p>No order history available.</p>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>Cart ID</th>
                        <th>Date</th>
                        <th>Time</th>
                        <th>Food Name</th>
                        <th>Quantity</th>
                        <th>Total Price</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orderHistory as $order): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($order['ID']); ?></td>
                            <td><?php echo htmlspecialchars($order['CART_DATE']); ?></td>
                            <td><?php echo htmlspecialchars($order['CART_TIME']); ?></td>
                            <td><?php echo htmlspecialchars($order['FOODNAME']); ?></td>
                            <td><?php echo htmlspecialchars($order['QUANTITY']); ?></td>
                            <td><?php echo htmlspecialchars($order['TOTALPRICE']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
    <script>
        function searchOrders() {
            const searchInput = document.getElementById('searchInput').value.trim();
            const startDate = document.getElementById('startDate').value;
            const endDate = document.getElementById('endDate').value;
            const urlParams = new URLSearchParams(window.location.search);
            
            if (searchInput) {
                urlParams.set('searchInput', searchInput);
            } else {
                urlParams.delete('searchInput');
            }
            if (startDate) {
                urlParams.set('startDate', startDate);
            } else {
                urlParams.delete('startDate');
            }
            if (endDate) {
                urlParams.set('endDate', endDate);
            } else {
                urlParams.delete('endDate');
            }

            window.location.search = urlParams.toString();
        }
    </script>
</body>
</html>
