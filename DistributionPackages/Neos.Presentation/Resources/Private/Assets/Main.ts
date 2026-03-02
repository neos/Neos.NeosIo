import Alpine from './Scripts';
import { AlpineComponent } from 'alpinejs'
import '../Fusion/Presentation/LogoBar';
import '../Fusion/Presentation/ImageCollage';
import '../Fusion/Presentation/Navigation';
import '../Fusion/Presentation/Slider';

import MobileMenu, { MobileMenuComponent } from '../Fusion/Presentation/Navigation/Mobile/MobileNavigation';

// @ts-ignore: Set Alpine as a global variable
window.Alpine = Alpine;

Alpine.data('mobileMenu', MobileMenu as (value: any) => AlpineComponent<MobileMenuComponent>)

Alpine.start();
