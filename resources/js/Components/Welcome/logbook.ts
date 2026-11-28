/**
 * The logbook math, ported from the server so the demo on the welcome page
 * behaves like the real thing. Keep it in sync with:
 * - app/CreditCards/BillingCycle.php
 * - app/Purchases/InstallmentPlan.php
 * - app/CreditCards/Statement.php
 * - CreditCard::balanceCents() and HasInstallments
 *
 * Days are "Y-m-d" strings (they compare correctly as text), amounts are
 * integer cents.
 */

export type Day = string

export function addDays(day: Day, days: number): Day {
    const [y, m, d] = day.split("-").map(Number)

    return new Date(Date.UTC(y, m - 1, d + days)).toISOString().slice(0, 10)
}

function parts(day: Day): [number, number] {
    const [y, m] = day.split("-").map(Number)

    return [y, m - 1]
}

/** $day of a month, or its last day when the month is shorter */
function dayOfMonth(year: number, month: number, day: number): Day {
    const last = new Date(Date.UTC(year, month + 1, 0)).getUTCDate()

    return new Date(Date.UTC(year, month, Math.min(day, last)))
        .toISOString()
        .slice(0, 10)
}

export class BillingCycle {
    constructor(
        public closingDay: number,
        public dueDay: number,
    ) {}

    nextClosing(from: Day): Day {
        return this.nextOccurrence(this.closingDay, from)
    }

    nextDue(from: Day): Day {
        return this.nextOccurrence(this.dueDay, from)
    }

    /** The latest closing, $on itself included */
    lastClosing(on: Day): Day {
        const [y, m] = parts(on)
        const closing = dayOfMonth(y, m, this.closingDay)

        return closing <= on ? closing : dayOfMonth(y, m - 1, this.closingDay)
    }

    dueAfter(closing: Day): Day {
        return this.nextDue(addDays(closing, 1))
    }

    /** Closings on or after $from and up to $to, both included */
    closingsBetween(from: Day, to: Day): number {
        let [y, m] = parts(from)
        let closings = 0

        while (dayOfMonth(y, m, 1) <= to) {
            const closing = dayOfMonth(y, m, this.closingDay)

            if (closing >= from && closing <= to) closings++

            m++
        }

        return closings
    }

    private nextOccurrence(day: number, from: Day): Day {
        const [y, m] = parts(from)
        const candidate = dayOfMonth(y, m, day)

        return candidate >= from ? candidate : dayOfMonth(y, m + 1, day)
    }
}

export class InstallmentPlan {
    constructor(
        public amountCents: number,
        public count: number,
        public installmentCents: number,
        public commissionCents = 0,
    ) {}

    static evenInstallmentCents(amountCents: number, count: number): number {
        return Math.floor((amountCents + count - 1) / count)
    }

    billedCents(billed: number): number {
        if (billed >= this.count) return this.amountCents

        return Math.min(Math.max(0, billed) * this.installmentCents, this.amountCents)
    }

    remainingCents(billed: number): number {
        return this.amountCents - this.billedCents(billed)
    }

    nextInstallmentCents(billed: number): number {
        if (billed >= this.count) return 0

        const remaining = this.remainingCents(billed)
        const installment =
            billed === this.count - 1
                ? remaining
                : Math.min(remaining, this.installmentCents)

        return billed === 0 ? installment + this.commissionCents : installment
    }
}

export type Charge = { day: Day; cents: number; plan: InstallmentPlan | null }
export type PaymentEntry = { day: Day; cents: number }

export type StatementCents = {
    closing: Day
    due: Day
    nextClosing: Day
    toAvoidInterest: number
    paidSinceClosing: number
    remaining: number
    periodCharges: number
    isOverdue: boolean
}

export function calculateStatement(
    cycle: BillingCycle,
    today: Day,
    charges: Charge[],
    payments: PaymentEntry[],
): StatementCents {
    const closing = cycle.lastClosing(today)
    const due = cycle.dueAfter(closing)

    const billed = (charge: Charge, day: Day): number => {
        if (charge.day > day) return 0
        if (!charge.plan) return charge.cents

        const count = Math.min(cycle.closingsBetween(charge.day, day), charge.plan.count)

        if (count === 0) return 0

        return charge.plan.billedCents(count) + charge.plan.commissionCents
    }

    const billedThrough = (day: Day) =>
        charges.reduce((sum, charge) => sum + billed(charge, day), 0)
    const paidThrough = (day: Day) =>
        payments
            .filter((payment) => payment.day <= day)
            .reduce((sum, payment) => sum + payment.cents, 0)

    const closingBalance = billedThrough(closing) - paidThrough(closing)
    const toAvoidInterest = Math.max(0, closingBalance)
    const paidSinceClosing = paidThrough(today) - paidThrough(closing)
    const remaining = Math.max(0, toAvoidInterest - paidSinceClosing)

    return {
        closing,
        due,
        nextClosing: cycle.nextClosing(addDays(closing, 1)),
        toAvoidInterest,
        paidSinceClosing,
        remaining,
        periodCharges: billedThrough(today) - billedThrough(closing),
        isOverdue: remaining > 0 && today > due,
    }
}
