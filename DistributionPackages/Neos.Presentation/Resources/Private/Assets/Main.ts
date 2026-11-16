import Alpine from './Scripts';
import { AlpineComponent } from 'alpinejs'
import '../Fusion/Presentation/LogoBar';
import '../Fusion/Presentation/ImageCollage';
import '../Fusion/Presentation/Navigation';
import '../Fusion/Presentation/Slider';
import { initStage } from '../Fusion/Presentation/Stage/Stage';
import { initCarousels } from '../Fusion/Presentation/Carousel/Carousel';

import MobileMenu, { MobileMenuComponent } from '../Fusion/Presentation/Navigation/Mobile/MobileNavigation';
import CaseListFilter, { CaseListFilterComponent } from '../Fusion/Presentation/CaseList/CaseList';

// @ts-ignore: Set Alpine as a global variable
window.Alpine = Alpine;

Alpine.data('mobileMenu', MobileMenu as (value: any) => AlpineComponent<MobileMenuComponent>)
Alpine.data('caseListFilter', CaseListFilter as () => AlpineComponent<CaseListFilterComponent>)

initStage();
initCarousels();
Alpine.start();
