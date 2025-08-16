import { useSelect } from '@wordpress/data';
import { useEffect } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';

/**
 * Hook to batch sync ingredients to custom table on post save.
 */
export function useIngredientSync() {
	const ingredientBlockName = 'meal-plannr/recipe-ingredients-block';
	const { recipeId, isSavingPost, isAutosavingPost, blocks } = useSelect(
		( select ) => {
			const editor = select( 'core/editor' );
			return {
				recipeId: editor.getCurrentPostId(),
				isSavingPost: editor.isSavingPost(),
				isAutosavingPost: editor.isAutosavingPost(),
				blocks: editor.getBlocks(),
			};
		},
		[]
	);

	useEffect( () => {
		if ( isSavingPost && ! isAutosavingPost ) {
			const ingredients: IngredientsArray = [];

			const collectIngredients = ( blockList ) => {
				blockList.forEach( ( block ) => {
					if ( block.name === ingredientBlockName ) {
						ingredients.push( {
							name: block.attributes.name,
							quantityVolume:
								block.attributes.quantityVolume || null,
							unitVolume: block.attributes.unitVolume || null,
							quantityWeight:
								block.attributes.quantityWeight || null,
							unitWeight: block.attributes.unitWeight || null,
							notes: block.attributes.description || null,
						} );
					}
					if ( block.innerBlocks?.length ) {
						collectIngredients( block.innerBlocks );
					}
				} );
			};

			collectIngredients( blocks );

			apiFetch( {
				path: '/mealplannr/v1/ingredients/batch',
				method: 'POST',
				data: {
					recipe_id: recipeId,
					ingredients,
				},
			} ).then( ( response ) => console.log( response ) );
		}
	}, [ isSavingPost, isAutosavingPost, blocks, recipeId ] );
}

type IngredientsArray = Array< {
	name: string;
	quantityVolume: number | null;
	unitVolume: string | null;
	quantityWeight: number | null;
	unitWeight: string | null;
	notes: string | null;
} >;
