function escapeHtml(value) {
    return String(value ?? '').replace(/[&<>"']/g, character => ({
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;',
    })[character])
}

function formatAmount(value) {
    return (Number(value) || 0).toFixed(2)
}

export function openReceiptWindow() {
    const receiptWindow = window.open('', '_blank', 'width=420,height=720')
    if (receiptWindow) receiptWindow.opener = null
    return receiptWindow
}

export function printOrderReceipt(receiptWindow, { order, invoice, restaurant, table, currency = 'AFS' }) {
    if (!receiptWindow || receiptWindow.closed) {
        throw new Error('The receipt window is unavailable. Allow pop-ups and try again.')
    }

    const items = (order.items ?? []).map(item => `
        <tr>
            <td class="item">${escapeHtml(item.product_name)}<br><small>${escapeHtml(item.quantity)} x ${escapeHtml(currency)} ${formatAmount(item.unit_price)}</small></td>
            <td class="amount">${escapeHtml(currency)} ${formatAmount(item.item_total)}</td>
        </tr>
    `).join('')

    const summaryLine = (label, amount) => `
        <div class="row"><span>${escapeHtml(label)}</span><span>${escapeHtml(currency)} ${formatAmount(amount)}</span></div>
    `
    const paidAmount = Number(order.paid_amount ?? order.grand_total)
    const payment = order.payments?.[order.payments.length - 1]
    const cashTendered = payment?.cash_tendered
    const cashChange = payment?.cash_change
    const address = [restaurant?.address, restaurant?.city, restaurant?.state].filter(Boolean).join(', ')
    const html = `<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Receipt ${escapeHtml(order.order_number)}</title>
    <style>
        @page { size: 80mm auto; margin: 3mm; }
        * { box-sizing: border-box; }
        body { width: 74mm; margin: 0 auto; color: #000; font: 12px Arial, sans-serif; }
        h1, p { margin: 0; }
        .center { text-align: center; }
        .business { font-size: 17px; font-weight: bold; margin-bottom: 4px; }
        .muted { font-size: 10px; margin: 2px 0; }
        .title { font-size: 14px; font-weight: bold; margin: 10px 0 6px; }
        .rule { border-top: 1px dashed #000; margin: 7px 0; }
        .meta { font-size: 10px; line-height: 1.5; }
        table { width: 100%; border-collapse: collapse; margin: 6px 0; }
        td { vertical-align: top; padding: 4px 0; }
        .item { text-align: left; }
        .amount { text-align: right; white-space: nowrap; }
        small { font-size: 10px; }
        .row { display: flex; justify-content: space-between; gap: 8px; padding: 2px 0; }
        .total { font-size: 15px; font-weight: bold; }
        .footer { margin-top: 12px; }
        @media screen { body { padding: 5mm 0; } }
    </style>
</head>
<body>
    <header class="center">
        <p class="business">${escapeHtml(restaurant?.name ?? 'Restaurant')}</p>
        ${address ? `<p class="muted">${escapeHtml(address)}</p>` : ''}
        ${restaurant?.phone ? `<p class="muted">${escapeHtml(restaurant.phone)}</p>` : ''}
        <p class="title">RECEIPT</p>
    </header>
    <div class="rule"></div>
    <div class="meta">
        <div>Invoice: ${escapeHtml(invoice?.invoice_number ?? order.order_number)}</div>
        <div>Order: ${escapeHtml(order.order_number)}</div>
        ${table?.name ? `<div>Table: ${escapeHtml(table.name)}</div>` : ''}
        ${payment?.payment_method ? `<div>Payment: ${escapeHtml(payment.payment_method)}</div>` : ''}
        <div>Date: ${escapeHtml(new Date().toLocaleString())}</div>
    </div>
    <div class="rule"></div>
    <table><tbody>${items}</tbody></table>
    <div class="rule"></div>
    ${summaryLine('Subtotal', order.sub_total)}
    ${summaryLine('Tax', order.tax_amount)}
    ${Number(order.discount_amount) > 0 ? summaryLine('Discount', -Number(order.discount_amount)) : ''}
    ${Number(order.service_charge_amount) > 0 ? summaryLine('Service charge', order.service_charge_amount) : ''}
    <div class="rule"></div>
    <div class="row total"><span>Total</span><span>${escapeHtml(currency)} ${formatAmount(order.grand_total)}</span></div>
    <div class="row"><span>Paid</span><span>${escapeHtml(currency)} ${formatAmount(paidAmount)}</span></div>
    ${cashTendered != null ? summaryLine('Cash received', cashTendered) : ''}
    ${cashChange != null && Number(cashChange) > 0 ? summaryLine('Change', cashChange) : ''}
    <p class="center muted footer">Thank you!</p>
</body>
</html>`

    let printed = false
    const startPrint = () => {
        if (printed || receiptWindow.closed) return
        printed = true
        receiptWindow.focus()
        receiptWindow.print()
    }

    receiptWindow.addEventListener('load', startPrint, { once: true })
    receiptWindow.addEventListener('afterprint', () => receiptWindow.close(), { once: true })
    receiptWindow.document.open()
    receiptWindow.document.write(html)
    receiptWindow.document.close()
    if (receiptWindow.document.readyState === 'complete') startPrint()
}
