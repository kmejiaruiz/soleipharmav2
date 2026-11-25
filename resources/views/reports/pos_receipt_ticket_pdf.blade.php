<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Ticket Venta #FAC-{{ str_pad($order->id, 6, '0', STR_PAD_LEFT) }}</title>
    <style>
        @page {
            margin: 2mm 3mm 2mm 3mm;
        }
        body {
            font-family: 'Courier New', Courier, monospace;
            font-size: 8pt;
            color: #000;
            line-height: 1.25;
            margin: 0;
            padding: 0;
        }
        .text-center { text-align: center; }
        .text-right  { text-align: right; }
        .text-left   { text-align: left; }
        .font-bold   { font-weight: bold; }
        .title       { font-size: 11pt; font-weight: bold; text-transform: uppercase; letter-spacing: 1px; }
        .subtitle    { font-size: 7pt; text-transform: uppercase; margin-top: 1px; }
        .divider     { border: none; border-top: 1px dashed #000; margin: 4px 0; }
        .double-line { border: none; border-top: 2px solid #000; margin: 5px 0; }
        table        { width: 100%; border-collapse: collapse; }
        td, th       { padding: 1.5px 0; vertical-align: top; font-size: 7.5pt; }
        th           { font-weight: bold; border-bottom: 1px dashed #000; }
        .highlight   { font-size: 9.5pt; font-weight: bold; }
    </style>
</head>
<body>
    <div class="text-center">
        <div class="title">FARMACIA SOLEI</div>
        <div class="subtitle">COMPROBANTE DE VENTA</div>
    </div>

    <div class="divider"></div>

    <table>
        <tr>
            <td class="text-left font-bold" style="width: 38%;">Factura #:</td>
            <td class="text-right font-bold">#FAC-{{ str_pad($order->id, 6, '0', STR_PAD_LEFT) }}</td>
        </tr>
        <tr>
            <td class="text-left" style="width: 38%;">Fecha:</td>
            <td class="text-right">{{ $order->created_at?->timezone('America/Managua')->format('d/m/Y, h:i a') }}</td>
        </tr>
        <tr>
            <td class="text-left" style="width: 38%;">Cajero:</td>
            <td class="text-right">{{ $order->user?->username ?? '—' }}</td>
        </tr>
        @if($order->branch)
        <tr>
            <td class="text-left" style="width: 38%;">Sucursal:</td>
            <td class="text-right">{{ $order->branch }}</td>
        </tr>
        @endif
        <tr>
            <td class="text-left" style="width: 38%;">Cliente:</td>
            <td class="text-right font-bold">{{ $order->client_name ?: 'Consumidor Final' }}</td>
        </tr>
    </table>

    <div class="divider"></div>

    <table>
        <thead>
            <tr>
                <th class="text-left" style="width: 50%;">Descrip.</th>
                <th class="text-center" style="width: 15%;">Cant</th>
                <th class="text-right" style="width: 17%;">P.U.</th>
                <th class="text-right" style="width: 18%;">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($order->items as $item)
            <tr>
                <td class="text-left" style="word-break: break-all;">
                    {{ $item->product?->name ?? 'Producto' }}
                </td>
                <td class="text-center">{{ $item->quantity }}</td>
                <td class="text-right">{{ number_format($item->unit_price, 2) }}</td>
                <td class="text-right font-bold">{{ number_format($item->quantity * $item->unit_price, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="divider"></div>

    <table>
        <tr>
            <td class="text-left">Subtotal:</td>
            <td class="text-right">C$ {{ number_format($order->subtotal ?? $order->total, 2) }}</td>
        </tr>
        @if(($order->discount ?? 0) > 0)
        <tr>
            <td class="text-left">Descuento:</td>
            <td class="text-right">-C$ {{ number_format($order->discount, 2) }}</td>
        </tr>
        @endif
        <tr class="highlight">
            <td class="text-left">TOTAL A PAGAR:</td>
            <td class="text-right">C$ {{ number_format($order->total, 2) }}</td>
        </tr>
        <tr>
            <td class="text-left">Método de Pago:</td>
            <td class="text-right font-bold" style="text-transform: capitalize;">{{ $order->payment_method ?? 'Efectivo' }}</td>
        </tr>
    </table>

    <div class="double-line"></div>

    <div class="text-center" style="font-size: 7.5pt; margin-top: 6px;">
        <div>¡Gracias por su compra!</div>
        <div style="font-size: 6.5pt; color: #555; margin-top: 3px;">Revise su producto al recibirlo.</div>
    </div>
</body>
</html>
