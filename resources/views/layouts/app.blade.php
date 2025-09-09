<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <title>@yield('title','App')</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
</head>
<body style="width:80%; margin:20px auto; font-family:Arial, sans-serif;">
@yield('content')
</body>
</html>
