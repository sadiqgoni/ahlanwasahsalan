<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <title>{{ config('app.name', 'Ahlan wa Sahlan') }} — Menu</title>
    <link rel="icon" href="data:,">
    @livewireStyles
</head>
<body style="margin:0;">
    {{ $slot }}
    @livewireScripts
</body>
</html>
