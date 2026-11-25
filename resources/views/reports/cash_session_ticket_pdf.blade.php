<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Ticket Cierre #CS-{{ str_pad($session->id, 4, '0', STR_PAD_LEFT) }}</title>
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
        .section     { font-size: 7.5pt; font-weight: bold; text-align: center; margin: 4px 0 2px 0; }
        .divider     { border: none; border-top: 1px dashed #000; margin: 4px 0; }
        .double-line { border: none; border-top: 2px solid #000; margin: 5px 0; }
        table        { width: 100%; border-collapse: collapse; }
        td           { padding: 1.5px 0; vertical-align: top; font-size: 7.5pt; }
        .highlight   { font-size: 9pt; font-weight: bold; }
        .signatures  { width: 100%; margin-top: 24px; }
        .sig-box     { width: 48%; text-align: center; font-size: 7pt; border-top: 1px solid #000; padding-top: 3px; }
        .spacer      { width: 4%; }
    </style>
</head>
<body>
    <div class="text-center">
        <div class="title">FARMACIA SOLEI</div>
        <div class="subtitle">REPORTE DE CIERRE Y ARQUEO DE CAJA</div>
    </div>

    <div class="divider"></div>

    <table>
        <tr>
            <td class="text-left font-bold" style="width: 40%;">Sesion #:</td>
            <td class="text-right font-bold">#{{ str_pad($session->id, 6, '0', STR_PAD_LEFT) }}</td>
        </tr>
        @if($session->branch)
        <tr>
            <td class="text-left" style="width: 40%;">Sucursal:</td>
            <td class="text-right">{{ $session->branch }}</td>
        </tr>
        @endif
        <tr>
            <td class="text-left" style="width: 40%;">Apertura:</td>
            <td class="text-right">{{ $session->opened_at?->timezone('America/Managua')->format('d/m/Y, h:i a') }}</td>
        </tr>
        <tr>
            <td class="text-left" style="width: 40%;">Cierre:</td>
            <td class="text-right">{{ $session->closed_at ? $session->closed_at->timezone('America/Managua')->format('d/m/Y, h:i a') : now()->timezone('America/Managua')->format('d/m/Y, h:i a') }}</td>
        </tr>
        <tr>
            <td class="text-left" style="width: 40%;">Abierto por:</td>
            <td class="text-right font-bold">{{ $session->opener?->username ?? '—' }}</td>
        </tr>
        <tr>
            <td class="text-left" style="width: 40%;">Cerrado por:</td>
            <td class="text-right font-bold">{{ $session->closer?->username ?? '—' }}</td>
        </tr>
    </table>

    <div class="divider"></div>
    <div class="section">--- RESUMEN DEL TURNO ---</div>
    <div class="divider"></div>

    <table>
        <tr>
            <td class="text-left">Monto inicial (+):</td>
            <td class="text-right">C$ {{ number_format($session->opening_amount, 2) }}</td>
        </tr>
        @php
            $cashSales = (float) \App\Models\Order::where('cash_session_id', $session->id)
                ->where('status', 'completado')
                ->where('payment_method', 'efectivo')
                ->sum('total');
            $totalWithdrawals = (float) $session->withdrawals->sum('total_amount');
            $expected = $session->closingCount?->expected_amount ?? ($session->opening_amount + $cashSales - $totalWithdrawals);
            $counted = $session->closingCount?->counted_amount ?? $expected;
            $diff = $session->closingCount?->difference ?? ($counted - $expected);
        @endphp
        <tr>
            <td class="text-left">Ventas en efectivo (+):</td>
            <td class="text-right">C$ {{ number_format($cashSales, 2) }}</td>
        </tr>
        <tr>
            <td class="text-left">Retiros (-):</td>
            <td class="text-right">-C$ {{ number_format($totalWithdrawals, 2) }}</td>
        </tr>
        <tr class="highlight">
            <td class="text-left">Esperado gaveta:</td>
            <td class="text-right">C$ {{ number_format($expected, 2) }}</td>
        </tr>
    </table>

    <div class="divider"></div>
    <div class="section">--- ARQUEO FÍSICO ---</div>
    <div class="divider"></div>

    <table>
        <tr>
            <td class="text-left font-bold">Contado en arqueo:</td>
            <td class="text-right font-bold">C$ {{ number_format($counted, 2) }}</td>
        </tr>
        <tr class="highlight">
            <td class="text-left">Diferencia:</td>
            <td class="text-right">{{ ($diff < 0 ? '-' : ($diff > 0 ? '+' : '')) }}C$ {{ number_format(abs($diff), 2) }}</td>
        </tr>
    </table>

    @php
        $denominations = is_array($session->closingCount?->denominations) 
            ? $session->closingCount->denominations 
            : json_decode($session->closingCount?->denominations ?? '[]', true);
        $denomValues = [1000, 500, 200, 100, 50, 20, 10, 5, 1];
    @endphp

    @if(!empty($denominations) && is_array($denominations))
        <div class="divider"></div>
        <div class="section">--- DESGLOSE BILLETES ---</div>
        <div class="divider"></div>
        <table>
            @foreach($denomValues as $d)
                @if(!empty($denominations[$d]) && (int)$denominations[$d] > 0)
                <tr>
                    <td class="text-left">C$ {{ number_format($d) }} x {{ $denominations[$d] }}</td>
                    <td class="text-right">C$ {{ number_format($d * (int)$denominations[$d], 2) }}</td>
                </tr>
                @endif
            @endforeach
        </table>
    @endif

    @if($session->withdrawals && $session->withdrawals->count() > 0)
        <div class="divider"></div>
        <div class="section">--- RETIROS REGISTRADOS ({{ $session->withdrawals->count() }}) ---</div>
        <div class="divider"></div>
        <table>
            @foreach($session->withdrawals as $w)
            <tr>
                <td class="text-left" style="max-width: 130px; word-break: break-all;">
                    #{{ str_pad($w->id, 4, '0', STR_PAD_LEFT) }} {{ $w->reason }}
                </td>
                <td class="text-right font-bold">-C$ {{ number_format($w->total_amount, 2) }}</td>
            </tr>
            @endforeach
        </table>
    @endif

    <div class="double-line"></div>

    <table class="signatures">
        <tr>
            <td class="sig-box">Cajero Entregante</td>
            <td class="spacer"></td>
            <td class="sig-box">Admin. Receptor</td>
        </tr>
    </table>

    <div style="text-align: center; font-size: 6.5pt; color: #555; margin-top: 10px;">
        Generado: {{ now()->timezone('America/Managua')->format('d/m/Y, h:i a') }}
    </div>
</body>
</html>
