
<!DOCTYPE html>
<html lang="fa" dir="rtl">

<head>

    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

    <title>داشبورد مدیریت</title>

    <!-- Bootstrap core CSS-->
    <link href="/css/appFront.css" rel="stylesheet">

</head>

<body id="page-top">

@include('Dashboard.Layouts.header')

<div id="wrapper">

    @include('Dashboard.Layouts.sidebar')

    <div id="content-wrapper">

        <div class="container-fluid">

            @yield('content')

        </div>
        <!-- /.container-fluid -->

        <!-- Sticky Footer -->
        <footer class="sticky-footer">
            <div class="container my-auto">
                <div class="copyright text-center my-auto">
                    <span>تمامی حقوق محفوظ است . طراحی شده توسط <a href="http://avaysi.com">ابوالفضل ویسی</a></span>
                </div>
            </div>
        </footer>

    </div>
    <!-- /.content-wrapper -->

</div>
<!-- /#wrapper -->

@include('Dashboard.Layouts.footer')
<!-- Bootstrap core JavaScript-->
<script src="/js/app.js"></script>

</body>

</html>
