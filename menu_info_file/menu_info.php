<?php
session_start();
if (!isset($_SESSION['username'])) {
    // 로그인하지 않은 사용자는 로그인 페이지로 리디렉션
    header('Location: ../login_page_file/login_page.php');
    exit;
}

$username = $_SESSION['username'];
$cno = $_SESSION['cno'];

// 메뉴 정보 가져오기
$menuItem = $_GET['item'];

// Oracle DB 연결 (UTF-8 인코딩 지정)
$conn = oci_connect('d202202485', '1111', 'localhost/xe', 'AL32UTF8');
if (!$conn) {
    $e = oci_error();
    echo "Oracle connection error: " . htmlentities($e['message']);
    exit;
}

$menuQuery = 'SELECT FOODNAME, PRICE FROM FOOD WHERE FOODNAME = :foodname';
$stid = oci_parse($conn, $menuQuery);
oci_bind_by_name($stid, ':foodname', $menuItem);
oci_execute($stid);

$menu = oci_fetch_array($stid, OCI_ASSOC);

oci_free_statement($stid);
oci_close($conn);

if (!$menu) {
    echo "Menu item not found.";
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Menu Info</title>
    <link rel="stylesheet" href="menu_info.css?v=<?php echo time(); ?>">
</head>
<body>
    <div class="header">
        <div class="text" onclick="window.location.href='../main_page_file/main_page.php'">C:ON</div>
    </div>
    <div class="container">
        <h2>Menu Information</h2>
        <form id="menuForm">
            <label for="item">Item:</label>
            <input type="text" id="item" name="item" value="<?php echo htmlspecialchars($menu['FOODNAME']); ?>" readonly>
            <label for="price">Price:</label>
            <input type="text" id="price" name="price" value="<?php echo htmlspecialchars($menu['PRICE']); ?>" readonly>
            <label for="quantity">Quantity:</label>
            <input type="number" id="quantity" name="quantity" value="1" min="1">
            <label for="total">Total Price:</label>
            <input type="text" id="total" name="total" value="<?php echo htmlspecialchars($menu['PRICE']); ?>" readonly>
            <button type="button" onclick="addToCart()">Add to Cart</button>
        </form>
    </div>
    <script>
        document.getElementById('quantity').addEventListener('input', function() {
            const price = document.getElementById('price').value;
            const quantity = this.value;
            if (quantity <= 0) {
                alert('Quantity must be greater than 0');
                return;
            }
            const total = price * quantity;
            document.getElementById('total').value = total;
        });

        function addToCart() {
            const item = document.getElementById('item').value;
            const price = document.getElementById('price').value;
            const quantity = document.getElementById('quantity').value;
            const total = document.getElementById('total').value;

            if (quantity <= 0) {
                alert('Quantity must be greater than 0');
                return;
            }

            fetch('add_to_cart.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: `item=${encodeURIComponent(item)}&price=${encodeURIComponent(price)}&quantity=${encodeURIComponent(quantity)}&total=${encodeURIComponent(total)}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Item added to cart successfully.');
                    window.location.href = '../main_page_file/main_page.php';
                } else {
                    alert('Error adding item to cart: ' + data.error);
                }
            })
            .catch(error => console.error('Error:', error));
        }
    </script>
</body>
</html>
