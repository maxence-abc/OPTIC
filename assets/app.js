import { startStimulusApp } from '@symfony/stimulus-bundle';
import CarouselController from './controllers/carousel_controller.js';
import FilePreviewController from './controllers/file_preview_controller.js';
import FlashToastController from './controllers/flash_toast_controller.js';
import NavbarController from './controllers/navbar_controller.js';

const app = startStimulusApp();
app.register('carousel', CarouselController);
app.register('file-preview', FilePreviewController);
app.register('flash-toast', FlashToastController);
app.register('navbar', NavbarController);
import './stimulus_bootstrap.js';
