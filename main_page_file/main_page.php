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

// 카테고리 정보 가져오기
$categoryQuery = 'SELECT CATEGORYNAME FROM CATEGORY';
$stid = oci_parse($conn, $categoryQuery);
oci_execute($stid);

$categories = [];
while ($row = oci_fetch_array($stid, OCI_ASSOC)) {
    $categories[] = $row['CATEGORYNAME'];
}

oci_free_statement($stid);
oci_close($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Cafe_Menu</title>
  <link rel="stylesheet" href="main_page.css?v=<?php echo time(); ?>">
</head>
<body>
  <div class="header">
      <div class="text" onclick="window.location.href='../main_page_file/main_page.php'">C:ON</div>
      <?php if ($username === 'C0'): ?>
          <div class="statistics">
              <button onclick="window.location.href='../statistics_file/statistics.php'">Statistics</button>
          </div>
      <?php endif; ?>
      <div class="user-info">
          Logged in as: <?php echo htmlspecialchars($username); ?>
      </div>
      <div class="logout">
          <button id="logoutButton" onclick="window.location.href='../login_page_file/login_page.php'">Log Out</button>
      </div>
  </div>
  <div class="container">
    <h2>Menu Categories</h2>
    <div class="categories">
      <?php foreach ($categories as $category): ?>
        <button class="category" data-category="<?php echo htmlspecialchars($category); ?>"><?php echo htmlspecialchars($category); ?></button>
      <?php endforeach; ?>
    </div>
    <div class="menu-items">
    </div>
  </div>
  <div class="footer">
    <button class="footer-button" id="cartButton" onclick="window.location.href='../my_cart_file/my_cart.php'">My Cart</button>
    <button class="footer-button" id="orderHistoryButton" onclick="window.location.href='../order_history_file/order_history.php'">Order History</button>
  </div>
  <script src="main_page.js"></script>
</body>
</html>
