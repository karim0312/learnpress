/**
 * External dependencies
 */
import refx from 'refx';

const effects = {
	ENROLL_COURSE_X: ( action, store ) => {
		enrollCourse: ( action, store ) => {
			const { dispatch } = store;

			//dispatch()
		};
	},
};

/**
 * Applies the custom middlewares used specifically in the editor module.
 *
 * @param {Object} store Store Object.
 *
 * @return {Object} Update Store Object.
 */
function applyMiddlewares( store ) {
	let enhancedDispatch = () => {
		throw new Error(
			'Dispatching while constructing your middleware is not allowed. ' +
				'Other middleware would not be applied to this dispatch.'
		);
	};

	const middlewareAPI = {
		getState: store.getState,
		dispatch: ( ...args ) => enhancedDispatch( ...args ),
	};

	enhancedDispatch = refx( effects )( middlewareAPI )( store.dispatch );

	store.dispatch = enhancedDispatch;
	return store;
}

export default applyMiddlewares;
