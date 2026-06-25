import { render, screen, fireEvent, waitFor } from '@testing-library/react'
import { describe, it, expect, vi, beforeEach } from 'vitest'

// Mock de l'API Axios
vi.mock('../api/axios', () => ({
  default: {
    put: vi.fn(),
  },
}))

import api from '../api/axios'

// Reproduction minimale du modal d'édition de lettre
function LettreModal({ demande, onClose, onSaved }) {
  const [form, setForm] = window.React.useState({
    motif_lettre:    demande.contenu_lettre?.motif_lettre    ?? '',
    lieu_jouissance: demande.contenu_lettre?.lieu_jouissance ?? '',
    complement:      demande.contenu_lettre?.complement      ?? '',
  })
  const [erreur, setErreur] = window.React.useState('')

  const sauvegarder = async () => {
    try {
      await api.put(`/api/demandes/${demande.id}/lettre`, form)
      onSaved(form)
      onClose()
    } catch {
      setErreur('Erreur lors de la sauvegarde.')
    }
  }

  return (
    <div data-testid="lettre-modal">
      <textarea
        data-testid="motif-input"
        value={form.motif_lettre}
        onChange={e => setForm(f => ({ ...f, motif_lettre: e.target.value }))}
      />
      <input
        data-testid="lieu-input"
        value={form.lieu_jouissance}
        onChange={e => setForm(f => ({ ...f, lieu_jouissance: e.target.value }))}
      />
      <textarea
        data-testid="complement-input"
        value={form.complement}
        onChange={e => setForm(f => ({ ...f, complement: e.target.value }))}
      />
      {erreur && <div data-testid="erreur">{erreur}</div>}
      <button data-testid="btn-annuler" onClick={onClose}>Annuler</button>
      <button data-testid="btn-sauvegarder" onClick={sauvegarder}>Enregistrer</button>
    </div>
  )
}

// Rendre React accessible globalement (requis par le composant inline ci-dessus)
import * as React from 'react'
window.React = React

describe('Modal édition de lettre', () => {
  const demande = { id: 42, type: 'CONGE', contenu_lettre: null }

  beforeEach(() => {
    vi.clearAllMocks()
  })

  it('affiche les champs vides si contenu_lettre est null', () => {
    render(<LettreModal demande={demande} onClose={() => {}} onSaved={() => {}} />)
    expect(screen.getByTestId('motif-input').value).toBe('')
    expect(screen.getByTestId('lieu-input').value).toBe('')
    expect(screen.getByTestId('complement-input').value).toBe('')
  })

  it('pré-remplit les champs depuis contenu_lettre existant', () => {
    const d = { ...demande, contenu_lettre: { motif_lettre: 'Vacances', lieu_jouissance: 'Dakar', complement: '' } }
    render(<LettreModal demande={d} onClose={() => {}} onSaved={() => {}} />)
    expect(screen.getByTestId('motif-input').value).toBe('Vacances')
    expect(screen.getByTestId('lieu-input').value).toBe('Dakar')
  })

  it("met à jour l'état au saisie dans le champ motif", () => {
    render(<LettreModal demande={demande} onClose={() => {}} onSaved={() => {}} />)
    fireEvent.change(screen.getByTestId('motif-input'), { target: { value: 'Congé annuel' } })
    expect(screen.getByTestId('motif-input').value).toBe('Congé annuel')
  })

  it("appelle api.put avec les bonnes données et ferme le modal", async () => {
    api.put.mockResolvedValueOnce({ data: { message: 'OK' } })
    const onClose = vi.fn()
    const onSaved = vi.fn()

    render(<LettreModal demande={demande} onClose={onClose} onSaved={onSaved} />)
    fireEvent.change(screen.getByTestId('lieu-input'), { target: { value: 'Saint-Louis' } })
    fireEvent.click(screen.getByTestId('btn-sauvegarder'))

    await waitFor(() => {
      expect(api.put).toHaveBeenCalledWith('/api/demandes/42/lettre', expect.objectContaining({
        lieu_jouissance: 'Saint-Louis',
      }))
      expect(onClose).toHaveBeenCalled()
      expect(onSaved).toHaveBeenCalled()
    })
  })

  it("affiche un message d'erreur si api.put échoue", async () => {
    api.put.mockRejectedValueOnce(new Error('Network error'))
    render(<LettreModal demande={demande} onClose={() => {}} onSaved={() => {}} />)
    fireEvent.click(screen.getByTestId('btn-sauvegarder'))

    await waitFor(() => {
      expect(screen.getByTestId('erreur')).toHaveTextContent('Erreur lors de la sauvegarde.')
    })
  })

  it('ferme le modal au clic sur Annuler', () => {
    const onClose = vi.fn()
    render(<LettreModal demande={demande} onClose={onClose} onSaved={() => {}} />)
    fireEvent.click(screen.getByTestId('btn-annuler'))
    expect(onClose).toHaveBeenCalled()
  })
})
