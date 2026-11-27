import { usePage } from "@inertiajs/vue3"
import type { DateString, Money } from "@/types"

/**
 * Dates are calendar days (purchases, closing and due dates) stored at
 * midnight UTC, so they're formatted in UTC: otherwise in Mexico they'd show
 * up as the day before.
 */
export function useFormat() {
    const locale = usePage().props.locale

    const currency = new Intl.NumberFormat(locale, {
        style: "currency",
        currency: "MXN",
    })

    const date = new Intl.DateTimeFormat(locale, {
        dateStyle: "medium",
        timeZone: "UTC",
    })

    return {
        toCurrency: (amount: Money) => currency.format(Number(amount)),
        toDate: (value: DateString) => date.format(new Date(value)),
    }
}
