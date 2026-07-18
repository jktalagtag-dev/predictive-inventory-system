import { type FormEvent, useEffect, useState } from 'react'
import { X } from 'lucide-react'
import { usePurchaseOrder } from '@/features/purchase-orders/hooks/usePurchaseOrders'
import type { PurchaseOrder, PurchaseOrderLine } from '@/features/purchase-orders/types/purchaseOrder'
import type { GoodsReceiptFormValues, GoodsReceiptLineInput } from '@/features/receiving/types/goodsReceipt'
import { Button } from '@/shared/components/Button'
import { cn } from '@/shared/lib/cn'
import { modalOverlayClass, modalPanelClass, sheetBodyClass, sheetFooterClass, sheetHeaderClass } from '@/shared/lib/modalClasses'

type GoodsReceiptFormDialogProps = {
  receivablePurchaseOrders: PurchaseOrder[]
  isSaving: boolean
  onClose: () => void
  onSave: (values: GoodsReceiptFormValues) => void
}

function lineFromPoLine(line: PurchaseOrderLine): GoodsReceiptLineInput {
  const remaining = (Number(line.orderedQuantity) - Number(line.receivedQuantity)).toFixed(4)
  return {
    purchaseOrderLineId: line.id,
    productSku: line.productSku,
    productName: line.productName,
    remainingQuantity: remaining,
    receivedQuantity: remaining,
    acceptedQuantity: remaining,
    rejectedQuantity: '0',
    lotNumber: '',
    serialNumber: '',
    expiryDate: '',
    rejectionReason: '',
    notes: '',
  }
}

