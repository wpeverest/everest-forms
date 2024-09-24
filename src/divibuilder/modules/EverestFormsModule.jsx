import React, { Component } from 'react';

class EverestFormsModule extends Component {
	static slug = 'everest_forms_divi_builder';


	render() {
		if (this.props.__rendered_evf_forms) {
			return (
				<div
					dangerouslySetInnerHTML={{
						__html: this.props.__rendered_evf_forms,
					}}
				></div>
			);
		}
		return null;
	}
}

export default EverestFormsModule;
