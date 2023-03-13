<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, shrink-to-fit=no">
    <title>OTP CODE</title>
    <link rel="stylesheet" href="{{url('')}}/public/assets/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="{{url('')}}/public/assets/css/dh-row-text-image-right.css">
    <link rel="stylesheet" href="{{url('')}}/public/assets/css/Footer-Dark-icons.css">
    <link rel="stylesheet" href="{{url('')}}/public/assets/css/Footer-with-social-media-icons.css">
</head>

<body>
    <div class="container text-start">
        <div class="col text-center"><img style="text-align: center;width: 315px;padding: 46px;" src="{{url('')}}/public/assets/img/logo.png"></div>
    </div>
    <div class="container">
        <div class="col">
            <div class="card" style="--bs-body-bg: #ededed;--bs-light: #cecfd0;--bs-light-rgb: 206,207,208;"></div>
        </div>
    </div>
    <div class="row clearmargin clearpadding row-image-txt">
        <div class="col-xs-12 col-sm-6 col-md-6 clearmargin clearpadding col-sm-push-6"><img class="flex-sm-fill" style="width: 100%;transform: translate(1px);" src="{{url('')}}/public/assets/img/email_barnner.webp" width="360" height="414"></div>
        <div class="col-xs-12 col-sm-6 col-md-6 col-sm-pull-6" style="padding: 66px;padding-bottom: 0px;">
            <h1><strong>OTP CODE&nbsp;</strong></h1>
            <hr>
            <p>Hi User,</p>
            <p>Your one Time Password is&nbsp; &nbsp;</p>
            <p style="color: var(--bs-white);font-size: 29px;"><strong>{{ $data1["sms_code"] }}</strong></p>
            <div style="text-align: center;"></div>
            <footer class="text-center bg-dark">
                <div class="container text-white py-4 py-lg-5" style="padding: 24px 12px;padding-bottom: 0px;margin-bottom: 0px;padding-top: 0px;padding-left: 0px;padding-right: 0px;margin-left: -4px;--bs-body-bg: #000000;background: #2a2a2a;text-align: left;">
                    
                    <p class="text-muted mb-0" style="--bs-body-font-weight: normal;height: 0px;width: 566px;text-align: left;font-size: 11px;">Copyright © ENKWAVE</p>
                </div>
            </footer>
            <div style="text-align: center;"></div>
            <div style="text-align: center;"></div>
        </div>
    </div>
    <script src="{{url('')}}/public/assets/bootstrap/js/bootstrap.min.js"></script>
</body>

</html>
