import dgbLogo from '../assets/dgb-logo.png'

/** Version navbar — logo officiel, petit format */
export function LogoNavbar() {
  return (
    <div style={{ display: 'flex', alignItems: 'center', gap: 10 }}>
      <img src={dgbLogo} alt="Logo DGB" style={{ height: 48, width: 'auto', display: 'block' }} />
    </div>
  )
}

/** Version sidebar — logo officiel */
export function LogoSidebar() {
  return (
    <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'center' }}>
      <img src={dgbLogo} alt="Logo DGB" style={{ height: 56, width: 'auto', display: 'block' }} />
    </div>
  )
}

/** Version login / plein écran — grand format */
export function LogoLogin() {
  return (
    <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'center' }}>
      <img src={dgbLogo} alt="Logo DGB" style={{ height: 100, width: 'auto', display: 'block' }} />
    </div>
  )
}
