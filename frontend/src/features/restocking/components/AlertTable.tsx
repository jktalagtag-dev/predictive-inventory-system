import { PanelRightOpen } from 'lucide-react'
import { AlertStatusBadge } from '@/features/restocking/components/AlertStatusBadge'
import { SeverityBadge } from '@/features/restocking/components/SeverityBadge'
import type { RestockingAlert } from '@/features/restocking/types/restocking'
import { Button } from '@/shared/components/Button'
import { RecordCard } from '@/shared/components/RecordCard'
import { Table, TableBody, TableCell, TableEmptyState, TableHead, TableHeaderCell, TableRow } from '@/shared/components/Table'

export function AlertTable({ alerts, onView }: { alerts: RestockingAlert[]; onView: (alert: RestockingAlert) => void }) {
  return (
    <>
      <div className="space-y-3 md:hidden">
        {alerts.length === 0 ? (
          <p className="rounded-card border border-border bg-surface p-6 text-center text-sm text-muted shadow-panel">No alerts match these filters.</p>
        ) : (
          alerts.map((alert) => (
            <RecordCard
              key={alert.id}
              ariaLabel={`View alert for ${alert.productName}`}
              badge={<SeverityBadge severity={alert.severity} />}
              title={alert.productName ?? '—'}
              subtitle={<span className="font-mono">{alert.productSku}</span>}
              fields={[
                { label: 'Status', value: <AlertStatusBadge status={alert.status} />, full: true },
                { label: 'Available', value: alert.availableQuantitySnapshot },
                { label: 'Reorder point', value: alert.reorderPointSnapshot },
                { label: 'Recommended order', value: alert.recommendedOrderQuantity ?? '—', full: true },
              ]}
              onClick={() => onView(alert)}
            />
          ))
        )}
      </div>

      <div className="hidden md:block">
        <Table minWidth={800}>
          <TableHead>
            <tr>
              <TableHeaderCell>Product</TableHeaderCell>
              <TableHeaderCell>Severity</TableHeaderCell>
              <TableHeaderCell>Status</TableHeaderCell>
              <TableHeaderCell align="right">Available</TableHeaderCell>
              <TableHeaderCell align="right">Reorder point</TableHeaderCell>
              <TableHeaderCell align="right">Recommended order</TableHeaderCell>
              <TableHeaderCell align="right">Actions</TableHeaderCell>
            </tr>
          </TableHead>
          <TableBody>
            {alerts.length === 0 ? (
              <TableEmptyState colSpan={7}>No alerts match these filters.</TableEmptyState>
            ) : (
              alerts.map((alert) => (
                <TableRow key={alert.id}>
                  <TableCell>
                    <p className="font-medium text-ink">{alert.productName ?? '—'}</p>
                    <p className="font-mono text-xs text-muted">{alert.productSku}</p>
                  </TableCell>
                  <TableCell><SeverityBadge severity={alert.severity} /></TableCell>
                  <TableCell><AlertStatusBadge status={alert.status} /></TableCell>
                  <TableCell align="right">{alert.availableQuantitySnapshot}</TableCell>
                  <TableCell align="right">{alert.reorderPointSnapshot}</TableCell>
                  <TableCell align="right" className="font-semibold text-ink">{alert.recommendedOrderQuantity ?? '—'}</TableCell>
                  <TableCell align="right">
                    <div className="flex justify-end gap-1">
                      <Button aria-label={`View alert for ${alert.productName}`} size="icon" variant="ghost" onClick={() => onView(alert)}><PanelRightOpen aria-hidden="true" size={17} /></Button>
                    </div>
                  </TableCell>
                </TableRow>
              ))
            )}
          </TableBody>
        </Table>
      </div>
    </>
  )
}