export function GoodsReceiptFormDialog({ receivablePurchaseOrders, isSaving, onClose, onSave }: GoodsReceiptFormDialogProps) {
  const [purchaseOrderId, setPurchaseOrderId] = useState('')
  const [supplierDeliveryNumber, setSupplierDeliveryNumber] = useState('')
  const [receivedAt, setReceivedAt] = useState(() => new Date().toISOString().slice(0, 10))
  const [notes, setNotes] = useState('')
  const [lines, setLines] = useState<GoodsReceiptLineInput[]>([])

  const selectedPoQuery = usePurchaseOrder(purchaseOrderId || undefined)

  useEffect(() => {
    if (selectedPoQuery.data) {
      setLines(
        selectedPoQuery.data.lines
          .filter((line) => Number(line.orderedQuantity) - Number(line.receivedQuantity) > 0)
          .map(lineFromPoLine),
      )
    } else {
      setLines([])
    }
  }, [selectedPoQuery.data])

  const updateLine = (index: number, patch: Partial<GoodsReceiptLineInput>) => {
    setLines((state) => state.map((line, i) => (i === index ? { ...line, ...patch } : line)))
  }

  const submit = (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault()
    onSave({ purchaseOrderId, supplierDeliveryNumber, receivedAt: new Date(receivedAt).toISOString(), notes, lines })
  }

  const isValid = purchaseOrderId !== '' && receivedAt !== '' && lines.length > 0
    && lines.every((line) => line.receivedQuantity !== '' && line.acceptedQuantity !== '' && line.rejectedQuantity !== ''
      && (Number(line.rejectedQuantity) === 0 || line.rejectionReason.trim() !== ''))

  return (
    <div className={modalOverlayClass} role="presentation">
      <section aria-labelledby="gr-form-title" aria-modal="true" className={modalPanelClass('sm:max-w-4xl')} role="dialog">
        <div className={sheetHeaderClass}>
          <div><h2 id="gr-form-title" className="text-lg font-bold text-ink">Record goods receipt</h2><p className="mt-1 text-sm text-muted">Select an ordered purchase order, then confirm what was actually delivered.</p></div>
          <Button aria-label="Close dialog" size="icon" variant="ghost" onClick={onClose}><X aria-hidden="true" size={18} /></Button>
        </div>
        <form className="flex min-h-0 flex-1 flex-col" onSubmit={submit}>
          <div className={cn(sheetBodyClass, 'space-y-5')}>
            <div className="grid gap-4 sm:grid-cols-3">
              <label className="text-sm font-semibold text-ink sm:col-span-2">Purchase order
                <select className="mt-2 h-11 w-full rounded-xl border border-border bg-surface px-3 text-sm outline-none focus:border-brand-600 focus:ring-2 focus:ring-brand-600/20" required value={purchaseOrderId} onChange={(event) => setPurchaseOrderId(event.target.value)}>
                  <option value="" disabled>Select a purchase order</option>
                  {receivablePurchaseOrders.map((po) => <option key={po.id} value={po.id}>{po.poNumber} — {po.supplier?.legalName ?? 'Unknown supplier'}</option>)}
                </select>
              </label>
              <label className="text-sm font-semibold text-ink">Received date<input className="mt-2 h-11 w-full rounded-xl border border-border bg-surface px-3 text-sm outline-none focus:border-brand-600 focus:ring-2 focus:ring-brand-600/20" required type="date" value={receivedAt} onChange={(event) => setReceivedAt(event.target.value)} /></label>
            </div>
            <label className="block text-sm font-semibold text-ink sm:max-w-sm">Supplier delivery reference<input className="mt-2 h-11 w-full rounded-xl border border-border bg-surface px-3 text-sm outline-none focus:border-brand-600 focus:ring-2 focus:ring-brand-600/20" value={supplierDeliveryNumber} onChange={(event) => setSupplierDeliveryNumber(event.target.value)} /></label>

            {selectedPoQuery.isFetching ? <p className="text-sm text-muted">Loading purchase order lines…</p> : null}

            {lines.length > 0 ? (
              <div>
                <h3 className="text-sm font-semibold text-ink">Lines</h3>
                <div className="mt-2 space-y-3">
                  {lines.map((line, index) => (
                    <div className="rounded-xl border border-border p-3" key={line.purchaseOrderLineId}>
                      <p className="text-sm font-medium text-ink">{line.productName} <span className="text-xs text-muted">({line.productSku})</span></p>
                      <p className="mt-0.5 text-xs text-muted">Remaining ordered: {line.remainingQuantity}</p>
                      <div className="mt-2 grid grid-cols-3 gap-2">
                        <label className="text-xs font-semibold text-muted">Received
                          <input className="mt-1 h-11 w-full rounded-lg border border-border bg-surface px-2 text-sm outline-none focus:border-brand-600" min="0" required step="0.0001" type="number" value={line.receivedQuantity} onChange={(event) => updateLine(index, { receivedQuantity: event.target.value })} />
                        </label>
                        <label className="text-xs font-semibold text-muted">Accepted
                          <input className="mt-1 h-11 w-full rounded-lg border border-border bg-surface px-2 text-sm outline-none focus:border-brand-600" min="0" required step="0.0001" type="number" value={line.acceptedQuantity} onChange={(event) => updateLine(index, { acceptedQuantity: event.target.value })} />
                        </label>
                        <label className="text-xs font-semibold text-muted">Rejected
                          <input className="mt-1 h-11 w-full rounded-lg border border-border bg-surface px-2 text-sm outline-none focus:border-brand-600" min="0" required step="0.0001" type="number" value={line.rejectedQuantity} onChange={(event) => updateLine(index, { rejectedQuantity: event.target.value })} />
                        </label>
                      </div>
                      {Number(line.rejectedQuantity) > 0 ? (
                        <label className="mt-2 block text-xs font-semibold text-muted">Rejection reason
                          <input className="mt-1 h-11 w-full rounded-lg border border-border bg-surface px-2 text-sm outline-none focus:border-brand-600" required value={line.rejectionReason} onChange={(event) => updateLine(index, { rejectionReason: event.target.value })} />
                        </label>
                      ) : null}
                    </div>
                  ))}
                </div>
              </div>
            ) : null}

            <label className="block text-sm font-semibold text-ink">Notes<textarea className="mt-2 w-full rounded-xl border border-border bg-surface px-3 py-2 text-sm outline-none focus:border-brand-600 focus:ring-2 focus:ring-brand-600/20" rows={2} value={notes} onChange={(event) => setNotes(event.target.value)} /></label>
          </div>

          <div className={sheetFooterClass}>
            <Button type="button" variant="secondary" onClick={onClose}>Cancel</Button>
            <Button disabled={isSaving || !isValid} type="submit">{isSaving ? 'Saving' : 'Create draft'}</Button>
          </div>
        </form>
      </section>
    </div>
  )
}
