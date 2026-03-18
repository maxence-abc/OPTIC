import { startStimulusApp } from '@symfony/stimulus-bundle';
import NavbarController from './controllers/navbar_controller.js';

const app = startStimulusApp();
app.register('navbar', NavbarController);
import './stimulus_bootstrap.js';
