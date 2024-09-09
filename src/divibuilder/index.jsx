import EverestFormsModule from './modules/EverestFormsModule';

jQuery(window).on('et_builder_api_ready', (_, API) => {
	API.registerModules([EverestFormsModule]);
});
