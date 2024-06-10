<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up</title>
    <link rel="stylesheet" href="signup_page.css?v=<?php echo time(); ?>">
</head>
<body>
    <div class="container">
        <h2>Sign Up</h2>
        <form id="signupForm" method="POST" action="signup_page.php">
            <label for="name">Username:</label>
            <input type="text" id="name" name="name" required>
            <label for="password">Password:</label>
            <input type="password" id="password" name="password" required>
            <label for="phonenumber">Phone Number:</label>
            <input type="text" id="phonenumber" name="phonenumber" required>
            <button type="submit">Sign Up</button>
        </form>
        <button id="loginButton" onclick="window.location.href='../login_page_file/login_page.php'">Return To Login</button>
        <div id="message"></div>
    </div>
    <script>
        function showSuccessMessage() {
            const messageDiv = document.getElementById('message');
            messageDiv.innerHTML = `
                <div class="success-message">
                    회원가입이 성공적으로 완료되었습니다.
                    <button onclick="redirectToLogin()">확인</button>
                </div>
            `;
        }

        function showErrorMessage(error) {
            const messageDiv = document.getElementById('message');
            messageDiv.innerHTML = `<div class="error-message">${error}</div>`;
        }

        function redirectToLogin() {
            window.location.href = '../login_page_file/login_page.php'; // 로그인 페이지 경로로 변경
        }
    </script>
    <?php
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $name = $_POST['name'];
        $password = $_POST['password'];
        $phonenumber = $_POST['phonenumber'];

        // Oracle DB 연결
        $conn = oci_connect('d202202485', '1111', 'localhost/xe');

        if (!$conn) {
            $e = oci_error();
            echo "<script>showErrorMessage('Oracle connection error: " . htmlentities($e['message']) . "');</script>";
        } else {
            try {
                // 사용자 이름 중복 체크
                $checkQuery = 'SELECT COUNT(*) AS CNT FROM CUSTOMER WHERE NAME = :name';
                $checkStid = oci_parse($conn, $checkQuery);
                oci_bind_by_name($checkStid, ':name', $name);
                oci_execute($checkStid);
                $row = oci_fetch_array($checkStid, OCI_ASSOC);

                if ($row['CNT'] > 0) {
                    echo "<script>showErrorMessage('이미 존재하는 사용자 이름입니다.');</script>";
                } else {
                    // 고객 번호 생성
                    $cnoQuery = "SELECT LPAD(NVL(MAX(TO_NUMBER(CNO)), 0) + 1, 3, '0') AS NEW_CNO FROM CUSTOMER";
                    $cnoStid = oci_parse($conn, $cnoQuery);
                    oci_execute($cnoStid);
                    $cnoRow = oci_fetch_array($cnoStid, OCI_ASSOC);
                    $cno = $cnoRow['NEW_CNO'];

                    // 고객 정보 삽입
                    $insertQuery = 'INSERT INTO CUSTOMER (CNO, NAME, PASSWORD, PHONENUMBER) VALUES (:cno, :name, :password, :phonenumber)';
                    $insertStid = oci_parse($conn, $insertQuery);
                    oci_bind_by_name($insertStid, ':cno', $cno);
                    oci_bind_by_name($insertStid, ':name', $name);
                    oci_bind_by_name($insertStid, ':password', $password);
                    oci_bind_by_name($insertStid, ':phonenumber', $phonenumber);

                    $r = oci_execute($insertStid);

                    if ($r) {
                        echo "<script>showSuccessMessage();</script>";
                    } else {
                        $e = oci_error($insertStid);
                        echo "<script>showErrorMessage('회원가입 중 오류가 발생했습니다: " . htmlentities($e['message']) . "');</script>";
                    }

                    oci_free_statement($insertStid);
                }
            } catch (Exception $e) {
                echo "<script>showErrorMessage('Unexpected error: " . htmlentities($e->getMessage()) . "');</script>";
            } finally {
                if (isset($checkStid)) {
                    oci_free_statement($checkStid);
                }
                if (isset($cnoStid)) {
                    oci_free_statement($cnoStid);
                }
                oci_close($conn);
            }
        }
    }
    ?>
</body>
</html>
