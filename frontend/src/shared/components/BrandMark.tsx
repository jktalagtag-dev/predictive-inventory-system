import { useState } from 'react'
import { LockKeyhole } from 'lucide-react'

type BrandMarkProps = {
  size?: number
}

/**
 * Reserves the logo slot at /logo.svg (served from frontend/public/) — falls
 * back to an icon lockup until that file is supplied, so nothing breaks in
 * the meantime.
 */
export function BrandMark({ size = 48 }: BrandMarkProps) {
  const [imageFailed, setImageFailed] = useState(false)

  if (imageFailed) {
    return (
      <div
        className="grid place-items-center rounded-xl bg-brand-600 text-white shadow-panel"
        style={{ height: size, width: size }}
      >
        <LockKeyhole aria-hidden="true" size={size * 0.46} />
      </div>
    )
  }

  return (
    <img
      alt="Steven Hydrotech Exponent"
      height={size}
      src="/logo.svg"
      width={size}
      onError={() => setImageFailed(true)}
    />
  )
}
