import Alpine from './Scripts';
import '../Fusion/Presentation/LogoBar';
import '../Fusion/Presentation/ImageCollage';
import '../Fusion/Presentation/Navigation';
import '../Fusion/Presentation/Slider';
import { initStage } from '../Fusion/Presentation/Stage/Stage';

// @ts-ignore: Set Alpine as a global variable
window.Alpine = Alpine;

initStage();
Alpine.start();
