import { getProductOptions } from '@/features/products/api/productsApi'
import { offlineDb } from '@/shared/offline/db'

/**
 * Refreshes the approved offline reference-data cache (product id/sku/name
 * only — no pricing, cost, or stock, since those are exactly the fields
 * that go stale fastest and must be revalidated online at posting time).
 * Silently no-ops when offline; the existing cache is left as-is.
 */
export async function refreshProductCache(userId: string): Promise<void> {
  if (typeof navigator !== 'undefined' && !navigator.onLine) return

  const products = await getProductOptions()

  await offlineDb.transaction('rw', offlineDb.productCache, offlineDb.meta, async () => {
    await offlineDb.productCache.where('userId').equals(userId).delete()
    await offlineDb.productCache.bulkAdd(products.map((product) => ({ ...product, userId })))

    const existing = await offlineDb.meta.get(userId)
    await offlineDb.meta.put({ userId, lastSyncAt: existing?.lastSyncAt ?? null, productCacheUpdatedAt: new Date().toISOString() })
  })
}

export async function getCachedProducts(userId: string): Promise<Array<{ id: string; sku: string; name: string }>> {
  const rows = await offlineDb.productCache.where('userId').equals(userId).toArray()
  return rows.map(({ id, sku, name }) => ({ id, sku, name }))
}
