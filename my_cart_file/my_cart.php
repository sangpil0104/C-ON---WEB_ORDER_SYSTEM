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

// 장바구니 정보 가져오기
$cartQuery = "
    SELECT OD.ITEMNO, OD.FOODNAME, OD.QUANTITY, OD.TOTALPRICE, C.ID
    FROM ORDERDETAIL OD
    JOIN CART C ON OD.ID = C.ID
    WHERE C.CNO = :cno AND C.PAID = 0
";
$stid = oci_parse($conn, $cartQuery);
oci_bind_by_name($stid, ':cno', $cno);
oci_execute($stid);

$cartItems = [];
while ($row = oci_fetch_array($stid, OCI_ASSOC)) {
    $cartItems[] = $row;
}

oci_free_statement($stid);
oci_close($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Cart</title>
    <link rel="stylesheet" href="../my_cart_file/my_cart.css?v=<?php echo time(); ?>">
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
        <h2>My Cart</h2>
        <?php if (empty($cartItems)): ?>
            <p>Your cart is empty.</p>
            <button onclick="window.location.href='../main_page_file/main_page.php'">Go to Main Page</button>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>Food Name</th>
                        <th>Quantity</th>
                        <th>Total Price</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $totalAmount = 0;
                    foreach ($cartItems as $item):
                        $totalAmount += $item['TOTALPRICE'];
                    ?>
                        <tr>
                            <td><?php echo htmlspecialchars($item['FOODNAME']); ?></td>
                            <td><?php echo htmlspecialchars($item['QUANTITY']); ?></td>
                            <td><?php echo htmlspecialchars($item['TOTALPRICE']); ?></td>
                            <td>
                                <button onclick="deleteItem('<?php echo $item['ITEMNO']; ?>', '<?php echo $item['ID']; ?>')">Delete</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <div class="total-amount">
                Total Amount: <?php echo htmlspecialchars($totalAmount); ?>
            </div>
            <button onclick="payCart()">Pay</button>
        <?php endif; ?>
    </div>
    <script>
        function deleteItem(itemNo, cartId) {
            if (confirm('Are you sure you want to delete this item?')) {
                fetch('delete_cart_item.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: `itemNo=${encodeURIComponent(itemNo)}&cartId=${encodeURIComponent(cartId)}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Item deleted successfully.');
                        window.location.reload();
                    } else {
                        alert('Error deleting item: ' + data.error);
                    }
                })
                .catch(error => console.error('Error:', error));
            }
        }

        function payCart() {
            if (confirm('Are you sure you want to pay for all items in the cart?')) {
                fetch('pay_cart.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Payment successful.');
                        window.location.reload();
                    } else {
                        alert('Error processing payment: ' + data.error);
                    }
                })
                .catch(error => console.error('Error:', error));
            }
        }
    </script>
</body>
</html>
