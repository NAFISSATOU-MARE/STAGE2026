/* Composant logo DGB — fidèle au logo officiel Direction générale du Budget */

function FlagO({ size = 40 }) {
  const id = `dgb-flag-${size}`
  return (
    <svg
      width={size} height={size}
      viewBox="0 0 64 64"
      style={{ flexShrink: 0, display: 'block' }}
    >
      <defs>
        <clipPath id={id}>
          <circle cx="32" cy="32" r="29" />
        </clipPath>
      </defs>

      {/* Drapeau tricolore sénégalais */}
      <rect x="3"  y="3" width="19" height="58" fill="#1B7A3A" clipPath={`url(#${id})`} />
      <rect x="22" y="3" width="20" height="58" fill="#F0BC00" clipPath={`url(#${id})`} />
      <rect x="42" y="3" width="19" height="58" fill="#C41230" clipPath={`url(#${id})`} />

      {/* Étoile verte à 5 branches dans la bande jaune */}
      <polygon
        points="32,14 34.6,22.6 43.6,22.6 36.5,28 39.1,36.6 32,31.2 24.9,36.6 27.5,28 20.4,22.6 29.4,22.6"
        fill="#1B7A3A"
        clipPath={`url(#${id})`}
      />

      {/* Bordure circulaire navy */}
      <circle cx="32" cy="32" r="29" fill="none" stroke="#0D2157" strokeWidth="3" />
    </svg>
  )
}

/** Version navbar — lettres blanches sur fond sombre */
export function LogoNavbar() {
  return (
    <div style={{ display: 'flex', alignItems: 'center', gap: 10 }}>
      <FlagO size={40} />
      <div>
        <div style={{
          fontFamily: '"Arial Black", "Arial Bold", Arial, sans-serif',
          fontWeight: 900,
          fontSize: 24,
          letterSpacing: 2,
          lineHeight: 1,
        }}>
          <span style={{ color: '#ffffff' }}>D</span>
          <span style={{ color: '#4ECB7A' }}>G</span>
          <span style={{ color: '#ffffff' }}>B</span>
        </div>
        <div style={{ fontSize: 10, color: 'rgba(255,255,255,.5)', marginTop: 2, letterSpacing: 0.3 }}>
          Gestion des congés et décisions
        </div>
      </div>
    </div>
  )
}

/** Version sidebar — lettres blanches sur fond navy */
export function LogoSidebar() {
  return (
    <div style={{ display: 'flex', alignItems: 'center', gap: 10 }}>
      <FlagO size={34} />
      <div>
        <div style={{
          fontFamily: '"Arial Black", "Arial Bold", Arial, sans-serif',
          fontWeight: 900,
          fontSize: 20,
          letterSpacing: 2,
          lineHeight: 1,
        }}>
          <span style={{ color: '#ffffff' }}>D</span>
          <span style={{ color: '#4ECB7A' }}>G</span>
          <span style={{ color: '#ffffff' }}>B</span>
        </div>
        <div style={{ fontSize: 9, color: 'rgba(255,255,255,.4)', marginTop: 3, lineHeight: 1.35 }}>
          Direction générale<br />du Budget
        </div>
      </div>
    </div>
  )
}

/** Version login / plein écran — grand format, couleurs officielles */
export function LogoLogin() {
  return (
    <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'center', gap: 18 }}>
      <FlagO size={72} />
      <div>
        <div style={{
          fontFamily: '"Arial Black", "Arial Bold", Arial, sans-serif',
          fontWeight: 900,
          fontSize: 56,
          letterSpacing: 4,
          lineHeight: 1,
        }}>
          <span style={{ color: '#0D2157' }}>D</span>
          <span style={{ color: '#1B7A3A' }}>G</span>
          <span style={{ color: '#0D2157' }}>B</span>
        </div>
        <div style={{ fontSize: 12, color: '#555', marginTop: 5, letterSpacing: 0.3 }}>
          Direction générale du{' '}
          <span style={{ color: '#1B7A3A', fontWeight: 700 }}>Budget</span>
        </div>
      </div>
    </div>
  )
}
