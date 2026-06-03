require('./bootstrap');

// Alpine.js 3: explizites Start-up. In 2.x lief der Start
// automatisch beim require, in 3.x muss er gefeuert werden.
import Alpine from 'alpinejs';

window.Alpine = Alpine;
Alpine.start();
