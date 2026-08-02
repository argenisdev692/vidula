/**
 * Cross-border exempt VAT notice for invoice form defaults (mirrors PHP InvoiceCrossBorderVatNotice).
 */

const SCHENGEN_CODES = new Set([
    'AT', 'BE', 'BG', 'CH', 'CZ', 'DE', 'DK', 'EE', 'ES', 'FI',
    'FR', 'GR', 'HR', 'HU', 'IS', 'IT', 'LI', 'LT', 'LU', 'LV',
    'MT', 'NL', 'NO', 'PL', 'PT', 'RO', 'SE', 'SI', 'SK',
]);

function clientCountryLabel(code: string, name: string | null): string {
    const trimmed = name?.trim() ?? '';
    if (trimmed !== '') {
        return trimmed;
    }

    switch (code) {
        case 'US':
            return 'United States';
        case 'PT':
            return 'Portugal';
        case 'ES':
            return 'Spain';
        default:
            return code !== '' ? code : 'client country';
    }
}

export function buildCrossBorderVatNotice(
    taxMode: 'EXEMPT' | 'PERCENT',
    taxRate: number,
    issuerCountryName: string,
    clientCountryName: string | null,
    clientCountryCode: string | null,
): string | null {
    if (taxMode !== 'EXEMPT' && taxRate !== 0) {
        return null;
    }

    const code = (clientCountryCode ?? '').trim().toUpperCase();
    const clientLabel = clientCountryLabel(code, clientCountryName);

    if (code === '') {
        return null;
    }

    if (code === 'US') {
        return `VAT - Reverse Charge: International transaction exempt from VAT. Cross-border service provision between ${issuerCountryName} and ${clientLabel} (B2B).\nWeb development services provided remotely.`;
    }

    if (code === 'PT') {
        return `IVA - Autoliquidação: Transação internacional com IVA exempto. Prestação de serviços transfronteiriça entre ${issuerCountryName} e ${clientLabel} (B2B).\nServiços de desenvolvimento web prestados remotamente.`;
    }

    if (SCHENGEN_CODES.has(code)) {
        return `IVA - Inversión del sujeto pasivo: Transacción internacional exenta de IVA. Prestación de servicios transfronteriza entre ${issuerCountryName} y ${clientLabel} (B2B).\nDesarrollo web prestado de forma remota.`;
    }

    return `VAT - Reverse Charge: International transaction exempt from VAT. Cross-border service provision between ${issuerCountryName} and ${clientLabel} (B2B).\nWeb development services provided remotely.`;
}
