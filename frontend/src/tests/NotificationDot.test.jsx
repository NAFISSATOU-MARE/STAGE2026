import { render, screen } from '@testing-library/react'
import { describe, it, expect } from 'vitest'

// Composant simplifié reproduisant le comportement du point de notification
function NotifBell({ nonLues }) {
  return (
    <button data-testid="notif-btn" style={{ position: 'relative' }}>
      🔔
      {nonLues > 0 && (
        <span
          data-testid="notif-dot"
          aria-label={`${nonLues} notification(s) non lue(s)`}
          style={{
            position: 'absolute', top: 4, right: 4,
            width: 9, height: 9, background: '#3b82f6',
            borderRadius: '50%',
          }}
        />
      )}
    </button>
  )
}

describe('Point de notification (cercle bleu)', () => {
  it("n'affiche pas le point quand il n'y a pas de notifications non lues", () => {
    render(<NotifBell nonLues={0} />)
    expect(screen.queryByTestId('notif-dot')).not.toBeInTheDocument()
  })

  it('affiche le point quand il y a au moins 1 notification non lue', () => {
    render(<NotifBell nonLues={1} />)
    expect(screen.getByTestId('notif-dot')).toBeInTheDocument()
  })

  it('affiche le point pour plusieurs notifications non lues', () => {
    render(<NotifBell nonLues={5} />)
    expect(screen.getByTestId('notif-dot')).toBeInTheDocument()
  })

  it('masque le point après lecture de toutes les notifications (nonLues = 0)', () => {
    const { rerender } = render(<NotifBell nonLues={3} />)
    expect(screen.getByTestId('notif-dot')).toBeInTheDocument()
    rerender(<NotifBell nonLues={0} />)
    expect(screen.queryByTestId('notif-dot')).not.toBeInTheDocument()
  })

  it('le point a un aria-label accessible avec le bon nombre', () => {
    render(<NotifBell nonLues={4} />)
    expect(screen.getByLabelText('4 notification(s) non lue(s)')).toBeInTheDocument()
  })
})
