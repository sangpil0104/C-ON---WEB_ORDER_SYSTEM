<?php
session_start();

// 로그인 폼 제출 처리
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Oracle DB 연결
    $conn = oci_connect('d202202485', '1111', 'localhost/xe');
    if (!$conn) {
        $e = oci_error();
        echo "Oracle connection error: " . htmlentities($e['message']);
        exit;
    }

    // 사용자 정보 확인
    $loginQuery = 'SELECT * FROM CUSTOMER WHERE NAME = :username AND PASSWORD = :password';
    $stid = oci_parse($conn, $loginQuery);
    oci_bind_by_name($stid, ':username', $username);
    oci_bind_by_name($stid, ':password', $password);
    oci_execute($stid);

    if ($row = oci_fetch_array($stid, OCI_ASSOC)) {
        // 로그인 성공
        $_SESSION['username'] = $username;
        $_SESSION['cno'] = $row['CNO'];  // CNO 정보도 세션에 저장
        header('Location: ../main_page_file/main_page.php');
        exit;
    } else {
        // 로그인 실패
        echo "<script>alert('Invalid username or password');</script>";
    }

    oci_free_statement($stid);
    oci_close($conn);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login</title>
    <link rel="stylesheet" href="login_page.css">
</head>
<body>
    <div class="container">
        <h2>Login</h2>
        <form method="POST" action="login_page.php">
            <label for="username">Username:</label>
            <input type="text" id="username" name="username" required>
            <label for="password">Password:</label>
            <input type="password" id="password" name="password" required>
            <button type="submit">Login</button>
        </form>
        <button class="signup-button" onclick="window.location.href='/dbterm/signup_page_file/signup_page.php'">Sign Up</button>
    </div>
</body>
</html>
