<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<title>Recibo #{{ sprintf('%06d', $order->id) }}</title>
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body {
        font-family: 'Courier New', Courier, monospace;
        font-size: 12px;
        background: #fff;
        color: #111;
        max-width: 300px;
        margin: 0 auto;
        padding: 12px;
    }
    .center { text-align: center; }
    .logo   { width: 60px; height: 60px; border-radius: 50%; margin: 0 auto 6px; display: block; object-fit: cover; }
    h1      { font-size: 14px; font-weight: bold; }
    .sep    { border-top: 1px dashed #555; margin: 6px 0; }
    .row    { display: flex; justify-content: space-between; margin-bottom: 2px; }
    .label  { color: #555; }
    .items  { margin: 6px 0; }
    .item   { margin-bottom: 4px; }
    .item-name  { font-weight: bold; }
    .item-detail { display: flex; justify-content: space-between; color: #333; }
    .total-row  { display: flex; justify-content: space-between; font-weight: bold; font-size: 14px; }
    .footer { text-align: center; margin-top: 10px; font-size: 10px; color: #777; }
    @media print {
        body { padding: 0; max-width: 100%; }
        button { display: none; }
    }
</style>
</head>
<body>
<div>
    <div class="center" style="margin-bottom:8px">
        <img src="{{ asset('assets/images/logo.jpg') }}" class="logo" alt="Logo" onerror="this.style.display='none'" />
        <h1>Farmacia Solei</h1>
        <p>{{ $order->branch ?? ($order->user->branch ?? 'Sucursal Principal') }}</p>
    </div>

    <div class="sep"></div>

    <div class="row"><span class="label">Recibo N°</span><span>#{{ sprintf('%06d', $order->id) }}</span></div>
    <div class="row"><span class="label">Fecha</span><span>{{ $order->created_at ? $order->created_at->format('d/m/Y h:i A') : date('d/m/Y h:i A') }}</span></div>
    <div class="row"><span class="label">Cajero</span><span>{{ $order->user->username ?? '—' }}</span></div>
    <div class="row"><span class="label">Cliente</span><span>Consumidor Final</span></div>
    <div class="row">
        <span class="label">Método</span>
        <span>
            @switch($order->payment_method)
                @case('efectivo') Efectivo @break
                @case('tarjeta') Tarjeta @break
                @case('transferencia') Transferencia @break
                @default {{ $order->payment_method }}
            @endswitch
        </span>
    </div>

    <div class="sep"></div>

    <div class="items">
        @foreach($order->items as $item)
            <div class="item">
                <div class="item-name">{{ $item->product->name ?? 'Producto' }}</div>
                <div class="item-detail">
                    <span>{{ $item->quantity }} x C$ {{ number_format($item->price ?? 0, 2) }}</span>
                    <span>C$ {{ number_format(($item->quantity * ($item->price ?? 0)), 2) }}</span>
                </div>
            </div>
        @endforeach
    </div>

    <div class="sep"></div>

    <div class="total-row">
        <span>TOTAL</span>
        <span>C$ {{ number_format($order->total, 2) }}</span>
    </div>

    @if($order->payment_method === 'efectivo')
        <div class="row" style="margin-top:4px">
            <span class="label">Recibido</span>
            <span>C$ {{ number_format($order->amount_paid ?? $order->total, 2) }}</span>
        </div>
        <div class="row">
            <span class="label">Cambio</span>
            <span>C$ {{ number_format($order->change_given ?? 0, 2) }}</span>
        </div>
    @endif

    <div class="sep"></div>

    <div class="footer">
        <p>¡Gracias por su compra!</p>
        <p style="margin-top:4px">Este documento es su comprobante de pago.</p>
    </div>

    <div style="text-align:center;margin-top:16px">
        <button onclick="window.print()" style="padding:6px 20px;background:#333;color:#fff;border:none;border-radius:4px;cursor:pointer;font-size:12px">
            🖨️ Imprimir
        </button>
    </div>
</div>

<script>
    window.addEventListener('load', function() {
        setTimeout(function() {
            window.print();
        }, 300);
    });
</script>
</body>
</html>
