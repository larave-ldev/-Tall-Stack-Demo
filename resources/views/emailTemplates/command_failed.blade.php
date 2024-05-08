<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $subject }} - {{ $command_name }}</title>
</head>
<body>
<div>
    <h2>{{ $subject }} - {{ $command_name }}</h2>
    <p>Please look into with cron schedule and general logs table as well as laravel log file</p>
    @if ($error_message)
        <p>Error Details:</p>
        <pre>{{ $error_message }}</pre>
    @endif

</div>
</body>
</html>
