<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Ticket Retiro #RET-{{ str_pad($w->id, 5, '0', STR_PAD_LEFT) }}</title>
    <style>
        @page {
            margin: 3mm 4mm 3mm 4mm;
        }
        body {
            font-family: 'Courier New', Courier, monospace;
            font-size: 8pt;
            color: #000;
            line-height: 1.35;
            margin: 0;
            padding: 0;
        }
        .text-center { text-align: center; }
        .text-right  { text-align: right; }
        .text-left   { text-align: left; }
        .font-bold   { font-weight: bold; }
        
        .header-box  { text-align: center; margin-bottom: 4px; }
        .title       { font-size: 12pt; font-weight: bold; text-transform: uppercase; letter-spacing: 1px; }
        .slogan      { font-size: 6.5pt; text-transform: uppercase; color: #333; margin-top: 1px; }
        .branch      { font-size: 8pt; font-weight: bold; margin-top: 2px; }
        .doc-title   { font-size: 8pt; font-weight: bold; text-transform: uppercase; margin-top: 3px; }
        .doc-number  { font-size: 9pt; font-weight: bold; letter-spacing: 0.5px; }

        .divider     { border: none; border-top: 1px dashed #000; margin: 5px 0; }
        .double-line { border: none; border-top: 2px solid #000; margin: 6px 0; }
        
        table        { width: 100%; border-collapse: collapse; }
        td           { padding: 2px 0; vertical-align: top; font-size: 8pt; }
        
        .reason-box  { margin: 4px 0; }
        .reason-lbl  { font-size: 7.5pt; text-transform: uppercase; font-weight: bold; color: #222; }
        .reason-val  { font-size: 9.5pt; font-weight: bold; padding: 4px 0 2px 0; word-break: break-all; }
        
        .amount-table { width: 100%; margin: 3px 0; }
        .amount-lbl   { font-size: 9.5pt; font-weight: bold; }
        .amount-val   { font-size: 11pt; font-weight: bold; }
        
        .disclaimer  { font-size: 6.5pt; text-align: justify; line-height: 1.25; color: #333; margin: 6px 0; }
        
        .signatures  { width: 100%; margin-top: 28px; }
        .sig-box     { width: 48%; text-align: center; vertical-align: top; font-size: 7pt; }
        .sig-line    { border-top: 1px solid #000; margin-bottom: 3px; }
        .sig-role    { font-weight: bold; text-transform: uppercase; font-size: 7pt; }
        .sig-name    { font-size: 6.5pt; color: #333; margin-top: 2px; }
        .spacer      { width: 4%; }
        
        .footer      { text-align: center; font-size: 6.5pt; color: #444; margin-top: 14px; line-height: 1.3; }
    </style>
</head>
<body>
    <div class="header-box">
        <div class="title">FARMACIA SOLEI</div>
        <div class="slogan">Tu Salud y Bienestar en Manos Expertas</div>
        @php
            $branch = $w->session?->branch ?? $w->cashSession?->branch;
        @endphp
        @if($branch)
        <div class="branch">{{ $branch }}</div>
        @endif
        <div class="divider"></div>
        <div class="doc-title">COMPROBANTE DE RETIRO DE CAJA</div>
        <div class="doc-number">N° RET-{{ str_pad($w->id, 5, '0', STR_PAD_LEFT) }}</div>
    </div>

    <div class="divider"></div>

    <table>
        <tr>
            <td class="text-left font-bold" style="width: 44%;">Sesión de Caja:</td>
            <td class="text-right font-bold">#{{ str_pad($w->session_id, 6, '0', STR_PAD_LEFT) }}</td>
        </tr>
        <tr>
            <td class="text-left" style="width: 44%;">Fecha y Hora:</td>
            <td class="text-right font-bold">
                {{ $w->created_at?->timezone('America/Managua')->format('d/m/Y, h:i a') }}
            </td>
        </tr>
        <tr>
            <td class="text-left" style="width: 44%;">Cajero Responsable:</td>
            <td class="text-right font-bold">{{ $w->withdrawer?->name ?? $w->withdrawer?->username ?? '—' }}</td>
        </tr>
        <tr>
            <td class="text-left" style="width: 44%;">Usuario / Cargo:</td>
            <td class="text-right">{{ $w->withdrawer?->username }} ({{ ucfirst($w->withdrawer?->role ?? 'Cajero') }})</td>
        </tr>
        <tr>
            <td class="text-left" style="width: 44%;">Tipo de Operación:</td>
            <td class="text-right">EGRESO DE EFECTIVO</td>
        </tr>
    </table>

    <div class="divider"></div>

    <div class="reason-box">
        <div class="reason-lbl">CONCEPTO / DESTINO DEL RETIRO:</div>
        <div class="reason-val">{{ $w->reason }}</div>
    </div>

    @php
        $denominations = is_array($w->denominations) ? $w->denominations : json_decode($w->denominations ?? '[]', true);
        $denomValues = [1000, 500, 200, 100, 50, 20, 10, 5, 1];
        $hasDenoms = false;
        if (is_array($denominations)) {
            foreach ($denomValues as $d) {
                if (!empty($denominations[$d]) && (int)$denominations[$d] > 0) {
                    $hasDenoms = true;
                    break;
                }
            }
        }
    @endphp

    @if($hasDenoms)
    <div class="divider"></div>
    <div style="font-size: 7.5pt; font-weight: bold; text-align: center; margin: 3px 0;">--- DESGLOSE DE BILLETES ---</div>
    <table>
        @foreach($denomValues as $d)
            @if(!empty($denominations[$d]) && (int)$denominations[$d] > 0)
            <tr>
                <td class="text-left">C$ {{ number_format($d) }} x {{ $denominations[$d] }}</td>
                <td class="text-right font-bold">C$ {{ number_format($d * (int)$denominations[$d], 2) }}</td>
            </tr>
            @endif
        @endforeach
    </table>
    @endif

    <div class="double-line"></div>

    <table class="amount-table">
        <tr>
            <td class="text-left amount-lbl">MONTO RETIRADO:</td>
            <td class="text-right amount-val">C$ {{ number_format($w->total_amount, 2) }}</td>
        </tr>
    </table>

    <div class="double-line"></div>

    <div class="disclaimer">
        * Certifico la entrega física y descargo en el sistema del monto detallado para los fines institucionales estipulados en este comprobante.
    </div>

    <table class="signatures">
        <tr>
            <td class="sig-box">
                <div class="sig-line"></div>
                <div class="sig-role">Firma Cajero</div>
                <div class="sig-name">{{ $w->withdrawer?->username }}</div>
            </td>
            <td class="spacer"></td>
            <td class="sig-box">
                <div class="sig-line"></div>
                <div class="sig-role">Firma Autorizado</div>
                <div class="sig-name">Recibido Conforme</div>
            </td>
        </tr>
    </table>

    <div class="footer">
        <div>#RET-{{ str_pad($w->id, 5, '0', STR_PAD_LEFT) }} &bull; Impreso: {{ now()->timezone('America/Managua')->format('d/m/Y, h:i a') }}</div>
        <div style="font-size: 6pt; color: #666; margin-top: 2px;">Comprobante de Control y Arqueo de Caja</div>
    </div>
</body>
</html>
