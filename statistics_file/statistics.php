<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['username'] !== 'C0') {
    // 관리자가 아닌 사용자는 접근할 수 없음
    header('Location: ../login_page_file/login_page.php');
    exit;
}

// Oracle DB 연결 (UTF-8 인코딩 지정)
$conn = oci_connect('d202202485', '1111', 'localhost/xe', 'AL32UTF8');
if (!$conn) {
    $e = oci_error();
    echo "Oracle connection error: " . htmlentities($e['message']);
    exit;
}

// 메뉴 별 매출 순위
$salesRankingQuery = "
    SELECT RANK() OVER (ORDER BY SUM(O.TOTALPRICE) DESC) AS RANK, O.FOODNAME, SUM(O.TOTALPRICE) AS TOTAL_SALES
    FROM ORDERDETAIL O
    JOIN CART C ON O.ID = C.ID
    WHERE C.PAID = 1
    GROUP BY O.FOODNAME
";
$stid1 = oci_parse($conn, $salesRankingQuery);
oci_execute($stid1);

$salesRanking = [];
while ($row = oci_fetch_array($stid1, OCI_ASSOC)) {
    $salesRanking[] = $row;
}

oci_free_statement($stid1);

// 메뉴 별 팔린 수량과 매출 (ROLLUP 사용)
$salesRollupQuery = "
    SELECT FOODNAME, SUM(QUANTITY) AS TOTAL_QUANTITY, SUM(TOTALPRICE) AS TOTAL_SALES
    FROM ORDERDETAIL
    GROUP BY ROLLUP(FOODNAME)
    ORDER BY FOODNAME
";
$stid2 = oci_parse($conn, $salesRollupQuery);
oci_execute($stid2);

$salesRollup = [];
while ($row = oci_fetch_array($stid2, OCI_ASSOC+OCI_RETURN_NULLS)) {
    $salesRollup[] = $row;
}

oci_free_statement($stid2);
oci_close($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Statistics</title>
    <link rel="stylesheet" href="../statistics_file/statistics.css?v=<?php echo time(); ?>">
</head>
<body>
    <div class="header">
        <div class="text" onclick="window.location.href='../main_page_file/main_page.php'">C:ON</div>
        <div class="user-info">
            Logged in as: <?php echo htmlspecialchars($_SESSION['username']); ?>
        </div>
        <div class="logout">
            <button id="logoutButton" onclick="window.location.href='../login_page_file/login_page.php'">Log Out</button>
        </div>
    </div>
    <div class="container">
        <div class="statistics-section">
            <div class="statistics-table">
                <h3>Menu Sales Ranking</h3>
                <table>
                    <thead>
                        <tr>
                            <th>Rank</th>
                            <th>Food Name</th>
                            <th>Total Sales</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($salesRanking as $rank): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($rank['RANK']); ?></td>
                                <td><?php echo htmlspecialchars($rank['FOODNAME']); ?></td>
                                <td><?php echo htmlspecialchars($rank['TOTAL_SALES']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="statistics-table">
                <h3>Menu Sales and Quantity</h3>
                <table>
                    <thead>
                        <tr>
                            <th>Food Name</th>
                            <th>Total Quantity</th>
                            <th>Total Sales</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($salesRollup as $rollup): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($rollup['FOODNAME'] ?: '합계'); ?></td>
                                <td><?php echo htmlspecialchars($rollup['TOTAL_QUANTITY']); ?></td>
                                <td><?php echo htmlspecialchars($rollup['TOTAL_SALES']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>
