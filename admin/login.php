<!DOCTYPE html>
<html lang="en">
<head>

    <title>Sehat Pro</title>
	<meta name="robots" content="noindex, nofollow">

    <link rel="stylesheet" href="assets/css/login.css">
	
	<link rel="shortcut icon" type="image/x-icon" href="assets/img/profiles/Favicon.png">
	
    <!-- jQuery CDN -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- jQuery Toast Plugin -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jquery-toast-plugin/1.3.2/jquery.toast.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-toast-plugin/1.3.2/jquery.toast.min.js"></script>
</head>
<body style="background-image: url(assets/img/images/login-bg.jpg); background-repeat: no-repeat; background-attachment: fixed; background-position: center; background-size: cover;">

<div class="container">
    <div class="form-container sign-in">
        <form action="verify.php" method="post">
		<img src="assets/img/profiles/sehatpro.png" class="login-logo">
            <h1>Login Here</h1>

            <?php if (!empty($_GET['msg_error'])) { ?>
            <script>
                $(document).ready(function() {
                    $.toast({
                        heading: 'Error',
                        text: "<?php echo $_GET['msg_error']; ?>",
                        showHideTransition: 'slide',
                        icon: 'error',
                        position: 'top-right'
                    });

                    // ✅ URL se msg_error hata do without refresh
                    const url = new URL(window.location);
                    url.searchParams.delete('msg_error');
                    window.history.replaceState({}, document.title, url);
                });
            </script>
            <?php } ?>

            <input type="text" name="txtlogin" placeholder="Username">
            <input type="password" name="txtpassword" placeholder="Password">
            <button type="submit">Sign In</button>
        </form>
    </div>
    <div class="toggle-container">
        <div class="toggle">
            <div class="toggle-panel toggle-right">
                <h1>SE Software Technologies</h1>
            </div>
        </div>
    </div>
</div>

</body>
</html>
