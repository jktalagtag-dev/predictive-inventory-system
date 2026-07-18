export function formatRole(roleCode: string | undefined): string {
  if (!roleCode) return ''
  return roleCode.charAt(0).toUpperCase() + roleCode.slice(1)
}
