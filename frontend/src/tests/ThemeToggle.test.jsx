import { render, screen, fireEvent } from '@testing-library/react'
import { describe, it, expect, beforeEach, vi } from 'vitest'
import { ThemeProvider, useTheme } from '../context/ThemeContext'

// Composant de test qui expose le toggle
function ThemeConsumer() {
  const { theme, toggleTheme } = useTheme()
  return (
    <div>
      <span data-testid="theme-value">{theme}</span>
      <button data-testid="toggle" onClick={toggleTheme}>Basculer</button>
    </div>
  )
}

// Mock minimal de localStorage compatible jsdom
const localStorageMock = (() => {
  let store = {}
  return {
    getItem: (k) => store[k] ?? null,
    setItem: (k, v) => { store[k] = String(v) },
    removeItem: (k) => { delete store[k] },
    clear: () => { store = {} },
  }
})()

describe('ThemeContext', () => {
  beforeEach(() => {
    vi.stubGlobal('localStorage', localStorageMock)
    localStorageMock.clear()
    document.documentElement.removeAttribute('data-theme')
  })

  it('démarre en mode clair par défaut', () => {
    render(<ThemeProvider><ThemeConsumer /></ThemeProvider>)
    expect(screen.getByTestId('theme-value').textContent).toBe('light')
  })

  it('bascule vers le mode sombre au clic', () => {
    render(<ThemeProvider><ThemeConsumer /></ThemeProvider>)
    fireEvent.click(screen.getByTestId('toggle'))
    expect(screen.getByTestId('theme-value').textContent).toBe('dark')
  })

  it('applique data-theme="dark" sur <html> après bascule', () => {
    render(<ThemeProvider><ThemeConsumer /></ThemeProvider>)
    fireEvent.click(screen.getByTestId('toggle'))
    expect(document.documentElement.getAttribute('data-theme')).toBe('dark')
  })

  it('persiste le thème dans localStorage', () => {
    render(<ThemeProvider><ThemeConsumer /></ThemeProvider>)
    fireEvent.click(screen.getByTestId('toggle'))
    expect(localStorage.getItem('dgb_theme')).toBe('dark')
  })

  it('recharge le thème depuis localStorage au montage', () => {
    localStorage.setItem('dgb_theme', 'dark')
    render(<ThemeProvider><ThemeConsumer /></ThemeProvider>)
    expect(screen.getByTestId('theme-value').textContent).toBe('dark')
    expect(document.documentElement.getAttribute('data-theme')).toBe('dark')
  })

  it('rebascule vers le mode clair depuis dark', () => {
    localStorage.setItem('dgb_theme', 'dark')
    render(<ThemeProvider><ThemeConsumer /></ThemeProvider>)
    fireEvent.click(screen.getByTestId('toggle'))
    expect(screen.getByTestId('theme-value').textContent).toBe('light')
    expect(localStorage.getItem('dgb_theme')).toBe('light')
  })
})
