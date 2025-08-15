<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>{{ $title ?? 'Panel' }}</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <script src="https://cdn.tailwindcss.com"></script>
  <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body class="bg-gray-50 text-gray-900">
  <nav class="bg-white border-b shadow-sm">
    <div class="max-w-6xl mx-auto px-4 py-3 flex gap-4">
      <a href="{{ route('home') }}" class="font-semibold">Inicio</a>
      <a href="{{ route('system-rrhh.departments') }}" class="hover:underline">Departamentos</a>
      <a href="{{ route('system-rrhh.skills') }}" class="hover:underline">Habilidades</a>
      <a href="{{ route('system-rrhh.employees') }}" class="hover:underline">Empleados</a>
    </div>
  </nav>
  <main class="max-w-6xl mx-auto px-4 py-6">
    {{ $slot }}
  </main>
</body>
</html>
