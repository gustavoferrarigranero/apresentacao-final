export function decimalToCents(value: string | number) {
    const text = String(value);
    if (!/^\d+(?:\.\d{1,2})?$/.test(text))
        throw new Error('Valor monetário inválido recebido da API.');
    const [units, decimal = ''] = text.split('.');
    const cents = Number(units) * 100 + Number(decimal.padEnd(2, '0'));
    if (!Number.isSafeInteger(cents))
        throw new Error('Valor monetário fora do intervalo suportado.');
    return cents;
}
export const money = (cents: number) =>
    new Intl.NumberFormat('pt-AO', {
        style: 'currency',
        currency: 'AOA',
        currencyDisplay: 'code',
    }).format(cents / 100);
