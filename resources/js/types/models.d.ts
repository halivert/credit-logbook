/**
 * Shapes of the models as Laravel serializes them to Inertia.
 *
 * Declared with `type` instead of `interface` so they can be passed to
 * Ziggy's `route()` as parameters, which requires an index signature.
 */

/** ISO 8601 date string, e.g. "2026-11-27T21:35:29.438000Z" */
export type DateString = string

/** `decimal:2` and `encrypted` casts are serialized as strings, e.g. "1250.50" */
export type Decimal = `${number}`

/** Accessors computed in PHP may come as numbers or decimal strings */
export type Money = number | Decimal

export type CreditCard = {
    id: string
    user_id: string
    name: string
    due_date: number
    closing_date: number
    interest_rate: Decimal
    limit: Decimal
    timezone: string
    next_due_date: DateString
    next_closing_date: DateString
    /** Only on the pages that show it */
    balance_used?: number
    available_credit?: number
    deleted_at: DateString | null
    created_at: DateString
    updated_at: DateString
}

export type Payment = {
    id: string
    credit_card_id: string
    /** "Y-m-d" */
    date: string
    amount: Decimal
    created_at: DateString
    updated_at: DateString
}

export type Purchase = {
    id: string
    credit_card_id: string
    concept: string
    /** "Y-m-d" */
    date: string
    /** Negative for returns */
    amount: Decimal
    installment_count: number | null
    /** Next installment to be billed (includes the commission on the first) */
    installment_amount: Money | null
    commission: Decimal | null
    interest_rate: Decimal | null
    /** "Y-m-d", when the bank applied it if different from `date` */
    applied_on: string | null
    created_at: DateString
    updated_at: DateString
    is_installment_purchase: boolean
    is_refund: boolean
    /** Installments already billed, one per closing date */
    billed_installments: number | null
    remaining_installments: number | null
    /** Still to be billed in future statements */
    remaining_amount: Money
}

export type PaginationLink = {
    url: string | null
    label: string
    page: number | null
    active: boolean
}

export type SimplePaginator<T> = {
    data: T[]
    current_page: number
    next_page_url: string | null
    prev_page_url: string | null
    per_page: number
}

export type LengthAwarePaginator<T> = SimplePaginator<T> & {
    links: PaginationLink[]
    last_page: number
    total: number
}
