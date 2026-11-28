import { usePage } from "@inertiajs/vue3"
import type { DateString, Money } from "@/types"

const DAY = 24 * 60 * 60 * 1000

/**
 * Dates are calendar days ("Y-m-d": purchases, payments, closing and due
 * dates). `new Date("2026-11-20")` is midnight UTC, so they're formatted in
 * UTC: otherwise in Mexico they'd show up as the day before.
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

    // Real moments (not calendar days), shown in the viewer's timezone
    const dateTime = new Intl.DateTimeFormat(locale, {
        day: "numeric",
        month: "short",
        hour: "numeric",
        minute: "2-digit",
    })

    return {
        toCurrency: (amount: Money) => currency.format(Number(amount)),
        /** "27 nov, 14:05" in the viewer's timezone, for timestamps */
        toDateTime: (value: string) => dateTime.format(new Date(value)),
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
    const todayUtc = Date.UTC(now.getFullYear(), now.getMonth(), now.getDate())

    return Math.round((new Date(value).getTime() - todayUtc) / DAY)
}

/**
 * Today in the viewer's calendar, as "Y-m-d"
 */
export function today(): string {
    const now = new Date()

    return [
        now.getFullYear(),
        String(now.getMonth() + 1).padStart(2, "0"),
        String(now.getDate()).padStart(2, "0"),
    ].join("-")
}
