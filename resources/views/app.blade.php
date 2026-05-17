<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DentalApp</title>
    {{-- Vite injecte automatiquement le CSS Tailwind et le JS compilé --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    {{-- Vue monte toute l'application ici --}}
    <div id="app"></div>
</body>
</html>