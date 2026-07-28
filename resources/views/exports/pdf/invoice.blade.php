<!DOCTYPE html>
<html lang="es">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Factura {{ $invoice->invoice_number }}</title>
    <style>
        @page { margin: 28px 32px; }
        * { box-sizing: border-box; }
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
            color: #333333;
            margin: 0;
            padding: 0;
            line-height: 1.4;
        }
        .header {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
        }
        .header td { vertical-align: middle; }
        .header-logo { width: 120px; }
        .header-logo img {
            max-width: 110px;
            max-height: 48px;
        }
        .header-title {
            text-align: right;
        }
        h1 {
            font-size: 22px;
            color: #2c3e50;
            margin: 0;
            letter-spacing: 0.5px;
        }
        h1.title-pending {
            color: #c62828;
        }
        h1.title-paid {
            color: #2e7d32;
        }
        .status-badge {
            display: inline-block;
            margin-top: 6px;
            padding: 3px 10px;
            border-radius: 3px;
            font-size: 11px;
            font-weight: bold;
            letter-spacing: 0.6px;
            text-transform: uppercase;
        }
        .status-badge--pending {
            background: #ffebee;
            color: #c62828;
            border: 1px solid #ef9a9a;
        }
        .status-badge--paid {
            background: #e8f5e9;
            color: #1b5e20;
            border: 1px solid #a5d6a7;
        }
        .meta { margin-bottom: 18px; }
        .meta p { margin: 2px 0; font-size: 12px; }
        .meta strong { color: #333; }
        .meta .invoice-number { color: #c62828; font-weight: bold; font-size: 13px; }
        .meta .estado-pending { color: #c62828; font-weight: bold; }
        .meta .estado-paid { color: #1b5e20; font-weight: bold; }
        .parties {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .parties td {
            width: 50%;
            vertical-align: top;
            padding-right: 16px;
        }
        .parties td:last-child { padding-right: 0; padding-left: 16px; }
        .party-title {
            font-size: 11px;
            font-weight: bold;
            color: #2c3e50;
            text-transform: uppercase;
            margin-bottom: 6px;
            letter-spacing: 0.3px;
        }
        .party-name {
            font-size: 12px;
            font-weight: bold;
            margin-bottom: 4px;
        }
        .party-line { margin: 1px 0; font-size: 11px; }
        table.items {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 14px;
        }
        table.items thead th {
            background: #2c3e50;
            color: #ffffff;
            font-size: 10px;
            text-align: left;
            padding: 8px 6px;
        }
        table.items thead th.num { text-align: right; }
        table.items tbody td {
            padding: 10px 6px;
            border-bottom: 1px solid #e0e0e0;
            vertical-align: top;
        }
        table.items tbody td.num { text-align: right; white-space: nowrap; }
        .item-title { font-weight: bold; font-size: 11px; }
        .item-desc { font-size: 10px; color: #555; margin-top: 2px; }
        .totals {
            width: 280px;
            margin-left: auto;
            margin-bottom: 18px;
        }
        .totals table { width: 100%; border-collapse: collapse; }
        .totals td { padding: 4px 0; font-size: 12px; }
        .totals td.lbl { text-align: right; padding-right: 12px; color: #555; }
        .totals td.val { text-align: right; font-weight: bold; }
        .totals .grand td {
            padding-top: 8px;
            font-size: 14px;
            color: #2c3e50;
            border-top: 2px solid #2c3e50;
        }
        .notes { margin-bottom: 16px; }
        .notes h3 {
            font-size: 12px;
            margin: 0 0 6px 0;
            color: #333;
        }
        .notes p { margin: 3px 0; font-size: 10px; color: #444; white-space: pre-line; }
        .bank {
            border: 1px solid #c8e6c9;
            background: #f1f8e9;
            padding: 12px 14px;
            margin-bottom: 18px;
        }
        .bank h3 {
            margin: 0 0 8px 0;
            font-size: 13px;
            color: #2e7d32;
        }
        .bank p { margin: 2px 0; font-size: 11px; }
        .pago-recibido {
            border: 1px solid #2e7d32;
            background: #e8f5e9;
            padding: 12px 14px;
            margin-bottom: 18px;
        }
        .pago-recibido h3 {
            margin: 0 0 8px 0;
            font-size: 13px;
            color: #1b5e20;
        }
        .pago-recibido p { margin: 3px 0; font-size: 11px; }
        .footer {
            text-align: center;
            color: #666666;
            font-size: 10px;
            margin-top: 8px;
        }
    </style>
</head>
<body>
    @php
        $currency = $invoice->currency ?: 'USD';
        $symbol = $currency === 'EUR' ? '€' : '$';
        $fmt = static fn ($n) => $symbol.number_format((float) $n, 2);
        $issue = $invoice->issue_date?->locale('es')->translatedFormat('j \\d\\e F \\d\\e Y') ?? '';
        $due = $invoice->due_date?->locale('es')->translatedFormat('j \\d\\e F \\d\\e Y') ?? '';
        $providerName = $company['legal_name'] ?: $company['name'];
    @endphp

    <table class="header">
        <tr>
            <td class="header-logo">
                @if (! empty($company['logo_dark_data_uri']))
                    <img src="{{ $company['logo_dark_data_uri'] }}" alt="{{ $company['name'] }}" />
                @endif
            </td>
            <td class="header-title">
                <h1 class="{{ $invoice->is_paid ? 'title-paid' : 'title-pending' }}">FACTURA / FATURA</h1>
                @if ($invoice->is_paid)
                    <div class="status-badge status-badge--paid">Paid</div>
                @else
                    <div class="status-badge status-badge--pending">Pending</div>
                @endif
            </td>
        </tr>
    </table>

    <div class="meta">
        <p><strong>Nº Factura:</strong> <span class="invoice-number">{{ $invoice->invoice_number }}</span></p>
        <p>
            <strong>Estado / Status:</strong>
            @if ($invoice->is_paid)
                <span class="estado-paid">PAID</span>
            @else
                <span class="estado-pending">PENDING</span>
            @endif
        </p>
        <p><strong>Fecha de emisión:</strong> {{ $issue }}</p>
        <p><strong>Fecha de vencimiento:</strong> {{ $due }}</p>
    </div>

    <table class="parties">
        <tr>
            <td>
                <div class="party-title">Proveedor / Fornecedor</div>
                <div class="party-name">{{ $providerName }}</div>
                @if (! empty($company['nif_nipc']))
                    <div class="party-line">NIF/NIPC: {{ $company['nif_nipc'] }}</div>
                @endif
                @if (! empty($company['nie']))
                    <div class="party-line">NIE: {{ $company['nie'] }}</div>
                @endif
                @if (! empty($company['address']))
                    <div class="party-line">{{ $company['address'] }}</div>
                @endif
                @if (! empty($company['email']))
                    <div class="party-line">Email: {{ $company['email'] }}</div>
                @endif
                @if (! empty($company['phone']))
                    <div class="party-line">Tel: {{ $company['phone'] }}</div>
                @endif
            </td>
            <td>
                <div class="party-title">Cliente / Cliente</div>
                <div class="party-name">{{ $invoice->client_name }}</div>
                @if ($invoice->client_tax_id)
                    <div class="party-line">EIN / Tax ID: {{ $invoice->client_tax_id }}</div>
                @endif
                @if ($invoice->client_address)
                    <div class="party-line">{{ $invoice->client_address }}</div>
                @endif
                @if ($invoice->client_city)
                    <div class="party-line">{{ $invoice->client_city }}</div>
                @endif
                @if ($invoice->client_country)
                    <div class="party-line">{{ $invoice->client_country }}</div>
                @endif
            </td>
        </tr>
    </table>

    <table class="items">
        <thead>
            <tr>
                <th>Concepto / Descrição</th>
                <th class="num">Cantidad</th>
                <th class="num">Precio Unitario</th>
                <th class="num">Importe</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($invoice->items as $item)
                <tr>
                    <td>
                        <div class="item-title">{{ $item->title }}</div>
                        @if ($item->description)
                            <div class="item-desc">{{ $item->description }}</div>
                        @endif
                    </td>
                    <td class="num">{{ number_format((float) $item->quantity, 0) }}</td>
                    <td class="num">{{ $fmt($item->unit_price) }}</td>
                    <td class="num">{{ $fmt($item->amount) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="totals">
        <table>
            <tr>
                <td class="lbl">Subtotal:</td>
                <td class="val">{{ $fmt($invoice->subtotal) }}</td>
            </tr>
            <tr>
                <td class="lbl">{{ $invoice->tax_label ?? 'IVA' }}:</td>
                <td class="val">
                    @if ($invoice->tax_mode === 'EXEMPT' || (float) ($invoice->tax_rate ?? 0) === 0.0)
                        Exento
                    @else
                        {{ $fmt($invoice->tax_amount) }}
                        ({{ rtrim(rtrim(number_format((float) $invoice->tax_rate, 2), '0'), '.') }}%)
                    @endif
                </td>
            </tr>
            <tr class="grand">
                <td class="lbl">{{ $invoice->is_paid ? 'TOTAL PAGADO / TOTAL PAID:' : 'TOTAL A PAGAR:' }}</td>
                <td class="val">{{ $fmt($invoice->total) }}</td>
            </tr>
        </table>
    </div>

    @if ($invoice->notes)
        <div class="notes">
            <h3>Notas importantes:</h3>
            <p>{{ $invoice->notes }}</p>
        </div>
    @endif

    @if ($invoice->is_paid)
        @php
            $paymentDateLabel = $invoice->payment_date?->locale('en')->translatedFormat('F j, Y') ?? '';
            $received = $invoice->amount_received ?? $invoice->total;
        @endphp
        <div class="pago-recibido">
            <h3>✓ PAYMENT RECEIVED</h3>
            @if ($invoice->payment_method)
                <p><strong>Payment Method:</strong> {{ $invoice->payment_method }}</p>
            @endif
            @if ($invoice->transfer_number)
                <p><strong>Transfer Number:</strong> {{ $invoice->transfer_number }}</p>
            @endif
            @if ($paymentDateLabel !== '')
                <p><strong>Payment Date:</strong> {{ $paymentDateLabel }}</p>
            @endif
            <p><strong>Amount Received:</strong> {{ $fmt($received) }} {{ $currency }}</p>
        </div>
    @elseif (! empty($company['bank_iban']) || ! empty($company['bank_beneficiary']))
        <div class="bank">
            <h3>Datos Bancarios / Dados Bancários — PENDING</h3>
            @if (! empty($company['bank_beneficiary']))
                <p><strong>Beneficiario:</strong> {{ $company['bank_beneficiary'] }}</p>
            @endif
            @if (! empty($company['bank_iban']))
                <p><strong>IBAN:</strong> {{ $company['bank_iban'] }}</p>
            @endif
            @if (! empty($company['bank_bic']))
                <p><strong>BIC/SWIFT:</strong> {{ $company['bank_bic'] }}</p>
            @endif
            @if (! empty($company['bank_name']))
                <p><strong>Banco:</strong> {{ $company['bank_name'] }}</p>
            @endif
        </div>
    @endif

    <div class="footer">Gracias por su confianza / Obrigado pela confiança</div>
</body>
</html>
