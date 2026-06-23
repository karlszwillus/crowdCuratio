import './bootstrap';

// Webfonts — IBM Plex Sans als Default-Schrift im Editor, IBM Plex
// Mono für die kleinen Caps-Labels. Source Serif 4 wird nur in den
// Preview-/PDF-Templates geladen (eigener Webfont-Bundle dort), um
// die Editor-Bundle-Grösse klein zu halten.
import '@fontsource/ibm-plex-sans/300.css';
import '@fontsource/ibm-plex-sans/400.css';
import '@fontsource/ibm-plex-sans/500.css';
import '@fontsource/ibm-plex-sans/600.css';
import '@fontsource/ibm-plex-sans/700.css';
import '@fontsource/ibm-plex-mono/400.css';
import '@fontsource/ibm-plex-mono/500.css';

// Alpine.js 3: explizites Start-up. In 2.x lief der Start
// automatisch beim require, in 3.x muss er gefeuert werden.
import Alpine from 'alpinejs';

window.Alpine = Alpine;
Alpine.start();
