<?php
// app/Views/auth/login.php
namespace App\Views\Auth;

use App\Config;
use App\Core\Session;

// Login işlemi sonrası flash mesajları gösterilebilir
$error_message = Session::getFlash('error');
$success_message = Session::getFlash('success');

// CSRF token oluştur
$csrf_token = Session::generateCsrfToken();
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0">
    <meta name="description" content="<?= Config::APP_NAME ?> Giriş Ekranı">
    <meta name="keywords" content="ödeme sistemi, login, admin panel, giriş">
    <meta name="author" content="<?= Config::APP_NAME ?>">
    <meta name="robots" content="noindex, nofollow">
    <title>Giriş Yap - <?= Config::APP_NAME ?></title>

    <link rel="shortcut icon" type="image/x-icon" href="<?= Config::BASE_URL ?>/assets/img/favicon.png">

    <link rel="stylesheet" href="<?= Config::BASE_URL ?>/assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="<?= Config::BASE_URL ?>/assets/plugins/fontawesome/css/fontawesome.min.css">
    <link rel="stylesheet" href="<?= Config::BASE_URL ?>/assets/plugins/fontawesome/css/all.min.css">
    <link rel="stylesheet" href="<?= Config::BASE_URL ?>/assets/css/style.css"> <link rel="stylesheet" href="<?= Config::BASE_URL ?>/assets/css/feather.css"> <link rel="stylesheet" href="<?= Config::BASE_URL ?>/assets/plugins/tabler-icons/tabler-icons.css"> <style>
        /* Tasarımınızdaki renkler ve boşluklar için hızlı düzenlemeler */
        body {
            background-color: #f7f7f7; /* Hafif gri arka plan */
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
            font-family: Arial, sans-serif; /* Daha genel bir font */
        }
        .login-container {
            display: flex;
            background-color: #ffffff;
            border-radius: 8px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            width: 100%;
            max-width: 900px; /* Genişliği ayarlayabiliriz */
        }
        .login-left {
            flex: 1;
            padding: 40px;
            display: flex;
            justify-content: center;
            align-items: center;
            background-color: #ffffff; /* Sol tarafın arka planı */
        }
        .login-left img {
            max-width: 100%;
            height: auto;
        }
        .login-right {
            flex: 1;
            padding: 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            background-color: #ffffff; /* Sağ tarafın arka planı */
        }
        .login-logo {
            text-align: center;
            margin-bottom: 20px;
        }
        .login-logo img {
            width: 60px; /* Logo boyutu */
            height: auto;
        }
        .login-title {
            text-align: center;
            font-size: 24px;
            margin-bottom: 5px;
            color: #333;
        }
        .login-subtitle {
            text-align: center;
            font-size: 14px;
            color: #777;
            margin-bottom: 30px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            font-size: 14px;
            color: #555;
            margin-bottom: 8px;
            display: block;
        }
        .form-control-with-icon {
            position: relative;
        }
        .form-control-with-icon input {
            padding-right: 40px; /* İkon için boşluk */
        }
        .form-control-with-icon .input-icon {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #aaa;
            pointer-events: none; /* İkon tıklanamaz olsun */
        }
        .form-control {
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 10px 15px;
            width: 100%;
            box-sizing: border-box;
            font-size: 16px;
        }
        .btn-login {
            background-color: #ffc107; /* Sarı buton rengi */
            color: #fff;
            padding: 12px 25px;
            border-radius: 5px;
            border: none;
            width: 100%;
            font-size: 16px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }
        .btn-login:hover {
            background-color: #e0a800; /* Koyu sarı */
        }
        .forgot-password {
            text-align: right;
            font-size: 13px;
            margin-bottom: 15px;
        }
        .forgot-password a {
            color: #007bff;
            text-decoration: none;
        }
        .create-account {
            text-align: center;
            font-size: 14px;
            color: #777;
            margin-top: 20px;
        }
        .create-account a {
            color: #007bff;
            text-decoration: none;
        }
        /* Flexbox ayarlamaları için */
        .account-page {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }
        .account-content {
            width: 100%;
        }
        .account-box {
            display: flex;
            justify-content: center;
            width: 100%;
        }
        .account-wrapper {
            flex: 1; /* Sağ tarafı kaplasın */
            padding: 30px;
        }
        /* Media query for smaller screens */
        @media (max-width: 768px) {
            .login-container {
                flex-direction: column;
                max-width: 400px;
            }
            .login-left {
                display: none; /* Sol taraftaki büyük logoyu gizle */
            }
        }
    </style>
</head>
<body class="account-page">
    <div class="main-wrapper">
        <div class="account-content">
            <div class="container">
                <?php if ($error_message): ?>
                    <div class="alert alert-danger text-center"><?= htmlspecialchars($error_message) ?></div>
                <?php endif; ?>
                <?php if ($success_message): ?>
                    <div class="alert alert-success text-center"><?= htmlspecialchars($success_message) ?></div>
                <?php endif; ?>

                <div class="account-box">
                    <div class="login-container">
                        <div class="login-left">
                            <img src="<?= Config::BASE_URL ?>/assets/img/login_logo_large.png" alt="Payment System Logo">
                            </div>
                        <div class="login-right">
                            <div class="account-wrapper">
                                <div class="login-logo">
                                    <img src="<?= Config::BASE_URL ?>/assets/img/logo.svg" alt="Logo">
                                </div>
                                <h3 class="login-title">Mağaza Paneli</h3>
                                <p class="login-subtitle">Kullanıcı adı ve şifrenizi giriniz</p>

                                <form action="<?= Config::BASE_URL ?>/login" method="POST">
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                                    
                                    <div class="form-group">
                                        <label>Kullanıcı Adı</label>
                                        <div class="form-control-with-icon">
                                            <input class="form-control" type="text" name="username" required>
                                            <span class="input-icon"><i class="ti ti-mail"></i></span>
                                        </div>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label>Şifre</label>
                                        <div class="form-control-with-icon">
                                            <input class="form-control" type="password" name="password" required>
                                            <span class="input-icon"><i class="ti ti-lock"></i></span>
                                        </div>
                                    </div>
                                    
                                    <div class="forgot-password">
                                        <a href="#">Şifremi Unuttum?</a>
                                    </div>
                                    
                                    <div class="form-group text-center">
                                        <button class="btn btn-login" type="submit">Giriş Yap</button>
                                    </div>
                                    
                                    <div class="create-account">
                                        Hesabınız yok mu? <a href="#">Kaydol</a>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="<?= Config::BASE_URL ?>/assets/js/jquery-3.7.1.min.js"></script>
    <script src="<?= Config::BASE_URL ?>/assets/js/bootstrap.bundle.min.js"></script>
    <script src="<?= Config::BASE_URL ?>/assets/js/script.js"></script>
    </body>
</html>