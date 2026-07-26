import { PanelRightOpen } from 'lucide-react'
import type { AuditLogEntry } from '@/features/audit/types/audit'
import { Button } from '@/shared/components/Button'
import { RecordCard } from '@/shared/components/RecordCard'
import { Table, TableBody, TableCell, TableEmptyState, TableHead, TableHeaderCell, TableRow } from '@/shared/components/Table'

export function AuditLogTable({ entries, onView }: { entries: AuditLogEntry[]; onView: (entry: AuditLogEntry) => void }) {
  return (
    <>
      <div className="space-y-3 md:hidden">
        {entries.length === 0 ? (
          <p className="rounded-card border border-border bg-surface p-6 text-center text-sm text-muted shadow-panel">No audit events match these filters.</p>
        ) : (
          entries.map((entry) => (
            <RecordCard
              key={entry.id}
              ariaLabel="View audit event details"
              title={entry.action}
              subtitle={`${entry.entityType}${entry.entityId ? ` #${entry.entityId}` : ''}`}
              fields={[
                { label: 'When', value: entry.createdAt ? new Date(entry.createdAt).toLocaleString() : '—', full: true },
                { label: 'Actor role', value: entry.actorRole ?? '—' },
                { label: 'Correlation ID', value: <span className="font-mono text-xs">{entry.correlationId}</span>, full: true },
              ]}
              onClick={() => onView(entry)}
            />
          ))
        )}
      </div>

      <div className="hidden md:block">
        <Table minWidth={800}>
          <TableHead>
            <tr>
              <TableHeaderCell>When</TableHeaderCell>
              <TableHeaderCell>Action</TableHeaderCell>
              <TableHeaderCell>Entity</TableHeaderCell>
              <TableHeaderCell>Actor role</TableHeaderCell>
              <TableHeaderCell>Correlation ID</TableHeaderCell>
              <TableHeaderCell align="right">Actions</TableHeaderCell>
            </tr>
          </TableHead>
          <TableBody>
            {entries.length === 0 ? (
              <TableEmptyState colSpan={6}>No audit events match these filters.</TableEmptyState>
            ) : (
              entries.map((entry) => (
                <TableRow key={entry.id}>
                  <TableCell className="whitespace-nowrap tabular-nums">{entry.createdAt ? new Date(entry.createdAt).toLocaleString() : '—'}</TableCell>
                  <TableCell className="font-medium text-ink">{entry.action}</TableCell>
                  <TableCell>{entry.entityType}{entry.entityId ? ` #${entry.entityId}` : ''}</TableCell>
                  <TableCell>{entry.actorRole ?? '—'}</TableCell>
                  <TableCell className="font-mono text-xs text-muted">{entry.correlationId}</TableCell>
                  <TableCell align="right">
                    <div className="flex justify-end">
                      <Button aria-label="View audit event details" size="icon" variant="ghost" onClick={() => onView(entry)}><PanelRightOpen aria-hidden="true" size={18} /></Button>
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
