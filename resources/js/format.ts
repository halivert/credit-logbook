import { usePage } from "@inertiajs/vue3"
import type { DateString, Money } from "@/types"

const DAY = 24 * 60 * 60 * 1000

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

    const shortDate = new Intl.DateTimeFormat(locale, {
        day: "numeric",
        month: "short",
        timeZone: "UTC",
    })

    return {
        toCurrency: (amount: Money) => currency.format(Number(amount)),
        toDate: (value: DateString) => date.format(new Date(value)),
        /** "9 dic" */
        toShortDate: (value: DateString) => shortDate.format(new Date(value)),
    }
}

/**
 * Whole days from today (in the viewer's calendar) until a calendar day.
 */
export function daysUntil(value: DateString): number {
    const now = new Date()
    const today = Date.UTC(now.getFullYear(), now.getMonth(), now.getDate())

    return Math.round((new Date(value).getTime() - today) / DAY)
}
