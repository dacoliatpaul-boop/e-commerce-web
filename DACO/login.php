<?php




?>

<!DOCTYPE html>
<html>    
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" type="text/css" href="css/login.css">
    <title>LOGIN</title>
</head>
<body>

<div>
    <a class =DCO>DCO</a>
    <div class = "login-page">
        <h1>Login</h1>
        <p class="message">WELCOME TO DCO</p>
        <div>
            <form method="POST" action="login.php">
                <div class="form">
                    <label for="email"></label>
                    <input type ="email" id="email" name="email" 
                    placeholder="EMAIL" required
                    value="<?= htmlspecialchars($_POST['email'] ?? '')  ?>"required>
                    
                </div>
                <div class="form">
                    <label for="password"></label>
                    <input type ="password" id="password" name="password" 
                        placeholder="PASSWORD" required>
                        <a href = "forgot-password.php">FORGOT PASSWORD?</a>
                </div>
                <div class="form">
                    <button type="submit">Login</button>
                </div>
                <p class ="register">DON'T HAVE AN ACCOUNT? <a href="register.php">REGISTER</a></p>

            </form>
        </div>
    </div>
</div>

















</body>
</html>
