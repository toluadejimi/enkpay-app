<!DOCTYPE html>
<html lang="en">

<head>
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>RESET PIN</title>
    <!-- plugins:css -->
    <link rel="stylesheet" href="../../{{url('')}}/public/assets/vendors/mdi/css/materialdesignicons.min.css">
    <link rel="stylesheet" href="../../{{url('')}}/public/assets/vendors/css/vendor.bundle.base.css">
    <!-- endinject -->
    <!-- Plugin css for this page -->
    <!-- End plugin css for this page -->
    <!-- inject:css -->
    <!-- endinject -->
    <!-- Layout styles -->
    <link rel="stylesheet" href="../../{{url('')}}/public/assets/css/style.css">
    <!-- End layout styles -->
    <link rel="shortcut icon" href="../../{{url('')}}/public/assets/images/favicon.ico" />
</head>

<body>
    <div class="container-scroller">
        <div class="container-fluid page-body-wrapper full-page-wrapper">
            <div class="content-wrapper d-flex align-items-center auth">
                <div class="row flex-grow">
                    <div class="col-lg-4 mx-auto">
                        <div class="auth-form-light text-left p-5">
                            <div class="brand-logo">
                                <img src="../../{{url('')}}/public/assets/images/logo.svg">
                            </div>

                            @if ($errors->any())
                            <div class="alert alert-danger">
                                <ul>
                                    @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                            @endif
                            @if (session()->has('message'))
                            <div class="alert alert-success">
                                {{ session()->get('message') }}
                            </div>
                            @endif
                            @if (session()->has('error'))
                            <div class="alert alert-danger">
                                {{ session()->get('error') }}
                            </div>
                            @endif


                            <h4>PIN RESET</h4>
                            <h6 class="font-weight-light">Update your pin</h6>
                            <form method="POST" action="/sign-in" class="pt-3">
                                @csrf

                                {{-- <div class="form-group">
                                    <input type="password" class="form-control form-control-lg" min="0" max="4"
                                        name="pin" id="pin" placeholder="Enter 4 Digit Pin" value="">
                                </div>

                                <div class="form-group">
                                    <input type="password" class="form-control form-control-lg" min="0" max="4"
                                        name="pin" id="pin_confirm" placeholder="Enter 4 Digit Pin" value="">
                                </div> --}}

                                <input name="email" hidden value="{{$email}}">

                                <div class="mb-3 form-password-toggle">
                                    <label class="form-label" for="password">Enter 4 Digit Pin</label>
                                    <div class="input-group input-group-merge">
                                        <span id="basic-icon-default-fullname2" class="input-group-text"><i
                                                class="bx bxs-lock-alt"></i></span>
                                        <input type="password" min="0" max="4" id="password" autofocus required class="form-control"
                                            name="password"
                                            placeholder="&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;"
                                            aria-describedby="password" />
                                        <span class="input-group-text cursor-pointer"><i class="bx bx-hide"></i></span>
                                    </div>
                                </div>

                                <div class="mb-3 form-password-toggle">
                                    <label class="form-label" for="password">Confirm Pin</label>
                                    <div class="input-group input-group-merge">
                                        <span id="basic-icon-default-fullname2" class="input-group-text"><i
                                                class="bx bxs-lock-alt"></i></span>
                                        <input type="password" id="password" min="0" max="4" autofocus required class="form-control"
                                            name="password_confirmation"
                                            placeholder="&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;"
                                            aria-describedby="password" />
                                        <span class="input-group-text cursor-pointer"><i class="bx bx-hide"></i></span>
                                    </div>
                                </div>

                                <div class="mt-3">
                                    <x-primary-button a
                                        class="btn btn-block btn-gradient-primary btn-lg font-weight-medium auth-form-btn">
                                        </a>
                                        {{ __('RESET PIN') }}
                                    </x-primary-button>

                                </div>






                                <!-- <div class="text-center mt-4 font-weight-light"> Don't have an account? <a href="{{ route('register') }}" class="text-primary">Create</a> -->
                        </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <!-- content-wrapper ends -->
    </div>
    <!-- page-body-wrapper ends -->
    </div>
    <!-- container-scroller -->
    <!-- plugins:js -->
    <script src="../../{{url('')}}/public/assets/vendors/js/vendor.bundle.base.js"></script>
    <!-- endinject -->
    <!-- Plugin js for this page -->
    <!-- End plugin js for this page -->
    <!-- inject:js -->
    <script src="../../{{url('')}}/public/assets/js/off-canvas.js"></script>
    <script src="../../{{url('')}}/public/assets/js/hoverable-collapse.js"></script>
    <script src="../../{{url('')}}/public/assets/js/misc.js"></script>
    <!-- endinject -->
</body>

</html>
