<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>QR label · {{ $title }}</title>
    <style>
        body { font-family: Arial, sans-serif; color: #17231d; margin: 24px; }
        .label { width: 340px; border: 1px solid #9aaba0; border-radius: 12px; padding: 20px; text-align: center; }
        .qr { width: 240px; height: 240px; margin: 14px auto; }
        .qr svg { display: block; width: 100%; height: 100%; shape-rendering: crispEdges; }
        h1 { font-size: 19px; margin: 0 0 6px; }
        p { color: #52645a; margin: 6px 0; overflow-wrap: anywhere; }
        .actions { margin-top: 16px; }
        button, a { font: inherit; margin: 4px; padding: 8px 12px; }
        @media print { body { margin: 0; } .label { border: 0; } .actions { display: none; } }
    </style>
</head>
<body>
    <main class="label">
        <h1>{{ $title }}</h1>
        <p>{{ $subtitle }}</p>
        <div class="qr">{!! $qrSvg !!}</div>
        <p>{{ $code }}</p>
        <div class="actions">
            <button type="button" onclick="window.print()">Print label</button>
            <a href="{{ url()->previous() }}">Back</a>
        </div>
    </main>
</body>
</html>
