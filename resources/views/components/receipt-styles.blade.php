@props([])

{{--
    Shared stylesheet for every customer-facing rendering of the receipt.

    It is included in three very different places on purpose, so the layouts
    can never drift apart:

      1. resources/views/emails/orders/receipt-online.blade.php  (email + PDF)
      2. resources/views/components/receipt-modal.blade.php      (on screen)
      3. the pop-up print window, which re-injects this block by its
         `receipt-style` id.

    Everything is plain CSS targeting the table markup: no Tailwind utility
    classes (they do not survive domPDF) and no CSS custom properties (domPDF
    does not understand them). The .stack rules are what let the two-column
    rows collapse to one column on a phone.

    The id must stay `receipt-style` - the print window looks it up by that.
--}}
<style id="receipt-style">
    table.rd { border-collapse: collapse; }
    .rd-card { width: 680px; max-width: 680px; }
    .rd-pad { padding-left: 32px; padding-right: 32px; }

    /* Phones: drop the card to full width, pad tighter, and stack the
       side-by-side cells so nothing is squeezed. */
    @media only screen and (max-width: 700px) {
        .rd-card { width: 100% !important; max-width: 100% !important; }
        .rd-pad { padding-left: 16px !important; padding-right: 16px !important; }
        .rd-stack { display: block !important; width: 100% !important; }
        .rd-stack-gap { height: 14px !important; line-height: 14px !important; font-size: 14px !important; }
        .rd-hide-sm { display: none !important; }
        .rd-amount { font-size: 26px !important; }
        .rd-item-name { font-size: 14px !important; }
        .rd-tot-label, .rd-tot-value { font-size: 14px !important; }
        .rd-tot-table { width: 100% !important; }
        .rd-tot-align { text-align: left !important; }
    }

    /* Printing the customer's own copy: drop the grey page, the shadow and
       the rounded corners so the ink lands on white paper. */
    @media print {
        .rd-page, .rd-body { background: #ffffff !important; }
        .rd-card { box-shadow: none !important; border-radius: 0 !important; width: 100% !important; max-width: 100% !important; }
        .rd-no-print { display: none !important; }
    }
</style>
