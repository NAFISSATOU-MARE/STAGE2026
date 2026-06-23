const MAP = {
  EN_ATTENTE: { label: 'En attente',  cls: 'badge-attente'  },
  APPROUVEE:  { label: 'Approuvée',   cls: 'badge-approuve' },
  REJETEE:    { label: 'Rejetée',     cls: 'badge-rejete'   },
}

export default function StatusBadge({ statut }) {
  const { label, cls } = MAP[statut] || { label: statut, cls: '' }
  return <span className={`badge ${cls}`}>{label}</span>
}
