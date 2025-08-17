import { useEffect } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';

import useEditorState from './_useEditorState';

/**
 * Hook to sync recipe macros to custom table on post save.
 */
export function useMacrosSync() {
	const { recipeId, isSavingPost, isAutosavingPost, blocks } =
		useEditorState();
	useEffect( () => {
		if ( isSavingPost && ! isAutosavingPost ) {
			const macros: Macros = {
				carbs: 0,
				fat: 0,
				protein: 0,
				calories: 0,
			};

			const collectMacros = ( blockList ) => {
				blockList.forEach( ( block ) => {
					if ( block.name === 'meal-plannr/recipe-meta-block' ) {
						macros.carbs = block.attributes.carbs || 0;
						macros.fat = block.attributes.fat || 0;
						macros.protein = block.attributes.protein || 0;
						macros.calories = block.attributes.calories || 0;
					}
					if ( block.innerBlocks?.length ) {
						collectMacros( block.innerBlocks );
					}
				} );
			};

			collectMacros( blocks );
			apiFetch( {
				path: `/mealplannr/v1/recipes/${ recipeId }/macros`,
				method: 'POST',
				data: {
					recipe_id: recipeId,
					data: macros,
				},
			} ).then( ( response ) => console.log( response ) );
		}
	}, [ isSavingPost, isAutosavingPost, blocks, recipeId ] );
}

type Macros = {
	carbs: number;
	fat: number;
	protein: number;
	calories: number;
};
